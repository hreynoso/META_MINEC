<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El acceso ya está protegido por el middleware 'auth'.
        return true;
    }

    /**
     * Normaliza los entregables: el formulario los envía como texto (uno por
     * línea) o como arreglo. Aquí se dejan siempre como arreglo limpio.
     */
    protected function prepareForValidation(): void
    {
        $deliverables = $this->input('deliverables');

        if (is_string($deliverables)) {
            $deliverables = collect(preg_split('/\r\n|\r|\n/', $deliverables))
                ->map(fn (string $line) => trim($line))
                ->filter()
                ->values()
                ->all();

            $this->merge(['deliverables' => $deliverables]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $projectId = $this->route('project')?->id;

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('projects', 'code')->ignore($projectId)],
            'name' => ['required', 'string', 'max:255'],
            'institution_id' => ['required', 'exists:institutions,id'],
            'presidential_goal_id' => ['nullable', 'exists:presidential_goals,id'],
            'status' => ['required', Rule::in(['planificado', 'en_ejecucion', 'en_riesgo', 'completado', 'retrasado'])],
            'risk_level' => ['required', Rule::in(['bajo', 'medio', 'alto'])],
            'budget' => ['required', 'numeric', 'min:0'],
            'executed' => ['nullable', 'numeric', 'min:0'],
            'physical_progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'source' => ['nullable', 'string', 'max:255'],
            'responsible' => ['required', 'string', 'max:255'],
            'beneficiaries' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'deliverables' => ['nullable', 'array'],
            'deliverables.*' => ['string', 'max:500'],
            'expected_impact' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre del proyecto',
            'institution_id' => 'institución',
            'presidential_goal_id' => 'meta presidencial',
            'status' => 'estado',
            'risk_level' => 'nivel de riesgo',
            'budget' => 'presupuesto',
            'executed' => 'ejecutado',
            'physical_progress' => 'avance físico',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
            'source' => 'fuente de financiamiento',
            'responsible' => 'responsable',
            'beneficiaries' => 'beneficiarios',
            'location' => 'ubicación',
        ];
    }
}
