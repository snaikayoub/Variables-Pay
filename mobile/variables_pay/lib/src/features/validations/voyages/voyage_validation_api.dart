import 'package:dio/dio.dart';

class VoyageValidationApi {
  VoyageValidationApi(this._dio);

  final Dio _dio;

  Future<List<VoyageItem>> list({required String scope, required String typePaie, required String status}) async {
    final resp = await _dio.get(
      '/api/responsable/$scope/voyages',
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
    if (items is! List) return const <VoyageItem>[];
    return items.whereType<Map>().map((e) => VoyageItem.fromJson(e.cast<String, dynamic>())).toList();
  }

  Future<BatchResult> batchAction({required String scope, required String action, required List<int> ids}) async {
    final resp = await _dio.post(
      '/api/responsable/$scope/voyages/batch/$action',
      data: {'ids': ids},
    );
    final data = resp.data;
    if (data is! Map<String, dynamic>) {
      throw const FormatException('Unexpected response');
    }
    return BatchResult.fromJson(data);
  }
}

class VoyageItem {
  VoyageItem({
    required this.id,
    required this.status,
    required this.typePaie,
    required this.employeeName,
    required this.employeeMatricule,
    required this.dateHeureDepart,
    required this.dateHeureRetour,
  });

  final int id;
  final String status;
  final String typePaie;
  final String employeeName;
  final String employeeMatricule;
  final DateTime? dateHeureDepart;
  final DateTime? dateHeureRetour;

  factory VoyageItem.fromJson(Map<String, dynamic> json) {
    final employee = json['employee'];
    final employeeName = employee is Map ? (employee['fullName'] ?? '') : '';
    final employeeMat = employee is Map ? (employee['matricule'] ?? '') : '';

    DateTime? parseDt(dynamic v) {
      if (v is! String || v.isEmpty) return null;
      return DateTime.tryParse(v);
    }

    return VoyageItem(
      id: (json['id'] ?? 0) as int,
      status: (json['status'] ?? '') as String,
      typePaie: (json['typePaie'] ?? '') as String,
      employeeName: employeeName.toString(),
      employeeMatricule: employeeMat.toString(),
      dateHeureDepart: parseDt(json['dateHeureDepart']),
      dateHeureRetour: parseDt(json['dateHeureRetour']),
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

    final done = pickList('validated');
    final rejected = pickList('rejected');
    final returned = pickList('returned');
    final combined = <int>{...done, ...rejected, ...returned}.toList();

    final skippedRaw = json['skipped'];
    final skipped = skippedRaw is List ? skippedRaw.whereType<Map>().map((e) => e.cast<String, dynamic>()).toList() : const <Map<String, dynamic>>[];

    return BatchResult(processed: processed, done: combined, skipped: skipped);
  }
}
