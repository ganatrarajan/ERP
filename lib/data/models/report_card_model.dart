class ReportCardModel {
  final int id;
  final String examName;
  final String startDate;
  final String endDate;
  final String pdfUrl;

  ReportCardModel({
    required this.id,
    required this.examName,
    required this.startDate,
    required this.endDate,
    required this.pdfUrl,
  });

  factory ReportCardModel.fromJson(Map<String, dynamic> json) {
    return ReportCardModel(
      id: json['id'] ?? 0,
      examName: json['exam_name'] ?? '',
      startDate: json['start_date'] ?? '',
      endDate: json['end_date'] ?? '',
      pdfUrl: json['pdf_url'] ?? '',
    );
  }
}
