<?php namespace App\Http\Controllers\API;

use App\Transformers\InvoiceTransformer;

class InvoiceController extends APIController
{

    /**
     * Return the invoices associated with a page.
     * @return \Dingo\Api\Http\Response
     */
    public function index()
    {
        $page = $this->page();

        /**
         * If the page doesn't have any subscriptions associated,
         * then no invoices are associated with it.
         */
        if (! $page->subscriptions()->exists()) {
            return $this->arrayResponse([]);
        }

        return $this->collectionResponse($page->invoices());
    }

    /**
     * Return details of an invoice.
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function show($id)
    {
        return $this->itemResponse($this->page()->findInvoiceOrFail($id));
    }

    protected function transformer()
    {
        return new InvoiceTransformer();
    }
}
