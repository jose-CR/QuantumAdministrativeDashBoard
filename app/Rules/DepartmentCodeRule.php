<?php

namespace App\Rules;

use App\Support\ElSalvadorCatalogo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DepartmentCodeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $departments = ElSalvadorCatalogo::departments();

        if (!array_key_exists($value, $departments)){
            $fail('El código del departamento no es válido.');
        }
    }
}
