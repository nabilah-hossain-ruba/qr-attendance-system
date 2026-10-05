<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BracuEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!str_ends_with($value, 'bracu.ac.bd')) {
            $fail('The :attribute must be a valid BRACU email address ending with bracu.ac.bd.');
        }
    }
}