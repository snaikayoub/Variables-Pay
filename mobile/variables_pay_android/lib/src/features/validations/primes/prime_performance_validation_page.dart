import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/http/api_client.dart';
import '../../../ui/app_scaffold.dart';
import 'prime_validation_api.dart';

final primeApiProvider = Provider<PrimeValidationApi>((ref) {
  final dio = ref.watch(dioProvider);
  return PrimeValidationApi(dio);
});

class PrimePerformanceValidationPage extends ConsumerStatefulWidget {
  const PrimePerformanceValidationPage({super.key, required this.scope});

  final String scope; // service | division

  @override
  ConsumerState<PrimePerformanceValidationPage> createState() => _PrimePerformanceValidationPageState();
}

class _PrimePerformanceValidationPageState extends ConsumerState<PrimePerformanceValidationPage> {
  String _typePaie = 'mensuelle';
  String _status = 'submitted';
  final Set<int> _selected = {};
  bool _busy = false;
  late Future<List<PrimeItem>> _future;

  String get _endpoint => '/api/responsable/${widget.scope}/prime-performance';

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (widget.scope == 'division' && _status == 'submitted') {
      _status = 'service_validated';
    }

    _future = _load();
  }

  Future<List<PrimeItem>> _load() async {
    final api = ref.read(primeApiProvider);
    return api.list(endpoint: _endpoint, typePaie: _typePaie, status: _status);
  }

  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'draft':
        return 'Brouillon';
      case 'submitted':
        return 'Soumis';
      case 'service_validated':
        return 'Service valide';
      case 'division_validated':
        return 'Division valide';
      case 'validated':
        return 'Valide';
      default:
        return status;
    }
  }

  Future<void> _runBatch(String action) async {
    if (_selected.isEmpty) return;
    setState(() => _busy = true);

    try {
      final api = ref.read(primeApiProvider);
      final res = await api.batchAction(endpoint: _endpoint, action: action, ids: _selected.toList());
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('OK: ${res.done.length} / ${res.processed}')),
        );
      }
      setState(() => _selected.clear());
      _reload();
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Erreur pendant l\'operation batch')),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final scopeLabel = widget.scope == 'division' ? 'Division' : 'Service';
    final scheme = Theme.of(context).colorScheme;

    return AppScaffold(
      title: 'Prime performance - $scopeLabel',
      showHomeButton: true,
      actions: [
        IconButton(
          tooltip: 'Rafraichir',
          icon: const Icon(Icons.refresh),
          onPressed: _busy ? null : _reload,
        ),
      ],
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    key: ValueKey('typePaie-$_typePaie'),
                    initialValue: _typePaie,
                    items: const [
                      DropdownMenuItem(value: 'mensuelle', child: Text('Mensuelle')),
                      DropdownMenuItem(value: 'quinzaine', child: Text('Quinzaine')),
                    ],
                    onChanged: _busy
                        ? null
                        : (v) {
                            if (v == null) return;
                            setState(() {
                              _typePaie = v;
                              _selected.clear();
                            });
                            _reload();
                          },
                    decoration: const InputDecoration(labelText: 'Type de paie'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    key: ValueKey('status-$_status'),
                    initialValue: _status,
                    items: widget.scope == 'division'
                        ? const [
                            DropdownMenuItem(value: 'service_validated', child: Text('Service valide')),
                            DropdownMenuItem(value: 'division_validated', child: Text('Division valide')),
                          ]
                        : const [
                            DropdownMenuItem(value: 'submitted', child: Text('Soumis')),
                            DropdownMenuItem(value: 'service_validated', child: Text('Service valide')),
                            DropdownMenuItem(value: 'division_validated', child: Text('Division valide')),
                          ],
                    onChanged: _busy
                        ? null
                        : (v) {
                            if (v == null) return;
                            setState(() {
                              _status = v;
                              _selected.clear();
                            });
                            _reload();
                          },
                    decoration: const InputDecoration(labelText: 'Statut'),
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _busy ? null : () => context.go('/validations'),
                    icon: const Icon(Icons.dashboard_outlined),
                    label: const Text('Tableau de bord'),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Row(
              children: [
                Expanded(
                  child: Text('${_selected.length} selection(s)', style: TextStyle(color: scheme.onSurfaceVariant)),
                ),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    FilledButton.tonal(
                      onPressed: (_busy || _selected.isEmpty) ? null : () => _runBatch('validate'),
                      child: const Text('Valider'),
                    ),
                    FilledButton.tonal(
                      onPressed: (_busy || _selected.isEmpty) ? null : () => _runBatch('retour'),
                      child: Text(widget.scope == 'division' ? 'Retour service' : 'Retour gestionnaire'),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => _reload(),
              child: FutureBuilder<List<PrimeItem>>(
                future: _future,
                builder: (context, snap) {
                  if (snap.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snap.hasError) {
                    return ListView(
                      children: const [
                        SizedBox(height: 160),
                        Center(child: Text('Erreur de chargement')),
                      ],
                    );
                  }
                  final items = snap.data ?? const <PrimeItem>[];
                  if (items.isEmpty) {
                    return ListView(
                      children: const [
                        SizedBox(height: 160),
                        Center(child: Text('Aucun element')),
                      ],
                    );
                  }

                  return ListView.separated(
                    padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final it = items[i];
                      final selected = _selected.contains(it.id);

                      return Card(
                        child: InkWell(
                          borderRadius: BorderRadius.circular(16),
                          onTap: _busy
                              ? null
                              : () {
                                  setState(() {
                                    if (selected) {
                                      _selected.remove(it.id);
                                    } else {
                                      _selected.add(it.id);
                                    }
                                  });
                                },
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            child: Row(
                              children: [
                                Checkbox(
                                  value: selected,
                                  onChanged: _busy
                                      ? null
                                      : (val) {
                                          setState(() {
                                            if (val == true) {
                                              _selected.add(it.id);
                                            } else {
                                              _selected.remove(it.id);
                                            }
                                          });
                                        },
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        '${it.employeeMatricule}  ${it.employeeName}',
                                        style: Theme.of(context).textTheme.titleMedium,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      const SizedBox(height: 6),
                                      Wrap(
                                        spacing: 8,
                                        runSpacing: 6,
                                        children: [
                                          Chip(
                                            label: Text(_statusLabel(it.status)),
                                            visualDensity: VisualDensity.compact,
                                          ),
                                          if (it.periodeLabel.isNotEmpty)
                                            Chip(
                                              label: Text(it.periodeLabel),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (it.tauxMonetaire != null)
                                            Chip(
                                              label: Text('TM: ${it.tauxMonetaire}'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (it.jours != null)
                                            Chip(
                                              label: Text('Jours: ${it.jours}'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (it.note != null)
                                            Chip(
                                              label: Text('Note: ${it.note}'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (it.scoreEquipe != null)
                                            Chip(
                                              label: Text('Score eq: ${it.scoreEquipe}'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (it.scoreCollectif != null)
                                            Chip(
                                              label: Text('Score col: ${it.scoreCollectif}'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (it.montant != null)
                                            Chip(
                                              label: Text('Montant: ${it.montant}'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
