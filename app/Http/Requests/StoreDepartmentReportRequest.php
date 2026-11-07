<?php

namespace App\Http\Requests;

use App\Enums\DataReportReason;
use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * A report on an existing department can be anything but "missing";
     * a report without a department suggests a new one, so it can only be "missing".
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $department = $this->route('department');
        $isDuplicate = $this->input('reason') === DataReportReason::Duplicate->value;

        return [
            'reason' => [
                'required',
                $department instanceof Department
                    ? Rule::enum(DataReportReason::class)->except([DataReportReason::Missing])
                    : Rule::enum(DataReportReason::class)->only([DataReportReason::Missing]),
            ],
            'comment' => [$isDuplicate ? 'nullable' : 'required', 'string'],
            'duplicate_of_id' => [
                $isDuplicate ? 'required' : 'prohibited',
                'integer',
                Rule::exists('departments', 'id'),
                Rule::notIn($department instanceof Department ? [$department->getKey()] : []),
            ],
        ];
    }
}
