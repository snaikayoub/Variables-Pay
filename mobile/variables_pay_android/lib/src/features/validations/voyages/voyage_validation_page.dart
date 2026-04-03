import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/http/api_client.dart';
import '../../../ui/app_scaffold.dart';
import 'voyage_validation_api.dart';

final voyageApiProvider = Provider<VoyageValidationApi>((ref) {
  final dio = ref.watch(dioProvider);
  return VoyageValidationApi(dio);
});

class VoyageValidationPage extends ConsumerStatefulWidget {
  const VoyageValidationPage({super.key, required this.scope});

  final String scope; // service | division

  @override
  ConsumerState<VoyageValidationPage> createState() => _VoyageValidationPageState();
}

class _VoyageValidationPageState extends ConsumerState<VoyageValidationPage> {
  String _typePaie = 'mensuelle';
  String _status = 'submitted';
  final Set<int> _selected = {};

  bool _busy = false;
  late Future<List<VoyageItem>> _future;

  Future<List<VoyageItem>> _load() async {
    final api = ref.read(voyageApiProvider);
    return api.list(scope: widget.scope, typePaie: _typePaie, status: _status);
  }

  void _reload() {
    setState(() {
      _future = _load();
    });
  }

  String _title() {
    final scopeLabel = widget.scope == 'division' ? 'Division' : 'Service';
    return 'Voyages - $scopeLabel';
  }

  List<_ActionSpec> _actions() {
    final scope = widget.scope;
    if (scope == 'division') {
      return const [
        _ActionSpec(label: 'Valider', action: 'validate'),
        _ActionSpec(label: 'Rejeter', action: 'reject'),
        _ActionSpec(label: 'Retour service', action: 'retour'),
      ];
    }
    return const [
      _ActionSpec(label: 'Valider', action: 'validate'),
      _ActionSpec(label: 'Rejeter', action: 'reject'),
      _ActionSpec(label: 'Retour gestionnaire', action: 'retour'),
    ];
  }

  Future<void> _runBatch(String action) async {
    if (_selected.isEmpty) return;

    setState(() => _busy = true);

    try {
      final api = ref.read(voyageApiProvider);
      final res = await api.batchAction(scope: widget.scope, action: action, ids: _selected.toList());

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
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (widget.scope == 'division' && _status == 'submitted') {
      _status = 'service_validated';
    }

    _future = _load();
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'draft':
        return 'Brouillon';
      case 'submitted':
        return 'Soumis';
      case 'service_validated':
        return 'Service valide';
      case 'validated':
        return 'Valide';
      case 'rejected':
        return 'Rejete';
      default:
        return status;
    }
  }

  String _fmtDt(DateTime? dt) {
    if (dt == null) return '';
    String two(int v) => v.toString().padLeft(2, '0');
    return '${two(dt.day)}/${two(dt.month)} ${two(dt.hour)}:${two(dt.minute)}';
  }

  String _routeLabel(String? from, String? to) {
    final a = (from ?? '').trim();
    final b = (to ?? '').trim();
    if (a.isEmpty && b.isEmpty) return '';
    if (a.isEmpty) return b;
    if (b.isEmpty) return a;
    return '$a -> $b';
  }

  Widget _prisEnChargeChip(BuildContext context, bool prisEnCharge) {
    final scheme = Theme.of(context).colorScheme;

    return Chip(
      avatar: Icon(
        prisEnCharge ? Icons.verified_outlined : Icons.remove_circle_outline,
        size: 18,
        color: prisEnCharge ? scheme.tertiary : scheme.onSurfaceVariant,
      ),
      label: Text(prisEnCharge ? 'Pris en charge' : 'Non pris en charge'),
      visualDensity: VisualDensity.compact,
      backgroundColor: prisEnCharge ? scheme.tertiaryContainer.withValues(alpha: 0.55) : scheme.surfaceContainerHighest,
      side: BorderSide(color: scheme.outlineVariant.withValues(alpha: 0.6)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final actions = _actions();
    final scheme = Theme.of(context).colorScheme;

    return AppScaffold(
      title: _title(),
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
                            DropdownMenuItem(value: 'validated', child: Text('Valide')),
                            DropdownMenuItem(value: 'rejected', child: Text('Rejete')),
                          ]
                        : const [
                            DropdownMenuItem(value: 'submitted', child: Text('Soumis')),
                            DropdownMenuItem(value: 'service_validated', child: Text('Service valide')),
                            DropdownMenuItem(value: 'validated', child: Text('Valide')),
                            DropdownMenuItem(value: 'rejected', child: Text('Rejete')),
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
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  '${_selected.length} selection(s)',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: scheme.onSurfaceVariant),
                ),
                const SizedBox(height: 8),
                Align(
                  alignment: Alignment.centerRight,
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final a in actions)
                        FilledButton.tonal(
                          onPressed: (_busy || _selected.isEmpty) ? null : () => _runBatch(a.action),
                          child: Text(a.label),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => _reload(),
              child: FutureBuilder<List<VoyageItem>>(
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

                  final items = snap.data ?? const <VoyageItem>[];
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
                      final v = items[i];
                      final selected = _selected.contains(v.id);

                      final aller = _routeLabel(v.villeDepartAller, v.villeArriveeAller);
                      final retour = _routeLabel(v.villeDepartRetour, v.villeArriveeRetour);
                      final distance = (v.distanceKm == null || v.distanceKm == 0) ? '' : '${v.distanceKm!.toStringAsFixed(0)} km';
                      final transport = v.modeTransport.trim().isEmpty ? '' : v.modeTransport.trim();
                      return Card(
                        child: InkWell(
                          borderRadius: BorderRadius.circular(16),
                          onTap: _busy
                              ? null
                              : () {
                                  setState(() {
                                    if (selected) {
                                      _selected.remove(v.id);
                                    } else {
                                      _selected.add(v.id);
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
                                              _selected.add(v.id);
                                            } else {
                                              _selected.remove(v.id);
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
                                        '${v.employeeMatricule}  ${v.employeeName}',
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
                                            label: Text(_statusLabel(v.status)),
                                            visualDensity: VisualDensity.compact,
                                          ),
                                          if (aller.isNotEmpty)
                                            Chip(
                                              label: Text('Aller: $aller'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (retour.isNotEmpty)
                                            Chip(
                                              label: Text('Retour: $retour'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (transport.isNotEmpty)
                                            Chip(
                                              label: Text('Transport: $transport'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (distance.isNotEmpty)
                                            Chip(
                                              label: Text(distance),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          _prisEnChargeChip(context, v.prisEnCharge),
                                          if (v.dateHeureDepart != null)
                                            Chip(
                                              label: Text('Depart: ${_fmtDt(v.dateHeureDepart)}'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if (v.dateHeureRetour != null)
                                            Chip(
                                              label: Text('Retour: ${_fmtDt(v.dateHeureRetour)}'),
                                              visualDensity: VisualDensity.compact,
                                            ),
                                          if ((v.typeVoyage ?? '').trim().isNotEmpty)
                                            Chip(
                                              label: Text('Type: ${v.typeVoyage}'),
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

class _ActionSpec {
  const _ActionSpec({required this.label, required this.action});

  final String label;
  final String action;
}
