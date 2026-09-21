<?php

namespace App\Models;

use Database\Factories\PrivacyPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivacyPolicy extends Model
{
    /** @use HasFactory<PrivacyPolicyFactory> */
    use HasFactory;
}
