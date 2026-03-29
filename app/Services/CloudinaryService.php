<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey    = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
    }

    /**
     * Upload un fichier image sur Cloudinary et retourne l'URL sécurisée.
     *
     * FIX : La signature Cloudinary doit être construite ainsi :
     *   1. Trier les paramètres par clé alphabétique
     *   2. Les joindre sous la forme "key=value&key=value" (sans URL-encoding des valeurs)
     *   3. Concaténer l'api_secret à la fin (sans séparateur)
     *   4. Hacher en SHA-1 (pas SHA-256)
     *
     * L'ancienne version utilisait http_build_query (encode les valeurs) + SHA-256 → rejeté par Cloudinary.
     */
    public function upload(UploadedFile $file, string $folder = 'immostay/properties'): string
    {
        $timestamp = time();

        // Paramètres signés — NE PAS inclure api_key, file, resource_type
        $params = [
            'folder'    => $folder,
            'timestamp' => $timestamp,
        ];

        $signature = $this->sign($params);

        $multipart = [
            ['name' => 'file',      'contents' => fopen($file->getRealPath(), 'r'),
                                    'filename'  => $file->getClientOriginalName()],
            ['name' => 'api_key',   'contents' => $this->apiKey],
            ['name' => 'timestamp', 'contents' => (string) $timestamp],
            ['name' => 'folder',    'contents' => $folder],
            ['name' => 'signature', 'contents' => $signature],
        ];

        $client   = new \GuzzleHttp\Client(['timeout' => 30]);
        $response = $client->post(
            "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload",
            ['multipart' => $multipart]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data['secure_url'])) {
            throw new \RuntimeException('Cloudinary upload failed: ' . json_encode($data));
        }

        return $data['secure_url'];
    }

    /**
     * Supprime une image Cloudinary à partir de son URL.
     */
    public function delete(string $url): void
    {
        if (!str_contains($url, 'cloudinary.com')) {
            return;
        }

        // Extraire le public_id depuis l'URL
        // Ex: https://res.cloudinary.com/xxx/image/upload/v1234/immostay/properties/abc.jpg
        preg_match('/\/upload\/(?:v\d+\/)?(.+)\.[a-z]+$/i', $url, $matches);
        if (empty($matches[1])) {
            return;
        }

        $publicId  = $matches[1];
        $timestamp = time();

        $params    = ['public_id' => $publicId, 'timestamp' => $timestamp];
        $signature = $this->sign($params);

        $client = new \GuzzleHttp\Client(['timeout' => 15]);
        $client->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy", [
            'form_params' => [
                'public_id' => $publicId,
                'api_key'   => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ],
        ]);
    }

    /**
     * Génère la signature Cloudinary correcte.
     *
     * Algorithme officiel :
     *   1. Trier les paramètres par clé (ordre alphabétique)
     *   2. Construire la chaîne : "key1=val1&key2=val2" (valeurs NON encodées)
     *   3. Ajouter api_secret sans séparateur à la fin
     *   4. SHA-1 de la chaîne complète
     */
    private function sign(array $params): string
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = "{$key}={$value}";
        }

        $stringToSign = implode('&', $parts) . $this->apiSecret;

        return sha1($stringToSign); // SHA-1, pas SHA-256
    }
}
