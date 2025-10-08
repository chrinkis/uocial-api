<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use ZxcvbnPhp\Zxcvbn;

class Password implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        assert(is_string($value));

        $zxcvbn = new Zxcvbn;
        $result = $zxcvbn->passwordStrength($value);

        if ($result['score'] === 4) {
            return;
        }

        $message = null;

        if (Arr::has($result, 'feedback.warning')) {
            $feedback = $result['feedback'];
            assert(is_array($feedback));

            $warning = $feedback['warning'];
            assert(is_string($warning));

            $fail($warning.'.');
        }

        if (Arr::has($result, 'feedback.suggestions')) {
            $feedback = $result['feedback'];
            assert(is_array($feedback));

            $suggestions = $feedback['suggestions'];
            assert(is_array($suggestions));

            $suggestion = $suggestions[0];
            assert(is_string($suggestion));

            $fail($suggestion);
        }

        $fail('Password is weak.');
    }
}
