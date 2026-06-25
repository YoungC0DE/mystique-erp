<?php

namespace App\Http\Requests\Module;

use App\Http\Requests\Concerns\Paginated;
use App\Models\Module;
use App\Services\Module\FieldFilterApplier;
use Illuminate\Foundation\Http\FormRequest;

class KanbanBoardRequest extends FormRequest
{
    use Paginated;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->paginatedRules(), [
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'created_by' => ['sometimes', 'nullable', 'uuid'],
            'filters' => ['sometimes', 'nullable'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function boardFilters(Module $module): array
    {
        $module->loadMissing('kanbanStatuses');

        $filters = array_filter([
            'q' => $this->query('q'),
            'created_by' => $this->query('created_by'),
        ], fn ($value) => $value !== null && $value !== '');

        $fieldFilters = $this->parseFieldFilters($module);

        if ($fieldFilters !== []) {
            $filters['field_filters'] = $fieldFilters;
        }

        foreach ($module->kanbanStatuses as $status) {
            $pageKey = $status->slug.'_page';

            if ($this->has($pageKey)) {
                $filters[$pageKey] = $this->integer($pageKey, 1);
            }
        }

        return $filters;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseFieldFilters(Module $module): array
    {
        $raw = $this->query('filters');

        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        return app(FieldFilterApplier::class)->normalize($raw, $module);
    }
}
