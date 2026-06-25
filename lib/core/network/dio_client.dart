import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../services/secure_storage_service.dart';
import 'api_exception.dart';

class DioClient {
  late final Dio _dio;
  final SecureStorageService _secureStorage = SecureStorageService();

  DioClient() {
    _dio = Dio(
      BaseOptions(
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        sendTimeout: const Duration(seconds: 15),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    // Interceptor to inject bearer token
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _secureStorage.getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          
          // Inject student_id into all authenticated requests
          final studentData = await _secureStorage.getStudentData();
          if (studentData != null && studentData['id'] != null) {
            final studentId = studentData['id'];
            if (options.method == 'GET') {
              options.queryParameters = Map<String, dynamic>.from(options.queryParameters)
                ..addAll({'student_id': studentId});
            } else if (options.method == 'POST' || options.method == 'PUT' || options.method == 'PATCH') {
              if (options.data == null) {
                options.data = {'student_id': studentId};
              } else if (options.data is Map) {
                final dataMap = Map<String, dynamic>.from(options.data as Map);
                dataMap['student_id'] = studentId;
                options.data = dataMap;
              } else if (options.data is FormData) {
                options.data.fields.add(MapEntry('student_id', studentId.toString()));
              }
            }
          }

          if (kDebugMode) {
            print('--> ${options.method} ${options.uri}');
            print('Headers: ${options.headers}');
            if (options.data != null) {
              print('Body: ${options.data}');
            }
          }
          return handler.next(options);
        },
        onResponse: (response, handler) {
          if (kDebugMode) {
            print('<-- ${response.statusCode} ${response.requestOptions.uri}');
            print('Response: ${response.data}');
          }
          return handler.next(response);
        },
        onError: (DioException e, handler) {
          if (kDebugMode) {
            print('<-- ERROR ${e.response?.statusCode} ${e.requestOptions.uri}');
            print('Error Message: ${e.message}');
            print('Error Response: ${e.response?.data}');
          }
          return handler.next(e);
        },
      ),
    );
  }

  Dio get dio => _dio;

  Future<Response> get(
    String uri, {
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onReceiveProgress,
  }) async {
    try {
      final response = await _dio.get(
        uri,
        queryParameters: queryParameters,
        options: options,
        cancelToken: cancelToken,
        onReceiveProgress: onReceiveProgress,
      );
      return response;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    } catch (e) {
      throw ApiException(message: "An unexpected error occurred: ${e.toString()}");
    }
  }

  Future<Response> post(
    String uri, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
    CancelToken? cancelToken,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    try {
      final response = await _dio.post(
        uri,
        data: data,
        queryParameters: queryParameters,
        options: options,
        cancelToken: cancelToken,
        onSendProgress: onSendProgress,
        onReceiveProgress: onReceiveProgress,
      );
      return response;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    } catch (e) {
      throw ApiException(message: "An unexpected error occurred: ${e.toString()}");
    }
  }

  // Specialized method for downloading files directly to local storage
  Future<Response> download(
    String urlPath,
    savePath, {
    ProgressCallback? onReceiveProgress,
    Map<String, dynamic>? queryParameters,
    CancelToken? cancelToken,
    Options? options,
  }) async {
    try {
      final response = await _dio.download(
        urlPath,
        savePath,
        onReceiveProgress: onReceiveProgress,
        queryParameters: queryParameters,
        cancelToken: cancelToken,
        options: options,
      );
      return response;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    } catch (e) {
      throw ApiException(message: "Download failed: ${e.toString()}");
    }
  }
}
