import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
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

  Future<List<VoyageItem>> _load() async {
    final api = ref.read(voyageApiProvider);
    return api.list(scope: widget.scope, typePaie: _typePaie, status: _status);
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
  }

  @override
  Widget build(BuildContext context) {
    final actions = _actions();

    return Scaffold(
      appBar: AppBar(title: Text(_title())),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _typePaie,
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
                            },
                      decoration: const InputDecoration(labelText: 'Type de paie'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _status,
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
                  Text('${_selected.length} selection(s)'),
                  const Spacer(),
                  for (final a in actions) ...[
                    const SizedBox(width: 8),
                    FilledButton.tonal(
                      onPressed: (_busy || _selected.isEmpty) ? null : () => _runBatch(a.action),
                      child: Text(a.label),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: FutureBuilder<List<VoyageItem>>(
                future: _load(),
                builder: (context, snap) {
                  if (snap.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snap.hasError) {
                    return const Center(child: Text('Erreur de chargement'));
                  }

                  final items = snap.data ?? const <VoyageItem>[];
                  if (items.isEmpty) {
                    return const Center(child: Text('Aucun element'));
                  }

                  return ListView.separated(
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final v = items[i];
                      final selected = _selected.contains(v.id);
                      return CheckboxListTile(
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
                        title: Text('${v.employeeMatricule} - ${v.employeeName}'),
                        subtitle: Text('${v.status} | ${v.dateHeureDepart ?? ''}'),
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ActionSpec {
  const _ActionSpec({required this.label, required this.action});

  final String label;
  final String action;
}
