import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:firebase_core/firebase_core.dart';
import 'core/theme/app_theme.dart';
import 'core/theme/theme_provider.dart';
import 'core/router/app_router.dart';
import 'core/services/notification_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Firebase and local notifications safely
  try {
    await Firebase.initializeApp();
    await NotificationService().init();

    // Request notification permission (iOS & Android 13+)
    await FirebaseMessaging.instance.requestPermission();

    // Get FCM token
    final token = await FirebaseMessaging.instance.getToken();

    debugPrint("====================================");
    print("FCM TOKEN: $token");
    debugPrint("====================================");

    // Listen for token refresh
    FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
      debugPrint("FCM TOKEN REFRESHED: $newToken");
    });
  } catch (e) {
    debugPrint("Firebase/Notification initialization failed: $e");
  }

  runApp(
    const ProviderScope(
      child: MyApp(),
    ),
  );
}

class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    final themeMode = ref.watch(themeProvider);

    return MaterialApp.router(
      title: 'EduvoraX Teacher App',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: themeMode,
      routerConfig: router,
    );
  }
}
