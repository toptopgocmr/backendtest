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
            // Méthode de paiement : mtn_momo ou airtel_money
            'payment_method' => 'required|string|in:mtn_momo,airtel_money',

            // Numéro de téléphone utilisé pour le paiement
            'phone_number'   => 'required|string|max:25',

            // ID de transaction retourné par l'opérateur
            'transaction_id' => 'required|string|max:100',

            // Capture d'écran de la confirmation (image uploadée)
            'proof_image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Veuillez choisir un mode de paiement (MTN MoMo ou Airtel Money).',
            'payment_method.in'       => 'Mode de paiement invalide. Choisissez MTN MoMo ou Airtel Money.',
            'phone_number.required'   => 'Le numéro de téléphone utilisé pour le paiement est obligatoire.',
            'phone_number.max'        => 'Le numéro de téléphone ne doit pas dépasser 25 caractères.',
            'transaction_id.required' => "L'identifiant de transaction est obligatoire.",
            'transaction_id.max'      => "L'identifiant de transaction ne doit pas dépasser 100 caractères.",
            'proof_image.image'       => 'Le fichier doit être une image (JPEG, PNG, WebP).',
            'proof_image.max'         => "L'image ne doit pas dépasser 5 Mo.",
        ];
    }
}
