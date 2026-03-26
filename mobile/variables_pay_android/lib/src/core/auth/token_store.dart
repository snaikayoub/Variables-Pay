import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

final tokenStoreProvider = Provider<TokenStore>((ref) {
  return TokenStore(const FlutterSecureStorage());
});

class TokenStore {
  TokenStore(this._storage);

  final FlutterSecureStorage _storage;

  static const _kAccessToken = 'access_token';
  static const _kRefreshToken = 'refresh_token';

  Future<String?> getAccessToken() => _storage.read(key: _kAccessToken);
  Future<String?> getRefreshToken() => _storage.read(key: _kRefreshToken);

  Future<void> setAccessToken(String token) => _storage.write(key: _kAccessToken, value: token);
  Future<void> setRefreshToken(String token) => _storage.write(key: _kRefreshToken, value: token);

  Future<void> clear() async {
    await _storage.delete(key: _kAccessToken);
    await _storage.delete(key: _kRefreshToken);
  }
}
