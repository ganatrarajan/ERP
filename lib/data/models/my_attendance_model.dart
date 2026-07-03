class MyAttendanceResponse {
  final List<MyAttendanceRecord> records;
  final MyAttendanceStats stats;

  MyAttendanceResponse({required this.records, required this.stats});

  factory MyAttendanceResponse.fromJson(Map<String, dynamic> json) {
    final list = json['attendances'] as List? ?? [];
    final records = list.map((e) => MyAttendanceRecord.fromJson(e)).toList();
    
    final statsJson = Map<String, dynamic>.from(json['stats'] ?? {});

    return MyAttendanceResponse(
      records: records,
      stats: MyAttendanceStats.fromJson(statsJson),
    );
  }
}

class MyAttendanceRecord {
  final int id;
  final String date; // YYYY-MM-DD
  final String status; // Present, Absent, Late, Half Day, Leave
  final String remarks;

  MyAttendanceRecord({
    required this.id,
    required this.date,
    required this.status,
    required this.remarks,
  });

  factory MyAttendanceRecord.fromJson(Map<String, dynamic> json) {
    return MyAttendanceRecord(
      id: json['id'] ?? 0,
      date: json['attendance_date'] ?? '',
      status: json['status'] ?? 'Absent',
      remarks: json['remarks'] ?? '',
    );
  }
}

class MyAttendanceStats {
  final int present;
  final int absent;
  final int late;
  final int halfDay;
  final int leave;
  final int total;
  final double rate;

  MyAttendanceStats({
    required this.present,
    required this.absent,
    required this.late,
    required this.halfDay,
    required this.leave,
    required this.total,
    required this.rate,
  });

  factory MyAttendanceStats.fromJson(Map<String, dynamic> json) {
    return MyAttendanceStats(
      present: json['Present'] ?? 0,
      absent: json['Absent'] ?? 0,
      late: json['Late'] ?? 0,
      halfDay: json['Half Day'] ?? 0,
      leave: json['Leave'] ?? 0,
      total: json['total'] ?? 0,
      rate: (json['attendance_rate'] as num?)?.toDouble() ?? 0.0,
    );
  }
}
