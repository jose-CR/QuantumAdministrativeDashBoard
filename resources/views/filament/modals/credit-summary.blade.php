@php
    use App\Support\CreditStatus;

    $cuotas = $credit->installments()->get();

    $pagadas = $cuotas->where('status', 'paid')->count();
    $pendientes = $cuotas->where('status', 'pending')->count();
    $atrasadas = $cuotas->where('status', 'late')->count();
    $refinanciadas = $cuotas->where('status', 'refinanced')->count();

    $periodicidad = match ($credit->periodicity) {
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'yearly' => 'Anual',
        default => (string) $credit->periodicity,
    };

    $interes = ($credit->interest_type ?? 'auto') === 'fixed'
        ? 'Fijo ' . (float) $credit->interest_rate . '% por cuota'
        : 'Calculado ' . (float) $credit->interest_rate . '% por cuota';

    $moneda = fn ($valor): string => '$' . number_format((float) $valor, 2);

    $montos = [
        'Monto inicial' => $moneda($credit->initial_amount),
        'Pago inicial' => $moneda($credit->down_payment),
        'Financiado' => $moneda($credit->financed_amount),
        'Monto de cuota' => $moneda($credit->installment_amount),
        'Intereses' => $moneda($credit->total_interest),
        'Total a pagar' => $moneda($credit->total_amount),
    ];

    $colorEstado = match ($credit->status) {
        'active' => 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/40',
        'completed' => 'text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/40',
        'refinanced' => 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/40',
        'cancelled' => 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/40',
        default => 'text-gray-700 bg-gray-100 dark:text-gray-300 dark:bg-gray-800',
    };
@endphp

<div class="space-y-5">
    {{-- Encabezado: estado, fecha y periodicidad --}}
    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $colorEstado }}">
            {{ CreditStatus::label($credit->status) }}
        </span>

        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ $credit->start_date?->format('d/m/Y') }} · {{ $periodicidad }} · {{ $credit->installments }} cuotas
        </span>

        @if ($credit->refinanced_from_id)
            <span class="text-sm text-gray-500 dark:text-gray-400">
                · Refinanciado del #{{ $credit->refinanced_from_id }}
            </span>
        @endif
    </div>

    {{-- Lo que queda por cobrar, primero y en grande --}}
    <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
        <div class="text-[0.7rem] uppercase tracking-wide text-red-700/70 dark:text-red-300/70">
            Saldo pendiente
        </div>
        <div class="text-2xl font-bold text-red-700 dark:text-red-300">
            {{ $moneda($credit->pending_balance) }}
        </div>
        <div class="mt-1 text-xs text-red-700/70 dark:text-red-300/70">{{ $interes }}</div>
    </div>

    {{-- Montos --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
        @foreach ($montos as $etiqueta => $valor)
            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                <div class="text-[0.7rem] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $etiqueta }}
                </div>
                <div class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ $valor }}</div>
            </div>
        @endforeach
    </div>

    {{-- Estado de las cuotas --}}
    <div>
        <div class="mb-2 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Cuotas</div>

        <div class="flex flex-wrap gap-2 text-sm">
            <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">{{ $pagadas }} pagadas</span>
            <span class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">{{ $pendientes }} pendientes</span>

            @if ($atrasadas > 0)
                <span class="rounded bg-red-100 px-2 py-1 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                    {{ $atrasadas }} atrasadas
                </span>
            @endif

            @if ($refinanciadas > 0)
                <span class="rounded bg-amber-100 px-2 py-1 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                    {{ $refinanciadas }} refinanciadas
                </span>
            @endif
        </div>
    </div>

    {{-- Bienes --}}
    <div>
        <div class="mb-1 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Bienes del crédito
        </div>

        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($credit->items as $item)
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="text-gray-700 dark:text-gray-200">{{ $item->display_name }}</span>
                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ $moneda($item->total) }}</span>
                </li>
            @empty
                <li class="py-2 text-sm text-gray-400">Sin bienes registrados.</li>
            @endforelse
        </ul>
    </div>
</div>
