<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Lawyer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\LawyerEnrollmentStatusLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        // Rate limiting
        $key = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many registration attempts. Please try again later.'
            ], 429);
        }

        // Log the request for debugging (without sensitive data)
        \Log::info('Registration attempt', [
            'email' => $request->email,
            'name' => $request->name,
            'ip' => $request->ip(),
            'account_type' => $request->account_type
        ]);
        
        // Enhanced validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]/',
            'phone' => 'required|string|max:20',
            'account_type' => 'nullable',
            'enrollment_no' => 'required_if:account_type,business,lawyer|string|max:50',
            'specialization' => 'required_if:account_type,business,lawyer|string|max:255',
            'years_of_experience' => 'nullable|integer|min:0|max:50',
            'consultation_fee' => 'nullable|numeric|min:0',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'name.regex' => 'Name can only contain letters and spaces.',
        ]);

        if($validator->fails()){
            RateLimiter::hit($key);
            \Log::warning('Registration failed: Validation errors', [
                'email' => $request->email,
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $key) {
                // Sanitize input
                $sanitizedName = trim(strip_tags($request->name));
                $sanitizedEmail = strtolower(trim($request->email));
                $accountType = $request->account_type ?? 'user';
                $phone = $request->phone ? trim(strip_tags($request->phone)) : null;
                
                // Create user
                $user = User::create([
                    'name' => $sanitizedName,
                    'email' => $sanitizedEmail,
                    'password' => Hash::make($request->password),
                    'phone' => $phone,
                    'user_type' => $accountType,
                ]);

                $lawyer = null;
                
                // Check if user type is business, lawyer, or numeric 2
                if (in_array($accountType, ['business', 'lawyer', 2], true) || $accountType == 2) {
                    // Ensure unique enrollment number
                    $enrollmentNo = $request->enrollment_no;
                    if (Lawyer::where('enrollment_no', $enrollmentNo)->exists()) {
                        throw new \Exception('Enrollment number already exists');
                    }

                    // Create lawyer record
                    $lawyer = Lawyer::create([
                        'user_id' => $user->id,
                        'full_name' => $sanitizedName,
                        'email' => $sanitizedEmail,
                        'password_hash' => Hash::make($request->password),
                        'active' => true,
                        'is_verified' => false,
                        'enrollment_no' => $enrollmentNo,
                        'specialization' => $request->specialization,
                        'years_of_experience' => $request->years_of_experience ?? 0,
                        'bio' => $request->bio ? trim(strip_tags($request->bio)) : null,
                        'consultation_fee' => $request->consultation_fee ?? 0.00,
                        'status' => '0',
                    ]);

                    // Log initial status
                    LawyerEnrollmentStatusLog::create([
                        'user_id' => $user->id,
                        'status' => '0'
                    ]);
                }

                // Create token
                $token = $user->createToken('auth_token')->plainTextToken;
                
                \Log::info('Registration successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'account_type' => $accountType
                ]);

                // Clear rate limiter on success
                RateLimiter::clear($key);

                // Prepare response data
                $response = [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'message' => 'User registered successfully',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'user_type' => $user->user_type,
                        'created_at' => $user->created_at,
                    ]
                ];
                
                // Add lawyer data if business account
                if ($lawyer) {
                    $response['lawyer'] = [
                        'uuid' => $lawyer->id,
                        'full_name' => $lawyer->full_name,
                        'email' => $lawyer->email,
                        'enrollment_no' => $lawyer->enrollment_no,
                        'specialization' => $lawyer->specialization,
                        'is_verified' => $lawyer->is_verified,
                        'status' => $lawyer->status,
                    ];
                }

                return response()->json($response, 201);
            });
            
        } catch (\Exception $e) {
            RateLimiter::hit($key);
            \Log::error('Registration error', [
                'email' => $request->email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during registration'
            ], 500);
        }
    }

    // Login
    public function login(Request $request)
    { 
        // dd($request->all());
        // Rate limiting
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again later.'
            ], 429);
        }

        // Log the request for debugging (without sensitive data)
        \Log::info('Login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);
        
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if($validator->fails()){
            RateLimiter::hit($key);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Sanitize email
            $email = strtolower(trim($request->email));
            
            if (!Auth::attempt(['email' => $email, 'password' => $request->password])) {
                RateLimiter::hit($key);
                \Log::warning('Login failed: Invalid credentials', ['email' => $email]);
                return response()->json([
                    'message' => 'Invalid login credentials'
                ], 401);
            }

            $user = User::where('email', $email)->firstOrFail();
            
            // Revoke any existing tokens for security
            // $user->tokens()->delete();

            // Create a new token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Clear rate limiter on success
            RateLimiter::clear($key);
            
            \Log::info('Login successful', ['user_id' => $user->id, 'email' => $user->email]);

            // Get lawyer data if user is a lawyer
            $lawyer = null;
            if (in_array($user->user_type, ['business', 'lawyer'])) {
                $lawyer = Lawyer::where('email', $user->email)->first();
            }

            $response = [
                'message' => 'Hi '.$user->name.', welcome back',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'created_at' => $user->created_at,
                ]
            ];

            // Add lawyer data if available
            if ($lawyer) {
                $response['lawyer'] = [
                    'uuid' => $lawyer->id,
                    'full_name' => $lawyer->full_name,
                    'email' => $lawyer->email,
                    'enrollment_no' => $lawyer->enrollment_no,
                    'specialization' => $lawyer->specialization,
                    'is_verified' => $lawyer->is_verified,
                    'status' => $lawyer->status,
                ];
            }

            return response()->json($response);
            
        } catch (\Exception $e) {
            RateLimiter::hit($key);
            \Log::error('Login error', [
                'email' => $request->email,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during login'
            ], 500);
        }
    }

    // Logout
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            // Log the request for debugging (without sensitive data)
            \Log::info('Logout attempt', [
                'user_id' => $user ? $user->id : null,
                'ip' => $request->ip(),
            ]);
            
            if ($user) {
                // Delete current access token
                $currentToken = $user->currentAccessToken();
                if ($currentToken) {
                    $currentToken->delete();
                }
                
                \Log::info('Logout successful', ['user_id' => $user->id]);
                
                return response()->json([
                    'message' => 'Successfully logged out'
                ]);
            } else {
                \Log::warning('Logout attempted without authenticated user', [
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'message' => 'No authenticated user found'
                ], 401);
            }

        } catch (\Exception $e) {
            \Log::error('Logout error', [
                'user_id' => $request->user() ? $request->user()->id : null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during logout'
            ], 500);
        }
    }
}
