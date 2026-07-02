class TeacherModel {
  final int id;
  final int schoolId;
  final String? employeeId;
  final String name;
  final String email;
  final String? mobile;
  final String? status;
  final String? address;
  final String? emergencyContactName;
  final String? emergencyContactMobile;

  TeacherModel({
    required this.id,
    required this.schoolId,
    this.employeeId,
    required this.name,
    required this.email,
    this.mobile,
    this.status,
    this.address,
    this.emergencyContactName,
    this.emergencyContactMobile,
  });

  factory TeacherModel.fromJson(Map<String, dynamic> json) {
    return TeacherModel(
      id: json['id'] ?? 0,
      schoolId: json['school_id'] ?? 0,
      employeeId: json['employee_id'],
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      mobile: json['mobile'],
      status: json['status'],
      address: json['address'],
      emergencyContactName: json['emergency_contact_name'],
      emergencyContactMobile: json['emergency_contact_mobile'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'school_id': schoolId,
      'employee_id': employeeId,
      'name': name,
      'email': email,
      'mobile': mobile,
      'status': status,
      'address': address,
      'emergency_contact_name': emergencyContactName,
      'emergency_contact_mobile': emergencyContactMobile,
    };
  }

  TeacherModel copyWith({
    int? id,
    int? schoolId,
    String? employeeId,
    String? name,
    String? email,
    String? mobile,
    String? status,
    String? address,
    String? emergencyContactName,
    String? emergencyContactMobile,
  }) {
    return TeacherModel(
      id: id ?? this.id,
      schoolId: schoolId ?? this.schoolId,
      employeeId: employeeId ?? this.employeeId,
      name: name ?? this.name,
      email: email ?? this.email,
      mobile: mobile ?? this.mobile,
      status: status ?? this.status,
      address: address ?? this.address,
      emergencyContactName: emergencyContactName ?? this.emergencyContactName,
      emergencyContactMobile: emergencyContactMobile ?? this.emergencyContactMobile,
    );
  }
}
