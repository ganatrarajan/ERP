import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'dart:convert';
import 'dart:io';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/profile_provider.dart';
import '../../../data/repositories/profile_repository.dart';
import '../../../core/theme/theme_provider.dart';
import '../../../core/services/download_service.dart';
import '../../../core/constants/api_endpoints.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import 'edit_profile_screen.dart';
import 'change_password_screen.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  Map<String, dynamic> _downloadedDocsInfo = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(profileProvider.notifier).fetchProfile();
      ref.read(profileProvider.notifier).fetchDocuments().then((_) {
        _syncAndCleanObsoleteFiles();
      });
    });
    _loadDownloadedDocsInfo();
  }

  Future<void> _loadDownloadedDocsInfo() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final dataStr = prefs.getString('downloaded_teacher_docs');
      if (dataStr != null) {
        setState(() {
          _downloadedDocsInfo = jsonDecode(dataStr);
        });
      }
    } catch (_) {}
  }

  Future<void> _syncAndCleanObsoleteFiles() async {
    final documents = ref.read(profileProvider).documents;
    if (documents.isEmpty) return;

    try {
      final prefs = await SharedPreferences.getInstance();
      final dataStr = prefs.getString('downloaded_teacher_docs');
      if (dataStr == null) return;

      Map<String, dynamic> infoMap = jsonDecode(dataStr);
      bool changed = false;

      final keys = List<String>.from(infoMap.keys);
      for (final key in keys) {
        final docId = int.tryParse(key);
        if (docId == null) continue;

        final onlineDoc = documents.firstWhere(
          (d) => d.id == docId,
          orElse: () => TeacherDocumentModel(id: -1, title: '', filePath: ''),
        );

        final docInfo = Map<String, dynamic>.from(infoMap[key]);
        final localPath = docInfo['localPath'] as String?;
        final onlinePath = docInfo['onlinePath'] as String?;

        if (onlineDoc.id == -1) {
          if (localPath != null) {
            final file = File(localPath);
            if (file.existsSync()) {
              file.deleteSync();
            }
          }
          infoMap.remove(key);
          changed = true;
        } else if (onlineDoc.filePath != onlinePath) {
          if (localPath != null) {
            final file = File(localPath);
            if (file.existsSync()) {
              file.deleteSync();
            }
          }
          infoMap.remove(key);
          changed = true;
        } else {
          if (localPath != null && !File(localPath).existsSync()) {
            infoMap.remove(key);
            changed = true;
          }
        }
      }

      if (changed) {
        await prefs.setString('downloaded_teacher_docs', jsonEncode(infoMap));
        setState(() {
          _downloadedDocsInfo = infoMap;
        });
      }
    } catch (_) {}
  }

  void _downloadDocument(TeacherDocumentModel doc) async {
    var ext = doc.filePath.contains('.') ? doc.filePath.split('.').last.toLowerCase() : 'pdf';
    if (ext == 'jfif') ext = 'jpg';
    final filename = '${doc.title.replaceAll(' ', '_')}.$ext';
    final url = ApiEndpoints.resolveAttachmentUrl(doc.filePath);
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text("Downloading ${doc.title}...")),
    );

    try {
      final localPath = await DownloadService().downloadFile(
        url: url,
        filename: filename,
      );

      if (mounted && localPath != null) {
        final prefs = await SharedPreferences.getInstance();
        final updatedInfo = Map<String, dynamic>.from(_downloadedDocsInfo);
        updatedInfo[doc.id.toString()] = {
          'localPath': localPath,
          'onlinePath': doc.filePath,
          'filename': filename,
        };
        await prefs.setString('downloaded_teacher_docs', jsonEncode(updatedInfo));

        setState(() {
          _downloadedDocsInfo = updatedInfo;
        });

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text("Downloaded: ${doc.title}"),
            backgroundColor: Colors.green,
            action: SnackBarAction(
              label: "Open",
              textColor: Colors.white,
              onPressed: () {
                DownloadService().openDownloadedFile(filename);
              },
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text("Download failed: ${e.toString()}"), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _viewDocument(TeacherDocumentModel doc) {
    final url = ApiEndpoints.resolveAttachmentUrl(doc.filePath);
    var ext = doc.filePath.contains('.') ? doc.filePath.split('.').last.toLowerCase() : 'pdf';
    if (ext == 'jfif') ext = 'jpg';
    final isImage = ['jpg', 'jpeg', 'png', 'gif', 'jfif', 'webp'].contains(ext);

    final isDownloaded = _downloadedDocsInfo.containsKey(doc.id.toString());
    final docInfo = isDownloaded ? _downloadedDocsInfo[doc.id.toString()] : null;
    final filename = docInfo != null ? docInfo['filename'] as String? : null;
    final localPath = docInfo != null ? docInfo['localPath'] as String? : null;

    if (isImage) {
      showDialog(
        context: context,
        builder: (context) => Dialog(
          insetPadding: const EdgeInsets.all(16),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              AppBar(
                title: Text(doc.title),
                leading: IconButton(
                  icon: const Icon(Icons.close_rounded),
                  onPressed: () => Navigator.pop(context),
                ),
                backgroundColor: Colors.transparent,
                elevation: 0,
              ),
              Container(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(context).size.height * 0.6,
                ),
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: InteractiveViewer(
                    minScale: 0.5,
                    maxScale: 4.0,
                    child: isDownloaded && localPath != null && File(localPath).existsSync()
                        ? Image.file(File(localPath), fit: BoxFit.contain)
                        : Image.network(
                            url,
                            fit: BoxFit.contain,
                            loadingBuilder: (context, child, loadingProgress) {
                              if (loadingProgress == null) return child;
                              return const Center(
                                child: Padding(
                                  padding: EdgeInsets.all(32.0),
                                  child: CircularProgressIndicator(),
                                ),
                              );
                            },
                            errorBuilder: (context, error, stackTrace) {
                              return const Center(
                                child: Padding(
                                  padding: EdgeInsets.all(32.0),
                                  child: Column(
                                    children: [
                                      Icon(Icons.broken_image_rounded, size: 48, color: Colors.grey),
                                      SizedBox(height: 8),
                                      Text("Failed to load image online", style: TextStyle(color: Colors.grey)),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Padding(
                padding: const EdgeInsets.only(left: 16, right: 16, bottom: 16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    if (isDownloaded && filename != null)
                      ElevatedButton.icon(
                        icon: const Icon(Icons.open_in_new_rounded),
                        label: const Text("Open Externally"),
                        onPressed: () {
                          Navigator.pop(context);
                          DownloadService().openDownloadedFile(filename);
                        },
                      )
                    else
                      ElevatedButton.icon(
                        icon: const Icon(Icons.download_rounded),
                        label: const Text("Download to Device"),
                        onPressed: () {
                          Navigator.pop(context);
                          _downloadDocument(doc);
                        },
                      ),
                    if (isDownloaded)
                      OutlinedButton.icon(
                        icon: const Icon(Icons.sync_rounded),
                        label: const Text("Sync / Re-download"),
                        onPressed: () {
                          Navigator.pop(context);
                          _downloadDocument(doc);
                        },
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    } else {
      if (isDownloaded && filename != null) {
        DownloadService().openDownloadedFile(filename);
      } else {
        _downloadDocument(doc);
      }
    }
  }

  void _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Log Out"),
        content: const Text("Are you sure you want to log out of your session?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text("Cancel"),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text("Logout"),
          ),
        ],
      ),
    );

    if (confirm == true && mounted) {
      await ref.read(authProvider.notifier).logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final profileState = ref.watch(profileProvider);
    final themeMode = ref.watch(themeProvider);
    
    final teacher = profileState.profile ?? ref.watch(authProvider).teacher;

    return Scaffold(
      appBar: AppBar(
        title: const Text("My Profile"),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout_rounded),
            tooltip: "Logout",
            onPressed: _handleLogout,
          ),
        ],
      ),
      body: profileState.isLoading && teacher == null
          ? const LoadingView(message: "Loading your profile...")
          : profileState.errorMessage != null && teacher == null
              ? ErrorView(
                  message: profileState.errorMessage!,
                  onRetry: () {
                    ref.read(profileProvider.notifier).fetchProfile();
                    ref.read(profileProvider.notifier).fetchDocuments();
                  },
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    children: [
                      // Header Card
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(20.0),
                          child: Column(
                            children: [
                              CircleAvatar(
                                radius: 46,
                                backgroundColor: theme.colorScheme.primary.withOpacity(0.1),
                                child: Icon(Icons.person_rounded, size: 54, color: theme.colorScheme.primary),
                              ),
                              const SizedBox(height: 16),
                              Text(
                                teacher?.name ?? 'Jane Teacher',
                                style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 4),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: theme.colorScheme.primary.withOpacity(0.08),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  teacher?.employeeId ?? 'EMP-2026-001',
                                  style: TextStyle(
                                    color: theme.colorScheme.primary,
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Contact Details Card
                      _buildSectionHeader("Contact details", context),
                      Card(
                        child: Column(
                          children: [
                            ListTile(
                              leading: const Icon(Icons.email_outlined),
                              title: const Text("Email Address"),
                              subtitle: Text(teacher?.email ?? 'teacher@school.com'),
                            ),
                            const Divider(height: 1),
                            ListTile(
                              leading: const Icon(Icons.phone_android_outlined),
                              title: const Text("Mobile Number"),
                              subtitle: Text(teacher?.mobile ?? 'N/A'),
                            ),
                            const Divider(height: 1),
                            ListTile(
                              leading: const Icon(Icons.home_outlined),
                              title: const Text("Home Address"),
                              subtitle: Text(teacher?.address ?? 'No address registered'),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Emergency details Card
                      _buildSectionHeader("Emergency contacts", context),
                      Card(
                        child: Column(
                          children: [
                            ListTile(
                              leading: const Icon(Icons.contact_phone_outlined),
                              title: const Text("Contact Person"),
                              subtitle: Text(teacher?.emergencyContactName ?? 'N/A'),
                            ),
                            const Divider(height: 1),
                            ListTile(
                              leading: const Icon(Icons.phone_outlined),
                              title: const Text("Contact Mobile"),
                              subtitle: Text(teacher?.emergencyContactMobile ?? 'N/A'),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Credentials & Documents Card
                      if (profileState.documents.isNotEmpty) ...[
                        _buildSectionHeader("My credentials documents", context),
                        Card(
                          child: ListView.separated(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: profileState.documents.length,
                            separatorBuilder: (_, __) => const Divider(height: 1),
                            itemBuilder: (context, index) {
                              final doc = profileState.documents[index];
                              final isDownloaded = _downloadedDocsInfo.containsKey(doc.id.toString());
                              final docInfo = isDownloaded ? _downloadedDocsInfo[doc.id.toString()] : null;
                              final filename = docInfo != null ? docInfo['filename'] as String? : null;

                              return ListTile(
                                leading: Icon(
                                  Icons.file_present_rounded,
                                  color: isDownloaded ? Colors.green : Colors.blue,
                                ),
                                title: Text(doc.title),
                                subtitle: Text(
                                  isDownloaded
                                      ? "Downloaded locally (Tap to view)"
                                      : "Tap to download credentials",
                                  style: TextStyle(
                                    color: isDownloaded ? Colors.green : Colors.grey,
                                  ),
                                ),
                                trailing: isDownloaded
                                    ? Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          IconButton(
                                            icon: const Icon(Icons.open_in_new_rounded, color: Colors.green),
                                            tooltip: "Open",
                                            onPressed: () => _viewDocument(doc),
                                          ),
                                          IconButton(
                                            icon: const Icon(Icons.sync_rounded, color: Colors.blue),
                                            tooltip: "Re-download",
                                            onPressed: () => _downloadDocument(doc),
                                          ),
                                        ],
                                      )
                                    : const Icon(Icons.download_rounded),
                                onTap: () => _viewDocument(doc),
                              );
                            },
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // Settings & Theme
                      _buildSectionHeader("Preferences & security", context),
                      Card(
                        child: Column(
                          children: [
                            ListTile(
                              leading: const Icon(Icons.dark_mode_outlined),
                              title: const Text("App Color Theme"),
                              subtitle: Text(
                                themeMode == ThemeMode.system
                                    ? "System Default"
                                    : themeMode == ThemeMode.dark
                                        ? "Dark Mode"
                                        : "Light Mode",
                              ),
                              trailing: const Icon(Icons.chevron_right_rounded),
                              onTap: () => _showThemeDialog(context),
                            ),
                            const Divider(height: 1),
                            ListTile(
                              leading: const Icon(Icons.edit_note_rounded),
                              title: const Text("Edit Profile Details"),
                              subtitle: const Text("Update address and contact numbers"),
                              trailing: const Icon(Icons.chevron_right_rounded),
                              onTap: () {
                                if (teacher != null) {
                                  Navigator.of(context).push(
                                    MaterialPageRoute(builder: (_) => EditProfileScreen(teacher: teacher)),
                                  );
                                }
                              },
                            ),
                            const Divider(height: 1),
                            ListTile(
                              leading: const Icon(Icons.lock_reset_rounded),
                              title: const Text("Change Password"),
                              subtitle: const Text("Update portal login credentials"),
                              trailing: const Icon(Icons.chevron_right_rounded),
                              onTap: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => const ChangePasswordScreen()),
                                );
                              },
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
    );
  }

  Widget _buildSectionHeader(String title, BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.only(left: 8, bottom: 8),
      alignment: Alignment.centerLeft,
      child: Text(
        title.toUpperCase(),
        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              fontWeight: FontWeight.bold,
              fontSize: 11,
              letterSpacing: 0.5,
              color: Theme.of(context).colorScheme.primary,
            ),
      ),
    );
  }

  void _showThemeDialog(BuildContext context) {
    final notifier = ref.read(themeProvider.notifier);
    final current = ref.read(themeProvider);

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Select Theme"),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<ThemeMode>(
              title: const Text("System Default"),
              value: ThemeMode.system,
              groupValue: current,
              onChanged: (val) {
                if (val != null) notifier.setThemeMode(val);
                Navigator.of(context).pop();
              },
            ),
            RadioListTile<ThemeMode>(
              title: const Text("Light Mode"),
              value: ThemeMode.light,
              groupValue: current,
              onChanged: (val) {
                if (val != null) notifier.setThemeMode(val);
                Navigator.of(context).pop();
              },
            ),
            RadioListTile<ThemeMode>(
              title: const Text("Dark Mode"),
              value: ThemeMode.dark,
              groupValue: current,
              onChanged: (val) {
                if (val != null) notifier.setThemeMode(val);
                Navigator.of(context).pop();
              },
            ),
          ],
        ),
      ),
    );
  }
}
