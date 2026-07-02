import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/profile_provider.dart';
import '../../../data/models/teacher.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_text_field.dart';

class EditProfileScreen extends ConsumerStatefulWidget {
  final TeacherModel teacher;
  const EditProfileScreen({super.key, required this.teacher});

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _phoneController;
  late TextEditingController _addressController;
  late TextEditingController _emerNameController;
  late TextEditingController _emerPhoneController;

  @override
  void initState() {
    super.initState();
    _phoneController = TextEditingController(text: widget.teacher.mobile);
    _addressController = TextEditingController(text: widget.teacher.address);
    _emerNameController = TextEditingController(text: widget.teacher.emergencyContactName);
    _emerPhoneController = TextEditingController(text: widget.teacher.emergencyContactMobile);
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _addressController.dispose();
    _emerNameController.dispose();
    _emerPhoneController.dispose();
    super.dispose();
  }

  void _handleSave() async {
    if (!_formKey.currentState!.validate()) return;

    final success = await ref.read(profileProvider.notifier).updateProfile(
          mobile: _phoneController.text.trim(),
          address: _addressController.text.trim(),
          emergencyContactName: _emerNameController.text.trim(),
          emergencyContactMobile: _emerPhoneController.text.trim(),
        );

    if (mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Profile details updated successfully"), backgroundColor: Colors.green),
        );
        Navigator.of(context).pop();
      } else {
        final err = ref.read(profileProvider).errorMessage;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(err ?? "Failed to update profile details"), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(profileProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text("Edit Profile Info"),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              CustomTextField(
                controller: _phoneController,
                label: "Mobile Number",
                prefixIcon: Icons.phone_android_rounded,
                keyboardType: TextInputType.phone,
                validator: (val) {
                  if (val == null || val.trim().isEmpty) {
                    return "Phone number is required";
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              CustomTextField(
                controller: _addressController,
                label: "Home Address",
                prefixIcon: Icons.home_outlined,
                validator: (val) {
                  if (val == null || val.trim().isEmpty) {
                    return "Address is required";
                  }
                  return null;
                },
              ),
              const SizedBox(height: 24),

              Text(
                "EMERGENCY CONTACT INFO",
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.primary,
                      fontSize: 11,
                      letterSpacing: 0.5,
                    ),
              ),
              const SizedBox(height: 12),

              CustomTextField(
                controller: _emerNameController,
                label: "Contact Person Name",
                prefixIcon: Icons.person_outline_rounded,
                validator: (val) {
                  if (val == null || val.trim().isEmpty) {
                    return "Contact person name is required";
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              CustomTextField(
                controller: _emerPhoneController,
                label: "Contact Phone Number",
                prefixIcon: Icons.phone_rounded,
                keyboardType: TextInputType.phone,
                validator: (val) {
                  if (val == null || val.trim().isEmpty) {
                    return "Contact phone number is required";
                  }
                  return null;
                },
              ),
              const SizedBox(height: 40),

              CustomButton(
                text: "Save Details",
                isLoading: state.isSaving,
                onPressed: _handleSave,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
