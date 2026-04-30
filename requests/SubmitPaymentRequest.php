<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:mtn_momo,airtel_money'],
            'phone_number'   => ['required', 'string', 'max:20'],
            'transaction_id' => ['required', 'string', 'min:4', 'max:100'],
            'proof_image'    => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 Mo max
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Veuillez sélectionner le mode de paiement (MTN ou Airtel).',
            'payment_method.in'       => 'Mode de paiement invalide.',
            'phone_number.required'   => 'Veuillez entrer le numéro utilisé pour le paiement.',
            'transaction_id.required' => 'Veuillez entrer l\'ID de la transaction.',
            'transaction_id.min'      => 'L\'ID de transaction semble trop court.',
            'proof_image.required'    => 'Veuillez joindre une capture d\'écran du paiement.',
            'proof_image.image'       => 'Le fichier doit être une image (JPG, PNG, WEBP).',
            'proof_image.max'         => 'L\'image ne doit pas dépasser 5 Mo.',
        ];
    }
}
