@props(['clientes' => [], 'titulo' => null])

{{-- Detalle de clientes de una fila del informe: nombre y monto, sin Tailwind nuevo. --}}
@if (empty($clientes))
    <p style="padding: .5rem 0; color: var(--gray-500); font-size: .875rem;">
        {{ __('ui.widgets.no_customers') }}
    </p>
@else
    <table style="width: 100%; font-size: .875rem; border-collapse: collapse;">
        <tbody>
            @foreach ($clientes as $cliente)
                <tr style="border-bottom: 1px solid var(--gray-200);">
                    <td style="padding: .5rem .25rem;">{{ $cliente['name'] }}</td>
                    <td style="padding: .5rem .25rem; text-align: right; font-weight: 600;">
                        ${{ number_format($cliente['amount'], 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th style="padding: .5rem .25rem; text-align: left;">{{ __('ui.widgets.amount') }}</th>
                <th style="padding: .5rem .25rem; text-align: right;">
                    ${{ number_format(collect($clientes)->sum('amount'), 2) }}
                </th>
            </tr>
        </tfoot>
    </table>
@endif
