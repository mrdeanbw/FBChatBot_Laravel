<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceFileController extends Controller
{

    /**
     * @param         $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download($id, Request $request)
    {
        return $this->page($request)->downloadInvoice($id, [
            'vendor'  => 'Mr.Reply',
            'product' => 'Pro Plan Monthly Subscription',
        ]);

    }
}
