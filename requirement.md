# Requirements: Lawyer Registration & Verification

This document outlines the required changes to officially support the new lawyer registration flow and 3-step verification process.

## 1. Fix Enrollment Number Saving on Signup
**Scope: [Backend - Chamber API]**
**Issue:** 
During lawyer registration, the Bar Council Enrollment Number is not being saved due to a field mismatch in `AuthController.php`.
- The frontend sends the payload with the key `enrollment_no`.
- The backend validates `license_number` (`'license_number' => 'required_if:account_type,business...`), but then subsequently attempts to read `$request->enrollment_no`. 
- This causes validation to fail or the field to be entirely missed if the frontend request does not send an identical duplicate key.

**Required Action:**
- Update `app/Http/Controllers/AuthController.php` registration validation rules to validate `enrollment_no` instead of `license_number`.
- Ensure the final array assignment correctly maps `$request->enrollment_no` to the `license_number` column on the `Lawyer` model instance.

## 2. Implement 3-Tiered Verification Tracking
**Scope: [Backend - Chamber API]**
**Issue:** 
The system needs to support a granular 3-step verification process, but the current implementation predominantly relies on a boolean `is_verified` flag.
- **Level 0**: Pending (Default state for new registrations)
- **Level 1**: Bar Council Verified (Automated frontend verification via Satyapan API)
- **Level 2**: Admin Verified (Manual review & approval by Mera Vakil admin)

**Required Action:**
- Create a new database migration to add a `verification_level` column (integer, default: 0) to the relevant table (e.g., `lawyers` or `lawyer_additional_details`).
- Update the profile response payload in `UserController@profile` and `AuthController@login` to consistently return both `is_verified` and `verification_level` for the lawyer object.

## 3. New API Endpoint: Level 1 (Satyapan) Verification Success
**Scope: [Backend - Chamber API] & [Frontend - React App]**
**Issue:** 
When a lawyer successfully verifies their Bar Council credentials via the Satyapan API on the frontend, there is currently no backend endpoint to save this progress.

**Required Action (Backend):**
- Create a new protected endpoint (e.g., `POST /api/lawyer/verification/level-1-complete`).
- **Logic:** This endpoint should update the authenticated lawyer's `verification_level` to `1`.
- **(Recommended)**: Accept the Satyapan API success response payload in the request body to store as an audit log or proof of verification.

**Required Action (Frontend):**
- Once the backend endpoint is created, update `src/components/LawyerAdmin/LawyerProfile.jsx` to call this new endpoint after a successful Satyapan API response.

## 4. Admin Verification Endpoint Update (Level 2)
**Scope: [Backend - Chamber API] & [Frontend - React App]**
**Issue:** 
The admin system needs to accommodate the new tier structure.

**Required Action (Backend):**
- Update the admin approval logic (likely in Admin Controllers or `LawyerAdditionalController`) to explicitly set `verification_level = 2` alongside setting `is_verified = true` when manually approving a lawyer's profile.

**Required Action (Frontend):**
- If there is an admin dashboard for approving lawyers, ensure the API request sends the proper status to mark the lawyer as Level 2 verified.
