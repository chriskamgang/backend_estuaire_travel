# Documentation OTP - Estuaire Travel

## Vue d'ensemble

Système de vérification par SMS utilisant l'API **Nexah SMS** pour vérifier le numéro de téléphone des utilisateurs.

## Configuration Backend

### 1. Variables d'environnement (.env)

```env
NEXAH_BASE_URL=https://smsvas.com/bulk/public/index.php/api/v1
NEXAH_USERNAME=steve.boussa@outlook.com
NEXAH_PASSWORD=votre_mot_de_passe_ici
NEXAH_SENDER_ID=UES
```

**Important:** Remplacez `votre_mot_de_passe_ici` par votre vrai mot de passe Nexah.

### 2. Structure de la base de données

Colonnes ajoutées à la table `users`:
- `otp_code` (varchar 6) - Code OTP à 6 chiffres
- `otp_expires_at` (timestamp) - Date d'expiration du code (10 minutes)
- `phone_verified` (boolean) - Indique si le téléphone est vérifié
- `phone_verified_at` (timestamp) - Date de vérification du téléphone

### 3. Routes API

#### Envoyer un OTP
```http
POST /api/otp/send
Content-Type: application/json

{
  "phone": "670000000"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Code OTP envoyé avec succès",
  "expires_in_minutes": 10
}
```

#### Vérifier un OTP
```http
POST /api/otp/verify
Content-Type: application/json

{
  "phone": "670000000",
  "otp": "123456"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Numéro de téléphone vérifié avec succès",
  "user": {
    "id": 1,
    "name": "John Doe",
    "phone": "237670000000",
    "phone_verified": true,
    "phone_verified_at": "2026-02-26T18:45:00.000000Z"
  }
}
```

#### Renvoyer un OTP
```http
POST /api/otp/resend
Content-Type: application/json

{
  "phone": "670000000"
}
```

### 4. Service SMS (app/Services/SmsService.php)

Le service gère:
- Envoi de SMS via l'API Nexah
- Génération de codes OTP aléatoires
- Nettoyage et formatage des numéros de téléphone (ajout automatique de l'indicatif +237)
- Vérification du solde de crédits SMS
- Logging des erreurs et succès

### 5. Contrôleur OTP (app/Http/Controllers/Api/OtpController.php)

Méthodes disponibles:
- `sendOtp()` - Envoyer un code OTP
- `verifyOtp()` - Vérifier le code OTP
- `resendOtp()` - Renvoyer un nouveau code

## Intégration Frontend (React Native)

### 1. Service OTP (src/services/otpService.js)

```javascript
import otpService from './services/otpService';

// Envoyer un OTP
const result = await otpService.sendOtp(phone);

// Vérifier un OTP
const result = await otpService.verifyOtp(phone, otp);

// Renvoyer un OTP
const result = await otpService.resendOtp(phone);
```

### 2. Écran de vérification (src/screens/OtpVerificationScreen.js)

Fonctionnalités:
- Saisie du code à 6 chiffres avec auto-focus
- Compte à rebours de 60 secondes avant de pouvoir renvoyer
- Bouton "Renvoyer le code"
- Validation et vérification du code
- Retour automatique à l'écran de connexion après succès

## Utilisation

### Lors de l'inscription

1. L'utilisateur s'inscrit avec son numéro de téléphone
2. Un code OTP est automatiquement envoyé
3. L'utilisateur est redirigé vers l'écran de vérification OTP
4. Il entre le code reçu par SMS
5. Son compte est vérifié et il peut se connecter

### Exemple de flux:

```javascript
// RegisterScreen.js
const handleRegister = async (userData) => {
  // 1. Créer le compte
  const registerResult = await authService.register(userData);

  if (registerResult.success) {
    // 2. Envoyer l'OTP
    const otpResult = await otpService.sendOtp(userData.phone);

    if (otpResult.success) {
      // 3. Naviguer vers l'écran de vérification
      navigation.navigate('OtpVerification', { phone: userData.phone });
    }
  }
};
```

## Format des numéros de téléphone

Le service accepte plusieurs formats et les convertit automatiquement:
- `670000000` → `237670000000`
- `0670000000` → `237670000000`
- `237670000000` → `237670000000`
- `+237670000000` → `237670000000`

## Message SMS

Le message envoyé aux utilisateurs:
```
Votre code de vérification Estuaire Travel est: 123456. Ce code expire dans 10 minutes.
```

## Sécurité

- Le code OTP expire après 10 minutes
- Le code est stocké de manière sécurisée dans la base de données
- Une fois vérifié, le code est supprimé automatiquement
- Les numéros déjà vérifiés ne peuvent pas recevoir de nouveau code

## Test

Pour tester l'API avec Postman ou curl:

```bash
# Envoyer un OTP
curl -X POST http://localhost:8001/api/otp/send \
  -H "Content-Type: application/json" \
  -d '{"phone":"670000000"}'

# Vérifier un OTP
curl -X POST http://localhost:8001/api/otp/verify \
  -H "Content-Type: application/json" \
  -d '{"phone":"670000000","otp":"123456"}'
```

## Vérifier le solde SMS

Pour vérifier votre solde de crédits SMS Nexah, vous pouvez utiliser la méthode:

```php
use App\Services\SmsService;

$smsService = new SmsService();
$credits = $smsService->checkCredits();
```

## Logs

Les logs SMS sont stockés dans `storage/logs/laravel.log`:
- Succès d'envoi
- Échecs d'envoi
- Erreurs de connexion à l'API

## Dépannage

### Problème: Le SMS n'est pas reçu
- Vérifiez que le Sender ID "UES" est approuvé dans votre compte Nexah
- Vérifiez votre solde de crédits SMS
- Vérifiez les logs Laravel pour les erreurs

### Problème: Erreur "Code OTP expiré"
- Le code est valide pendant 10 minutes seulement
- Demandez un nouveau code avec `/api/otp/resend`

### Problème: Erreur "Numéro déjà vérifié"
- Le numéro a déjà été vérifié
- Cette protection empêche les abus du système

## Contact Support Nexah

- Email: support@nexah.net
- Documentation: https://nexah.net/docs
