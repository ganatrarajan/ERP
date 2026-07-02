# EduvoraX Teacher App REST API Specifications

This document contains the complete REST API documentation for the **EduvoraX Teacher App** backend.

---

## 1. Global API Configuration

*   **Content-Type**: `application/json`
*   **Accept**: `application/json`
*   **Authorization Header**: `Authorization: Bearer <Sanctum_Token_Here>` (Required for all secured routes)
*   **Unified Response Format**:
    ```json
    {
        "success": true,
        "message": "Action completed successfully.",
        "data": { ... } // Or list arrays
    }
    ```
*   **Error Response Format**:
    ```json
    {
        "success": false,
        "message": "Brief description of the error",
        "errors": {
            "field_name": [
                "Detailed validation error message."
            ]
        }
    }
    ```

---

## 2. Authentication Module

### A. Verify School Code
*   **Endpoint**: `/api/mobile/school/verify`
*   **HTTP Method**: `POST`
*   **Authentication**: None
*   **Request Body**:
    ```json
    {
        "school_code": "SCH10"
    }
    ```
*   **Validation**:
    *   `school_code` (Required, string, exact 5 characters)
*   **Success Response**:
    ```json
    {
        "success": true,
        "school_id": 1,
        "school_name": "Eduvora School Systems",
        "school_logo": "http://localhost/uploads/logo.png",
        "school_address": "Main Street Campus",
        "school_phone": "+91 99999 99999",
        "mobile_academic_year_id": 1,
        "mobile_academic_year_title": "2025 - 2026"
    }
    ```

### B. Teacher Login
*   **Endpoint**: `/api/mobile/teacher/login`
*   **HTTP Method**: `POST`
*   **Authentication**: None
*   **Request Body**:
    ```json
    {
        "school_code": "SCH10",
        "email": "teacher@school.com",
        "password": "password123",
        "academic_year_id": 1
    }
    ```
*   **Validation**:
    *   `school_code` (Required, string, exact 5 chars)
    *   `email` (Required, email format)
    *   `password` (Required, string)
    *   `academic_year_id` (Optional, integer, valid academic year ID)
*   **Success Response**:
    ```json
    {
        "success": true,
        "message": "Logged in successfully.",
        "token": "4|aBcdEFgHiJkLmNo...",
        "teacher": {
            "id": 12,
            "school_id": 1,
            "employee_id": "EMP-2026-001",
            "name": "Jane Teacher",
            "email": "teacher@school.com",
            "mobile": "9876543210",
            "status": "active"
        }
    }
    ```

### C. Update FCM Device Token
*   **Endpoint**: `/api/mobile/teacher/fcm-token`
*   **HTTP Method**: `POST`
*   **Authentication**: Sanctum Token Required
*   **Request Body**:
    ```json
    {
        "firebase_token": "fcm_token_string_here",
        "device_type": "Android",
        "device_name": "Samsung S24 Ultra",
        "app_version": "1.0.0"
    }
    ```
*   **Validation**:
    *   `firebase_token` (Required, string, max 255)
    *   `device_type` (Required, string, max 50)
    *   `device_name` (Optional, string, max 255)
    *   `app_version` (Optional, string, max 50)

### D. Logout
*   **Endpoint**: `/api/mobile/teacher/logout`
*   **HTTP Method**: `POST`
*   **Authentication**: Sanctum Token Required
*   **Success Response**:
    ```json
    {
        "success": true,
        "message": "Logged out successfully."
    }
    ```

---

## 3. Teacher Profile Module

### A. View Profile
*   **Endpoint**: `/api/mobile/teacher/profile`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Success Response**:
    ```json
    {
        "success": true,
        "teacher": {
            "id": 12,
            "name": "Jane Teacher",
            "email": "teacher@school.com",
            "mobile": "9876543210",
            "address": "123 School Lane",
            "emergency_contact_name": "Emergency Contact",
            "emergency_contact_mobile": "9999988888"
        }
    }
    ```

### B. Update Profile Fields
*   **Endpoint**: `/api/mobile/teacher/profile/update`
*   **HTTP Method**: `POST`
*   **Authentication**: Sanctum Token Required
*   **Request Body**:
    ```json
    {
        "emergency_contact_name": "John Family",
        "emergency_contact_mobile": "9111122222",
        "address": "456 New Street Road",
        "mobile": "9876543211"
    }
    ```
*   **Validation**:
    *   `emergency_contact_name` (Optional, string, max 255)
    *   `emergency_contact_mobile` (Optional, string, max 20)
    *   `address` (Optional, string, max 500)
    *   `mobile` (Optional, string, max 20)

