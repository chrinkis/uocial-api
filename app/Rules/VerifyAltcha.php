<?php

namespace App\Rules;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\ChallengeParameters;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\Solution;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VerifyAltcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hmacKey = config('services.altcha.hmac_key');
        assert(is_string($hmacKey));

        $payload = $this->parsePayload($value);

        if ($payload === null) {
            $fail('The :attribute is invalid.');

            return;
        }

        $altcha = new Altcha(hmacSignatureSecret: $hmacKey);
        $result = $altcha->verifySolution(new VerifySolutionOptions(
            payload: $payload,
            algorithm: new Pbkdf2(),
        ));

        if (! $result->verified) {
            $fail('The :attribute is invalid.');
        }
    }

    private function parsePayload(mixed $value): ?Payload
    {
        if (! is_string($value)) {
            return null;
        }

        $json = base64_decode($value, true);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (! is_array($data)) {
            return null;
        }

        if (! isset($data['challenge']['parameters']) || ! is_array($data['challenge']['parameters'])) {
            return null;
        }

        if (! isset($data['solution']['counter'], $data['solution']['derivedKey'])) {
            return null;
        }

        $params = ChallengeParameters::fromArray($data['challenge']['parameters']);
        $signature = isset($data['challenge']['signature']) && is_string($data['challenge']['signature'])
            ? $data['challenge']['signature']
            : null;

        $challenge = new Challenge($params, $signature);
        $solution = new Solution(
            counter: (int) $data['solution']['counter'],
            derivedKey: (string) $data['solution']['derivedKey'],
        );

        return new Payload($challenge, $solution);
    }
}
