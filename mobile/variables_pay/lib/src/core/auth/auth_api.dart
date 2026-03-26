import 'package:dio/dio.dart';

class AuthApi {
  AuthApi(this._dio);

  final Dio _dio;

  Future<LoginResponse> login({required String email, required String password}) async {
    final resp = await _dio.post(
      '/api/auth/login',
      data: {'email': email, 'password': password},
    );
    final data = resp.data;
    if (data is! Map<String, dynamic>) {
      throw const FormatException('Unexpected response');
    }
    return LoginResponse.fromJson(data);
  }

  Future<UserDto> me() async {
    final resp = await _dio.get('/api/me');
    final data = resp.data;
    if (data is! Map<String, dynamic>) {
      throw const FormatException('Unexpected response');
    }
    return UserDto.fromJson(data);
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
