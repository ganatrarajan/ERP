import 'package:flutter_test/flutter_test.dart';
import 'package:student_app/main.dart';

void main() {
  testWidgets('Splash screen smoke test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const EduvoraApp());

    // Verify that our app name is displayed on the Splash Screen.
    expect(find.text('Eduvora'), findsOneWidget);
    expect(find.text('Smart Student Portal'), findsOneWidget);
  });
}
