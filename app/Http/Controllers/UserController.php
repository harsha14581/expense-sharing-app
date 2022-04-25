<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Expense;
use App\Models\UserBalance;

class UserController extends Controller
{
    function create(Request $request)
    {
        $user_details  = $request->validate([
            'name' => 'required|string',
            'mobile_number' => 'required|string',
            'email' => 'required|string|email'
        ]);

        $user_details['user_id'] = uniqid();
        $user = User::create($user_details);
        
        if(empty($user)){
            return response()->json([
                'error' => true,
                'code' => 500,
                'message' => 'Something went wrong while creating user. please try again later.',
                'data' => []
            ], 500);
        }

        return response()->json([
            'error' => false,
            'code' => 200,
            'message' => 'Successfully created user',
            'data' => []
        ], 200);
    }

    function createExpense(Request $request)
    {
        $user_details = $request->validate([
            'user_id' => 'required|string',
            'amount' => 'required',
            'split_type' => 'required|string'
        ]);


        switch($user_details['split_type']){
            case 'EQUAL':
                $active_users =  User::where('deleted_at', null)->get();
               $user_count = count($active_users);
               $equal_amount_balance =  round($user_details['amount'] / $user_count, 2);
               foreach($active_users as $user){
                   $user_balance =  UserBalance::where('user_id', $user->user_id)->first();
                   if(empty($user_balance)){
                        UserBalance::create([
                            'user_id' => $user->user_id,
                            'balance' => $equal_amount_balance
                        ]);
                   }else{
                        $existing_balance = $user_balance['balance'] + $equal_amount_balance;
                        $user_balance->update([
                            'balance' => $existing_balance
                        ]);
                   }
                 
               }

               return response()->json([
                'error' => false,
                'code' => 200,
                'message' => 'Successfully updated.',
                'data' => []
            ], 200);   
            
            case 'PERCENT':

                $request->validate([
                    'users' => 'array',
                    'users.*.user_id' => 'required|exists:users,user_id',
                    'users.*.percentage' => 'required|string'
                ]);

                $user_with_percentages = $request['users'];

                $sum  = 0;
                foreach($user_with_percentages as $user_with_percentage){
                   $sum = $sum + $user_with_percentage['percentage'];
                }
                
                if($sum > 100 || $sum < 100){
                        return response()->json([
                            'error' => true,
                            'code' => 500,
                            'message' => 'Percentage share cannot be more or less than 100',
                            'data' => []
                        ], 500);
                }

                foreach($user_with_percentages  as $user_percentage){
                        $user_balance =  UserBalance::where('user_id', $user_percentage['user_id'])->first();
                        if(empty($user_balance)){
                                UserBalance::create([
                                    'user_id' => $user_percentage['user_id'],
                                    'balance' => round(($user_details['amount'] * $user_percentage['percentage']) / 100, 2),
                                ]);
                        }else{
                            $percentage_amount = round(($user_details['amount'] * $user_percentage['percentage']) / 100, 2);
                                $existing_balance = $user_balance['balance'] + $percentage_amount;
                                $user_balance->update([
                                    'balance' => $existing_balance
                                ]);
                        }
                }

                return response()->json([
                    'error' => false,
                    'code' => 200,
                    'message' => 'Successfully updated.',
                    'data' => []
                ], 200);   
            
            case 'EXACT':

                $request->validate([
                    'users' => 'array',
                    'users.*.user_id' => 'required|exists:users,user_id',
                    'users.*.amount' => 'required|string'
                ]);

                $user_with_exact_amounts = $request['users'];

                $sum  = 0;
                
                foreach($user_with_exact_amounts as $user_with_exact_amount){
                   $sum = $sum + $user_with_exact_amount['amount'];
                }
                
                if($user_details['amount'] != $sum){
                        return response()->json([
                            'error' => true,
                            'code' => 500,
                            'message' => 'payment amount should be exactly divided.',
                            'data' => []
                        ], 500);   
                }

                foreach($user_with_exact_amounts  as $user_with_exact_amount){
                        $user_balance =  UserBalance::where('user_id', $user_with_exact_amount['user_id'])->first();
                        if(empty($user_balance)){
                                UserBalance::create([
                                    'user_id' => $user_with_exact_amount['user_id'],
                                    'balance' =>  $user_with_exact_amount['amount']
                                ]);
                        }else{
                                $existing_balance = $user_balance['balance'] + $user_with_exact_amount['amount'];
                                $user_balance->update([
                                    'balance' => $existing_balance
                                ]);
                        }
                }

                return response()->json([
                    'error' => false,
                    'code' => 200,
                    'message' => 'Successfully updated.',
                    'data' => []
                ], 200);   

            default:
                return response()->json([
                    'error' => true,
                    'code' => 422,
                    'message' => 'Please provide splity type EXACT, PERCENT or EQUAL',
                    'data' => []
                ], 422);   

        }
    }

    function getBalances()
    {
        $user_balances = User::with('balance')->where('deleted_at', null)->get();
        return response()->json([
            'error' => false,
            'code' => 200,
            'message' => 'users',
            'data' => $user_balances
        ], 200);   

    }
}