### C. Change Password
*   **Endpoint**: `/api/mobile/teacher/change-password`
*   **HTTP Method**: `POST`
*   **Authentication**: Sanctum Token Required
*   **Request Body**:
    ```json
    {
        "old_password": "password123",
        "new_password": "newpassword123",
        "confirm_password": "newpassword123"
    }
    ```
*   **Validation**:
    *   `old_password` (Required, string)
    *   `new_password` (Required, string, min 6 characters)
    *   `confirm_password` (Required, string, matches `new_password`)

---

## 4. Dashboard & Assignments Module

### A. Dashboard Summary Stats
*   **Endpoint**: `/api/mobile/teacher/dashboard`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Success Response**:
    ```json
    {
        "success": true,
        "assigned_classes_count": 3,
        "today_attendance_marked_sections": 2,
        "pending_homework_count": 5,
        "recent_notices": [
            {
                "id": 15,
                "title": "Final Examination Postponed",
                "notice_date": "2026-07-02"
            }
        ]
    }
    ```

### B. My Assigned Classes & Subjects
*   **Endpoint**: `/api/mobile/teacher/assignments`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Success Response**:
    ```json
    {
        "success": true,
        "assignments": [
            {
                "id": 2,
                "class_id": 1,
                "section_id": 2,
                "subject_id": 5,
                "is_class_teacher": true,
                "class": { "id": 1, "name": "Grade 10" },
                "section": { "id": 2, "name": "B" },
                "subject": { "id": 5, "name": "Mathematics" }
            }
        ]
    }
    ```

---

## 5. Attendance Module

### A. List of Classes for Attendance
*   **Endpoint**: `/api/mobile/teacher/attendance/classes`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Success Response**:
    ```json
    {
        "success": true,
        "classes": [
            {
                "class_id": 1,
                "section_id": 2,
                "class": { "id": 1, "name": "Grade 10" },
                "section": { "id": 2, "name": "B" }
            }
        ]
    }
    ```

### B. Fetch Student List for Attendance
*   **Endpoint**: `/api/mobile/teacher/attendance/students`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Query Parameters**:
    *   `class_id` (Required, integer)
    *   `section_id` (Required, integer)
    *   `attendance_date` (Required, date string in `YYYY-MM-DD` format)
*   **Success Response**:
    ```json
    {
        "success": true,
        "students": [
            {
                "student_id": 4,
                "roll_no": "01",
                "name": "Alex Mercer",
                "admission_no": "ADM-098",
                "gender": "Male",
                "attendance_status": "Present",
                "remarks": "",
                "is_holiday": false
            }
        ]
    }
    ```

### C. Submit or Update Attendance
*   **Endpoint**: `/api/mobile/teacher/attendance/save` (or `/api/mobile/teacher/attendance/update`)
*   **HTTP Method**: `POST`
*   **Authentication**: Sanctum Token Required
*   **Request Body**:
    ```json
    {
        "class_id": 1,
        "section_id": 2,
        "attendance_date": "2026-07-02",
        "students": [
            {
                "student_id": 4,
                "status": "Present",
                "remarks": ""
            },
            {
                "student_id": 5,
                "status": "Absent",
                "remarks": "Sick leave request pending"
            }
        ]
    }
    ```
*   **Validation**:
    *   `class_id`, `section_id` (Required, valid IDs)
    *   `attendance_date` (Required, `YYYY-MM-DD` format)
    *   `students` (Required array)
    *   `students.*.status` (Must be: `Present`, `Absent`, `Late`, `Half Day`, `Leave`, `Holiday`)

### D. Monthly Attendance Grid
*   **Endpoint**: `/api/mobile/teacher/attendance/monthly`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Query Parameters**:
    *   `class_id` (Required, integer)
    *   `section_id` (Required, integer)
    *   `month` (Required, format `YYYY-MM`)
*   **Success Response**:
    ```json
    {
        "success": true,
        "days_in_month": 31,
        "matrix": [
            {
                "student_id": 4,
                "roll_no": "01",
                "name": "Alex Mercer",
                "days": {
                    "1": "Present",
                    "2": "Present",
                    "3": "Holiday"
                },
                "stats": {
                    "Present": 2,
                    "Absent": 0,
                    "Holiday": 1
                }
            }
        ]
    }
    ```

---

## 6. Homework Module

### A. List Homeworks Created
*   **Endpoint**: `/api/mobile/teacher/homeworks`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Query Parameters**:
    *   `class_id`, `section_id` (Optional filter)
    *   `page` (Optional, for paginated pages)
