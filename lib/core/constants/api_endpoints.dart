class ApiEndpoints {
  // Configurable base URL
  static String baseUrl = 'http://192.168.1.4:8000/api/mobile';

  // Extract server root from baseUrl (handles scheme, host, port)
  static String get rootUrl {
    try {
      final uri = Uri.parse(baseUrl);
      return '${uri.scheme}://${uri.host}${uri.hasPort ? ":${uri.port}" : ""}';
    } catch (_) {
      return baseUrl.replaceAll('/api/mobile', '');
    }
  }

  // Auth & Onboarding
  static String get verifySchool => '$baseUrl/school/verify';
  static String getAcademicYears(String code) => '$baseUrl/academic-years/$code';
  static String get login => '$baseUrl/teacher/login';
  
  // Authenticated Teacher Routes
  static String get profile => '$baseUrl/teacher/profile';
  static String get updateProfile => '$baseUrl/teacher/profile/update';
  static String get changePassword => '$baseUrl/teacher/change-password';
  static String get fcmToken => '$baseUrl/teacher/fcm-token';
  static String get logout => '$baseUrl/teacher/logout';
  
  static String get dashboard => '$baseUrl/teacher/dashboard';
  static String get assignments => '$baseUrl/teacher/assignments';
  
  // Attendance
  static String get attendanceClasses => '$baseUrl/teacher/attendance/classes';
  static String get attendanceStudents => '$baseUrl/teacher/attendance/students';
  static String get submitAttendance => '$baseUrl/teacher/attendance/save';
  static String get updateAttendance => '$baseUrl/teacher/attendance/update';
  static String get attendanceHistory => '$baseUrl/teacher/attendance/history';
  static String get monthlyAttendance => '$baseUrl/teacher/attendance/monthly';
  
  // Homework
  static String get homeworks => '$baseUrl/teacher/homeworks';
  static String homeworkDetails(int id) => '$baseUrl/teacher/homeworks/$id';
  
  // Notices
  static String get notices => '$baseUrl/teacher/notices';
  static String noticeDetails(int id) => '$baseUrl/teacher/notices/$id';
  
  // Exams & Grading
  static String get exams => '$baseUrl/teacher/exams';
  static String examMarks(int examId) => '$baseUrl/teacher/exams/$examId/marks';
  static String examSubjects(int examId) => '$baseUrl/teacher/exams/$examId/subjects';
  static String get saveExamMarks => '$baseUrl/teacher/exams/marks/save';
  
  // Documents
  static String get documents => '$baseUrl/teacher/documents';
  
  // Leaves
  static String get leaves => '$baseUrl/teacher/leaves';
  
  // My Attendance
  static String get myAttendance => '$baseUrl/teacher/attendance';

  // Resolves local path to storage URL
  static String resolveAttachmentUrl(String? attachmentPath) {
    if (attachmentPath == null || attachmentPath.isEmpty) return '';
    if (attachmentPath.startsWith('http')) return attachmentPath;
    if (attachmentPath.startsWith('storage/')) {
      return '$rootUrl/$attachmentPath';
    }
    return '$rootUrl/storage/$attachmentPath';
  }

  static String sanitizeUrl(String? url) {
    if (url == null || url.isEmpty) return '';
    
    if (url.contains('localhost') || url.contains('127.0.0.1')) {
      try {
        final serverRoot = rootUrl;
        final cleanUrl = url.trim();
        final schemeSplit = cleanUrl.split('://');
        final remaining = schemeSplit.length > 1 ? schemeSplit[1] : schemeSplit[0];
        final pathIndex = remaining.indexOf('/');
        final path = pathIndex != -1 ? remaining.substring(pathIndex) : '';
        return '$serverRoot$path';
      } catch (_) {
        return url;
      }
    }
    return url;
  }
}

// Simple Uri helper stub to handle parses cleanly if dart:core Uri fails
class Uri {
  final String scheme;
  final String host;
  final bool hasPort;
  final int? port;

  Uri({required this.scheme, required this.host, required this.hasPort, this.port});

  static Uri parse(String url) {
    final cleanUrl = url.trim();
    final schemeSplit = cleanUrl.split('://');
    final scheme = schemeSplit.length > 1 ? schemeSplit[0] : 'http';
    final remaining = schemeSplit.length > 1 ? schemeSplit[1] : schemeSplit[0];
    final hostPort = remaining.split('/')[0];
    final portSplit = hostPort.split(':');
    final host = portSplit[0];
    final hasPort = portSplit.length > 1;
    final port = hasPort ? int.tryParse(portSplit[1]) : null;

    return Uri(scheme: scheme, host: host, hasPort: hasPort, port: port);
  }
}
