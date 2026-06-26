import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'core/services/notification_service.dart';

// Themes
import 'core/theme/app_theme.dart';

// Providers
import 'providers/auth_provider.dart';
import 'providers/dashboard_provider.dart';
import 'providers/attendance_provider.dart';
import 'providers/homework_provider.dart';
import 'providers/notice_provider.dart';
import 'providers/result_provider.dart';
import 'providers/fee_provider.dart';
import 'providers/receipt_provider.dart';
import 'providers/report_card_provider.dart';

import 'providers/onboarding_provider.dart';

// Screens
import 'presentation/screens/splash_screen.dart';

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  
  // Set background messaging handler
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
  
  // Initialize Notification Service
  await NotificationService.instance.init();
  
  runApp(const EduvoraApp());
}

class EduvoraApp extends StatelessWidget {
  const EduvoraApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => OnboardingProvider()),
        ChangeNotifierProvider(create: (_) => DashboardProvider()),
        ChangeNotifierProvider(create: (_) => AttendanceProvider()),
        ChangeNotifierProvider(create: (_) => HomeworkProvider()),
        ChangeNotifierProvider(create: (_) => NoticeProvider()),
        ChangeNotifierProvider(create: (_) => ResultProvider()),
        ChangeNotifierProvider(create: (_) => FeeProvider()),
        ChangeNotifierProvider(create: (_) => ReceiptProvider()),
        ChangeNotifierProvider(create: (_) => ReportCardProvider()),
      ],
      child: MaterialApp(
        navigatorKey: NotificationService.navigatorKey,
        title: 'EduvoraX',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        darkTheme: AppTheme.darkTheme,
        themeMode: ThemeMode.system, // Auto detect dark mode
        home: const SplashScreen(),
      ),
    );
  }
}