*   **Success Response**:
    ```json
    {
        "success": true,
        "current_page": 1,
        "data": [
            {
                "id": 18,
                "title": "Geometry homework",
                "submission_date": "2026-07-06",
                "class": { "name": "Grade 10" },
                "section": { "name": "B" },
                "subject": { "name": "Mathematics" }
            }
        ],
        "next_page_url": null
    }
    ```

### B. Create Homework
*   **Endpoint**: `/api/mobile/teacher/homeworks`
*   **HTTP Method**: `POST`
*   **Authentication**: Sanctum Token Required
*   **Request Body**:
    ```json
    {
        "class_id": 1,
        "section_id": 2,
        "subject_id": 5,
        "title": "Trigonometry Basics",
        "description": "Solve Chapter 2 exercise problems.",
        "submission_date": "2026-07-06",
        "max_marks": 100
    }
    ```
*   **Validation**:
    *   `class_id`, `section_id`, `subject_id` (Required)
    *   `title` (Required, string, max 255)
    *   `description` (Required)
    *   `submission_date` (Required, `YYYY-MM-DD` on or after today)

### C. Update Homework
*   **Endpoint**: `/api/mobile/teacher/homeworks/{id}`
*   **HTTP Method**: `PUT`
*   **Authentication**: Sanctum Token Required
*   **Request Body**:
    ```json
    {
        "title": "Trigonometry Basics V2",
        "max_marks": 50
    }
    ```

### D. Delete Homework
*   **Endpoint**: `/api/mobile/teacher/homeworks/{id}`
*   **HTTP Method**: `DELETE`
*   **Authentication**: Sanctum Token Required

---

## 7. Notice Board Module

### A. List Notices
*   **Endpoint**: `/api/mobile/teacher/notices`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required

### B. Notice Details
*   **Endpoint**: `/api/mobile/teacher/notices/{id}`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required

---

## 8. Examinations & Grading Module

### A. Fetch Assigned Exams list
*   **Endpoint**: `/api/mobile/teacher/exams`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Success Response**:
    ```json
    {
        "success": true,
        "exams": [
            {
                "id": 2,
                "name": "First Mid Term",
                "status": "active"
            }
        ]
    }
    ```

### B. Fetch Class Marks Sheet for grading
*   **Endpoint**: `/api/mobile/teacher/exams/{exam_id}/marks`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Query Parameters**:
    *   `class_id` (Required)
    *   `section_id` (Required)
    *   `subject_id` (Required)
*   **Success Response**:
    ```json
    {
        "success": true,
        "schedule": {
            "id": 10,
            "max_marks": 100,
            "min_marks": 35
        },
        "grades": [
            { "id": 1, "grade_name": "A", "min_percentage": 85 }
        ],
        "students": [
            {
                "student_id": 4,
                "roll_no": "01",
                "name": "Alex Mercer",
                "admission_no": "ADM-098",
                "marks_obtained": 90,
                "is_absent": false,
                "grade_id": 1,
                "remarks": "Excellent"
            }
        ]
    }
    ```

### C. Save/Update Student Exam Marks
*   **Endpoint**: `/api/mobile/teacher/exams/marks/save`
*   **HTTP Method**: `POST`
*   **Authentication**: Sanctum Token Required
*   **Request Body**:
    ```json
    {
        "exam_id": 2,
        "exam_schedule_id": 10,
        "subject_id": 5,
        "marks": [
            {
                "student_id": 4,
                "marks_obtained": 90,
                "is_absent": false,
                "remarks": "Excellent"
            },
            {
                "student_id": 5,
                "marks_obtained": null,
                "is_absent": true,
                "remarks": "Absent without notice"
            }
        ]
    }
    ```

---

## 9. Documents Module

### A. Fetch Teacher Documents List
*   **Endpoint**: `/api/mobile/teacher/documents`
*   **HTTP Method**: `GET`
*   **Authentication**: Sanctum Token Required
*   **Success Response**:
    ```json
    {
        "success": true,
        "documents": [
            {
                "id": 3,
                "title": "Aadhaar Card Copy",
                "file_path": "uploads/documents/user_3_aadhaar.pdf"
            }
        ]
    }
    ```

---

## 10. Notes for Flutter Developers

1.  **Strict Data Isolation**: The middleware uses token abilities configured upon login (e.g. `school:1`, `academic_year:1`) to bound all search scopes.
2.  **Date Format standard**: Send dates formatted exactly as `YYYY-MM-DD`.
3.  **Future APIs (Planned)**: Teacher leave requests and direct broadcast notification history are mapped to future releases. Use placeholders on the frontend client for these if required.
