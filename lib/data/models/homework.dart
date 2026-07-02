import 'assignment.dart';

class HomeworkModel {
  final int id;
  final int classId;
  final int sectionId;
  final int subjectId;
  final String title;
  final String description;
  final String submissionDate; // YYYY-MM-DD
  final double? maxMarks;
  final String? attachment;
  final ClassNameModel? className;
  final SectionNameModel? sectionName;
  final SubjectNameModel? subjectName;

  HomeworkModel({
    required this.id,
    required this.classId,
    required this.sectionId,
    required this.subjectId,
    required this.title,
    required this.description,
    required this.submissionDate,
    this.maxMarks,
    this.attachment,
    this.className,
    this.sectionName,
    this.subjectName,
  });

  factory HomeworkModel.fromJson(Map<String, dynamic> json) {
    return HomeworkModel(
      id: json['id'] ?? 0,
      classId: json['class_id'] ?? 0,
      sectionId: json['section_id'] ?? 0,
      subjectId: json['subject_id'] ?? 0,
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      submissionDate: json['submission_date'] ?? '',
      maxMarks: json['max_marks'] != null ? double.tryParse(json['max_marks'].toString()) : null,
      attachment: json['attachment'] ?? json['file_path'],
      className: json['class'] != null ? ClassNameModel.fromJson(json['class']) : null,
      sectionName: json['section'] != null ? SectionNameModel.fromJson(json['section']) : null,
      subjectName: json['subject'] != null ? SubjectNameModel.fromJson(json['subject']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'class_id': classId,
      'section_id': sectionId,
      'subject_id': subjectId,
      'title': title,
      'description': description,
      'submission_date': submissionDate,
      'max_marks': maxMarks,
    };
  }
}
