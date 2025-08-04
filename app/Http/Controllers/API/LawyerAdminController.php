<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\LawyersCase;
use App\Models\LawyerCategory;
 use Carbon\Carbon;
 

class LawyerAdminController extends Controller
{
   
public function index($userId)
{
    // Fetch upcoming appointments for the lawyer
    $upcomingAppointments = Appointment::where('lawyer_id', $userId)
        ->upcoming()
        ->orderBy('appointment_time', 'asc')
        ->get();

    // Count active cases for the lawyer
    $activeCasesCount = LawyersCase::where('lawyer_id', $userId)->count();

    // Fetch lawyer categories


$categoryNames = LawyersCase::with('category')
    ->where('lawyer_id', $userId)
    ->get()
    ->pluck('category.category_name') // from the relation
    ->unique()
    ->values()
    ->toArray(); // <-- array output



    // Today's appointments with status = scheduled
    $todaysAppointments = Appointment::where('lawyer_id', $userId)
        ->whereBetween('appointment_time', [
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay()
        ])
        ->where('status', Appointment::STATUS_SCHEDULED)
        ->orderBy('appointment_time', 'asc')
        ->get();
    


    return response()->json([
        'message' => 'Dashboard data for user ' . $userId,
        'data' => [
            'active_cases' => $activeCasesCount,
            'upcoming_appointments' => $upcomingAppointments,
            'pending_documents' => [], // Placeholder
            'monthly_revenue' => 0, // Placeholder
            'appointment_trends' => [], // Placeholder
            'revenue_trends' => [], // Placeholder
             'appointment_trends' => [
            ['month' => 'Jan', 'appointments' => 22],
            ['month' => 'Feb', 'appointments' => 30],
            ['month' => 'Mar', 'appointments' => 27],
            ['month' => 'Apr', 'appointments' => 35],
            ['month' => 'May', 'appointments' => 40],
            ['month' => 'Jun', 'appointments' => 45],
        ],
        'revenue_trends' => [
            ['month' => 'Jan', 'revenue' => 12000],
            ['month' => 'Feb', 'revenue' => 15000],
            ['month' => 'Mar', 'revenue' => 14000],
            ['month' => 'Apr', 'revenue' => 17500],
            ['month' => 'May', 'revenue' => 20000],
            ['month' => 'Jun', 'revenue' => 26000],
        ],
        'case_type_distribution' => [
            ['category' => 'Corporate', 'percentage' => 35],
            ['category' => 'Criminal', 'percentage' => 25],
            ['category' => 'Family', 'percentage' => 20],
            ['category' => 'Civil', 'percentage' => 20],
        ],
           
            'todays_appointments' => $todaysAppointments, // Placeholder
            'recent_clients' => [] // Placeholder
        ]
    ]);
}
}