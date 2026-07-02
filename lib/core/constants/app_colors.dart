import 'package:flutter/material.dart';

class AppColors {
  // Brand Palette
  static const Color primary = Color(0xFF1E3A8A);      // Deep Royal Blue
  static const Color primaryLight = Color(0xFF3B82F6); // Active Light Blue
  static const Color primaryDark = Color(0xFF172554);  // Deep Dark Navy
  
  static const Color accent = Color(0xFFD97706);       // Warm Amber/Gold
  static const Color accentLight = Color(0xFFFBBF24);  // Soft Gold
  
  // Status Colors
  static const Color success = Color(0xFF10B981);      // Emerald Green (Present)
  static const Color error = Color(0xFFEF4444);        // Red (Absent)
  static const Color warning = Color(0xFFF59E0B);      // Orange (Late)
  static const Color info = Color(0xFF3B82F6);         // Light Blue (Leave)
  static const Color halfDay = Color(0xFF8B5CF6);      // Purple
  static const Color holiday = Color(0xFFEC4899);      // Pink (Holiday)

  // Neutral Backgrounds & Cards - Light Mode
  static const Color bgLight = Color(0xFFF8FAFC);      // Slate 50
  static const Color cardLight = Color(0xFFFFFFFF);
  static const Color textPrimaryLight = Color(0xFF0F172A); // Slate 900
  static const Color textSecondaryLight = Color(0xFF64748B); // Slate 500
  static const Color borderLight = Color(0xFFE2E8F0);    // Slate 200

  // Neutral Backgrounds & Cards - Dark Mode
  static const Color bgDark = Color(0xFF0F172A);       // Slate 900
  static const Color cardDark = Color(0xFF1E293B);       // Slate 800
  static const Color textPrimaryDark = Color(0xFFF1F5F9);  // Slate 100
  static const Color textSecondaryDark = Color(0xFF94A3B8); // Slate 400
  static const Color borderDark = Color(0xFF334155);     // Slate 700

  // Glassmorphic backgrounds (low opacity overlays)
  static Color glassBg(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return isDark ? const Color(0xFF1E293B).withOpacity(0.8) : const Color(0xFFFFFFFF).withOpacity(0.8);
  }
}
