@php
    $initialLegend = old('legend', $scenario->legend ?: []);
    $initialCombos = old('combos', $scenario->combos ?: []);
    $initialStats = old('stats', $scenario->stats ?: ['badges' => [], 'bars' => []]);
@endphp

<div x-data="{
        legend: @js($initialLegend),
        combos: @js($initialCombos),
        stats: @js($initialStats),
        selectedKey: null,
        init() { this.selectedKey = this.legend[0]?.key ?? null; },
        paint(hand) {
            if (this.selectedKey) { this.combos[hand] = this.selectedKey; }
            else { delete this.combos[hand]; }
        },
        colorFor(key) {
            if (!key) return '#ffffff';
            const row = this.legend.find(r => r.key === key);
            return row ? row.color : '#ffffff';
        },
        addLegendRow() { this.legend.push({ key: '', label: '', color: '#94a3b8' }); },
        removeLegendRow(i) { this.legend.splice(i, 1); },
        addBadge() { this.stats.badges.push({ label: '', value: '', highlight: false }); },
        removeBadge(i) { this.stats.badges.splice(i, 1); },
        addBar() { this.stats.bars.push({ label: '', pct: 0, color: '#94a3b8' }); },
        removeBar(i) { this.stats.bars.splice(i, 1); },
     }">
    @csrf

    {{-- Navigation placement --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">Group <span class="text-gray-400">(box title, e.g. "vs 3bet")</span></label>
            <input name="group_label" value="{{ old('group_label', $scenario->group_label) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <x-input-error :messages="$errors->get('group_label')" class="mt-1" />
            <input name="group_order" type="number" min="0" value="{{ old('group_order', $scenario->group_order ?? 0) }}"
                   class="mt-1 block w-24 rounded-md border-gray-300 shadow-sm text-xs" title="Group sort order">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Row <span class="text-gray-400">(e.g. "CO")</span></label>
            <input name="row_label" value="{{ old('row_label', $scenario->row_label) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <x-input-error :messages="$errors->get('row_label')" class="mt-1" />
            <input name="row_order" type="number" min="0" value="{{ old('row_order', $scenario->row_order ?? 0) }}"
                   class="mt-1 block w-24 rounded-md border-gray-300 shadow-sm text-xs" title="Row sort order">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Button <span class="text-gray-400">(e.g. "vs SBBB")</span></label>
            <input name="button_label" value="{{ old('button_label', $scenario->button_label) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <x-input-error :messages="$errors->get('button_label')" class="mt-1" />
            <input name="button_order" type="number" min="0" value="{{ old('button_order', $scenario->button_order ?? 0) }}"
                   class="mt-1 block w-24 rounded-md border-gray-300 shadow-sm text-xs" title="Button sort order">
        </div>
    </div>

    <label class="mt-4 inline-flex items-center gap-2 text-sm text-gray-700">
        <input type="hidden" name="is_default" value="0">
        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $scenario->is_default ?? false))
               class="rounded border-gray-300 text-indigo-600">
        Default scenario <span class="text-gray-400">— shown when the study page first opens</span>
    </label>

    {{-- Actions / legend --}}
    <div class="mt-8">
        <h4 class="text-sm font-semibold text-gray-900">Actions</h4>
        <p class="text-xs text-gray-500">The colors used to paint the grid below. Pick one, then click cells to paint them.</p>
        <x-input-error :messages="$errors->get('legend')" class="mt-1" />

        <div class="mt-2 flex flex-wrap items-center gap-2">
            <template x-for="(row, i) in legend" :key="i">
                <div class="flex items-center gap-1 rounded-md border border-gray-200 px-2 py-1"
                     :class="selectedKey === row.key && row.key ? 'ring-2 ring-indigo-500' : ''">
                    <button type="button" @click="selectedKey = row.key" class="h-5 w-5 rounded border border-gray-300" :style="{ backgroundColor: row.color }"></button>
                    <input type="text" x-model="row.key" :name="'legend[' + i + '][key]'" placeholder="key" class="w-16 rounded border-gray-300 text-xs">
                    <input type="text" x-model="row.label" :name="'legend[' + i + '][label]'" placeholder="Label" class="w-24 rounded border-gray-300 text-xs">
                    <input type="color" x-model="row.color" :name="'legend[' + i + '][color]'" class="h-6 w-8 rounded border-gray-300 p-0">
                    <button type="button" @click="removeLegendRow(i)" class="text-gray-400 hover:text-red-600">&times;</button>
                </div>
            </template>
            <button type="button" @click="addLegendRow()" class="text-xs text-indigo-600 hover:underline">+ Add action</button>
            <button type="button" @click="selectedKey = null"
                    class="rounded-md border border-dashed border-gray-300 px-2 py-1 text-xs text-gray-500"
                    :class="selectedKey === null ? 'ring-2 ring-indigo-500' : ''">
                Eraser
            </button>
        </div>
    </div>

    {{-- Grid --}}
    <div class="mt-6">
        <h4 class="text-sm font-semibold text-gray-900">Grid</h4>
        <p class="text-xs text-gray-500">Click a hand to paint it with the selected action. Right-click clears a cell.</p>

        <div style="display:grid; grid-template-columns: repeat(13, minmax(0,1fr)); gap:2px; max-width: 640px;" class="mt-2">
            @foreach (\App\Poker\PreflopGrid::rows() as $row)
                @foreach ($row as $hand)
                    <button type="button"
                            @click="paint('{{ $hand }}')"
                            @contextmenu.prevent="delete combos['{{ $hand }}']"
                            :style="{ backgroundColor: colorFor(combos['{{ $hand }}']) }"
                            class="aspect-square rounded-sm border border-gray-200 text-[13px] font-bold text-gray-800 hover:opacity-80"
                            title="{{ $hand }}">{{ $hand }}</button>
                    <input type="hidden" name="combos[{{ $hand }}]" x-model="combos['{{ $hand }}']">
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Stats --}}
    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <h4 class="text-sm font-semibold text-gray-900">Badges <span class="text-gray-400 font-normal">(e.g. VPIP, OR 2.2BBs)</span></h4>
            <template x-for="(b, i) in stats.badges" :key="i">
                <div class="mt-1 flex items-center gap-2">
                    <input type="text" x-model="b.label" :name="'stats[badges][' + i + '][label]'" placeholder="Label" class="w-28 rounded border-gray-300 text-xs">
                    <input type="text" x-model="b.value" :name="'stats[badges][' + i + '][value]'" placeholder="Value" class="w-20 rounded border-gray-300 text-xs">
                    <label class="flex items-center gap-1 text-xs text-gray-500">
                        <input type="hidden" :name="'stats[badges][' + i + '][highlight]'" value="0">
                        <input type="checkbox" x-model="b.highlight" :name="'stats[badges][' + i + '][highlight]'" value="1" class="rounded border-gray-300">
                        highlight
                    </label>
                    <button type="button" @click="removeBadge(i)" class="text-gray-400 hover:text-red-600">&times;</button>
                </div>
            </template>
            <button type="button" @click="addBadge()" class="mt-1 text-xs text-indigo-600 hover:underline">+ Add badge</button>
        </div>

        <div>
            <h4 class="text-sm font-semibold text-gray-900">Bars <span class="text-gray-400 font-normal">(e.g. Fold/Call/Raise %)</span></h4>
            <template x-for="(b, i) in stats.bars" :key="i">
                <div class="mt-1 flex items-center gap-2">
                    <input type="text" x-model="b.label" :name="'stats[bars][' + i + '][label]'" placeholder="Label" class="w-16 rounded border-gray-300 text-xs">
                    <input type="number" min="0" max="100" x-model.number="b.pct" :name="'stats[bars][' + i + '][pct]'" placeholder="%" class="w-16 rounded border-gray-300 text-xs">
                    <input type="color" x-model="b.color" :name="'stats[bars][' + i + '][color]'" class="h-6 w-8 rounded border-gray-300 p-0">
                    <button type="button" @click="removeBar(i)" class="text-gray-400 hover:text-red-600">&times;</button>
                </div>
            </template>
            <button type="button" @click="addBar()" class="mt-1 text-xs text-indigo-600 hover:underline">+ Add bar</button>
        </div>
    </div>

    <div class="mt-8 flex items-center gap-3">
        <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.range-studies.edit', $study) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
    </div>
</div>
