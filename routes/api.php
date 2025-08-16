<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\GitDeployController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\AvailabilitySlotController;
use App\Http\Controllers\API\LegalQueryController;
use App\Http\Controllers\API\AppointmentController;
use App\Http\Controllers\API\LawyerController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\LawyerAdminController;
use App\Http\Controllers\API\LawyerCaseController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/deploy', [GitDeployController::class, 'deploy']);
Route::get('/optimize', [GitDeployController::class, 'optimize']);


// Route::prefix('users')->group(function(){
//     Route::options('/user', [WelcomeController::class, 'apiResponse']);
//     Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'fetchUser']);
//     Route::post('/create', [UserController::class, 'register']);
//     Route::post('/login', [UserController::class, 'login']);
//     Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);
//     Route::middleware('auth:sanctum')->post('/update/upi', [UserController::class, 'updatePaymentUpi']);
//     Route::post('/otp/send', [OtpController::class, 'sendOtp']);
//     Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
//     Route::middleware('auth:sanctum')->post('/password/reset', [UserController::class, 'updatePassword']);
//     Route::middleware('auth:sanctum')->get('/refer/list', [UserController::class, 'listReferredUsers']);
// });

/*
|--------------------------------------------------------------------------
| CSRF & Authentication Routes
|--------------------------------------------------------------------------
*/

// Direct avatar upload routes for compatibility with frontend
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/update-avatar', [UserController::class, 'updateAvatar']);
    Route::post('/avatar', [UserController::class, 'updateAvatar']);
});



Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::options('/register', [WelcomeController::class, 'apiResponse']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    // Route::options('/register', function () {
    //     return response()->json(['status' => 'success'], 200);
    // });
    // Route::options('/login', function () {
    //     return response()->json(['status' => 'success'], 200);
    // });
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum']);
Route::options('/logout', function () {
    return response()->json(['status' => 'success'], 200);
});

Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/password/send-otp', [PasswordResetController::class, 'sendOtp']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
});
Route::get('/password/verify-otp', [PasswordResetController::class, 'verifyOtp']);

/*
|--------------------------------------------------------------------------
| PUBLIC CONTACT ROUTES
|--------------------------------------------------------------------------
| Contact form routes (assuming they can be public)
*/

Route::apiResource('contacts', ContactController::class);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES - REQUIRE AUTHENTICATION
|--------------------------------------------------------------------------
| All routes below require authentication via Sanctum
*/

