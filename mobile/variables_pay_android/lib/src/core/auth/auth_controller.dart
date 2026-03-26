import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../http/api_client.dart';
import 'auth_api.dart';
import 'token_store.dart';

enum AuthStatus { unknown, unauthenticated, authenticated }

class AuthState {
  const AuthState({required this.status, this.user});

  final AuthStatus status;
  final UserDto? user;
}

final authControllerProvider = ChangeNotifierProvider<AuthController>((ref) {
  final dio = ref.watch(dioProvider);
  final tokenStore = ref.watch(tokenStoreProvider);
  final api = AuthApi(dio);
  final controller = AuthController(api: api, tokenStore: tokenStore);
  controller.bootstrap();
  return controller;
});

class AuthController extends ChangeNotifier {
  AuthController({required this.api, required this.tokenStore});

  final AuthApi api;
  final TokenStore tokenStore;

  AuthState _state = const AuthState(status: AuthStatus.unknown);
  AuthState get state => _state;
  AuthStatus get status => _state.status;
  UserDto? get user => _state.user;

  Future<void> bootstrap() async {
    final token = await tokenStore.getAccessToken();
    if (token == null || token.isEmpty) {
      _state = const AuthState(status: AuthStatus.unauthenticated);
      notifyListeners();
      return;
    }

    try {
      final me = await api.me();
      _state = AuthState(status: AuthStatus.authenticated, user: me);
    } catch (_) {
      await tokenStore.clear();
      _state = const AuthState(status: AuthStatus.unauthenticated);
    }
    notifyListeners();
  }

  Future<void> login({required String email, required String password}) async {
    final res = await api.login(email: email, password: password);
    if (res.token.isEmpty || res.refreshToken.isEmpty) {
      throw Exception('Missing token');
    }
    await tokenStore.setAccessToken(res.token);
    await tokenStore.setRefreshToken(res.refreshToken);
    final me = await api.me();
    _state = AuthState(status: AuthStatus.authenticated, user: me);
    notifyListeners();
  }

  Future<void> logout() async {
    await tokenStore.clear();
    _state = const AuthState(status: AuthStatus.unauthenticated);
    notifyListeners();
  }
}
