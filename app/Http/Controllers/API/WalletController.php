<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class WalletController extends Controller
{
    /**
     * GET /api/wallet/balance
     * Get the authenticated user's wallet balance
     */
    public function getBalance(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'success'              => true,
            'user_id'              => $user->id,
            'total_balance'        => (float) ($user->wallet_balance ?? 0),
            'earned_balance'       => (float) ($user->earned_balance ?? 0),
            'promotional_balance'  => (float) ($user->promotional_balance ?? 0),
            'currency'             => 'INR',
        ]);
    }

    /**
     * GET /api/wallet/transactions
     * Get paginated transaction history for the authenticated user
     */
    public function getTransactions(Request $request)
    {
        $user  = Auth::user();
        $limit = (int) $request->query('limit', 10);
        $skip  = (int) $request->query('skip', 0);
        $page  = (int) $request->query('page', 1);

        // Support both skip-based and page-based pagination
        if ($request->has('page') && !$request->has('skip')) {
            $skip = ($page - 1) * $limit;
        }

        $query = DB::table('wallet_transactions')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        $total        = $query->count();
        $transactions = $query->skip($skip)->take($limit)->get();

        return response()->json([
            'success'      => true,
            'transactions' => $transactions,
            'total'        => $total,
            'has_more'     => ($skip + $limit) < $total,
            'current_page' => $page,
        ]);
    }

    /**
     * POST /api/wallet/recharge
     * Add funds to the user's wallet (earned balance)
     * Body: { amount, description? }
     */
    public function recharge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount'      => 'required|numeric|min:1|max:100000',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user   = Auth::user();
        $amount = (float) $request->amount;

        DB::beginTransaction();
        try {
            // Update user wallet balance
            DB::table('users')->where('id', $user->id)->update([
                'wallet_balance'   => DB::raw("wallet_balance + {$amount}"),
                'earned_balance'   => DB::raw("earned_balance + {$amount}"),
                'updated_at'       => now(),
            ]);

            // Record the transaction
            $txnId = DB::table('wallet_transactions')->insertGetId([
                'user_id'      => $user->id,
                'type'         => 'CREDIT',
                'amount'       => $amount,
                'category'     => 'RECHARGE',
                'balance_type' => 'earned',
                'description'  => $request->description ?? 'Wallet Recharge',
                'status'       => 'SUCCESS',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::commit();

            // Fetch updated user
            $updatedUser = DB::table('users')->where('id', $user->id)->first();

            return response()->json([
                'success'             => true,
                'message'             => 'Wallet recharged successfully',
                'transaction_id'      => $txnId,
                'amount'              => $amount,
                'total_balance'       => (float) $updatedUser->wallet_balance,
                'earned_balance'      => (float) $updatedUser->earned_balance,
                'promotional_balance' => (float) $updatedUser->promotional_balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Recharge failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/wallet/withdraw
     * Withdraw from earned balance only
     * Body: { amount, description? }
     */
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount'      => 'required|numeric|min:1|max:100000',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user   = Auth::user();
        $amount = (float) $request->amount;

        // Fetch latest balances from DB
        $dbUser = DB::table('users')->where('id', $user->id)->first();

        if ((float) $dbUser->earned_balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient earned balance. Only earned balance can be withdrawn.',
                'earned_balance' => (float) $dbUser->earned_balance,
            ], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('users')->where('id', $user->id)->update([
                'wallet_balance' => DB::raw("wallet_balance - {$amount}"),
                'earned_balance' => DB::raw("earned_balance - {$amount}"),
                'updated_at'     => now(),
            ]);

            $txnId = DB::table('wallet_transactions')->insertGetId([
                'user_id'      => $user->id,
                'type'         => 'DEBIT',
                'amount'       => $amount,
                'category'     => 'WITHDRAWAL',
                'balance_type' => 'earned',
                'description'  => $request->description ?? 'Wallet Withdrawal',
                'status'       => 'SUCCESS',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::commit();

            $updatedUser = DB::table('users')->where('id', $user->id)->first();

            return response()->json([
                'success'             => true,
                'message'             => 'Withdrawal request submitted successfully',
                'transaction_id'      => $txnId,
                'amount'              => $amount,
                'total_balance'       => (float) $updatedUser->wallet_balance,
                'earned_balance'      => (float) $updatedUser->earned_balance,
                'promotional_balance' => (float) $updatedUser->promotional_balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
