<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Personnel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personnel';

    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'mobile_number',
        'email',
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Rules for validation
    public static function rules(): array
    {
        return [
            'prefix' => [
                'required',
                'string',
                'min:2',
                'max:20',
                'regex:/^[\p{L}.]+[\p{L}.\s]*[\p{L}.]$/u',
                function ($attribute, $value, $fail) {
                    if (
                        preg_match('/[.]{2,}/', $value) ||
                        preg_match('/[\s]{2,}/', $value)
                    ) {
                        $fail('The prefix format is invalid.');
                    }
                }
            ],
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}]+[\p{L}\s\'-]*[\p{L}]$/u',
                function ($attribute, $value, $fail) {
                    if (
                        preg_match('/[\s]{2,}/', $value) ||
                        preg_match('/[\-]{2,}/', $value) ||
                        preg_match('/[\'][\']/', $value)
                    ) {
                        $fail('The first name format is invalid.');
                    }
                }
            ],
            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}]+[\p{L}\s\'-]*[\p{L}]$/u',
                function ($attribute, $value, $fail) {
                    if (
                        preg_match('/[\s]{2,}/', $value) ||
                        preg_match('/[\-]{2,}/', $value) ||
                        preg_match('/[\'][\']/', $value)
                    ) {
                        $fail('The last name format is invalid.');
                    }
                }
            ],
            'mobile_number' => [
                'required',
                'string',
                'min:7',
                'max:20',
                'regex:/^(\+?[1-9]\d{0,3})?[\s\-\(\)]?[1-9][\d\s\-\(\)]*\d$/',
                function ($attribute, $value, $fail) {
                    $cleaned = preg_replace('/[^\+0-9]/', '', $value);

                    $digitCount = strlen(preg_replace('/[^\d]/', '', $cleaned));
                    if ($digitCount < 7) {
                        $fail('The mobile number must contain at least 7 digits.');
                    }

                    if (
                        preg_match('/^[\+\-\s\(\)]+$/', $value) ||
                        preg_match('/[\+]{2,}/', $value) ||
                        preg_match('/[\-]{3,}/', $value) ||
                        preg_match('/[\s]{3,}/', $value) ||
                        preg_match('/[\(\)]{3,}/', $value) ||
                        preg_match('/\+.*\+/', $value)
                    ) {
                        $fail('The mobile number format is invalid.');
                    }

                    $exists = self::where('mobile_number', $value)
                        ->whereNull('deleted_at')
                        ->exists();
                    if ($exists) {
                        $fail('The mobile number has already been taken.');
                    }
                }
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'min:5',
                'max:255',
                function ($attribute, $value, $fail) {
                    $exists = self::where('email', $value)
                        ->whereNull('deleted_at')
                        ->exists();
                    if ($exists) {
                        $fail('The email has already been taken.');
                    }
                }
            ],
        ];
    }

    public static function updateRules($id): array
    {
        return [
            'prefix' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:20',
                'regex:/^[\p{L}.]+[\p{L}.\s]*[\p{L}.]$/u',
                function ($attribute, $value, $fail) {
                    if (
                        preg_match('/[.]{2,}/', $value) ||
                        preg_match('/[\s]{2,}/', $value)
                    ) {
                        $fail('The prefix format is invalid.');
                    }
                }
            ],
            'first_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}]+[\p{L}\s\'-]*[\p{L}]$/u',
                function ($attribute, $value, $fail) {
                    if (
                        preg_match('/[\s]{2,}/', $value) ||
                        preg_match('/[\-]{2,}/', $value) ||
                        preg_match('/[\'][\']/', $value)
                    ) {
                        $fail('The first name format is invalid.');
                    }
                }
            ],
            'last_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}]+[\p{L}\s\'-]*[\p{L}]$/u',
                function ($attribute, $value, $fail) {
                    if (
                        preg_match('/[\s]{2,}/', $value) ||
                        preg_match('/[\-]{2,}/', $value) ||
                        preg_match('/[\'][\']/', $value)
                    ) {
                        $fail('The last name format is invalid.');
                    }
                }
            ],
            'mobile_number' => [
                'sometimes',
                'required',
                'string',
                'min:7',
                'max:20',
                'regex:/^(\+?[1-9]\d{0,3})?[\s\-\(\)]?[1-9][\d\s\-\(\)]*\d$/',
                function ($attribute, $value, $fail) use ($id) {
                    $cleaned = preg_replace('/[^\+0-9]/', '', $value);

                    $digitCount = strlen(preg_replace('/[^\d]/', '', $cleaned));
                    if ($digitCount < 7) {
                        $fail('The mobile number must contain at least 7 digits.');
                    }

                    if (
                        preg_match('/^[\+\-\s\(\)]+$/', $value) ||
                        preg_match('/[\+]{2,}/', $value) ||
                        preg_match('/[\-]{3,}/', $value) ||
                        preg_match('/[\s]{3,}/', $value) ||
                        preg_match('/[\(\)]{3,}/', $value) ||
                        preg_match('/\+.*\+/', $value)
                    ) {
                        $fail('The mobile number format is invalid.');
                    }

                    $exists = self::where('mobile_number', $value)
                        ->where('id', '!=', $id)
                        ->whereNull('deleted_at')
                        ->exists();
                    if ($exists) {
                        $fail('The mobile number has already been taken.');
                    }
                }
            ],
            'email' => [
                'sometimes',
                'required',
                'email:rfc,dns',
                'min:5',
                'max:255',
                function ($attribute, $value, $fail) use ($id) {
                    $exists = self::where('email', $value)
                        ->where('id', '!=', $id)
                        ->whereNull('deleted_at')
                        ->exists();
                    if ($exists) {
                        $fail('The email has already been taken.');
                    }
                }
            ],
        ];
    }

    // Sanitation to prevent XSS and other vulnerabilities
    public function setAttribute($key, $value)
    {
        if (in_array($key, ['first_name', 'last_name', 'prefix'])) {
            $value = $this->sanitizeName($value);
        } elseif ($key === 'mobile_number') {
            $value = $this->sanitizeMobileNumber($value);
        } elseif ($key === 'email') {
            $value = $this->sanitizeEmail($value);
        }

        return parent::setAttribute($key, $value);
    }

    private function sanitizeName($value)
    {
        if (!is_string($value)) return $value;

        $value = preg_replace('/[^\p{L}\s\'-]/u', '', $value);
        $value = preg_replace('/\s+/', ' ', trim($value));

        return $value;
    }

    private function sanitizeMobileNumber($value)
    {
        if (!is_string($value)) return $value;

        $value = preg_replace('/[^\d\+\-\s\(\)]/', '', $value);
        $value = trim($value);

        return $value;
    }

    private function sanitizeEmail($value)
    {
        if (!is_string($value)) return $value;

        $value = filter_var(trim(strtolower($value)), FILTER_SANITIZE_EMAIL);

        return $value;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();

                Log::info('Personnel record created', [
                    'personnel_id' => null,
                    'action' => 'create',
                    'user_id' => Auth::id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'has_email' => !empty($model->email),
                    'has_mobile' => !empty($model->mobile_number),
                    'timestamp' => now()
                ]);
            }
        });

        static::created(function ($model) {
            Log::info('Personnel record created successfully', [
                'personnel_id' => $model->id,
                'action' => 'created',
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();

                $changes = $model->getDirty();
                $logData = [
                    'personnel_id' => $model->id,
                    'action' => 'update',
                    'user_id' => Auth::id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'changed_fields' => array_keys($changes),
                    'timestamp' => now()
                ];

                if (array_key_exists('email', $changes)) {
                    $logData['email_changed'] = true;
                }
                if (array_key_exists('mobile_number', $changes)) {
                    $logData['mobile_changed'] = true;
                }

                Log::info('Personnel record updated', $logData);
            }
        });

        static::deleted(function ($model) {
            Log::warning('Personnel record deleted', [
                'personnel_id' => $model->id,
                'action' => 'delete',
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'soft_delete' => true,
                'timestamp' => now()
            ]);
        });
    }

    public function logSensitiveAccess($operation, $additional_data = [])
    {
        Log::warning('Individual personnel data accessed', array_merge([
            'personnel_id' => $this->id,
            'operation' => $operation,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ], $additional_data));
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->prefix . ' ' . $this->first_name . ' ' . $this->last_name);
    }

    public function getMaskedEmailAttribute(): string
    {
        if (empty($this->email)) return '';

        $parts = explode('@', $this->email);
        if (count($parts) !== 2) return '***';

        $username = $parts[0];
        $domain = $parts[1];

        $maskedUsername = substr($username, 0, 2) . str_repeat('*', max(0, strlen($username) - 2));

        return $maskedUsername . '@' . $domain;
    }

    public function getMaskedMobileAttribute(): string
    {
        if (empty($this->mobile_number)) return '';

        $mobile = preg_replace('/\D/', '', $this->mobile_number);
        $length = strlen($mobile);

        if ($length <= 4) return str_repeat('*', $length);

        return substr($mobile, 0, 2) . str_repeat('*', $length - 4) . substr($mobile, -2);
    }
}
