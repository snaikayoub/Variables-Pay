import 'package:dio/dio.dart';

class AuthApi {
  AuthApi(this._dio);

  final Dio _dio;

  Future<LoginResponse> login({required String email, required String password}) async {
    try {
      final resp = await _dio.post(
        '/api/auth/login',
        data: {'email': email, 'password': password},
      );
      final data = resp.data;
      if (data is! Map<String, dynamic>) {
        throw const FormatException('Unexpected response');
      }
      return LoginResponse.fromJson(data);
    } on DioException catch (e) {
      throw AuthApiException.fromDio(e);
    }
  }

  Future<UserDto> me() async {
    try {
      final resp = await _dio.get('/api/me');
      final data = resp.data;
      if (data is! Map<String, dynamic>) {
        throw const FormatException('Unexpected response');
      }
      return UserDto.fromJson(data);
    } on DioException catch (e) {
      throw AuthApiException.fromDio(e);
    }
  }
}

class AuthApiException implements Exception {
  AuthApiException({required this.message, this.statusCode});

  final String message;
  final int? statusCode;

  factory AuthApiException.fromDio(DioException e) {
    final code = e.response?.statusCode;

    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout) {
      return AuthApiException(message: 'Timeout de connexion', statusCode: code);
    }

    if (e.type == DioExceptionType.badCertificate) {
      return AuthApiException(message: 'Certificat SSL invalide', statusCode: code);
    }

    if (code == 401) {
      return AuthApiException(message: 'Identifiants invalides', statusCode: code);
    }

    if (code != null) {
      return AuthApiException(message: 'Erreur serveur ($code)', statusCode: code);
    }

    return AuthApiException(message: 'Erreur reseau');
  }
}

class LoginResponse {
  LoginResponse({required this.token, required this.refreshToken});

  final String token;
  final String refreshToken;

  factory LoginResponse.fromJson(Map<String, dynamic> json) {
    return LoginResponse(
      token: (json['token'] ?? '') as String,
      refreshToken: (json['refresh_token'] ?? '') as String,
    );
  }
}

class UserDto {
  UserDto({required this.id, required this.email, required this.fullName, required this.roles});

  final int id;
  final String email;
  final String fullName;
  final List<String> roles;

  factory UserDto.fromJson(Map<String, dynamic> json) {
    final rolesRaw = json['roles'];
    return UserDto(
      id: (json['id'] ?? 0) as int,
      email: (json['email'] ?? '') as String,
      fullName: (json['fullName'] ?? '') as String,
      roles: rolesRaw is List ? rolesRaw.map((e) => e.toString()).toList() : const <String>[],
    );
  }
}
