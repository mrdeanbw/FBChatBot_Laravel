<?php
namespace App\Transformers;

class InvoiceTransformer extends BaseTransformer
{

    public function transform($invoice)
    {
        return [
            'id'    => $invoice->id,
            'date'  => $invoice->date()->toFormattedDateString(),
            'total' => $invoice->total(),
        ];
    }
}