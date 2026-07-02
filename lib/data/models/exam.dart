class ExamModel {
  final int id;
  final String name;
  final String status;

  ExamModel({
    required this.id,
    required this.name,
    required this.status,
  });

  factory ExamModel.fromJson(Map<String, dynamic> json) {
    return ExamModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      status: json['status'] ?? 'active',
    );
  }
}
