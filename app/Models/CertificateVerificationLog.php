<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateVerificationLog extends Model
{
    protected $fillable = [
        'verifier_name',
        'verifier_email',
        'verification_reason',
        'certificate_code',
        'verification_status',
        'certificate_id',
        'ip_address',
    ];
}
