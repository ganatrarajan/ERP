class AcademicYearModel {
  final int id;
  final String name;
  final bool isCurrent;

  AcademicYearModel({
    required this.id,
    required this.name,
    required this.isCurrent,
  });

  factory AcademicYearModel.fromJson(Map<String, dynamic> json) {
    return AcademicYearModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? json['title'] ?? '',
      isCurrent: (json['is_current'] == 1 || json['is_current'] == true),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'is_current': isCurrent ? 1 : 0,
    };
  }
}