Route::middleware('auth:sanctum')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | USER PROFILE ROUTES
    |--------------------------------------------------------------------------
    | Routes for managing user profile
    | Base URL: /api/user
    */
    
    Route::prefix('user')->name('user.')->group(function () {
        // Profile management
        Route::get('/profile', [UserController::class, 'profile'])->name('profile');
        Route::put('/profile', [UserController::class, 'updateProfile'])->name('update-profile');
        
        // Password management
        Route::post('/change-password', [UserController::class, 'changePassword'])->name('change-password');
        
        // Avatar management - multiple endpoints for compatibility
        Route::post('/avatar', [UserController::class, 'updateAvatar'])->name('avatar');
        Route::post('/update-avatar', [UserController::class, 'updateAvatar'])->name('update-avatar');
        Route::post('/{user}/avatar', [UserController::class, 'updateAvatar'])->name('user-avatar');
        
        // Get user by ID - must be last to avoid conflicts with other routes
        Route::get('/{user}', [UserController::class, 'show'])->name('show');

    });

    /*
    |--------------------------------------------------------------------------
    | AVAILABILITY SLOT ROUTES
    |--------------------------------------------------------------------------
    | Routes for managing lawyer availability slots
    | Base URL: /api/availability-slots
    */
    
    Route::prefix('availability-slots')->name('availability-slots.')->group(function () {
        // Standard CRUD Operations
        Route::get('/', [AvailabilitySlotController::class, 'index'])->name('index');
        Route::post('/', [AvailabilitySlotController::class, 'store'])->name('store');
        Route::get('/{availabilitySlot}', [AvailabilitySlotController::class, 'show'])->name('show');
        Route::put('/{availabilitySlot}', [AvailabilitySlotController::class, 'update'])->name('update');
        Route::delete('/{availabilitySlot}', [AvailabilitySlotController::class, 'destroy'])->name('destroy');
        
        // Slot Booking Operations
        Route::post('/{availabilitySlot}/book', [AvailabilitySlotController::class, 'book'])->name('book');
        Route::post('/{availabilitySlot}/cancel', [AvailabilitySlotController::class, 'cancel'])->name('cancel');
        
        // Bulk Operations
        Route::post('/generate-weekly', [AvailabilitySlotController::class, 'generateWeekly'])->name('generate-weekly');
        Route::delete('/bulk-delete', [AvailabilitySlotController::class, 'bulkDelete'])->name('bulk-delete');
        
        // Query Operations
        Route::get('/available/search', [AvailabilitySlotController::class, 'getAvailable'])->name('get-available');
    });

    /*
    |--------------------------------------------------------------------------
    | APPOINTMENT ROUTES
    |--------------------------------------------------------------------------
    | Routes for managing appointments between users and lawyers
    | Base URL: /api/appointments
    */
    
    Route::prefix('appointments')->name('appointments.')->group(function () {
        // Standard CRUD Operations
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('show');
        Route::put('/{appointment}', [AppointmentController::class, 'update'])->name('update');
        Route::delete('/{appointment}', [AppointmentController::class, 'destroy'])->name('destroy');
        
        // Status Management Operations
        Route::patch('/{appointment}/complete', [AppointmentController::class, 'markAsCompleted'])->name('complete');
        Route::patch('/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('cancel');
        Route::patch('/{appointment}/no-show', [AppointmentController::class, 'markAsNoShow'])->name('no-show');
        Route::patch('/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('reschedule');
        
        // Query Operations
        Route::get('/user/{userId}', [AppointmentController::class, 'getUserAppointments'])->name('user-appointments');
        Route::get('/lawyer/{lawyerId}', [AppointmentController::class, 'getLawyerAppointments'])->name('lawyer-appointments');
        Route::get('/upcoming/all', [AppointmentController::class, 'getUpcoming'])->name('upcoming');
        Route::get('/today/all', [AppointmentController::class, 'getTodaysAppointments'])->name('today');
        
        // Meeting Operations
        Route::post('/{appointment}/generate-meeting-link', [AppointmentController::class, 'generateMeetingLink'])->name('generate-meeting-link');
        
        // Bulk Operations
        Route::post('/bulk-cancel', [AppointmentController::class, 'bulkCancel'])->name('bulk-cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | LEGAL QUERY ROUTES
    |--------------------------------------------------------------------------
    | Routes for managing AI-powered legal queries
    | Base URL: /api/legal-queries
    */
    
    Route::prefix('legal-queries')->name('legal-queries.')->group(function () {
        // Standard CRUD Operations
        Route::get('/', [LegalQueryController::class, 'index'])->name('index');
        Route::post('/', [LegalQueryController::class, 'store'])->name('store');
        Route::get('/{legalQuery}', [LegalQueryController::class, 'show'])->name('show');
        Route::put('/{legalQuery}', [LegalQueryController::class, 'update'])->name('update');
        Route::delete('/{legalQuery}', [LegalQueryController::class, 'destroy'])->name('destroy');
        
        // AI Response Operations
        Route::middleware(['throttle:10,1'])->group(function () {
            Route::post('/{legalQuery}/generate-response', [LegalQueryController::class, 'generateAiResponse'])->name('generate-response');
            Route::post('/{legalQuery}/regenerate-response', [LegalQueryController::class, 'regenerateAiResponse'])->name('regenerate-response');
        });
        Route::delete('/{legalQuery}/clear-response', [LegalQueryController::class, 'clearAiResponse'])->name('clear-response');
        
        // Query Operations
        Route::get('/user/{userId}', [LegalQueryController::class, 'getUserQueries'])->name('user-queries');
        Route::get('/search/text', [LegalQueryController::class, 'searchQueries'])->name('search');
        Route::get('/{legalQuery}/similar', [LegalQueryController::class, 'getSimilarQueries'])->name('similar');
        
        // Analytics Operations
        Route::get('/analytics/summary', [LegalQueryController::class, 'getAnalytics'])->name('analytics');
        Route::get('/export/user/{userId}', [LegalQueryController::class, 'exportUserQueries'])->name('export-user');
        
        // Bulk Operations
        Route::middleware(['throttle:10,1'])->group(function () {
            Route::post('/bulk-generate-responses', [LegalQueryController::class, 'bulkGenerateResponses'])->name('bulk-generate');
        });
        Route::delete('/bulk-delete', [LegalQueryController::class, 'bulkDelete'])->name('bulk-delete');
    });

    


     /*
    |--------------------------------------------------------------------------
    | LAWYERS  ROUTES
    |--------------------------------------------------------------------------
    | Routes for managing AI-powered legal queries
    | Base URL: /api/legal-queries
    */

        Route::prefix('lawyers')->name('lawyers.')->group(function () {
        // Standard CRUD Operations (you can adjust access control as needed)
        Route::get('/', [LawyerController::class, 'index'])->name('index');
        Route::post('/', [LawyerController::class, 'store'])->name('store');
        Route::get('/{lawyer}', [LawyerController::class, 'show'])->name('show');
        Route::put('/{lawyer}', [LawyerController::class, 'update'])->name('update');
        Route::delete('/{lawyer}', [LawyerController::class, 'destroy'])->name('destroy');
        Route::get('/lawyer-details', [LawyerController::class, 'lawyer_with_details'])->name('lawyer-details');

        // Filtered/Query Operations
        Route::get('/specialization/{specialization}', [LawyerController::class, 'bySpecialization'])->name('by-specialization');
        Route::get('/active/all', [LawyerController::class, 'getActive'])->name('active');
        Route::get('/verified/all', [LawyerController::class, 'getVerified'])->name('verified');

        // Public Profile Info
        Route::get('/{lawyer}/profile', [LawyerController::class, 'publicProfile'])->name('profile');

        // Availability Slots for a specific lawyer
        Route::get('/{lawyer}/available-today', [LawyerController::class, 'todayAvailableSlots'])->name('available-today');
    });


        Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/user/{userId}', [NotificationController::class, 'userNotifications']);
        Route::get('/user/{userId}/unread', [NotificationController::class, 'unreadByUser']);
        Route::get('/user/{userId}/read', [NotificationController::class, 'readByUser']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::put('/{id}', [NotificationController::class, 'update']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/{id}/unread', [NotificationController::class, 'markAsUnread']);
    });


       Route::prefix('lawyer_admin')->group(function () {
        Route::get('/{id}', [LawyerAdminController::class, 'index']);
    });

    /*
    |--------------------------------------------------------------------------
    | LAWYER CASES ROUTES
    |--------------------------------------------------------------------------
    | Routes for managing lawyer cases
    | Base URL: /api/lawyer-cases
    */
    
    Route::prefix('lawyer-cases')->name('lawyer-cases.')->group(function () {
        // Standard CRUD Operations
        Route::get('/', [LawyerCaseController::class, 'index'])->name('index');
        Route::post('/', [LawyerCaseController::class, 'store'])->name('store');
        Route::get('/{id}', [LawyerCaseController::class, 'show'])->name('show');
        Route::put('/{id}', [LawyerCaseController::class, 'update'])->name('update');
        Route::delete('/{id}', [LawyerCaseController::class, 'destroy'])->name('destroy');
        
        // Query Operations
        Route::get('/lawyer/{lawyerId}', [LawyerCaseController::class, 'getCasesByLawyer'])->name('by-lawyer');
        Route::get('/category/{categoryId}', [LawyerCaseController::class, 'getCasesByCategory'])->name('by-category');
    });
});
