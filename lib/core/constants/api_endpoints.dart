class ApiEndpoints {
  // Configurable base URL
  static String baseUrl = 'http://168.144.147.94/api/mobile';
  // static String baseUrl = 'http://192.168.1.10:8000/api/mobile';

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
  static String get login => '$baseUrl/login';
  static String get changePassword => '$baseUrl/change-password';
  static String get registerDevice => '$baseUrl/register-device';
  static String get logout => '$baseUrl/logout';
  
  // Authenticated
  static String get dashboard => '$baseUrl/dashboard';
  static String get profile => '$baseUrl/profile';
  static String get attendance => '$baseUrl/attendance';
  static String get homework => '$baseUrl/homework';
  static String get notices => '$baseUrl/notices';
  static String get subjects => '$baseUrl/subjects';
  static String get results => '$baseUrl/results';
  static String get reportCards => '$baseUrl/report-cards';
  
  static String reportCardPdfUrl(int examId) => '$baseUrl/report-card/$examId';
  static String reportCardDownloadUrl(int examId) => '$baseUrl/report-card/$examId/download';
  
  static String get fees => '$baseUrl/fees';
  static String get receipts => '$baseUrl/receipts';
  
  static String receiptPdfUrl(int receiptId) => '$baseUrl/receipt/$receiptId';
  static String receiptDownloadUrl(int receiptId) => '$baseUrl/receipt/$receiptId/download';

  // Resolves the local database/API path to a fully qualified URL pointing to storage
  static String resolveAttachmentUrl(String? attachmentPath) {
    if (attachmentPath == null || attachmentPath.isEmpty) return '';
    if (attachmentPath.startsWith('http')) return attachmentPath;
    if (attachmentPath.startsWith('storage/')) {
      return '$rootUrl/$attachmentPath';
    }
    return '$rootUrl/storage/$attachmentPath';
  }
}

