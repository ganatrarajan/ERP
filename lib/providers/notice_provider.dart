import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/notice.dart';
import '../data/repositories/notice_repository.dart';

class NoticeState {
  final bool isLoading;
  final List<NoticeModel> notices;
  final NoticeModel? activeDetails;
  final int currentPage;
  final bool hasNextPage;
  final String? errorMessage;

  NoticeState({
    this.isLoading = false,
    this.notices = const [],
    this.activeDetails,
    this.currentPage = 1,
    this.hasNextPage = false,
    this.errorMessage,
  });

  NoticeState copyWith({
    bool? isLoading,
    List<NoticeModel>? notices,
    NoticeModel? activeDetails,
    int? currentPage,
    bool? hasNextPage,
    String? errorMessage,
  }) {
    return NoticeState(
      isLoading: isLoading ?? this.isLoading,
      notices: notices ?? this.notices,
      activeDetails: activeDetails ?? this.activeDetails,
      currentPage: currentPage ?? this.currentPage,
      hasNextPage: hasNextPage ?? this.hasNextPage,
      errorMessage: errorMessage,
    );
  }
}

class NoticeNotifier extends StateNotifier<NoticeState> {
  final NoticeRepository _repository = NoticeRepository();

  NoticeNotifier() : super(NoticeState());

  Future<void> fetchNotices({bool refresh = false}) async {
    if (state.isLoading) return;

    final pageToLoad = refresh ? 1 : state.currentPage;
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      final res = await _repository.getNotices(page: pageToLoad);
      final dynamic rawData = res['data'];
      List listData = [];
      String? nextUrl;

      if (rawData is List) {
        listData = rawData;
        nextUrl = res['next_page_url'] as String?;
      } else if (rawData is Map) {
        listData = rawData['data'] as List? ?? [];
        nextUrl = rawData['next_page_url'] as String?;
      }

      final list = listData.map((e) => NoticeModel.fromJson(e)).toList();

      state = state.copyWith(
        notices: refresh ? list : [...state.notices, ...list],
        currentPage: pageToLoad + 1,
        hasNextPage: nextUrl != null,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<void> fetchDetails(int id) async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final details = await _repository.getNoticeDetails(id);
      state = state.copyWith(activeDetails: details, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }
}

final noticeProvider = StateNotifierProvider<NoticeNotifier, NoticeState>((ref) {
  return NoticeNotifier();
});
