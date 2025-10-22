# API Authentication Documentation

This Laravel application now includes a complete API-based authentication system with email verification using Laravel Sanctum.

## Features

- ✅ User Registration
- ✅ User Login
- ✅ User Logout
- ✅ Email Verification
- ✅ Resend Verification Email
- ✅ Get Authenticated User
- ✅ Token-based Authentication (Bearer tokens)

## API Endpoints

### Public Endpoints (No Authentication Required)

#### 1. Register a New User

**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Success Response (201):**
```json
{
  "message": "User registered successfully. Please check your email to verify your account.",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2025-10-22T10:00:00.000000Z"
  },
  "access_token": "1|abcdef123456...",
  "token_type": "Bearer"
}
```

**Validation Rules:**
- `name`: required, string, max 255 characters
- `email`: required, valid email, max 255 characters, unique
- `password`: required, string, min 8 characters, must be confirmed

---

#### 2. Login

**Endpoint:** `POST /api/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "access_token": "2|xyz789...",
  "token_type": "Bearer"
}
```

**Error Response (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

**Note:** Login revokes all previous tokens for the user (single session). Remove the `$user->tokens()->delete();` line in the controller if you want to allow multiple sessions.

---

#### 3. Verify Email

**Endpoint:** `GET /api/email/verify/{id}/{hash}`

This endpoint is called when the user clicks the verification link in their email. The URL is signed and includes the user ID and hash.

**Success Response (200):**
```json
{
  "message": "Email verified successfully"
}
```

**Error Responses:**
- `403`: Invalid verification link
- `400`: Email already verified

---

### Protected Endpoints (Authentication Required)

All protected endpoints require the `Authorization` header with a Bearer token:

```
Authorization: Bearer {your_access_token}
```

#### 4. Get Authenticated User

**Endpoint:** `GET /api/user`

**Headers:**
```
Authorization: Bearer 1|abcdef123456...
```

**Success Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2025-10-22T10:05:00.000000Z",
    "created_at": "2025-10-22T10:00:00.000000Z",
    "updated_at": "2025-10-22T10:05:00.000000Z"
  }
}
```

---

#### 5. Logout

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer 1|abcdef123456...
```

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

**Note:** This revokes only the current access token. Other tokens (if any) remain valid.

---

#### 6. Resend Verification Email

**Endpoint:** `POST /api/email/verification-notification`

**Headers:**
```
Authorization: Bearer 1|abcdef123456...
```

**Success Response (200):**
```json
{
  "message": "Verification email sent"
}
```

**Error Response (400):**
```json
{
  "message": "Email already verified"
}
```

---

#### 7. Check Email Verification Status

**Endpoint:** `GET /api/email/verify/check`

**Headers:**
```
Authorization: Bearer 1|abcdef123456...
```

**Success Response (200):**
```json
{
  "verified": true,
  "email": "john@example.com"
}
```

---

## Testing with cURL

### Register a User
```bash
curl -X POST http://localhost:85/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login
```bash
curl -X POST http://localhost:85/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Get User (with token)
```bash
curl -X GET http://localhost:85/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Logout
```bash
curl -X POST http://localhost:85/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Resend Verification Email
```bash
curl -X POST http://localhost:85/api/email/verification-notification \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Check Verification Status
```bash
curl -X GET http://localhost:85/api/email/verify/check \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Email Configuration

By default, the application uses the `log` mailer, which writes emails to `storage/logs/laravel.log`. 

To use a real email service, update your `.env` file:

### Using SMTP (e.g., Gmail, Mailtrap)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS="noreply@yourapp.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Using Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your-mailgun-secret
MAIL_FROM_ADDRESS="noreply@yourapp.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Security Notes

1. **HTTPS in Production**: Always use HTTPS in production to protect tokens in transit
2. **Token Storage**: Store tokens securely on the client side (e.g., secure HTTP-only cookies or encrypted storage)
3. **Token Expiration**: By default, Sanctum tokens don't expire. You can set expiration in `config/sanctum.php`:
   ```php
   'expiration' => 60, // tokens expire after 60 minutes
   ```
4. **Rate Limiting**: Consider adding rate limiting to authentication endpoints
5. **Email Verification**: The verification link is signed and expires after a certain time (configurable in `config/auth.php`)

---

## Implementation Details

### Files Modified/Created

1. **User Model** (`src/app/Models/User.php`)
   - Added `HasApiTokens` trait
   - Implements `MustVerifyEmail` interface

2. **Auth Controller** (`src/app/Http/Controllers/Api/AuthController.php`)
   - `register()` - Register new users and send verification email
   - `login()` - Authenticate users and issue tokens
   - `logout()` - Revoke current token
   - `user()` - Get authenticated user details
   - `sendVerificationEmail()` - Resend verification email
   - `verifyEmail()` - Verify email from link
   - `checkEmailVerification()` - Check verification status

3. **API Routes** (`src/routes/api.php`)
   - Public routes: register, login, email verification
   - Protected routes: user, logout, resend verification, check verification

---

## Next Steps

1. **Test the API** using the cURL examples or a tool like Postman
2. **Configure Email** if you want to send real emails instead of logging them
3. **Add Middleware** to require email verification for certain routes:
   ```php
   Route::middleware(['auth:sanctum', 'verified'])->group(function () {
       // Routes that require verified email
   });
   ```
4. **Customize Email Templates** in `resources/views/vendor/notifications/email.blade.php`
5. **Add Password Reset** functionality if needed
6. **Implement Rate Limiting** for security

---

## Troubleshooting

### Emails not sending
- Check `storage/logs/laravel.log` if using log mailer
- Verify mail configuration in `.env`
- Run `php artisan config:clear` after changing mail settings

### Token not working
- Ensure you're sending the `Authorization: Bearer {token}` header
- Check that the token hasn't been revoked
- Verify the user exists and is active

### Verification link not working
- Check that the link hasn't expired
- Ensure the URL signature is valid
- Verify `APP_KEY` is set in `.env`

