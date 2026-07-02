class AssignmentModel {
  final int id;
  final int classId;
  final int sectionId;
  final int subjectId;
  final bool isClassTeacher;
  final ClassNameModel className;
  final SectionNameModel sectionName;
  final SubjectNameModel subjectName;
  int? totalStudents; // Derived or loaded from student list count

  AssignmentModel({
    required this.id,
    required this.classId,
    required this.sectionId,
    required this.subjectId,
    required this.isClassTeacher,
    required this.className,
    required this.sectionName,
    required this.subjectName,
    this.totalStudents,
  });

  factory AssignmentModel.fromJson(Map<String, dynamic> json) {
    return AssignmentModel(
      id: json['id'] ?? 0,
      classId: json['class_id'] ?? 0,
      sectionId: json['section_id'] ?? 0,
      subjectId: json['subject_id'] ?? 0,
      isClassTeacher: json['is_class_teacher'] == 1 || json['is_class_teacher'] == true,
      className: ClassNameModel.fromJson(json['class'] ?? {}),
      sectionName: SectionNameModel.fromJson(json['section'] ?? {}),
      subjectName: SubjectNameModel.fromJson(json['subject'] ?? {}),
      totalStudents: json['total_students'] as int?,
    );
  }
}

class ClassNameModel {
  final int id;
  final String name;

  ClassNameModel({required this.id, required this.name});

  factory ClassNameModel.fromJson(Map<String, dynamic> json) {
    return ClassNameModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
    );
  }
}

class SectionNameModel {
  final int id;
  final String name;

  SectionNameModel({required this.id, required this.name});

  factory SectionNameModel.fromJson(Map<String, dynamic> json) {
    return SectionNameModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
    );
  }
}

class SubjectNameModel {
  final int id;
  final String name;

  SubjectNameModel({required this.id, required this.name});

  factory SubjectNameModel.fromJson(Map<String, dynamic> json) {
    return SubjectNameModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
    );
  }
}
