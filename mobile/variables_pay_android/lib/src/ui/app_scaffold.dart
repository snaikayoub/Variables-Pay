import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class AppScaffold extends StatelessWidget {
  const AppScaffold({
    super.key,
    required this.title,
    required this.body,
    this.showHomeButton = false,
    this.actions = const <Widget>[],
  });

  final String title;
  final Widget body;
  final bool showHomeButton;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        title: Text(title),
        actions: [
          if (showHomeButton)
            IconButton(
              tooltip: 'Tableau de bord',
              icon: const Icon(Icons.dashboard_outlined),
              onPressed: () => context.go('/validations'),
            ),
          ...actions,
        ],
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              scheme.primaryContainer.withValues(alpha: 0.70),
              scheme.secondaryContainer.withValues(alpha: 0.35),
              scheme.surface,
            ],
          ),
        ),
        child: SafeArea(child: body),
      ),
    );
  }
}
