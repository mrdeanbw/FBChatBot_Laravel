<?php

namespace App\Http\Controllers\API;

use App\Models\PaymentPlan;
use App\Transformers\PaymentPlanTransformer;
use Illuminate\Http\Request;

class PaymentPlanController extends APIController
{

    /**
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $query = PaymentPlan::orderBy('subscribers');

        if ($name = $request->get('name')) {
            $query->whereName($name);
        }

        return $this->collectionResponse($query->get());
    }

    /** @return PaymentPlanTransformer */
    protected function transformer()
    {
        return new PaymentPlanTransformer();
    }
}
