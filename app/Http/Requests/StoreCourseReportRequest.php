<?php

namespace App\Http\Requests;

use App\Enums\DataReportReason;
use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseReportRequest extends FormRequest
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
     * A report on an existing course can be anything but "missing";
     * a report without a course suggests a new one, so it can only be "missing".
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $course = $this->route('course');
        $isDuplicate = $this->input('reason') === DataReportReason::Duplicate->value;

        return [
            'reason' => [
                'required',
                $course instanceof Course
                    ? Rule::enum(DataReportReason::class)->except([DataReportReason::Missing])
                    : Rule::enum(DataReportReason::class)->only([DataReportReason::Missing]),
            ],
            'comment' => [$isDuplicate ? 'nullable' : 'required', 'string'],
            'duplicate_of_id' => [
                $isDuplicate ? 'required' : 'prohibited',
                'integer',
                Rule::exists('courses', 'id'),
                Rule::notIn($course instanceof Course ? [$course->getKey()] : []),
            ],
        ];
    }
}
