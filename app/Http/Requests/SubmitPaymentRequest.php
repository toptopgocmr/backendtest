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
            // Flutter envoie 'method' (mtn_momo | airtel_money)
            'method'       => 'required|string|in:mtn_momo,airtel_money',

            // Flutter envoie 'provider_ref' (ID de transaction opérateur)
            'provider_ref' => 'required|string|max:100',

            // Flutter envoie 'phone' (numéro complet avec indicatif)
            'phone'        => 'required|string|max:25',

            // Flutter envoie 'proof' en multipart (mobile) ou 'proof_base64' (web)
            'proof'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'proof_base64' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'method.required'       => 'Veuillez choisir un mode de paiement (MTN MoMo ou Airtel Money).',
            'method.in'             => 'Mode de paiement invalide.',
            'provider_ref.required' => "L'identifiant de transaction est obligatoire.",
            'provider_ref.max'      => "L'identifiant de transaction ne doit pas dépasser 100 caractères.",
            'phone.required'        => 'Le numéro de téléphone utilisé pour le paiement est obligatoire.',
            'phone.max'             => 'Le numéro de téléphone ne doit pas dépasser 25 caractères.',
            'proof.image'           => 'Le fichier doit être une image (JPEG, PNG, WebP).',
            'proof.max'             => "L'image ne doit pas dépasser 5 Mo.",
        ];
    }
}
