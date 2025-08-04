<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LawyerCase;
use App\Models\Lawyer;
use App\Models\LawyerCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LawyerCaseController extends Controller
{
    /**
     * Display a listing of the cases.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // If user is a lawyer, show only their cases
        if ($user->isLawyer()) {
            $lawyer = Lawyer::where('email', $user->email)->first();
            if (!$lawyer) {
                return response()->json(['message' => 'Lawyer profile not found'], 404);
            }
            
            $cases = LawyerCase::with(['user', 'category'])
                ->where('lawyer_id', $lawyer->id)
                ->paginate(10);
        } 
        // If user is a client, show only their cases
        elseif ($user->isClient()) {
            $cases = LawyerCase::with(['lawyer', 'category'])
                ->where('user_id', $user->id)
                ->paginate(10);
        } 
        // If user is an admin, show all cases
        else {
            $cases = LawyerCase::with(['user', 'lawyer', 'category'])
                ->paginate(10);
        }
        
        return response()->json($cases);
    }

    /**
     * Store a newly created case in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lawyer_id' => 'required|exists:lawyers,id',
            'casename' => 'required|string|max:255',
            'category_id' => 'required|exists:lawyer_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        
        $case = LawyerCase::create([
            'user_id' => $user->id,
            'lawyer_id' => $request->lawyer_id,
            'casename' => $request->casename,
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'message' => 'Case created successfully',
            'case' => $case
        ], 201);
    }

    /**
     * Display the specified case.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $case = LawyerCase::with(['user', 'lawyer', 'category'])->find($id);
        
        if (!$case) {
            return response()->json(['message' => 'Case not found'], 404);
        }

        // Check if the user has permission to view this case
        if ($user->isClient() && $case->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->isLawyer()) {
            $lawyer = Lawyer::where('email', $user->email)->first();
            if (!$lawyer || $case->lawyer_id != $lawyer->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return response()->json($case);
    }

    /**
     * Update the specified case in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'casename' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:lawyer_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $case = LawyerCase::find($id);
        
        if (!$case) {
            return response()->json(['message' => 'Case not found'], 404);
        }

        // Check if the user has permission to update this case
        if ($user->isClient() && $case->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->isLawyer()) {
            $lawyer = Lawyer::where('email', $user->email)->first();
            if (!$lawyer || $case->lawyer_id != $lawyer->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $case->update($request->only(['casename', 'category_id']));

        return response()->json([
            'message' => 'Case updated successfully',
            'case' => $case
        ]);
    }

    /**
     * Remove the specified case from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $case = LawyerCase::find($id);
        
        if (!$case) {
            return response()->json(['message' => 'Case not found'], 404);
        }

        // Only admin or the client who created the case can delete it
        if ($user->isClient() && $case->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$user->isAdmin() && !$user->isClient()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $case->delete();

        return response()->json(['message' => 'Case deleted successfully']);
    }
    
    /**
     * Get cases by lawyer ID.
     *
     * @param  string  $lawyerId
     * @return \Illuminate\Http\Response
     */
    public function getCasesByLawyer($lawyerId)
    {
        $lawyer = Lawyer::find($lawyerId);
        
        if (!$lawyer) {
            return response()->json(['message' => 'Lawyer not found'], 404);
        }
        
        $cases = LawyerCase::with(['user', 'category'])
            ->where('lawyer_id', $lawyerId)
            ->paginate(10);
            
        return response()->json($cases);
    }
    
    /**
     * Get cases by category ID.
     *
     * @param  string  $categoryId
     * @return \Illuminate\Http\Response
     */
    public function getCasesByCategory($categoryId)
    {
        $category = LawyerCategory::find($categoryId);
        
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        
        $cases = LawyerCase::with(['user', 'lawyer'])
            ->where('category_id', $categoryId)
            ->paginate(10);
            
        return response()->json($cases);
    }
}