import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/homework.dart';
import '../data/repositories/homework_repository.dart';

class HomeworkState {
  final bool isLoading;
  final bool isSaving;
  final List<HomeworkModel> homeworks;
  final HomeworkModel? activeDetails;
  final int currentPage;
  final bool hasNextPage;
  final String? errorMessage;

  HomeworkState({
    this.isLoading = false,
    this.isSaving = false,
    this.homeworks = const [],
    this.activeDetails,
    this.currentPage = 1,
    this.hasNextPage = false,
    this.errorMessage,
  });

  HomeworkState copyWith({
    bool? isLoading,
    bool? isSaving,
    List<HomeworkModel>? homeworks,
    HomeworkModel? activeDetails,
    int? currentPage,
    bool? hasNextPage,
    String? errorMessage,
  }) {
    return HomeworkState(
      isLoading: isLoading ?? this.isLoading,
      isSaving: isSaving ?? this.isSaving,
      homeworks: homeworks ?? this.homeworks,
      activeDetails: activeDetails ?? this.activeDetails,
      currentPage: currentPage ?? this.currentPage,
      hasNextPage: hasNextPage ?? this.hasNextPage,
      errorMessage: errorMessage,
    );
  }
}

class HomeworkNotifier extends StateNotifier<HomeworkState> {
  final HomeworkRepository _repository = HomeworkRepository();

  HomeworkNotifier() : super(HomeworkState());

  Future<void> fetchHomeworks({int? classId, int? sectionId, bool refresh = false}) async {
    if (state.isLoading) return;
    
    final pageToLoad = refresh ? 1 : state.currentPage;
    state = state.copyWith(isLoading: true, errorMessage: null);

    try {
      final res = await _repository.getHomeworks(
        classId: classId,
        sectionId: sectionId,
        page: pageToLoad,
      );

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

      final list = listData.map((e) => HomeworkModel.fromJson(e)).toList();

      state = state.copyWith(
        homeworks: refresh ? list : [...state.homeworks, ...list],
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
      final details = await _repository.getHomeworkDetails(id);
      state = state.copyWith(activeDetails: details, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<bool> createHomework(HomeworkModel homework) async {
    state = state.copyWith(isSaving: true, errorMessage: null);
    try {
      final created = await _repository.createHomework(homework);
      state = state.copyWith(
        homeworks: [created, ...state.homeworks],
        isSaving: false,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isSaving: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }

  Future<bool> updateHomework(
    int id, {
    required String title,
    required String description,
    required String submissionDate,
    double? maxMarks,
  }) async {
    state = state.copyWith(isSaving: true, errorMessage: null);
    try {
      final updated = await _repository.updateHomework(
        id,
        title: title,
        description: description,
        submissionDate: submissionDate,
        maxMarks: maxMarks,
      );

      final updatedList = state.homeworks.map((h) {
        return h.id == id ? updated : h;
      }).toList();

      state = state.copyWith(
        homeworks: updatedList,
        activeDetails: updated,
        isSaving: false,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isSaving: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }

  Future<bool> deleteHomework(int id) async {
    state = state.copyWith(isSaving: true, errorMessage: null);
    try {
      final success = await _repository.deleteHomework(id);
      if (success) {
        final updatedList = state.homeworks.where((h) => h.id != id).toList();
        state = state.copyWith(
          homeworks: updatedList,
          isSaving: false,
        );
        return true;
      }
      state = state.copyWith(isSaving: false);
      return false;
    } catch (e) {
      state = state.copyWith(
        isSaving: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }
}

final homeworkProvider = StateNotifierProvider<HomeworkNotifier, HomeworkState>((ref) {
  return HomeworkNotifier();
});
