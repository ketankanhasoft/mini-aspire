<?php

namespace App\Http\Controllers;

use JWTAuth;
use DB;
use App\Models\Loan;
use App\Models\LoanInstallmentPayment;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = JWTAuth::parseToken()->authenticate();
    }

    /**
     * Check that user is authenticated with JWT or Not
     *
     * @return void
     */
    public function getAuthenticatedUser()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());
        }

        // the token is valid and we have found the user via the sub claim
        return response()->json(compact('user'));
    }

    /**
     * Allows user to apply for the Loan
     *
     * @param Request $request
     * @return void
     */
    public function loanApply(Request $request)
    {
        $this->validate($request, [
            'loan_amount' => 'required',
            'term' => 'required|integer'
        ]);

        $loan = new Loan();
        $loan->loan_amount = $request->loan_amount;
        $loan->term = $request->term;

        if ($this->user->loans()->save($loan))
            return response()->json([
                'success' => true,
                'loan' => $loan
            ]);
        else
            return response()->json([
                'success' => false,
                'message' => 'Sorry, loan could not be added'
            ], 500);
    }

    /**
     * If user is logged in as Admin, then allow Admin to approve the loan applied by user
     *
     * @param Request $request
     * @return void
     */
    public function loanApprove(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $user_id = $authenticatedUser->original['user']['id'];

        $loan_id = $request->loan_id;

        // Check if the logged in user is Admin or not
        if ($user_id == 1) {
            $loan = $this->user->loans()->find($loan_id);

            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, loan with id ' . $loan_id . ' cannot be found'
                ], 400);
            }

            $approved = $loan->fill(array('is_approved' => '1'))->save();

            if ($approved) {
                return response()->json([
                    'success' => true,
                    'message' => 'Loan has been approved'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, loan could not be approved'
                ], 500);
            }
        }
    }

    /**
     * Pay the installment calculated by the term and the loan ammount and then allow user to pay the installment
     *
     * @param Request $request
     * @return void
     */
    public function payInstallment(Request $request)
    {
        $loan_id = $request->loan_id;
        $amount = $request->amount;

        $loan = $this->user->loans()->find($loan_id);
        if($loan->is_approved == 1)
        {
            $minimum_installment = $loan->loan_amount / $loan->term;

            $where = array('loan_id' => $loan_id);

            $totalPaidPayment = LoanInstallmentPayment::where($where)->sum('amount');
            $remainingAmount =  $loan->loan_amount - $totalPaidPayment;

            if ($remainingAmount > 0) {
                if ( $remainingAmount >= $amount && $minimum_installment <= $amount ) {
                    $installment = new LoanInstallmentPayment;
                    $installment->loan_id = $request->loan_id;
                    $installment->amount = $request->amount;
                    $installment->save();
                    return response()->json([
                        'success' => true,
                        'message' => 'Successfully paid the installment'
                    ], 200);
                } else if($amount == $remainingAmount){
                    $installment = new LoanInstallmentPayment;
                    $installment->loan_id = $request->loan_id;
                    $installment->amount = $request->amount;
                    $installment->save();
                    return response()->json([
                        'success' => true,
                        'message' => 'Successfully paid the installment'
                    ], 200);                
                } else if ($amount < $minimum_installment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Minimum amount should be ' . ($totalPaidPayment == 0 ? $minimum_installment : $remainingAmount)
                    ], 200);
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'Amount should be ' . ($totalPaidPayment == 0 ? $minimum_installment : $remainingAmount)
                    ], 200);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You already paid all the installments.'
                ], 200);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Your loan is still not approved.'
            ], 200);
        }
    }

    /**
     * Provides the list of loans
     *
     * @return void
     */
    
    public function loansList()
    {
        $loan = $this->user->loans()->get();
        return response()->json([
            'success' => true,
            'data' => $loan
        ], 200);
    }
}
