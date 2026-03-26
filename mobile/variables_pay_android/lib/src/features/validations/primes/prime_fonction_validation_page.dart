import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import 'prime_validation_api.dart';

final primeApiProvider = Provider<PrimeValidationApi>((ref) {
  final dio = ref.watch(dioProvider);
  return PrimeValidationApi(dio);
});

class PrimeFonctionValidationPage extends ConsumerStatefulWidget {
  const PrimeFonctionValidationPage({super.key, required this.scope});

  final String scope; // service | division

  @override
  ConsumerState<PrimeFonctionValidationPage> createState() => _PrimeFonctionValidationPageState();
}

class _PrimeFonctionValidationPageState extends ConsumerState<PrimeFonctionValidationPage> {
  String _typePaie = 'mensuelle';
  String _status = 'submitted';
  final Set<int> _selected = {};
  bool _busy = false;

  String get _endpoint => '/api/responsable/${widget.scope}/prime-fonction';

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (widget.scope == 'division' && _status == 'submitted') {
      _status = 'service_validated';
    }
  }

  Future<List<PrimeItem>> _load() async {
    final api = ref.read(primeApiProvider);
    return api.list(endpoint: _endpoint, typePaie: _typePaie, status: _status);
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
    return Scaffold(
      appBar: AppBar(title: Text('Prime fonction - $scopeLabel')),
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
                  FilledButton.tonal(
                    onPressed: (_busy || _selected.isEmpty) ? null : () => _runBatch('validate'),
                    child: const Text('Valider'),
                  ),
                  const SizedBox(width: 8),
                  FilledButton.tonal(
                    onPressed: (_busy || _selected.isEmpty) ? null : () => _runBatch('retour'),
                    child: Text(widget.scope == 'division' ? 'Retour service' : 'Retour gestionnaire'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: FutureBuilder<List<PrimeItem>>(
                future: _load(),
                builder: (context, snap) {
                  if (snap.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snap.hasError) {
                    return const Center(child: Text('Erreur de chargement'));
                  }
                  final items = snap.data ?? const <PrimeItem>[];
                  if (items.isEmpty) {
                    return const Center(child: Text('Aucun element'));
                  }

                  return ListView.separated(
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, i) {
                      final it = items[i];
                      final selected = _selected.contains(it.id);
                      final subtitle = it.montant != null ? '${it.periodeLabel} | Montant: ${it.montant}' : it.periodeLabel;
                      return CheckboxListTile(
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
                        title: Text('${it.employeeMatricule} - ${it.employeeName}'),
                        subtitle: Text('${it.status} | $subtitle'),
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
