<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Register</title>
    @vite(['resources/css/register.css'])
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>

<body>

    <div class="register-card">

        <!-- Header & Branding -->
        <div class="brand-container">
            <div class="logo-circle">
                <img src="{{ asset('logo.jpg') }}" style="width: 80px; height: auto;" alt="Logo">
            </div>
            <h1 class="brand-title">Create Account</h1>
            <p class="brand-subtitle">Join the team to manage and report assets</p>
        </div>

        <!-- Validation Errors Display -->
        @if ($errors->any())
            <div style="color: #e63946; background: #ffe6e6; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                @foreach ($errors->all() as $error)
                    <p style="margin: 0;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Registration Form -->
        <form class="register-form" action="{{ route('register.post') }}" method="POST">
            @csrf

            <!-- Full Name -->
            <div class="input-group">
                <label for="name">Full Name</label>
                <div class="input-field">
                    <span class="material-icons-outlined icon">person</span>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Your Name" required>
                </div>
            </div>

            <!-- Email Address -->
            <div class="input-group">
                <label for="email">Work Email</label>
                <div class="input-field">
                    <span class="material-icons-outlined icon">email</span>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="user@company.com" required>
                </div>
            </div>

            <!-- Account Role Selection -->
            <div class="input-group">
                <label for="role">Select Your Role</label>
                <div class="input-field">
                    <span class="material-icons-outlined icon">badge</span>
                    <select id="role" name="role" required>
                        <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>General Staff (Report Damage)</option>
                        <option value="asset_team" {{ old('role') == 'asset_team' ? 'selected' : '' }}>Asset Team (Procurement & Issuance)</option>
                    </select>
                </div>
            </div>

            <!-- Password -->
            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-field">
                    <span class="material-icons-outlined icon">lock</span>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="input-group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="input-field">
                    <span class="material-icons-outlined icon">lock_reset</span>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="submit-btn" id="submitBtn">Register Account</button>

            <!-- Redirect to Login -->
            <div class="form-footer">
                <span>Already have an account?</span>
                <a href="{{ route('login') }}" class="login-link">Sign In</a>
            </div>

        </form>

    </div>

</body>

</html>