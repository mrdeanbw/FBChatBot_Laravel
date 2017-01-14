<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\APIController;
use App\Models\Page;
use App\Transformers\BaseTransformer;
use App\Transformers\InvoiceTransformer;

class InvoiceController extends APIController
{

    public function index()
    {
        $page = $this->page();
        
        if (! $page->subscriptions()->exists()) {
            return [];
        }

        return $this->collectionResponse($page->invoices());
    }

    public function show($id)
    {
        return $this->itemResponse($this->page()->findInvoiceOrFail($id));
    }

    protected function transformer()
    {
        return new InvoiceTransformer();
    }
}
