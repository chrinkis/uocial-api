<?php

namespace App\Http\Requests;

use App\Enums\DataReportReason;
use App\Models\CourseOffering;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseOfferingReportRequest extends FormRequest
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
     * A report on an existing offering can be anything but "missing";
     * a report without an offering suggests a new one, so it can only be "missing".
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $offering = $this->route('offering');
        $isDuplicate = $this->input('reason') === DataReportReason::Duplicate->value;

        return [
            'reason' => [
                'required',
                $offering instanceof CourseOffering
                    ? Rule::enum(DataReportReason::class)->except([DataReportReason::Missing])
                    : Rule::enum(DataReportReason::class)->only([DataReportReason::Missing]),
            ],
            'comment' => [$isDuplicate ? 'nullable' : 'required', 'string'],
            'duplicate_of_id' => [
                $isDuplicate ? 'required' : 'prohibited',
                'integer',
                Rule::exists('course_offerings', 'id'),
                Rule::notIn($offering instanceof CourseOffering ? [$offering->getKey()] : []),
            ],
        ];
    }
}
