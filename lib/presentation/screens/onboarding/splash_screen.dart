import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/onboarding_provider.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    );
    _animation = CurvedAnimation(parent: _controller, curve: Curves.easeInOutBack);
    _controller.forward();

    _initializeApp();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _initializeApp() async {
    try {
      // Wait for the animation to complete
      await Future.delayed(const Duration(milliseconds: 2000));
      if (!mounted) return;
      
      // Load onboarding configurations from secure storage
      await ref.read(onboardingProvider.notifier).loadSavedData();
      if (!mounted) return;
      
      // Check if the school session academic year changed
      final onboardingState = ref.read(onboardingProvider);
      if (onboardingState.school != null) {
        final oldYearId = onboardingState.academicYear?.id;
        final success = await ref.read(onboardingProvider.notifier).verifySchoolCode(onboardingState.school!.code);
        if (!mounted) return;
        
        if (!success) {
          final err = ref.read(onboardingProvider).errorMessage;
          await ref.read(onboardingProvider.notifier).clearOnboarding();
          await ref.read(authProvider.notifier).logout(context);
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(err ?? "School code is invalid or inactive."),
              backgroundColor: Colors.red,
              behavior: SnackBarBehavior.floating,
            ),
          );
          return; // Stop initialization and prevent other API calls
        }

        final newYearId = ref.read(onboardingProvider).academicYear?.id;
        if (oldYearId != null && newYearId != null && oldYearId != newYearId) {
          // Log out immediately if academic year has changed
          await ref.read(authProvider.notifier).logout(context);
          if (!mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text("The school session has changed. Please log in again."),
              backgroundColor: Colors.orange,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      }

      // Refresh auth session
      await ref.read(authProvider.notifier).checkSavedSession();
    } catch (e) {
      debugPrint("Splash initialization error: $e");
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      body: Stack(
        children: [
          // Background Gradient
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  theme.colorScheme.primary,
                  theme.colorScheme.primary.withRed(30).withGreen(50),
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
          ),
          // Content
          Center(
            child: ScaleTransition(
              scale: _animation,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // App Icon
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black12,
                          blurRadius: 20,
                          offset: Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Icon(
                      Icons.school_rounded,
                      size: 72,
                      color: theme.colorScheme.primary,
                    ),
                  ),
                  const SizedBox(height: 24),
                  // Title
                  const Text(
                    "EduvoraX",
                    style: TextStyle(
                      fontSize: 36,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                      letterSpacing: 1,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    "Smart Teacher Portal",
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.white.withOpacity(0.8),
                      letterSpacing: 0.5,
                    ),
                  ),
                ],
              ),
            ),
          ),
          // Footer
          Positioned(
            bottom: 40,
            left: 0,
            right: 0,
            child: Center(
              child: Text(
                "School ERP Mobile App",
                style: TextStyle(
                  color: Colors.white.withOpacity(0.5),
                  fontSize: 12,
                  letterSpacing: 1,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
