class HomeworkModel {
  final int id;
  final String title;
  final String description;
  final String submissionDate; // YYYY-MM-DD
  final String? attachment; // relative URL
  final SubjectModel? subject;

  HomeworkModel({
    required this.id,
    required this.title,
    required this.description,
    required this.submissionDate,
    this.attachment,
    this.subject,
  });

  factory HomeworkModel.fromJson(Map<String, dynamic> json) {
    return HomeworkModel(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      submissionDate: json['submission_date'] ?? '',
      attachment: json['attachment'],
      subject: json['subject'] != null ? SubjectModel.fromJson(json['subject']) : null,
    );
  }
}

class SubjectModel {
  final int id;
  final String name;
  final String code;

  SubjectModel({required this.id, required this.name, required this.code});

  factory SubjectModel.fromJson(Map<String, dynamic> json) {
    return SubjectModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      code: json['code'] ?? '',
    );
  }
}
