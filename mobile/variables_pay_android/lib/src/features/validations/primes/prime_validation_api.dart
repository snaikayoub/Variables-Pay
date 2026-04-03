import 'package:dio/dio.dart';

class PrimeValidationApi {
  PrimeValidationApi(this._dio);

  final Dio _dio;

  Future<List<PrimeItem>> list({required String endpoint, required String typePaie, required String status}) async {
    final resp = await _dio.get(
      endpoint,
      queryParameters: {
        'typePaie': typePaie,
        'status': status,
      },
    );
    final data = resp.data;
    if (data is! Map<String, dynamic>) {
      throw const FormatException('Unexpected response');
    }
    final items = data['items'];
    if (items is! List) return const <PrimeItem>[];
    return items.whereType<Map>().map((e) => PrimeItem.fromJson(e.cast<String, dynamic>())).toList();
  }

  Future<BatchResult> batchAction({required String endpoint, required String action, required List<int> ids}) async {
    final resp = await _dio.post(
      '$endpoint/batch/$action',
      data: {'ids': ids},
    );
    final data = resp.data;
    if (data is! Map<String, dynamic>) {
      throw const FormatException('Unexpected response');
    }
    return BatchResult.fromJson(data);
  }
}

class PrimeItem {
  PrimeItem({
    required this.id,
    required this.status,
    required this.employeeName,
    required this.employeeMatricule,
    required this.periodeLabel,
    this.montant,
    this.tauxMonetaire,
    this.jours,
    this.note,
    this.scoreEquipe,
    this.scoreCollectif,
  });

  final int id;
  final String status;
  final String employeeName;
  final String employeeMatricule;
  final String periodeLabel;
  final double? montant;
  final double? tauxMonetaire;
  final double? jours;
  final double? note;
  final double? scoreEquipe;
  final double? scoreCollectif;

  factory PrimeItem.fromJson(Map<String, dynamic> json) {
    final employee = json['employee'];
    final employeeName = employee is Map ? (employee['fullName'] ?? '') : '';
    final employeeMat = employee is Map ? (employee['matricule'] ?? '') : '';

    String periodeLabel = '';
    final periode = json['periode'];
    if (periode is Map) {
      periodeLabel = (periode['label'] ?? '') as String;
    } else if (json['periode'] is String) {
      periodeLabel = (json['periode'] ?? '') as String;
    }

    double? montant;
    final mp = json['montantPerf'];
    final mf = json['montantFonction'];
    if (mp is num) montant = mp.toDouble();
    if (mf is num) montant = mf.toDouble();

    double? pickNum(String key) {
      final v = json[key];
      if (v is num) return v.toDouble();
      return null;
    }

    // Prime performance keys: tauxMonetaire, joursPerf, noteHierarchique, scoreEquipe, scoreCollectif
    // Prime fonction keys: tauxMonetaireFonction, nombreJours, noteHierarchique
    final taux = pickNum('tauxMonetaire') ?? pickNum('tauxMonetaireFonction');
    final jours = pickNum('joursPerf') ?? pickNum('nombreJours');
    final note = pickNum('noteHierarchique');
    final se = pickNum('scoreEquipe');
    final sc = pickNum('scoreCollectif');

    return PrimeItem(
      id: (json['id'] ?? 0) as int,
      status: (json['status'] ?? '') as String,
      employeeName: employeeName.toString(),
      employeeMatricule: employeeMat.toString(),
      periodeLabel: periodeLabel,
      montant: montant,
      tauxMonetaire: taux,
      jours: jours,
      note: note,
      scoreEquipe: se,
      scoreCollectif: sc,
    );
  }
}

class BatchResult {
  BatchResult({required this.processed, required this.done, required this.skipped});

  final int processed;
  final List<int> done;
  final List<Map<String, dynamic>> skipped;

  factory BatchResult.fromJson(Map<String, dynamic> json) {
    final processed = (json['processed'] ?? 0) as int;

    List<int> pickList(String key) {
      final v = json[key];
      if (v is List) return v.map((e) => (e as num).toInt()).toList();
      return const <int>[];
    }

    final validated = pickList('validated');
    final returned = pickList('returned');
    final combined = <int>{...validated, ...returned}.toList();

    final skippedRaw = json['skipped'];
    final skipped = skippedRaw is List ? skippedRaw.whereType<Map>().map((e) => e.cast<String, dynamic>()).toList() : const <Map<String, dynamic>>[];

    return BatchResult(processed: processed, done: combined, skipped: skipped);
  }
}
