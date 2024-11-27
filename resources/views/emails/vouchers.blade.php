<!DOCTYPE html>
<html>

<head>
    <title>Comprobantes Subidos</title>
</head>

<body>
    <h1>Estimado {{ $user->name }},</h1>
    @if ($success)
        <p>Hemos recibido tus comprobantes registrados exitosamente:</p>
        @foreach ($success as $voucher)
            <ul>
                <li>Nombre del Emisor: {{ $voucher->issuer_name }}</li>
                <li>Tipo de Documento del Emisor: {{ $voucher->issuer_document_type }}</li>
                <li>Número de Documento del Emisor: {{ $voucher->issuer_document_number }}</li>
                <li>Nombre del Receptor: {{ $voucher->receiver_name }}</li>
                <li>Tipo de Documento del Receptor: {{ $voucher->receiver_document_type }}</li>
                <li>Número de Documento del Receptor: {{ $voucher->receiver_document_number }}</li>
                <li>Monto Total: {{ $voucher->total_amount }}</li>
            </ul>
        @endforeach
    @endif

    @if ($failed)
        <p>Comprobantes que no se pudieron registrar:</p>
        @foreach ($failed as $voucher)
            <ul>
                <li>{{ $voucher['factura'] }}</li>
                <p>Message error: {{ $voucher['error'] }}</p>
            </ul>
        @endforeach
    @endif
    <p>¡Gracias por usar nuestro servicio!</p>
</body>

</html>
