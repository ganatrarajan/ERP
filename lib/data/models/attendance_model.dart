class AttendanceResponse {
  final List<AttendanceRecord> records;
  final AttendanceStats stats;

  AttendanceResponse({required this.records, required this.stats});

  factory AttendanceResponse.fromJson(Map<String, dynamic> json) {
    final list = json['attendances'] as List? ?? [];
    final records = list.map((e) => AttendanceRecord.fromJson(e)).toList();
    
    // Fallback logic to count holidays since the API stats might not include it
    int holidayCount = records.where((r) => r.status.toLowerCase() == 'h' || r.status.toLowerCase() == 'holiday').length;
    
    final statsJson = Map<String, dynamic>.from(json['stats'] ?? {});
    if (!statsJson.containsKey('Holiday')) {
      statsJson['Holiday'] = holidayCount;
    }

    return AttendanceResponse(
      records: records,
      stats: AttendanceStats.fromJson(statsJson),
    );
  }
}

class AttendanceRecord {
  final int id;
  final String date; // YYYY-MM-DD
  final String status; // Present, Absent, Late, Half Day, Leave
  final String remarks;

  AttendanceRecord({
    required this.id,
    required this.date,
    required this.status,
    required this.remarks,
  });

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) {
    return AttendanceRecord(
      id: json['id'] ?? 0,
      date: json['attendance_date'] ?? '',
      status: json['status'] ?? 'Absent',
      remarks: json['remarks'] ?? '',
    );
  }
}

class AttendanceStats {
  final int present;
  final int absent;
  final int late;
  final int halfDay;
  final int leave;
  final int holiday;
  final int total;
  final double rate;

  AttendanceStats({
    required this.present,
    required this.absent,
    required this.late,
    required this.halfDay,
    required this.leave,
    required this.holiday,
    required this.total,
    required this.rate,
  });

  factory AttendanceStats.fromJson(Map<String, dynamic> json) {
    return AttendanceStats(
      present: json['Present'] ?? 0,
      absent: json['Absent'] ?? 0,
      late: json['Late'] ?? 0,
      halfDay: json['Half Day'] ?? 0,
      leave: json['Leave'] ?? 0,
      holiday: json['Holiday'] ?? 0,
      total: json['total'] ?? 0,
      rate: (json['attendance_rate'] as num?)?.toDouble() ?? 0.0,
    );
  }
}
