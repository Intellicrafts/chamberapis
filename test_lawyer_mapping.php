<?php

use App\Models\User;
use App\Models\Lawyer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

function testLawyerCreation() {
    $email = 'test_lawyer_' . time() . '@example.com';
    $password = 'Password123!';
    
    echo "--- TESTING LAWYER CREATION FLOW ---\n";
    echo "Target Email: $email\n\n";

    // 1. Simulate the logic from LawyerController@store
    DB::beginTransaction();
    try {
        echo "STEP 1: Checking for user account...\n";
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            echo "Result: User not found. Creating new user record...\n";
            $user = User::create([
                'name' => 'Test Lawyer',
                'email' => $email,
                'password' => Hash::make($password),
                'user_type' => 2,
                'active' => true,
            ]);
            echo "SUCCESS: User ID [{$user->id}] generated.\n";
        }

        echo "\nSTEP 2: Mapping to Lawyers table...\n";
        $lawyer = Lawyer::create([
            'user_id' => $user->id,
            'full_name' => 'Test Lawyer',
            'email' => $email,
            'password_hash' => Hash::make($password),
            'enrollment_no' => 'TEST-' . time(),
            'status' => '0',
            'active' => true,
        ]);
        echo "SUCCESS: Lawyer Profile [ID: {$lawyer->id}] registered with user_id: {$user->id}\n";

        // Verification
        echo "\n--- VERIFICATION ---\n";
        $checkUser = User::find($user->id);
        $checkLawyer = Lawyer::find($lawyer->id);

        echo "User exists in DB? " . ($checkUser ? "YES" : "NO") . "\n";
        echo "Lawyer exists in DB? " . ($checkLawyer ? "YES" : "NO") . "\n";
        echo "Mapping Match (Lawyer.user_id == User.id)? " . ($checkLawyer->user_id == $checkUser->id ? "YES" : "NO") . "\n";
        echo "User Type matches (2)? " . ($checkUser->user_type == 2 ? "YES" : "NO") . "\n";

        DB::commit();
        echo "\nFinal Status: SYNC SUCCESSFUL\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

testLawyerCreation();
