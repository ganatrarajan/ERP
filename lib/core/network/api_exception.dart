import 'package:dio/dio.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException({required this.message, this.statusCode});

  factory ApiException.fromDioError(DioException dioError) {
    switch (dioError.type) {
      case DioExceptionType.cancel:
        return ApiException(message: "Request to API server was cancelled");
      case DioExceptionType.connectionTimeout:
        return ApiException(message: "Connection timeout with API server");
      case DioExceptionType.receiveTimeout:
        return ApiException(message: "Receive timeout in connection with API server");
      case DioExceptionType.sendTimeout:
        return ApiException(message: "Send timeout in connection with API server");
      case DioExceptionType.connectionError:
        return ApiException(message: "Failed to connect to the server at ${dioError.requestOptions.uri.host}. Please verify your connection.");
      case DioExceptionType.badResponse:
        return ApiException._fromResponse(dioError.response);
      default:
        final errMsg = dioError.message ?? "";
        if (errMsg.contains('Connection failed') || errMsg.contains('SocketException') || errMsg.contains('NetworkIsUnreachable')) {
          return ApiException(message: "Failed to connect to the server at ${dioError.requestOptions.uri.host}. Please verify your connection.");
        }
        return ApiException(message: errMsg.isNotEmpty ? errMsg : "Something went wrong. Please try again.");
    }
  }

  factory ApiException._fromResponse(Response? response) {
    if (response == null) {
      return ApiException(message: "Failed to connect to the server");
    }

    final statusCode = response.statusCode;
    final data = response.data;

    String message = "Received invalid status code: $statusCode";
    
    if (data is Map) {
      if (data.containsKey('message')) {
        message = data['message'].toString();
      } else if (data.containsKey('errors')) {
        // If Laravel validation errors are present
        final errors = data['errors'];
        if (errors is Map) {
          final firstErrorList = errors.values.first;
          if (firstErrorList is List && firstErrorList.isNotEmpty) {
            message = firstErrorList.first.toString();
          }
        }
      }
    }

    return ApiException(message: message, statusCode: statusCode);
  }

  @override
  String toString() => message;
}
