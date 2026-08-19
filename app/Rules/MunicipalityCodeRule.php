<?php

namespace App\Rules;

use App\Support\ElSalvadorCatalogo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MunicipalityCodeRule implements ValidationRule
{
    public function __construct(private string $departmentCode)
    {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $municipalities = ElSalvadorCatalogo::municipalities($this->departmentCode); 
    
        if (!array_key_exists($value, $municipalities)){
            $fail('El código del municipio no es válido.');
        }
    }
}
