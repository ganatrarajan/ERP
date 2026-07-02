import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/repositories/leave_repository.dart';

class LeaveRequestModel {
  final String id;
  final String leaveType; // "Casual Leave", "Sick Leave", "Earned Leave"
  final String startDate; // YYYY-MM-DD
  final String endDate; // YYYY-MM-DD
  final String reason;
  final String status; // "Pending", "Approved", "Rejected"
  final String requestedDate; // YYYY-MM-DD

  LeaveRequestModel({
    required this.id,
    required this.leaveType,
    required this.startDate,
    required this.endDate,
    required this.reason,
    required this.status,
    required this.requestedDate,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'leave_type': leaveType,
        'start_date': startDate,
        'end_date': endDate,
        'reason': reason,
        'status': status,
        'requested_date': requestedDate,
      };

  factory LeaveRequestModel.fromJson(Map<String, dynamic> json) => LeaveRequestModel(
        id: (json['id'] ?? '').toString(),
        leaveType: json['leave_type'] ?? '',
        startDate: json['start_date'] ?? '',
        endDate: json['end_date'] ?? '',
        reason: json['reason'] ?? '',
        status: json['status'] ?? 'Pending',
        requestedDate: json['requested_date'] ?? '',
      );
}

class LeaveState {
  final List<LeaveRequestModel> requests;
  final bool isLoading;
  final String? errorMessage;

  LeaveState({
    this.requests = const [],
    this.isLoading = false,
    this.errorMessage,
  });

  LeaveState copyWith({
    List<LeaveRequestModel>? requests,
    bool? isLoading,
    String? errorMessage,
  }) {
    return LeaveState(
      requests: requests ?? this.requests,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: errorMessage,
    );
  }
}

class LeaveNotifier extends StateNotifier<LeaveState> {
  final LeaveRepository _leaveRepository = LeaveRepository();

  LeaveNotifier() : super(LeaveState()) {
    loadLeaves();
  }

  Future<void> loadLeaves() async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final list = await _leaveRepository.getLeaves();
      // Sort so most recent is first
      list.sort((a, b) => b.requestedDate.compareTo(a.requestedDate));
      state = state.copyWith(requests: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<bool> applyLeave({
    required String leaveType,
    required String startDate,
    required String endDate,
    required String reason,
  }) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final newRequest = await _leaveRepository.applyLeave(
        leaveType: leaveType,
        startDate: startDate,
        endDate: endDate,
        reason: reason,
      );
      state = state.copyWith(
        requests: [newRequest, ...state.requests],
        isLoading: false,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }
}

final leaveProvider = StateNotifierProvider<LeaveNotifier, LeaveState>((ref) {
  return LeaveNotifier();
});
