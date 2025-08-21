<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VttContent implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Rule 1: Must not be empty and must start with WEBVTT
        if (empty($value) || !str_starts_with(trim($value), 'WEBVTT')) {
            $fail('The file must be a valid VTT format and start with "WEBVTT".');
            return;
        }

        $lines = preg_split("/\r\n|\n|\r/", $value);
        $foundCue = false;

        foreach ($lines as $line) {
            // Rule 2: Check for at least one valid timestamp cue
            // A simple regex to match the format 00:00:00.000 --> 00:00:00.000
            if (preg_match('/(\d{2}:)?\d{2}:\d{2}\.\d{3}\s-->\s(\d{2}:)?\d{2}:\d{2}\.\d{3}/', $line)) {
                $foundCue = true;
                break;
            }
        }

        if (!$foundCue) {
            $fail('The VTT content does not contain any valid timestamp cues.');
        }
    }
}
