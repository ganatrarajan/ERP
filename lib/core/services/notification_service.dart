import 'dart:convert';
import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../network/dio_client.dart';
import '../constants/api_endpoints.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import '../router/app_router.dart';
import 'secure_storage.dart';
import '../../presentation/screens/leave/leave_list_screen.dart';
import '../../presentation/screens/notice/notice_list_screen.dart';
import '../../presentation/screens/homework/homework_list_screen.dart';

class NotificationModel {
  final String id;
  final String title;
  final String body;
  final String timestamp;
  final bool read;

  NotificationModel({
    required this.id,
    required this.title,
    required this.body,
    required this.timestamp,
    this.read = false,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'body': body,
        'timestamp': timestamp,
        'read': read,
      };

  factory NotificationModel.fromJson(Map<String, dynamic> json) => NotificationModel(
        id: json['id'] ?? '',
        title: json['title'] ?? '',
        body: json['body'] ?? '',
        timestamp: json['timestamp'] ?? '',
        read: json['read'] ?? false,
      );

  NotificationModel copyWith({bool? read}) {
    return NotificationModel(
      id: id,
      title: title,
      body: body,
      timestamp: timestamp,
      read: read ?? this.read,
    );
  }
}

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final _firebaseMessaging = FirebaseMessaging.instance;
  final _localNotifications = FlutterLocalNotificationsPlugin();
  final _secureStorage = SecureStorageService();
  
  static const _notificationsCacheKey = 'cached_notifications_list';

  Future<void> init() async {
    // Request permissions
    await _firebaseMessaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // Initialize local notifications
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings();
    const initSettings = InitializationSettings(android: androidSettings, iOS: iosSettings);

    await _localNotifications.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (NotificationResponse response) {
        final payload = response.payload;
        if (payload != null) {
          try {
            final Map<String, dynamic> data = Map<String, dynamic>.from(jsonDecode(payload));
            _handleNotificationClick(data);
          } catch (e) {
            debugPrint('Error parsing local notification click payload: $e');
          }
        }
      },
    );

    // Setup foreground listeners
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      final notification = message.notification;
      if (notification != null) {
        _showLocalNotification(
          notification.title ?? 'EduvoraX Alert',
          notification.body ?? '',
          jsonEncode(message.data),
        );
        _cacheNotification(notification.title ?? 'EduvoraX Alert', notification.body ?? '');
      }
    });

    // Handle when app is opened from background state by clicking a notification
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      _handleNotificationClick(message.data);
    });

    // Check if app was opened from terminated state
    _firebaseMessaging.getInitialMessage().then((initialMessage) {
      if (initialMessage != null) {
        Future.delayed(const Duration(milliseconds: 1000), () {
          _handleNotificationClick(initialMessage.data);
        });
      }
    });

    // Listen to token refresh
    _firebaseMessaging.onTokenRefresh.listen((token) {
      updateTokenToServer(token);
    });

    // Sync FCM token on startup if already logged in
    final hasAuthToken = await _secureStorage.getToken();
    if (hasAuthToken != null) {
      updateTokenToServer();
    }
  }

  void _handleNotificationClick(Map<String, dynamic> data) {
    final type = data['type']?.toString().toLowerCase();
    
    if (type == 'leave') {
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => const LeaveListScreen()),
      );
    } else if (type == 'homework') {
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => const HomeworkListScreen()),
      );
    } else if (type == 'notice') {
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => const NoticeListScreen()),
      );
    }
  }

  Future<void> updateTokenToServer([String? token]) async {
    try {
      final fcmToken = token ?? await _firebaseMessaging.getToken();
      debugPrint("Attempting to sync FCM token: $fcmToken");
      if (fcmToken == null) {
        debugPrint("FCM Token is null, cannot sync.");
        return;
      }

      final deviceName = await _getDeviceName();
      final appVersion = await _getAppVersion();
      final deviceType = Platform.isAndroid ? 'Android' : 'iOS';

      final response = await DioClient().post(
        ApiEndpoints.fcmToken,
        data: {
          'firebase_token': fcmToken,
          'device_type': deviceType,
          'device_name': deviceName,
          'app_version': appVersion,
        },
      );
      
      // Token registered successfully
      debugPrint("FCM Token successfully synced: ${response.data}");
    } catch (e) {
      debugPrint("FCM Token sync failed error: $e");
    }
  }

  Future<void> _showLocalNotification(String title, String body, String payload) async {
    const androidDetails = AndroidNotificationDetails(
      'eduvorax_teacher_channel',
      'EduvoraX Teacher Alerts',
      channelDescription: 'Important school announcements and notifications',
      importance: Importance.max,
      priority: Priority.high,
    );
    const iosDetails = DarwinNotificationDetails();
    const details = NotificationDetails(android: androidDetails, iOS: iosDetails);

    await _localNotifications.show(
      DateTime.now().millisecond,
      title,
      body,
      details,
      payload: payload,
    );
  }

  Future<void> _cacheNotification(String title, String body) async {
    final list = await getCachedNotifications();
    final newNotif = NotificationModel(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      title: title,
      body: body,
      timestamp: DateTime.now().toIso8601String(),
    );

    list.insert(0, newNotif);
    
    // Save back to prefs
    final prefs = await SharedPreferences.getInstance();
    final rawList = list.map((e) => jsonEncode(e.toJson())).toList();
    await prefs.setStringList(_notificationsCacheKey, rawList);
  }

  Future<List<NotificationModel>> getCachedNotifications() async {
    final prefs = await SharedPreferences.getInstance();
    final rawList = prefs.getStringList(_notificationsCacheKey) ?? [];
    return rawList.map((e) => NotificationModel.fromJson(jsonDecode(e))).toList();
  }

  Future<void> markAsRead(String id) async {
    final list = await getCachedNotifications();
    final updatedList = list.map((n) {
      if (n.id == id) return n.copyWith(read: true);
      return n;
    }).toList();

    final prefs = await SharedPreferences.getInstance();
    final rawList = updatedList.map((e) => jsonEncode(e.toJson())).toList();
    await prefs.setStringList(_notificationsCacheKey, rawList);
  }

  Future<void> clearAll() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_notificationsCacheKey);
  }

  Future<String> _getDeviceName() async {
    final deviceInfo = DeviceInfoPlugin();
    if (Platform.isAndroid) {
      final androidInfo = await deviceInfo.androidInfo;
      return '${androidInfo.brand} ${androidInfo.model}';
    } else {
      final iosInfo = await deviceInfo.iosInfo;
      return iosInfo.name;
    }
  }

  Future<String> _getAppVersion() async {
    final packageInfo = await PackageInfo.fromPlatform();
    return packageInfo.version;
  }
}
