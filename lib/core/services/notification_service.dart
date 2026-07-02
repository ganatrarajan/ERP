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
        // Handle click action
      },
    );

    // Setup foreground listeners
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      final notification = message.notification;
      if (notification != null) {
        _showLocalNotification(notification.title ?? 'EduvoraX Alert', notification.body ?? '');
        _cacheNotification(notification.title ?? 'EduvoraX Alert', notification.body ?? '');
      }
    });

    // Listen to token refresh
    _firebaseMessaging.onTokenRefresh.listen((token) {
      updateTokenToServer(token);
    });
  }

  Future<void> updateTokenToServer([String? token]) async {
    try {
      final fcmToken = token ?? await _firebaseMessaging.getToken();
      if (fcmToken == null) return;

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
    } catch (_) {
      // Ignore or log error
    }
  }

  Future<void> _showLocalNotification(String title, String body) async {
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
