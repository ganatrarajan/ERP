# EduvoraX - Student Application

EduvoraX is a comprehensive Flutter-based mobile application built for students and parents to seamlessly connect with the School ERP system. The app allows users to check attendance, download fee receipts, view report cards, and track daily homework.

---

## 🛠️ System Requirements & Versions

To successfully build and run this application on a new device or environment, ensure you have the following installed:

### **1. Flutter & Dart SDK**
- **Flutter SDK**: `3.24.x` (or any modern `3.x` version)
- **Dart SDK**: `^3.5.3`

### **2. Android Environment**
- **Android Studio**: Latest Version (Ladybug or Koala recommended)
- **Java SDK**: Java 17 (Required for modern Gradle versions)
- **Android API Level**: Target SDK 34, Minimum SDK 21

### **3. iOS Environment (Mac Only)**
- **Xcode**: Version 15.0 or higher
- **CocoaPods**: Version 1.15.x or higher
- **iOS Target**: iOS 12.0 or higher

---

## 📦 Core Dependencies (pubspec.yaml)
The project heavily relies on the following major packages:
- `provider: ^6.1.2` (State Management)
- `dio: ^5.7.0` (API & Network Requests)
- `flutter_secure_storage: ^9.2.2` (Encrypted Token & Session Storage)
- `table_calendar: ^3.1.2` (Attendance View)
- `permission_handler: ^11.3.1` (Storage Permissions for Downloading Files)
- `flutter_pdfview: ^1.3.2` (In-App Document Viewing)

---

## 🚀 How to Setup and Run on a New Device

Follow these step-by-step instructions to clone and run the app fresh on a new computer:

### Step 1: Clone or Copy the Repository
Place the `student_app` folder in your desired workspace (e.g., `D:\Projects\student_app`).

### Step 2: Install Packages
Open your terminal inside the `student_app` directory and run:
```bash
flutter clean
flutter pub get
```
*(This ensures all cached files are cleared and fresh dependencies are installed).*

### Step 3: Setup API Base URL
The backend base URL is globally configured inside the application.
1. Open the file: `lib/core/constants/api_endpoints.dart`
2. Ensure `baseUrl` matches your active local or production server:
```dart
static const String baseUrl = "http://192.168.0.173:8000/api/mobile";
```

### Step 4: Run the Application
Ensure you have an Emulator running or a Physical Device plugged in via USB/Wireless Debugging.
```bash
flutter run
```

---

## 🔑 App Flow & Architecture

1. **Multi-Tenancy Onboarding**: 
   - **School Code Screen**: User enters their school code (e.g., `19475`).
   - **Session Screen**: App retrieves school info and asks the user to select the `Academic Year`.
2. **Authentication**:
   - **Login Screen**: Requires `Admission Number` and `Password`.
   - **Force Password Change**: If it's a first-time login, the app intercepts the flow and forces a password change.
3. **Dashboard**: Access to Attendance, Homework, Noticeboard, Fee Receipts, and Report Cards.

## 🐛 Troubleshooting

**1. "MissingPluginException" on Secure Storage:**
If you see this error when running the app, it means the native Android/iOS code wasn't compiled properly. 
**Fix**: Completely stop the app in your terminal. Run `flutter clean`, then `flutter run` again. Do not rely on Hot Restart.

**2. Cannot Download Fee Receipts / Report Cards:**
Ensure your Android Emulator has storage access enabled, or test on a physical Android device where `Permission.storage` behaves accurately. Files are downloaded directly to the device's public `Downloads` folder.
