<?php

namespace App\Http\Controllers;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\CreateChallengeOptions;
use Illuminate\Http\JsonResponse;

class AltchaController extends Controller
{
    public function challenge(): JsonResponse
    {
        $hmacKey = config('services.altcha.hmac_key');
        assert(is_string($hmacKey));

        $altcha = new Altcha(hmacSignatureSecret: $hmacKey);

        $challenge = $altcha->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2(),
            cost: 5000,
            counter: random_int(5000, 10000),
            expiresAt: time() + 600,
        ));

        return response()->json($challenge->toArray());
    }
}
