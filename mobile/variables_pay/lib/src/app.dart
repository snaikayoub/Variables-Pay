import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'core/auth/auth_controller.dart';
import 'features/auth/login_page.dart';
import 'features/validations/validations_home_page.dart';
import 'features/validations/voyages/voyage_validation_page.dart';
import 'features/validations/primes/prime_fonction_validation_page.dart';
import 'features/validations/primes/prime_performance_validation_page.dart';

class VariablesPayApp extends ConsumerWidget {
  const VariablesPayApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);

    final router = GoRouter(
      initialLocation: '/validations',
      refreshListenable: auth,
      redirect: (context, state) {
        final loggedIn = auth.status == AuthStatus.authenticated;
        final goingToLogin = state.matchedLocation == '/login';

        if (!loggedIn && !goingToLogin) return '/login';
        if (loggedIn && goingToLogin) return '/validations';
        return null;
      },
      routes: [
        GoRoute(
          path: '/login',
          builder: (context, state) => const LoginPage(),
        ),
        GoRoute(
          path: '/validations',
          builder: (context, state) => const ValidationsHomePage(),
        ),
        GoRoute(
          path: '/validations/voyages/:scope',
          builder: (context, state) {
            final scope = state.pathParameters['scope'] ?? 'service';
            return VoyageValidationPage(scope: scope);
          },
        ),
        GoRoute(
          path: '/validations/primes/performance/:scope',
          builder: (context, state) {
            final scope = state.pathParameters['scope'] ?? 'service';
            return PrimePerformanceValidationPage(scope: scope);
          },
        ),
        GoRoute(
          path: '/validations/primes/fonction/:scope',
          builder: (context, state) {
            final scope = state.pathParameters['scope'] ?? 'service';
            return PrimeFonctionValidationPage(scope: scope);
          },
        ),
      ],
    );

    return MaterialApp.router(
      title: 'Variables-Pay',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1F5A76)),
        useMaterial3: true,
      ),
      routerConfig: router,
    );
  }
}
