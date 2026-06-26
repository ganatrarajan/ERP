import 'dart:io';
import 'dart:convert';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../network/dio_client.dart';
import '../constants/api_endpoints.dart';
import '../services/secure_storage_service.dart';
import '../../providers/auth_provider.dart';
import '../../providers/notice_provider.dart';
import '../../data/models/notice_model.dart';
import '../../presentation/screens/academics/homework_screen.dart';
import '../../presentation/screens/academics/notice_list_screen.dart';
import '../../presentation/screens/academics/notice_detail_screen.dart';
import '../../presentation/screens/academics/results_screen.dart';
import '../../presentation/screens/fees/fees_screen.dart';

class NotificationService {
  NotificationService._privateConstructor();

  static final NotificationService instance = NotificationService._privateConstructor();

  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  final DioClient _dioClient = DioClient();
  final SecureStorageService _secureStorage = SecureStorageService();
  final FlutterLocalNotificationsPlugin _localNotificationsPlugin = FlutterLocalNotificationsPlugin();

  bool _isInitialized = false;

  // Initialize service
  Future<void> init() async {
    if (_isInitialized) return;

    // Set up foreground notification presentation options
    await FirebaseMessaging.instance.setForegroundNotificationPresentationOptions(
      alert: true,
      badge: true,
      sound: true,
    );

    // Initialize Local Notifications for Android to show toolbar alerts in foreground
    if (Platform.isAndroid) {
      const AndroidInitializationSettings initializationSettingsAndroid =
          AndroidInitializationSettings('@mipmap/ic_launcher');
      
      const InitializationSettings initializationSettings = InitializationSettings(
        android: initializationSettingsAndroid,
      );

      await _localNotificationsPlugin.initialize(
        initializationSettings,
        onDidReceiveNotificationResponse: (NotificationResponse response) {
          final payload = response.payload;
          if (payload != null) {
            try {
              final Map<String, dynamic> data = Map<String, dynamic>.from(jsonDecode(payload));
              final RemoteMessage dummyMessage = RemoteMessage(data: data);
              _handleNotificationClick(dummyMessage);
            } catch (e) {
              print('Error parsing local notification click payload: $e');
            }
          }
        },
      );
    }

    // Listen to token refresh
    FirebaseMessaging.instance.onTokenRefresh.listen((newToken) async {
      final token = await _secureStorage.getToken();
      if (token != null) {
        await registerDevice();
      }
    });

    // Handle when app is in foreground
    FirebaseMessaging.onMessage.listen((RemoteMessage message) async {
      print('Foreground message received. Data: ${message.data}, Notification: ${message.notification?.title}');
      if (Platform.isAndroid) {
        await _showNativeAndroidNotification(message);
      }
    });

    // Handle when app is opened from background state by clicking a notification
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      _handleNotificationClick(message);
    });

    // Check if app was opened from terminated state
    final RemoteMessage? initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      // Small delay to make sure navigator has initialized
      Future.delayed(const Duration(milliseconds: 1000), () {
        _handleNotificationClick(initialMessage);
      });
    }

    _isInitialized = true;
  }

  // Request notifications permission
  Future<bool> requestNotificationPermission() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('has_requested_notifications', true);

    NotificationSettings settings = await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
      provisional: false,
    );

    final granted = settings.authorizationStatus == AuthorizationStatus.authorized ||
        settings.authorizationStatus == AuthorizationStatus.provisional;

    if (granted) {
      // If permission is newly granted and we're logged in, register device
      final token = await _secureStorage.getToken();
      if (token != null) {
        await registerDevice();
      }
    }
    return granted;
  }

  // Check if first login and request permissions
  Future<void> requestPermissionOnFirstLogin() async {
    final prefs = await SharedPreferences.getInstance();
    final requestedBefore = prefs.getBool('has_requested_notifications') ?? false;
    if (!requestedBefore) {
      await requestNotificationPermission();
    }
  }

  // Register device FCM token to backend
  Future<void> registerDevice() async {
    try {
      final fcmToken = await FirebaseMessaging.instance.getToken();
      if (fcmToken == null) {
        print('FCM Token is null, skipping registration');
        return;
      }

      final DeviceInfoPlugin deviceInfo = DeviceInfoPlugin();
      String deviceName = 'Unknown Device';
      String deviceType = 'web';

      if (Platform.isAndroid) {
        AndroidDeviceInfo androidInfo = await deviceInfo.androidInfo;
        deviceName = '${androidInfo.brand} ${androidInfo.model}';
        deviceType = 'android';
      } else if (Platform.isIOS) {
        IosDeviceInfo iosInfo = await deviceInfo.iosInfo;
        deviceName = iosInfo.name;
        deviceType = 'ios';
      }

      final PackageInfo packageInfo = await PackageInfo.fromPlatform();
      final appVersion = packageInfo.version;

      await _dioClient.post(
        ApiEndpoints.registerDevice,
        data: {
          'firebase_token': fcmToken,
          'device_type': deviceType,
          'device_name': deviceName,
          'app_version': appVersion,
        },
      );
      print('FCM Token registered successfully: $fcmToken');
    } catch (e) {
      print('Failed to register FCM token: $e');
    }
  }

  // Deactivate device token on logout
  Future<void> deactivateDevice() async {
    try {
      final fcmToken = await FirebaseMessaging.instance.getToken();
      if (fcmToken != null) {
        await _dioClient.post(
          ApiEndpoints.logout,
          data: {
            'firebase_token': fcmToken,
          },
        );
        print('Device deactivated successfully on backend');
      }
    } catch (e) {
      print('Failed to deactivate device on backend: $e');
    }
  }

  // Show a native Android system notification in the toolbar tray
  Future<void> _showNativeAndroidNotification(RemoteMessage message) async {
    final notification = message.notification;
    final String title = notification?.title ?? message.data['title'] ?? 'New Announcement';
    final String body = notification?.body ?? message.data['body'] ?? '';

    if (title == 'New Announcement' && body.isEmpty) {
      print('Notification skipped: Empty title and body.');
      return;
    }

    const AndroidNotificationDetails androidPlatformChannelSpecifics = AndroidNotificationDetails(
      'high_importance_channel',
      'High Importance Notifications',
      channelDescription: 'This channel is used for important push notifications.',
      importance: Importance.max,
      priority: Priority.high,
      showWhen: true,
    );

    const NotificationDetails notificationDetails = NotificationDetails(
      android: androidPlatformChannelSpecifics,
    );

    final int notificationId = DateTime.now().millisecondsSinceEpoch.remainder(100000);

    await _localNotificationsPlugin.show(
      notificationId,
      title,
      body,
      notificationDetails,
      payload: jsonEncode(message.data),
    );
  }

  // Process clicking of notifications and routes appropriately
  void _handleNotificationClick(RemoteMessage message) {
    final context = navigatorKey.currentContext;
    if (context == null) return;

    // Check if user is authenticated
    final authProvider = context.read<AuthProvider>();
    if (authProvider.status != AuthStatus.authenticated) {
      return;
    }

    final data = message.data;
    final type = data['type']?.toString().toLowerCase();
    final id = data['id']?.toString();

    if (type == 'homework') {
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => const HomeworkScreen()),
      );
    } else if (type == 'notice') {
      _navigateToNoticeDetail(id);
    } else if (type == 'exam' || type == 'result') {
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => const ResultsScreen()),
      );
    } else if (type == 'fees' || type == 'fee') {
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => const FeesScreen()),
      );
    }
  }

  void _navigateToNoticeDetail(String? noticeId) async {
    final context = navigatorKey.currentContext;
    if (context == null) return;

    if (noticeId == null) {
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => const NoticeListScreen()),
      );
      return;
    }

    final noticeProvider = context.read<NoticeProvider>();
    await noticeProvider.fetchNotices();

    NoticeModel? notice;
    try {
      notice = noticeProvider.notices.firstWhere((n) => n.id.toString() == noticeId);
    } catch (_) {
      notice = null;
    }

    if (notice != null) {
      final NoticeModel noticeToPush = notice;
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => NoticeDetailScreen(notice: noticeToPush)),
      );
    } else {
      navigatorKey.currentState?.push(
        MaterialPageRoute(builder: (_) => const NoticeListScreen()),
      );
    }
  }
}
