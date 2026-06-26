# Mobile FCM Device Registration API Documentation

This document describes the API endpoints and database logic implemented for Firebase Cloud Messaging (FCM) Device Token Management.

---

## 1. Database Schema (`student_device_tokens`)

The following schema is used to store and manage student device tokens:

| Column | Type | Nullable | Description |
|---|---|---|---|
| `id` | `bigint unsigned` | No | Primary Key |
| `school_id` | `bigint unsigned` | No | Foreign Key constraint referencing `schools.id` |
| `student_id` | `bigint unsigned` | No | Foreign Key constraint referencing `students.id` |
| `device_type` | `varchar(255)` | No | Device platform type (e.g. `'android'`, `'ios'`, `'web'`) |
| `device_name` | `varchar(255)` | Yes | Device model name (e.g. `'Samsung Galaxy S24'`) |
| `app_version` | `varchar(255)` | Yes | Current mobile app version (e.g. `'1.0.0'`) |
| `firebase_token` | `varchar(255)` | No | Unique FCM Registration Token |
| `last_login_at` | `timestamp` | Yes | Date and time when the device last registered / logged in |
| `status` | `tinyint` | No | `1` = Active, `0` = Inactive |
| `created_at` | `timestamp` | Yes | Record creation timestamp |
| `updated_at` | `timestamp` | Yes | Record update timestamp |

### Token Rules
1. **Multiple Devices**: A student may have multiple devices registered.
2. **Never Overwrite**: When registering a new device token, existing device records for the student are not deleted or overwritten.
3. **FCM Token Uniqueness**: If the same `firebase_token` already exists in the database, it will be updated (owner student_id, school_id, last_login_at, device_name, app_version, and status set to `1`).

---

## 2. API Endpoints

All endpoints below require authentication using a Bearer token (Sanctum) and execute within the mobile context.

### A. Register / Update Device Token
Allows the mobile application to register its FCM token after a successful login.

* **Endpoint**: `POST /api/mobile/register-device`
* **Headers**:
  ```http
  Authorization: Bearer <sanctum_token>
  Accept: application/json
  Content-Type: application/json
  ```
* **Request Payload**:
  ```json
  {
    "firebase_token": "fcm_token_string_xxxxxxxxxxxx",
    "device_type": "android",
    "device_name": "Samsung Galaxy S24",
    "app_version": "1.0.0"
  }
  ```
* **Validation Rules**:
  * `firebase_token`: Required, string, max 255.
  * `device_type`: Required, string, max 50 (e.g. `'android'`, `'ios'`, `'web'`).
  * `device_name`: Optional, string, max 255.
  * `app_version`: Optional, string, max 50.
  * `school_id` and `student_id` are **automatically** resolved from the authenticated user and cannot be passed in the request body (improving security and preventing ID-spoofing).
* **Success Response (200 OK)**:
  ```json
  {
    "success": true,
    "id": 1,
    "school_id": 1,
    "student_id": 12,
    "device_type": "android",
    "device_name": "Samsung Galaxy S24",
    "app_version": "1.0.0",
    "firebase_token": "fcm_token_string_xxxxxxxxxxxx",
    "last_login_at": "2026-06-26T11:45:00.000000Z",
    "status": 1,
    "created_at": "2026-06-26T11:45:00.000000Z",
    "updated_at": "2026-06-26T11:45:00.000000Z"
  }
  ```

---

### B. Logout & Deactivate Device Token
Logs the student out of the application, revokes their API session, and marks their FCM token as inactive (preventing future push notifications until they log back in).

* **Endpoint**: `POST /api/mobile/logout`
* **Headers**:
  ```http
  Authorization: Bearer <sanctum_token>
  Accept: application/json
  Content-Type: application/json
  ```
* **Request Payload**:
  ```json
  {
    "firebase_token": "fcm_token_string_xxxxxxxxxxxx"
  }
  ```
* **Validation Rules**:
  * `firebase_token`: Required, string.
* **Success Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Logged out and device deactivated successfully."
  }
  ```
* **Behavior**:
  1. Locates the device record with `firebase_token` for the authenticated student.
  2. Sets `status = 0` (Inactive) and updates `last_login_at`.
  3. Revokes (deletes) the current Sanctum personal access token.
