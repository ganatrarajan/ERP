import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/auth_provider.dart';
import '../../../core/services/notification_service.dart';
import '../change_password_screen.dart';
import '../onboarding/school_code_screen.dart';
import '../login_screen.dart';
import '../../../providers/onboarding_provider.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AuthProvider>().fetchProfile();
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final authProv = context.watch<AuthProvider>();
    final student = authProv.student;

    if (student == null) {
      return const Scaffold(
        body: Center(child: Text("No Profile Data Available")),
      );
    }

    final record = student.academicRecord;
    final className = record?.classInfo?.name ?? 'N/A';
    final sectionName = record?.sectionInfo?.name ?? 'N/A';
    final rollNo = record?.rollNo ?? 'N/A';

    return Scaffold(
      appBar: AppBar(
        title: const Text("My Profile"),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
          // 1. Profile Avatar Header
          Center(
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: theme.colorScheme.primary, width: 2),
                  ),
                  child: CircleAvatar(
                    radius: 48,
                    backgroundColor: theme.colorScheme.primary.withOpacity(0.1),
                    child: Text(
                      student.firstName.isNotEmpty ? student.firstName[0] : 'S',
                      style: TextStyle(
                        fontSize: 36,
                        fontWeight: FontWeight.bold,
                        color: theme.colorScheme.primary,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  student.fullName,
                  style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Text(
                  "Admission Number: ${student.admissionNo}",
                  style: theme.textTheme.bodyMedium,
                ),
              ],
            ),
          ),
          const SizedBox(height: 32),

          // 2. Academic Information
          Text(
            "Academic Details",
            style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Card(
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                children: [
                  _buildProfileRow("School Name", student.schoolName ?? 'N/A', theme),
                  const Divider(),
                  _buildProfileRow("Academic Year", student.mobileAcademicYear ?? 'N/A', theme),
                  const Divider(),
                  _buildProfileRow("Class", className, theme),
                  const Divider(),
                  _buildProfileRow("Section", sectionName, theme),
                  const Divider(),
                  _buildProfileRow("Roll Number", rollNo, theme),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // 3. Parent Information
          Text(
            "Parent Details",
            style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Card(
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                children: [
                  if (student.parent?.fatherName != null && student.parent!.fatherName.isNotEmpty) ...[
                    _buildProfileRow("Father's Name", student.parent!.fatherName, theme),
                    if (student.parent?.fatherMobile != null && student.parent!.fatherMobile!.isNotEmpty) ...[
                      const Divider(),
                      _buildProfileRow("Father's Mobile", student.parent!.fatherMobile!, theme),
                    ],
                    if (student.parent?.fatherEmail != null && student.parent!.fatherEmail!.isNotEmpty) ...[
                      const Divider(),
                      _buildProfileRow("Father's Email", student.parent!.fatherEmail!, theme),
                    ],
                  ],
                  if (student.parent?.motherName != null && student.parent!.motherName!.isNotEmpty) ...[
                    const Divider(),
                    _buildProfileRow("Mother's Name", student.parent!.motherName!, theme),
                    if (student.parent?.motherMobile != null && student.parent!.motherMobile!.isNotEmpty) ...[
                      const Divider(),
                      _buildProfileRow("Mother's Mobile", student.parent!.motherMobile!, theme),
                    ],
                    if (student.parent?.motherEmail != null && student.parent!.motherEmail!.isNotEmpty) ...[
                      const Divider(),
                      _buildProfileRow("Mother's Email", student.parent!.motherEmail!, theme),
                    ],
                  ],
                  if (student.parent?.guardianName != null && student.parent!.guardianName!.isNotEmpty) ...[
                    const Divider(),
                    _buildProfileRow("Guardian Name", student.parent!.guardianName!, theme),
                    if (student.parent?.guardianMobile != null && student.parent!.guardianMobile!.isNotEmpty) ...[
                      const Divider(),
                      _buildProfileRow("Guardian Mobile", student.parent!.guardianMobile!, theme),
                    ],
                  ],
                  if (student.parent == null)
                    _buildProfileRow("Parent Info", "N/A", theme),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),

          // 4. Operations
          Text(
            "Account Operations",
            style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Card(
            margin: EdgeInsets.zero,
            child: Column(
              children: [
                ListTile(
                  leading: Icon(Icons.lock_reset_rounded, color: theme.colorScheme.primary),
                  title: const Text("Change Password"),
                  subtitle: const Text("Reset security credential"),
                  trailing: const Icon(Icons.keyboard_arrow_right_rounded),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => ChangePasswordScreen(
                          studentId: student.id,
                        ),
                      ),
                    );
                  },
                ),

                const Divider(height: 1),
                ListTile(
                  leading: Icon(Icons.notifications_active_rounded, color: theme.colorScheme.primary),
                  title: const Text("Push Notifications"),
                  subtitle: const Text("Manage notification permissions"),
                  trailing: const Icon(Icons.keyboard_arrow_right_rounded),
                  onTap: () async {
                    final granted = await NotificationService.instance.requestNotificationPermission();
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(granted 
                            ? "Notifications enabled successfully!" 
                            : "Notification permission was denied. Please enable it in system settings."),
                          backgroundColor: granted ? Colors.green : Colors.red,
                          behavior: SnackBarBehavior.floating,
                        ),
                      );
                    }
                  },
                ),

                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.swap_horiz_rounded, color: Colors.orange),
                  title: const Text("Change School", style: TextStyle(color: Colors.orange)),
                  subtitle: const Text("Switch to a different school"),
                  onTap: () {
                    showDialog(
                      context: context,
                      builder: (dialogContext) => AlertDialog(
                        title: const Text("Change School"),
                        content: const Text("This will clear your current session and require you to enter a new school code. Continue?"),
                        actions: [
                          TextButton(
                            child: const Text("Cancel"),
                            onPressed: () => Navigator.pop(dialogContext),
                          ),
                          TextButton(
                            child: const Text("Confirm", style: TextStyle(color: Colors.orange)),
                            onPressed: () async {
                              Navigator.pop(dialogContext); // Pop the dialog
                              // Clear both auth and onboarding
                              await context.read<AuthProvider>().logout();
                              if (!context.mounted) return;
                              await context.read<OnboardingProvider>().clearOnboarding();
                              if (!context.mounted) return;
                              Navigator.of(context, rootNavigator: true).pushAndRemoveUntil(
                                MaterialPageRoute(builder: (_) => const SchoolCodeScreen()),
                                (route) => false,
                              );
                            },
                          ),
                        ],
                      ),
                    );
                  },
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.exit_to_app_rounded, color: Colors.red),
                  title: const Text("Log Out", style: TextStyle(color: Colors.red)),
                  subtitle: const Text("Clear session from this device"),
                  onTap: () {
                    showDialog(
                      context: context,
                      builder: (dialogContext) => AlertDialog(
                        title: const Text("Logout"),
                        content: const Text("Are you sure you want to log out of the student application?"),
                        actions: [
                          TextButton(
                            child: const Text("Cancel"),
                            onPressed: () => Navigator.pop(dialogContext),
                          ),
                          TextButton(
                            child: const Text("Logout", style: TextStyle(color: Colors.red)),
                            onPressed: () async {
                              Navigator.pop(dialogContext); // Pop dialog
                              await context.read<AuthProvider>().logout();
                              
                              if (!context.mounted) return; // Check outer screen context
                              Navigator.of(context, rootNavigator: true).pushAndRemoveUntil(
                                MaterialPageRoute(builder: (_) => const LoginScreen()),
                                (route) => false,
                              );
                            },
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),),
    );
  }

  Widget _buildProfileRow(String label, String value, ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.textTheme.bodyMedium?.color?.withOpacity(0.6),
            ),
          ),
          Text(
            value,
            style: theme.textTheme.bodyLarge?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}
