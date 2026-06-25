class ApiEndpoints {
  // Configurable base URL
  static String baseUrl = 'http://192.168.1.10:8000/api/mobile';

  // Auth & Onboarding
  static String get verifySchool => '$baseUrl/school/verify';
  static String getAcademicYears(String code) => '$baseUrl/academic-years/$code';
  static String get login => '$baseUrl/login';
  static String get changePassword => '$baseUrl/change-password';
  
  // Authenticated
  static String get dashboard => '$baseUrl/dashboard';
  static String get profile => '$baseUrl/profile';
  static String get attendance => '$baseUrl/attendance';
  static String get homework => '$baseUrl/homework';
  static String get notices => '$baseUrl/notices';
  static String get results => '$baseUrl/results';
  static String get reportCards => '$baseUrl/report-cards';
  
  static String reportCardPdfUrl(int examId) => '$baseUrl/report-card/$examId';
  static String reportCardDownloadUrl(int examId) => '$baseUrl/report-card/$examId/download';
  
  static String get fees => '$baseUrl/fees';
  static String get receipts => '$baseUrl/receipts';
  
  static String receiptPdfUrl(int receiptId) => '$baseUrl/receipt/$receiptId';
  static String receiptDownloadUrl(int receiptId) => '$baseUrl/receipt/$receiptId/download';
}
