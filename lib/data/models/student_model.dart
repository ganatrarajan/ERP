class StudentModel {
  final int id;
  final String firstName;
  final String lastName;
  final String admissionNo;
  final String grNo;
  final ParentModel? parent;
  final AcademicRecordModel? academicRecord;
  final String? schoolName;
  final String? mobileAcademicYear;

  StudentModel({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.admissionNo,
    required this.grNo,
    this.parent,
    this.academicRecord,
    this.schoolName,
    this.mobileAcademicYear,
  });

  String get fullName => '$firstName $lastName';

  factory StudentModel.fromJson(Map<String, dynamic> json) {
    return StudentModel(
      id: json['id'] ?? 0,
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      admissionNo: json['admission_no'] ?? '',
      grNo: json['gr_no'] ?? '',
      parent: json['parent'] != null ? ParentModel.fromJson(json['parent']) : null,
      academicRecord: json['current_academic_record'] != null
          ? AcademicRecordModel.fromJson(json['current_academic_record'])
          : null,
      schoolName: json['school_name'],
      mobileAcademicYear: json['mobile_academic_year'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'admission_no': admissionNo,
      'gr_no': grNo,
      'parent': parent?.toJson(),
      'current_academic_record': academicRecord?.toJson(),
      'school_name': schoolName,
      'mobile_academic_year': mobileAcademicYear,
    };
  }
}

class ParentModel {
  final int id;
  final String fatherName;
  final String? fatherMobile;
  final String? fatherEmail;
  final String? motherName;
  final String? motherMobile;
  final String? motherEmail;
  final String? guardianName;
  final String? guardianMobile;

  ParentModel({
    required this.id,
    required this.fatherName,
    this.fatherMobile,
    this.fatherEmail,
    this.motherName,
    this.motherMobile,
    this.motherEmail,
    this.guardianName,
    this.guardianMobile,
  });

  factory ParentModel.fromJson(Map<String, dynamic> json) {
    return ParentModel(
      id: json['id'] ?? 0,
      fatherName: json['father_name'] ?? '',
      fatherMobile: json['father_mobile'],
      fatherEmail: json['father_email'],
      motherName: json['mother_name'],
      motherMobile: json['mother_mobile'],
      motherEmail: json['mother_email'],
      guardianName: json['guardian_name'],
      guardianMobile: json['guardian_mobile'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'father_name': fatherName,
      'father_mobile': fatherMobile,
      'father_email': fatherEmail,
      'mother_name': motherName,
      'mother_mobile': motherMobile,
      'mother_email': motherEmail,
      'guardian_name': guardianName,
      'guardian_mobile': guardianMobile,
    };
  }
}

class AcademicRecordModel {
  final int id;
  final int classId;
  final int sectionId;
  final String rollNo;
  final ClassModel? classInfo;
  final SectionModel? sectionInfo;

  AcademicRecordModel({
    required this.id,
    required this.classId,
    required this.sectionId,
    required this.rollNo,
    this.classInfo,
    this.sectionInfo,
  });

  factory AcademicRecordModel.fromJson(Map<String, dynamic> json) {
    return AcademicRecordModel(
      id: json['id'] ?? 0,
      classId: json['class_id'] ?? 0,
      sectionId: json['section_id'] ?? 0,
      rollNo: json['roll_no'] ?? '',
      classInfo: json['class'] != null ? ClassModel.fromJson(json['class']) : null,
      sectionInfo: json['section'] != null ? SectionModel.fromJson(json['section']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'class_id': classId,
      'section_id': sectionId,
      'roll_no': rollNo,
      'class': classInfo?.toJson(),
      'section': sectionInfo?.toJson(),
    };
  }
}

class ClassModel {
  final int id;
  final String name;

  ClassModel({required this.id, required this.name});

  factory ClassModel.fromJson(Map<String, dynamic> json) {
    return ClassModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
    };
  }
}

class SectionModel {
  final int id;
  final String name;

  SectionModel({required this.id, required this.name});

  factory SectionModel.fromJson(Map<String, dynamic> json) {
    return SectionModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
    };
  }
}
