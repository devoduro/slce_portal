{{--
    Repeatable criteria rows, shared by the create and edit screens.

    Expects: $criteria (existing rows, possibly empty) and $markedIds (criterion ids that already
    have marks against them and so cannot be removed). Alpine keeps the running total live while
    the admin types, since "does this sheet add up to 100?" is the question being answered.
--}}
@php
    $rows = old('criteria', $criteria->map(fn ($c) => [
        'id' => $c->id,
        'label' => $c->label,
        'max_mark' => rtrim(rtrim(number_format((float) $c->max_mark, 2, '.', ''), '0'), '.'),
    ])->all());

    if (empty($rows)) {
        $rows = [['id' => null, 'label' => '', 'max_mark' => '']];
    }

    $markedIds = $markedIds ?? [];
@endphp

<div x-data="criteriaBuilder({{ Js::from(array_values($rows)) }}, {{ Js::from(array_map('strval', $markedIds)) }})">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Criterion Label <span class="text-red-500">*</span>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-44">
                        Maximum Mark <span class="text-red-500">*</span>
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="(row, index) in rows" :key="row.key">
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-400" x-text="index + 1"></td>
                        <td class="px-4 py-3">
                            <input type="hidden" :name="`criteria[${index}][id]`" :value="row.id ?? ''">
                            <input type="text"
                                   :name="`criteria[${index}][label]`"
                                   x-model="row.label"
                                   placeholder="e.g. Lesson Delivery"
                                   maxlength="255"
                                   class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3">
                            <input type="number"
                                   :name="`criteria[${index}][max_mark]`"
                                   x-model="row.max_mark"
                                   step="0.01" min="0.01" max="999.99"
                                   placeholder="0.00"
                                   class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </td>
                        <td class="px-4 py-3 text-right">
                            <template x-if="isLocked(row)">
                                <span class="text-xs text-gray-400" title="Supervisors have already entered marks for this criterion">
                                    <i class="fas fa-lock"></i> In use
                                </span>
                            </template>
                            <template x-if="!isLocked(row)">
                                <button type="button" @click="remove(index)"
                                        class="text-red-600 hover:text-red-800 text-sm" title="Remove this criterion">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="2" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Total marks for this level</td>
                    <td class="px-4 py-3 text-sm font-bold text-gray-900" x-text="total.toFixed(2)"></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mt-4 flex items-center justify-between">
        <button type="button" @click="add()"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-plus"></i> Add Criterion
        </button>

        <p class="text-sm" :class="total == 100 ? 'text-green-600' : 'text-gray-500'">
            <template x-if="total == 100"><span><i class="fas fa-check-circle"></i> Adds up to 100.</span></template>
            <template x-if="total != 100">
                <span>Sheet totals <span class="font-semibold" x-text="total.toFixed(2)"></span> marks — that is fine if it is what you intend.</span>
            </template>
        </p>
    </div>
</div>

@push('scripts')
<script>
    function criteriaBuilder(initialRows, markedIds) {
        let nextKey = 0;

        return {
            markedIds: markedIds.map(String),
            rows: initialRows.map(row => ({
                key: nextKey++,
                id: row.id ?? null,
                label: row.label ?? '',
                max_mark: row.max_mark ?? '',
            })),

            get total() {
                return this.rows.reduce((sum, row) => sum + (parseFloat(row.max_mark) || 0), 0);
            },

            // A criterion supervisors have already marked against cannot be removed here - the
            // marks are keyed to it, so dropping the row would discard them.
            isLocked(row) {
                return row.id !== null && this.markedIds.includes(String(row.id));
            },

            add() {
                this.rows.push({ key: nextKey++, id: null, label: '', max_mark: '' });
            },

            remove(index) {
                if (this.isLocked(this.rows[index])) {
                    return;
                }

                this.rows.splice(index, 1);

                if (this.rows.length === 0) {
                    this.add();
                }
            },
        };
    }
</script>
@endpush
