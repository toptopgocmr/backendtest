# ImmoStay Backend — Tholad Group © 2025

Backend Laravel 11 — API REST + Panel Admin

---

## 🚀 Installation en 5 étapes

```bash
# 1. Installer les dépendances
composer install

# 2. Copier le fichier d'environnement
cp .env.example .env
php artisan key:generate

# 3. Créer la base de données MySQL
#    Dans phpMyAdmin ou MySQL CLI :
#    CREATE DATABASE immostay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Lancer les migrations + données de test
php artisan migrate --seed

# 5. Lancer le serveur
php artisan serve
```

## 🔑 Accès Admin

| URL          | http://localhost:8000/admin |
|--------------|-----------------------------|
| Email        | admin@immostay.com          |
| Mot de passe | Admin@1234                  |

## 📱 API Mobile (Flutter)

Base URL : `http://10.0.2.2:8000/api/v1` (émulateur Android)

| Endpoint                    | Description              |
|-----------------------------|--------------------------|
| POST /auth/register         | Inscription              |
| POST /auth/login            | Connexion (par téléphone)|
| POST /auth/verify-otp       | Vérification OTP         |
| POST /auth/send-otp         | Renvoi OTP               |
| GET  /properties            | Liste des biens          |
| GET  /properties/featured   | Biens à la une           |
| GET  /properties/{id}       | Détail d'un bien         |
| POST /bookings              | Créer une réservation    |
| GET  /bookings              | Mes réservations         |
| POST /payments/initiate     | Initier un paiement      |
| GET  /favorites             | Mes favoris              |
| POST /favorites/{id}        | Ajouter/retirer favori   |
| GET  /notifications         | Mes notifications        |
| GET  /messages              | Mes conversations        |

---

## 🐛 Bugs corrigés (v2)

| # | Fichier | Bug | Fix |
|---|---------|-----|-----|
| 1 | `bootstrap/app.php` | routes/admin.php jamais chargé | Ajout `then:` callback |
| 2 | `routes/web.php` | 5 controllers inexistants | Remplacé par redirection |
| 3 | `app/Http/Kernel.php` | Inutile Laravel 11, fruitcake/cors absent | Supprimé |
| 4 | `Property` model | `user_id`, `price_per_night`, `rating_avg`, `area_m2` | Aligné sur migration |
| 5 | `Property` model | `scopeActive('active')` → enum est `'disponible'` | Corrigé |
| 6 | `Property` model | `is_cover` → migration a `is_primary` | Corrigé |
| 7 | `Booking` model | `subtotal`, `service_fee`, `special_requests` | Aligné sur migration |
| 8 | `Payment` model | `gateway`, `gateway_ref`, `phone_number` + accessor manquant | Corrigé |
| 9 | `User` model | `status` dans fillable, colonne absente | Remplacé par `is_active` |
| 10 | Admin `PropertyController` | `primaryImage()` non défini | Ajouté dans Property model |
| 11 | `routes/admin.php` | Middleware `auth:admin` invalide | Corrigé → `auth.admin` |
| 12 | `DashboardController` | Statuts en anglais vs enum FR | Aligné |
| 13 | `DatabaseSeeder` | Tous champs incorrects | Entièrement réécrit |
| 14 | Migration 0001 | Double création table `users` | Conflit résolu |
| 15 | API Controllers | Champs prix/statuts incohérents | Tous corrigés |
| 16 | `Conversation` model | Manquait complètement | Ajouté |
| 17 | `PropertyAmenity` model | Manquait complètement | Ajouté |
| 18 | `composer.json` | `fruitcake/cors` inexistant | Retiré |
| 19 | Fichiers Admin | Caractères `\r` Windows | Nettoyés |

---

**Tholad Group © 2025** — ImmoStay v2.0
