<?php

namespace App\Rules;

use Exception;
use Illuminate\Contracts\Validation\Rule;

class DataUrlImageRule implements Rule
{
    private $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  string  $dataUrl
     * @return bool
     */
    public function passes($attribute, $dataUrl)
    {
        try {

            $ext = explode('/', $dataUrl);
            $ext = explode(';', $ext[1])[0];

            if (!in_array($ext, ['gif', 'png', 'jpeg', 'bmp', 'webp'])) {

                $this->message = 'Mimetype inválido. Os type\'s válidos são: gif, png, jpeg, bmp, webp';

                return false;

            }

            $base64 = explode(',', $dataUrl)[1];

            if (!base64_decode($base64)) {

                $this->message = 'DataUrl token não é base64.';

                return false;

            }

            return true;

        } catch (Exception $e) {

            $this->message = 'DataUrl inválida.';

            return false;

        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
