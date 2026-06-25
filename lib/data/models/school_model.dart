class SchoolModel {
  final int id;
  final String code;
  final String name;
  final String logo;
  final String address;
  final String phone;
  final int? mobileAcademicYearId;
  final String? mobileAcademicYearTitle;

  SchoolModel({
    required this.id,
    required this.code,
    required this.name,
    required this.logo,
    required this.address,
    required this.phone,
    this.mobileAcademicYearId,
    this.mobileAcademicYearTitle,
  });

  factory SchoolModel.fromJson(Map<String, dynamic> json, {String? code}) {
    return SchoolModel(
      id: json['school_id'] ?? 0,
      code: code ?? json['code'] ?? '',
      name: json['school_name'] ?? '',
      logo: json['school_logo'] ?? '',
      address: json['school_address'] ?? '',
      phone: json['school_phone'] ?? '',
      mobileAcademicYearId: json['mobile_academic_year_id'],
      mobileAcademicYearTitle: json['mobile_academic_year_title'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'school_id': id,
      'code': code,
      'school_name': name,
      'school_logo': logo,
      'school_address': address,
      'school_phone': phone,
      'mobile_academic_year_id': mobileAcademicYearId,
      'mobile_academic_year_title': mobileAcademicYearTitle,
    };
  }
}
