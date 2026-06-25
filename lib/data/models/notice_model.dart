class NoticeModel {
  final int id;
  final String title;
  final String description;
  final String noticeDate; // YYYY-MM-DD
  final String targetType; // Entire School, Class
  final String? attachment;
  final CreatorModel? creator;

  NoticeModel({
    required this.id,
    required this.title,
    required this.description,
    required this.noticeDate,
    required this.targetType,
    this.attachment,
    this.creator,
  });

  factory NoticeModel.fromJson(Map<String, dynamic> json) {
    return NoticeModel(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      description: json['description'] ?? '',
      noticeDate: json['notice_date'] ?? '',
      targetType: json['target_type'] ?? 'Entire School',
      attachment: json['attachment'],
      creator: json['creator'] != null ? CreatorModel.fromJson(json['creator']) : null,
    );
  }
}

class CreatorModel {
  final int id;
  final String name;

  CreatorModel({required this.id, required this.name});

  factory CreatorModel.fromJson(Map<String, dynamic> json) {
    return CreatorModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
    );
  }
}
