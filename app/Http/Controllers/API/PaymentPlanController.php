<?php namespace App\Http\Controllers\API;

use Common\Models\PaymentPlan;
use Illuminate\Http\Request;
use App\Transformers\PaymentPlanTransformer;

class PaymentPlanController extends APIController
{

    /**
     * List of payment plans.
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $paymentPlans = $this->filterPaymentPlans($request);
        
        return $this->collectionResponse($paymentPlans );
    }

    /** @return PaymentPlanTransformer */
    protected function transformer()
    {
        return new PaymentPlanTransformer();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    private function filterPaymentPlans(Request $request)
    {
        $query = PaymentPlan::orderBy('subscribers');

        if ($name = $request->get('name')) {
            $query->whereName($name);
        }

        $paymentPlans = $query->get();

        return $paymentPlans;
    }
}
