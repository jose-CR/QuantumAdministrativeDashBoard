<x-filament-panels::page>
    <div class="q-apps-grid">
        @foreach ($this->getApps() as $app)
            <section class="q-app-card">
                <a href="{{ $app['url'] }}" class="q-app-card__header">
                    <span class="q-app-card__icon" style="--q-app-color: {{ $app['color'] }}">
                        <x-filament::icon :icon="$app['icon']" class="h-6 w-6" />
                    </span>

                    <span class="q-app-card__titles">
                        <span class="q-app-card__name">{{ $app['label'] }}</span>
                        @if (filled($app['description']))
                            <span class="q-app-card__description">{{ $app['description'] }}</span>
                        @endif
                    </span>
                </a>

                @if ($app['items'] !== [])
                    <ul class="q-app-card__items">
                        @foreach ($app['items'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}" class="q-app-card__item">
                                    <span class="truncate">{{ $item['label'] }}</span>

                                    <x-filament::icon
                                        icon="heroicon-m-chevron-right"
                                        class="q-app-card__chevron h-4 w-4"
                                    />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
