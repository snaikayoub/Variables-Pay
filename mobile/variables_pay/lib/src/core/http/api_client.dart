import 'dart:io';

import 'package:dio/dio.dart';
import 'package:dio/io.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../config/app_config.dart';
import '../auth/token_store.dart';

final dioProvider = Provider<Dio>((ref) {
  final tokenStore = ref.watch(tokenStoreProvider);

  final dio = Dio(
    BaseOptions(
      baseUrl: AppConfig.baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 20),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ),
  );

  if (kDebugMode) {
    final adapter = dio.httpClientAdapter;
    if (adapter is IOHttpClientAdapter) {
      adapter.createHttpClient = () {
        final client = HttpClient();
        client.badCertificateCallback = (X509Certificate cert, String host, int port) => true;
        return client;
      };
    }
  }

  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await tokenStore.getAccessToken();
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) async {
        final isUnauthorized = error.response?.statusCode == 401;
        final alreadyRetried = error.requestOptions.extra['retried'] == true;

        if (!isUnauthorized || alreadyRetried) {
          handler.next(error);
          return;
        }

        final refreshToken = await tokenStore.getRefreshToken();
        if (refreshToken == null || refreshToken.isEmpty) {
          handler.next(error);
          return;
        }

        try {
          final refreshDio = Dio(BaseOptions(baseUrl: AppConfig.baseUrl));
          if (kDebugMode) {
            final adapter = refreshDio.httpClientAdapter;
            if (adapter is IOHttpClientAdapter) {
              adapter.createHttpClient = () {
                final client = HttpClient();
                client.badCertificateCallback = (X509Certificate cert, String host, int port) => true;
                return client;
              };
            }
          }
          final refreshResp = await refreshDio.post(
            '/api/auth/refresh',
            data: {'refresh_token': refreshToken},
            options: Options(headers: {'Content-Type': 'application/json'}),
          );

          final data = refreshResp.data;
          if (data is Map<String, dynamic>) {
            final newAccess = (data['token'] ?? '') as String;
            final newRefresh = (data['refresh_token'] ?? '') as String;

            if (newAccess.isNotEmpty) {
              await tokenStore.setAccessToken(newAccess);
            }
            if (newRefresh.isNotEmpty) {
              await tokenStore.setRefreshToken(newRefresh);
            }
          }

          final retryOptions = error.requestOptions;
          retryOptions.extra['retried'] = true;
          retryOptions.headers['Authorization'] = 'Bearer ${await tokenStore.getAccessToken()}';

          final cloned = await dio.fetch(retryOptions);
          handler.resolve(cloned);
        } catch (_) {
          handler.next(error);
        }
      },
    ),
  );

  return dio;
});
