import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/models/teacher.dart';
import '../data/repositories/profile_repository.dart';
import 'auth_provider.dart';

class ProfileState {
  final bool isLoading;
  final bool isSaving;
  final TeacherModel? profile;
  final List<TeacherDocumentModel> documents;
  final String? errorMessage;

  ProfileState({
    this.isLoading = false,
    this.isSaving = false,
    this.profile,
    this.documents = const [],
    this.errorMessage,
  });

  ProfileState copyWith({
    bool? isLoading,
    bool? isSaving,
    TeacherModel? profile,
    List<TeacherDocumentModel>? documents,
    String? errorMessage,
  }) {
    return ProfileState(
      isLoading: isLoading ?? this.isLoading,
      isSaving: isSaving ?? this.isSaving,
      profile: profile ?? this.profile,
      documents: documents ?? this.documents,
      errorMessage: errorMessage,
    );
  }
}

class ProfileNotifier extends StateNotifier<ProfileState> {
  final ProfileRepository _repository = ProfileRepository();
  final Ref _ref;

  ProfileNotifier(this._ref) : super(ProfileState());

  Future<void> fetchProfile() async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final profile = await _repository.getProfile();
      
      // Update global auth provider cached teacher profile
      _ref.read(authProvider.notifier).updateTeacherProfile(profile);

      state = state.copyWith(profile: profile, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<void> fetchDocuments() async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final docs = await _repository.getDocuments();
      state = state.copyWith(documents: docs, isLoading: false);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
    }
  }

  Future<bool> updateProfile({
    String? emergencyContactName,
    String? emergencyContactMobile,
    String? address,
    String? mobile,
  }) async {
    state = state.copyWith(isSaving: true, errorMessage: null);
    try {
      final updated = await _repository.updateProfile(
        emergencyContactName: emergencyContactName,
        emergencyContactMobile: emergencyContactMobile,
        address: address,
        mobile: mobile,
      );

      _ref.read(authProvider.notifier).updateTeacherProfile(updated);
      state = state.copyWith(profile: updated, isSaving: false);
      return true;
    } catch (e) {
      state = state.copyWith(
        isSaving: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }

  Future<bool> changePassword({
    required String oldPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    state = state.copyWith(isSaving: true, errorMessage: null);
    try {
      final success = await _repository.changePassword(
        oldPassword: oldPassword,
        newPassword: newPassword,
        confirmPassword: confirmPassword,
      );
      state = state.copyWith(isSaving: false);
      return success;
    } catch (e) {
      state = state.copyWith(
        isSaving: false,
        errorMessage: e.toString().replaceAll('Exception: ', ''),
      );
      return false;
    }
  }
}

final profileProvider = StateNotifierProvider<ProfileNotifier, ProfileState>((ref) {
  return ProfileNotifier(ref);
});
