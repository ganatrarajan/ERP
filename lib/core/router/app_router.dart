import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../presentation/screens/onboarding/splash_screen.dart';
import '../../presentation/screens/onboarding/school_code_screen.dart';
import '../../presentation/screens/onboarding/login_screen.dart';
import '../../presentation/screens/dashboard/dashboard_screen.dart';

// Import providers to watch authentication states for route guards
import '../../providers/auth_provider.dart';
import '../../providers/onboarding_provider.dart';

final navigatorKey = GlobalKey<NavigatorState>();

final routerProvider = Provider<GoRouter>((ref) {
  final hasSchool = ref.watch(onboardingProvider.select((s) => s.school != null));
  final isAuthenticated = ref.watch(authProvider.select((a) => a.isAuthenticated));

  return GoRouter(
    navigatorKey: navigatorKey,
    initialLocation: '/splash',
    refreshListenable: _RiverpodRouterRefreshListenable(ref),
    redirect: (context, state) {
      final isSplash = state.matchedLocation == '/splash';
      final isSchoolCode = state.matchedLocation == '/school-code';
      final isLogin = state.matchedLocation == '/login';

      if (isSplash) {
        if (!hasSchool) {
          return '/school-code';
        }
        if (!isAuthenticated) {
          return '/login';
        }
        return '/';
      }

      if (isSchoolCode) {
        if (hasSchool) {
          return '/login';
        }
        return null;
      }

      if (isLogin) {
        if (!hasSchool) {
          return '/school-code';
        }
        if (isAuthenticated) {
          return '/';
        }
        return null;
      }

      // Secure route guard
      if (!hasSchool) {
        return '/school-code';
      }
      if (!isAuthenticated) {
        return '/login';
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/school-code',
        builder: (context, state) => const SchoolCodeScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/',
        builder: (context, state) => const DashboardScreen(),
      ),
    ],
  );
});

// Helper class to trigger GoRouter updates when Riverpod state changes
class _RiverpodRouterRefreshListenable extends ChangeNotifier {
  _RiverpodRouterRefreshListenable(Ref ref) {
    ref.listen(authProvider, (previous, next) {
      if (previous?.isAuthenticated != next.isAuthenticated) {
        notifyListeners();
      }
    });
    ref.listen(onboardingProvider, (previous, next) {
      final prevHasSchool = previous?.school != null;
      final nextHasSchool = next.school != null;
      if (prevHasSchool != nextHasSchool) {
        notifyListeners();
      }
    });
  }
}
