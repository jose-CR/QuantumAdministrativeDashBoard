<?php

namespace App\Rules;

use App\Support\ElSalvadorCatalogo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DistrictCodeRule implements ValidationRule
{
    public function __construct(private string $municipalitiesCode)
    {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $districts = ElSalvadorCatalogo::districts($this->municipalitiesCode); 
    
        if (!array_key_exists($value, $districts)){
            $fail('El código del distrito no es válido.');
        }
    }
}
