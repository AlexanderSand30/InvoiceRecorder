<?php

namespace App\Services;

use App\Events\Vouchers\VouchersCreated;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;

class VoucherService
{
    public function getVouchers(
        int $page,
        int $paginate,
        ?string $serie,
        ?string $number,
        ?string $invoiceType,
        ?string $currency,
        string $starDate,
        string $endDate
    ): LengthAwarePaginator {
        return Voucher::with(['lines', 'user'])
            ->when($serie, fn($q, $serie) => $q->where('invoice_series', "$serie"))
            ->when($number, fn($q, $number) => $q->where('invoice_number', 'like', "$number%"))
            ->when($invoiceType, fn($q, $invoiceType) => $q->where('invoice_type', "$invoiceType%"))
            ->when($currency, fn($q, $currency) => $q->where('currency_code', "$currency%"))
            ->whereDate('created_at', '>=', $starDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where('user_id', Auth::user()->id)
            ->paginate(perPage: $paginate, page: $page);
    }

    /**
     * @param string[] $xmlContents
     * @param User $user
     * @return Voucher[]
     */
    public function storeVouchersFromXmlContents(array $xmlContents, User $user)
    {
        $failed = [];
        $success = [];

        foreach ($xmlContents as $xmlContent) {
            try {
                // throw new \Exception("Error simulado: No se pudo registrar el comprobante");
                $voucher = $this->storeVoucherFromXmlContent($xmlContent, $user);
                $success[] = $voucher;
            } catch (\Exception $e) {
                $xml = new SimpleXMLElement($xmlContent);
                $serieAndNumber = (string) $xml->xpath('//cbc:ID')[0];

                $failed[] = [
                    'factura' => "Factura:" . $serieAndNumber,
                    'error' => $e->getMessage(),
                ];
            }
        }

        VouchersCreated::dispatch($success, $failed, $user);
    }

    public function storeVoucherFromXmlContent(string $xmlContent, User $user): Voucher
    {
        $xml = new SimpleXMLElement($xmlContent);

        $serieAndNumber = (string) $xml->xpath('//cbc:ID')[0];
        $partes = explode("-", $serieAndNumber);

        $invoiceSeries = $partes[0];
        $invoiceNumber = str_pad($partes[1], 8, "0", STR_PAD_LEFT);
        $invoiceType = (string) $xml->xpath('//cbc:InvoiceTypeCode')[0];
        $currencyCode = (string) $xml->xpath('//cbc:DocumentCurrencyCode')[0];

        $issuerName = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name')[0];
        $issuerDocumentType = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID/@schemeID')[0];
        $issuerDocumentNumber = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID')[0];

        $receiverName = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName')[0];
        $receiverDocumentType = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID/@schemeID')[0];
        $receiverDocumentNumber = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID')[0];

        $totalAmount = (string) $xml->xpath('//cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount')[0];

        $voucher = new Voucher([
            'invoice_series' => $invoiceSeries,
            'invoice_number' => $invoiceNumber,
            'invoice_type' => $invoiceType,
            'currency_code' => $currencyCode,
            'issuer_name' => $issuerName,
            'issuer_document_type' => $issuerDocumentType,
            'issuer_document_number' => $issuerDocumentNumber,
            'receiver_name' => $receiverName,
            'receiver_document_type' => $receiverDocumentType,
            'receiver_document_number' => $receiverDocumentNumber,
            'total_amount' => $totalAmount,
            'xml_content' => $xmlContent,
            'user_id' => $user->id,
        ]);
        $voucher->save();

        foreach ($xml->xpath('//cac:InvoiceLine') as $invoiceLine) {
            $name = (string) $invoiceLine->xpath('cac:Item/cbc:Description')[0];
            $quantity = (float) $invoiceLine->xpath('cbc:InvoicedQuantity')[0];
            $unitPrice = (float) $invoiceLine->xpath('cac:Price/cbc:PriceAmount')[0];

            $voucherLine = new VoucherLine([
                'name' => $name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'voucher_id' => $voucher->id,
            ]);

            $voucherLine->save();
        }

        return $voucher;
    }

    public function getVouchersAmounts()
    {
        return Voucher::select('currency_code', DB::raw('SUM(voucher_lines.unit_price) as total'))
            ->join('voucher_lines', 'vouchers.id', 'voucher_lines.voucher_id')
            ->where('user_id', Auth::user()->id)
            ->whereIn('currency_code', ['USD', 'PEN'])
            ->groupBy('currency_code')
            ->get();
    }

    public function deleteVoucher($id)
    {
        $voucher = Voucher::find($id);
        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        if ($voucher->user_id !== Auth::user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $voucher->delete();
        return response()->json(['message' => 'Voucher deleted successfully'], 200);
    }

    public function regularizeVouchers(Collection $vouchers)
    {
        foreach ($vouchers as $key => $voucher) {
            $xmlContent = $voucher->xml_content;
            if (empty($xmlContent))  continue;

            if (strpos($xmlContent, '<?xml') !== 0) continue;

            $xml = new SimpleXMLElement($xmlContent);

            $serieAndNumber = (string) $xml->xpath('//cbc:ID')[0];
            $partes = explode("-", $serieAndNumber);

            $invoiceSeries = $partes[0];
            $invoiceNumber = str_pad($partes[1], 8, "0", STR_PAD_LEFT);
            $invoiceType = (string) $xml->xpath('//cbc:InvoiceTypeCode')[0];
            $currencyCode = (string) $xml->xpath('//cbc:DocumentCurrencyCode')[0];

            $issuerName = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name')[0];
            $issuerDocumentType = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID/@schemeID')[0];
            $issuerDocumentNumber = (string) $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID')[0];

            $receiverName = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName')[0];
            $receiverDocumentType = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID/@schemeID')[0];
            $receiverDocumentNumber = (string) $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID')[0];

            $totalAmount = (string) $xml->xpath('//cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount')[0];

            $voucher->invoice_series = $invoiceSeries;
            $voucher->invoice_number = $invoiceNumber;
            $voucher->invoice_type = $invoiceType;
            $voucher->currency_code = $currencyCode;
            $voucher->issuer_name = $issuerName;
            $voucher->issuer_document_type = $issuerDocumentType;
            $voucher->issuer_document_number = $issuerDocumentNumber;
            $voucher->receiver_name = $receiverName;
            $voucher->receiver_document_type = $receiverDocumentType;
            $voucher->receiver_document_number = $receiverDocumentNumber;
            $voucher->total_amount = $totalAmount;
            $voucher->updated_at = now();
            $voucher->save();

            $voucher->lines()->delete();

            foreach ($xml->xpath('//cac:InvoiceLine') as $invoiceLine) {
                $name = (string) $invoiceLine->xpath('cac:Item/cbc:Description')[0];
                $quantity = (float) $invoiceLine->xpath('cbc:InvoicedQuantity')[0];
                $unitPrice = (float) $invoiceLine->xpath('cac:Price/cbc:PriceAmount')[0];

                $voucherLine = new VoucherLine([
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'voucher_id' => $voucher->id,
                ]);

                $voucherLine->save();
            }
        }
    }
}
