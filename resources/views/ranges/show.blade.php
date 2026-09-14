@php
    $scenariosData = $scenarios->map(fn ($s) => [
        'id' => $s->id,
        'button_label' => $s->button_label,
        'grid' => $s->flatGrid(),
        'legend' => $s->legend,
        'stats' => $s->stats,
    ]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $study->name }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12"
         x-data="{
            scenarios: @js($scenariosData),
            activeId: {{ $defaultScenario?->id ?? 'null' }},
            get active() { return this.scenarios.find(s => s.id === this.activeId) ?? this.scenarios[0] ?? null; },
         }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            @if ($scenarios->isEmpty())
                <div class="pc-card p-6 text-sm text-gray-500">This study has no scenarios yet.</div>
            @else
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {{-- Grid + stats --}}
                    <div class="lg:col-span-2 space-y-4">
                        <div class="pc-card p-4 sm:p-6">
                            <p class="mb-3 text-sm font-semibold text-gray-700" x-text="active?.button_label"></p>
                            <div style="display:grid; grid-template-columns: repeat(13, minmax(0,1fr)); gap:2px;">
                                <template x-for="cell in (active?.grid || [])" :key="cell.hand">
                                    <div class="aspect-square flex items-center justify-center rounded-sm text-[9px] font-bold text-gray-800"
                                         :style="{ backgroundColor: cell.color || '#f8fafc' }"
                                         :title="cell.hand" x-text="cell.hand"></div>
                                </template>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3 text-xs" x-show="active?.legend?.length">
                                <template x-for="row in (active?.legend || [])" :key="row.key">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="h-3 w-3 rounded-sm" :style="{ backgroundColor: row.color }"></span>
                                        <span class="text-gray-600" x-text="row.label"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="pc-card p-4 sm:p-6" x-show="active?.stats?.badges?.length || active?.stats?.bars?.length">
                            <div class="flex flex-wrap items-center gap-3">
                                <template x-for="badge in (active?.stats?.badges || [])" :key="badge.label">
                                    <div :class="badge.highlight ? 'rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5' : ''">
                                        <span class="text-xs font-semibold text-gray-500" x-text="badge.label"></span>
                                        <span class="ml-1 text-sm font-bold text-gray-900" x-text="badge.value"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3 space-y-1.5" x-show="active?.stats?.bars?.length">
                                <template x-for="bar in (active?.stats?.bars || [])" :key="bar.label">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="w-6 shrink-0 font-semibold text-gray-600" x-text="bar.label"></span>
                                        <div class="h-2 flex-1 rounded-full bg-gray-100">
                                            <div class="h-2 rounded-full" :style="{ width: bar.pct + '%', backgroundColor: bar.color }"></div>
                                        </div>
                                        <span class="w-10 shrink-0 text-right text-gray-500" x-text="bar.pct + '%'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <div class="space-y-3">
                        <div class="pc-card p-4">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $study->name }}</h3>
                            @foreach ($navigation as $groupLabel => $rows)
                                <div class="mt-3 rounded-lg border border-gray-200 p-3">
                                    <h4 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $groupLabel }}</h4>
                                    <div class="mt-2 space-y-1.5">
                                        @foreach ($rows as $rowLabel => $rowScenarios)
                                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                                <span class="w-9 shrink-0 font-medium text-gray-500">{{ $rowLabel }}</span>
                                                @foreach ($rowScenarios as $scenario)
                                                    <button type="button" @click="activeId = {{ $scenario->id }}"
                                                            :class="activeId === {{ $scenario->id }} ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                                            class="rounded px-2 py-1 font-medium">
                                                        {{ $scenario->button_label }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
