import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/auth/auth_controller.dart';

class ValidationsHomePage extends ConsumerWidget {
  const ValidationsHomePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final user = auth.user;

    final roles = user?.roles ?? const <String>[];
    final canService = roles.contains('ROLE_RESPONSABLE_SERVICE') || roles.contains('ROLE_ADMIN') || roles.contains('ROLE_RH');
    final canDivision = roles.contains('ROLE_RESPONSABLE_DIVISION') || roles.contains('ROLE_ADMIN') || roles.contains('ROLE_RH');

    return Scaffold(
      appBar: AppBar(
        title: const Text('Validations'),
        actions: [
          IconButton(
            onPressed: () => ref.read(authControllerProvider).logout(),
            icon: const Icon(Icons.logout),
            tooltip: 'Deconnexion',
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (user != null) ...[
            Text('Bonjour, ${user.fullName}', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
          ],
          Text('Voyages', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 8),
          if (canService)
            Card(
              child: ListTile(
                title: const Text('Validations service'),
                subtitle: const Text('Soumissions en attente / actions batch'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/validations/voyages/service'),
              ),
            ),
          if (canDivision)
            Card(
              child: ListTile(
                title: const Text('Validations division'),
                subtitle: const Text('Validation finale / actions batch'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/validations/voyages/division'),
              ),
            ),
          const SizedBox(height: 16),
          Text('Primes', style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 8),
          if (canService)
            Card(
              child: ListTile(
                title: const Text('Prime performance - service'),
                subtitle: const Text('Soumissions en attente / actions batch'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/validations/primes/performance/service'),
              ),
            ),
          if (canDivision)
            Card(
              child: ListTile(
                title: const Text('Prime performance - division'),
                subtitle: const Text('Validation finale / actions batch'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/validations/primes/performance/division'),
              ),
            ),
          if (canService)
            Card(
              child: ListTile(
                title: const Text('Prime fonction - service'),
                subtitle: const Text('Soumissions en attente / actions batch'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/validations/primes/fonction/service'),
              ),
            ),
          if (canDivision)
            Card(
              child: ListTile(
                title: const Text('Prime fonction - division'),
                subtitle: const Text('Validation finale / actions batch'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.go('/validations/primes/fonction/division'),
              ),
            ),
        ],
      ),
    );
  }
}
