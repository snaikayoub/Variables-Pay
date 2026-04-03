import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/auth/auth_controller.dart';
import '../../ui/app_scaffold.dart';

class ValidationsHomePage extends ConsumerWidget {
  const ValidationsHomePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final user = auth.user;
    final scheme = Theme.of(context).colorScheme;

    final roles = user?.roles ?? const <String>[];
    final canService = roles.contains('ROLE_RESPONSABLE_SERVICE') || roles.contains('ROLE_ADMIN') || roles.contains('ROLE_RH');
    final canDivision = roles.contains('ROLE_RESPONSABLE_DIVISION') || roles.contains('ROLE_ADMIN') || roles.contains('ROLE_RH');

    Widget tile({
      required IconData icon,
      required String title,
      required String subtitle,
      required VoidCallback onTap,
    }) {
      return Card(
        child: ListTile(
          leading: CircleAvatar(
            backgroundColor: scheme.primaryContainer,
            foregroundColor: scheme.onPrimaryContainer,
            child: Icon(icon),
          ),
          title: Text(title),
          subtitle: Text(subtitle),
          trailing: const Icon(Icons.chevron_right),
          onTap: onTap,
        ),
      );
    }

    return AppScaffold(
      title: 'Validations',
      actions: [
        IconButton(
          onPressed: () => ref.read(authControllerProvider).logout(),
          icon: const Icon(Icons.logout),
          tooltip: 'Deconnexion',
        ),
      ],
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
        children: [
          if (user != null) ...[
            Text('Bonjour, ${user.fullName}', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
          ],
          Text('Voyages', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 8),
          if (canService)
            tile(
              icon: Icons.directions_car_filled,
              title: 'Validations service',
              subtitle: 'Soumissions en attente / actions batch',
              onTap: () => context.go('/validations/voyages/service'),
            ),
          if (canDivision)
            tile(
              icon: Icons.approval_outlined,
              title: 'Validations division',
              subtitle: 'Validation finale / actions batch',
              onTap: () => context.go('/validations/voyages/division'),
            ),
          const SizedBox(height: 16),
          Text('Primes', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 8),
          if (canService)
            tile(
              icon: Icons.workspace_premium,
              title: 'Prime performance - service',
              subtitle: 'Soumissions en attente / actions batch',
              onTap: () => context.go('/validations/primes/performance/service'),
            ),
          if (canDivision)
            tile(
              icon: Icons.verified_outlined,
              title: 'Prime performance - division',
              subtitle: 'Validation finale / actions batch',
              onTap: () => context.go('/validations/primes/performance/division'),
            ),
          if (canService)
            tile(
              icon: Icons.badge_outlined,
              title: 'Prime fonction - service',
              subtitle: 'Soumissions en attente / actions batch',
              onTap: () => context.go('/validations/primes/fonction/service'),
            ),
          if (canDivision)
            tile(
              icon: Icons.fact_check_outlined,
              title: 'Prime fonction - division',
              subtitle: 'Validation finale / actions batch',
              onTap: () => context.go('/validations/primes/fonction/division'),
            ),
        ],
      ),
    );
  }
}
