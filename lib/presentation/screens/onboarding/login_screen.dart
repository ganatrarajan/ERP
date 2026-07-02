import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/auth_provider.dart';
import '../../../providers/onboarding_provider.dart';
import '../../../data/models/academic_year.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_text_field.dart';
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  AcademicYearModel? _selectedYear;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      try {
        final onboardingState = ref.read(onboardingProvider);
        if (onboardingState.academicYears.isEmpty) {
          final onboarding = ref.read(onboardingProvider.notifier);
          await onboarding.fetchAcademicYears();
        }
        if (!mounted) return;
        
        final currentYear = ref.read(onboardingProvider).academicYear;
        if (currentYear != null) {
          // Find matching year object in list to ensure same instance for dropdown mapping
          final matchingYear = ref.read(onboardingProvider).academicYears.firstWhere(
            (y) => y.id == currentYear.id,
            orElse: () => currentYear,
          );
          setState(() {
            _selectedYear = matchingYear;
          });
        }
      } catch (e) {
        debugPrint("Failed to load academic years: $e");
      }
    });
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedYear == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text("Please select an academic year"),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final onboardingState = ref.read(onboardingProvider);
    final authNotifier = ref.read(authProvider.notifier);

    final success = await authNotifier.login(
      schoolCode: onboardingState.school!.code,
      email: _emailController.text.trim(),
      password: _passwordController.text,
      academicYearId: _selectedYear!.id,
    );

    if (mounted) {
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("Logged in successfully!"),
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
          ),
        );
      } else {
        final error = ref.read(authProvider).errorMessage;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(error ?? "Login failed. Check credentials."),
            backgroundColor: Theme.of(context).colorScheme.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  void _handleChangeSchool() {
    ref.read(onboardingProvider.notifier).clearOnboarding();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final authState = ref.watch(authProvider);
    final onboardingState = ref.watch(onboardingProvider);
    final school = onboardingState.school;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24.0),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // School Logo
                  // if (school != null && school.logo.isNotEmpty)
                  //   Center(
                  //     child: Container(
                  //       margin: const EdgeInsets.only(bottom: 16),
                  //       child: CachedNetworkImage(
                  //         imageUrl: ApiEndpoints.sanitizeUrl(school.logo),
                  //         imageBuilder: (context, imageProvider) => Container(
                  //           height: 80,
                  //           width: 80,
                  //           decoration: BoxDecoration(
                  //             shape: BoxShape.circle,
                  //             image: DecorationImage(
                  //               image: imageProvider,
                  //               fit: BoxFit.contain,
                  //             ),
                  //           ),
                  //         ),
                  //         placeholder: (context, url) => const SizedBox(
                  //           height: 80,
                  //           width: 80,
                  //           child: Center(
                  //             child: CircularProgressIndicator(strokeWidth: 2),
                  //           ),
                  //         ),
                  //         errorWidget: (context, url, error) => Icon(
                  //           Icons.school_rounded,
                  //           size: 80,
                  //           color: theme.colorScheme.primary,
                  //         ),
                  //       ),
                  //     ),
                  //   )
                  // else
                  //   Icon(
                  //     Icons.school_rounded,
                  //     size: 80,
                  //     color: theme.colorScheme.primary,
                  //   ),
                  
                  Text(
                    school?.name ?? "EduvoraX",
                    style: theme.textTheme.headlineMedium?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: theme.colorScheme.primary,
                      fontSize: school != null ? 24 : null,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    "Teacher Portal Login",
                    style: theme.textTheme.titleMedium?.copyWith(
                      color: theme.colorScheme.onSurface.withOpacity(0.7),
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 32),

                  // Academic Year Selection Dropdown
                  if (onboardingState.academicYears.isNotEmpty)
                    DropdownButtonFormField<AcademicYearModel>(
                      value: _selectedYear,
                      hint: const Text("Select Academic Year"),
                      decoration: const InputDecoration(
                        labelText: "Academic Year",
                        prefixIcon: Icon(Icons.calendar_today_rounded),
                      ),
                      items: onboardingState.academicYears.map((year) {
                        return DropdownMenuItem<AcademicYearModel>(
                          value: year,
                          child: Text(year.name),
                        );
                      }).toList(),
                      onChanged: (AcademicYearModel? newValue) {
                        if (newValue != null) {
                          setState(() {
                            _selectedYear = newValue;
                          });
                          ref.read(onboardingProvider.notifier).selectAcademicYear(newValue);
                        }
                      },
                      validator: (value) => value == null ? "Select academic year" : null,
                    )
                  else if (onboardingState.isLoading)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 12.0),
                      child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                    )
                  else
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 8.0),
                      child: Text(
                        "No academic years found. Verify internet or school code.",
                        style: TextStyle(color: theme.colorScheme.error),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  const SizedBox(height: 20),

                  // Fields
                  CustomTextField(
                    controller: _emailController,
                    label: "Email Address",
                    hint: "teacher@school.com",
                    prefixIcon: Icons.email_outlined,
                    keyboardType: TextInputType.emailAddress,
                    validator: (val) {
                      if (val == null || val.trim().isEmpty) {
                        return "Please enter your email";
                      }
                      if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(val.trim())) {
                        return "Please enter a valid email address";
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 20),

                  CustomTextField(
                    controller: _passwordController,
                    label: "Password",
                    hint: "••••••••",
                    prefixIcon: Icons.lock_outline_rounded,
                    obscureText: _obscurePassword,
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                        color: theme.colorScheme.onSurface.withOpacity(0.6),
                      ),
                      onPressed: () {
                        setState(() {
                          _obscurePassword = !_obscurePassword;
                        });
                      },
                    ),
                    validator: (val) {
                      if (val == null || val.isEmpty) {
                        return "Please enter your password";
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 32),

                  // Login Button
                  CustomButton(
                    text: "Login",
                    isLoading: authState.isLoading,
                    onPressed: _handleLogin,
                  ),
                  const SizedBox(height: 24),

                  // Change School Button
                  TextButton.icon(
                    onPressed: _handleChangeSchool,
                    icon: const Icon(Icons.compare_arrows_rounded),
                    label: const Text("Change School"),
                    style: TextButton.styleFrom(
                      foregroundColor: theme.colorScheme.secondary,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
