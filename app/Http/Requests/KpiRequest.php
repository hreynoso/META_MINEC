<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso ya está protegido por el middleware 'auth'.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'strategic' => $this->boolean('strategic'),
            'sort' => $this->input('sort', 0) === '' ? 0 : $this->input('sort', 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $kpiId = $this->route('kpi')?->id;

        return [
            'key' => ['required', 'string', 'max:255', Rule::unique('kpis', 'key')->ignore($kpiId)],
            'label' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'target' => ['nullable', 'numeric', 'min:0'],
            'trend' => ['required', Rule::in(['up', 'down', 'flat'])],
            'strategic' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];
    }

    // Los nombres de campo (attributes) se resuelven por idioma desde
    // lang/{es,en}/validation.php para respetar el idioma del sistema.
}
