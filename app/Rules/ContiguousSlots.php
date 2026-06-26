<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContiguousSlots implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('Format slot waktu tidak valid.');
            return;
        }

        if (count($value) === 0) {
            return;
        }

        // Convert slot strings (HH:00) to integer hours
        $hours = [];
        foreach ($value as $slot) {
            if (!is_string($slot) || !preg_match('/^\d{2}:00$/', $slot)) {
                $fail('Format slot waktu harus HH:00.');
                return;
            }
            $hours[] = (int) substr($slot, 0, 2);
        }

        // Sort the hours
        sort($hours);

        // Check for contiguity
        for ($i = 1; $i < count($hours); $i++) {
            if ($hours[$i] !== $hours[$i - 1] + 1) {
                $fail('Slot harus berurutan tanpa jeda (mis. 16:00, 17:00, 18:00).');
                return;
            }
        }
    }
}
