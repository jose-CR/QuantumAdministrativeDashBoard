{{-- Detalle de facturas del widget de caja: número, fecha, cliente, facturado, cobrado y saldo. --}}
@props(['facturas' => []])

@if (empty($facturas))
    <p style="padding: .75rem 0; font-size: .875rem; color: var(--gray-500);">
        {{ __('ui.widgets.no_invoices') }}
    </p>
@else
    <div style="overflow-x: auto;">
        <table style="width: 100%; font-size: .85rem; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 1px solid var(--gray-300);">
                    <th style="padding: .45rem .3rem;">{{ __('resources.flow.inflow.cod_invoice') }}</th>
                    <th style="padding: .45rem .3rem;">{{ __('resources.flow.inflow.date') }}</th>
                    <th style="padding: .45rem .3rem;">{{ __('resources.flow.inflow.client') }}</th>
                    <th style="padding: .45rem .3rem; text-align: right;">{{ __('resources.flow.inflow.amount') }}</th>
                    <th style="padding: .45rem .3rem; text-align: right;">{{ __('resources.flow.inflow.amount_paid') }}</th>
                    <th style="padding: .45rem .3rem; text-align: right;">{{ __('resources.flow.inflow.receivable') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($facturas as $factura)
                    <tr style="border-bottom: 1px solid var(--gray-200);">
                        <td style="padding: .45rem .3rem; font-weight: 600;">{{ $factura['invoice'] }}</td>
                        <td style="padding: .45rem .3rem;">{{ $factura['date'] }}</td>
                        <td style="padding: .45rem .3rem;">{{ $factura['customer'] }}</td>
                        <td style="padding: .45rem .3rem; text-align: right;">${{ number_format($factura['amount'], 2) }}</td>
                        <td style="padding: .45rem .3rem; text-align: right;">${{ number_format($factura['paid'], 2) }}</td>
                        <td style="padding: .45rem .3rem; text-align: right; {{ $factura['balance'] > 0 ? 'font-weight: 600;' : '' }}">
                            ${{ number_format($factura['balance'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" style="padding: .5rem .3rem; text-align: left;">{{ __('ui.tables.total') }}</th>
                    <th style="padding: .5rem .3rem; text-align: right;">${{ number_format(collect($facturas)->sum('amount'), 2) }}</th>
                    <th style="padding: .5rem .3rem; text-align: right;">${{ number_format(collect($facturas)->sum('paid'), 2) }}</th>
                    <th style="padding: .5rem .3rem; text-align: right;">${{ number_format(collect($facturas)->sum('balance'), 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
