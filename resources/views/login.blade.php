<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Tracker - Login</title>
    @vite(['resources/css/style.css'])
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
</head>

<body>

    <div class="login-card">

        <!-- Logo & Branding -->
        <div class="brand-container">
            <div class="logo-circle">
                <img src="{{ asset('logo.jpg') }}" style="width: 80px; height: auto;" alt="Logo">
            </div>
            <h1 class="brand-title">Asset Tracker</h1>
            <p class="brand-subtitle">Scan, manage, and report office equipment</p>
        </div>

        <!-- Role Selector Tabs -->
        <div class="role-switcher">
            <button type="button" class="role-btn active" id="staffBtn" onclick="selectRole('staff')">General Staff</button>
            <button type="button" class="role-btn" id="adminBtn" onclick="selectRole('asset_team')">Asset Team</button>
        </div>

        <!-- Validation Errors Display -->
        @if ($errors->any())
            <div style="color: #e63946; background: #ffe6e6; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                @foreach ($errors->all() as $error)
                    <p style="margin: 0;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Login Form -->
        <form class="login-form" action="{{ route('login.post') }}" method="POST">
            @csrf
            <input type="hidden" id="selectedRole" name="role" value="staff">

            <!-- Email Input -->
            <div class="input-group">
                <label for="email">Email Address</label>
                <div class="input-field">
                    <span class="material-icons-outlined icon">email</span>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="user@company.com" required>
                </div>
            </div>

            <!-- Password Input -->
            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-field">
                    <span class="material-icons-outlined icon">lock</span>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <!-- Forgot Password & Register Navigation -->
            <div class="form-footer">
                <a href="#" class="forgot-link">Forgot Password?</a>
                <span class="register-text">Don't have an account? <a href="{{ route('register') }}" class="register-link">Register Here</a></span>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="submit-btn" id="submitBtn">Sign In as Staff</button>
        </form>

    </div>

    <script>
        // Role switching logic
        function selectRole(role) {
            const staffBtn = document.getElementById('staffBtn');
            const adminBtn = document.getElementById('adminBtn');
            const submitBtn = document.getElementById('submitBtn');
            const roleInput = document.getElementById('selectedRole');

            roleInput.value = role;

            if (role === 'staff') {
                staffBtn.classList.add('active');
                adminBtn.classList.remove('active');
                submitBtn.textContent = 'Sign In as Staff';
            } else {
                adminBtn.classList.add('active');
                staffBtn.classList.remove('active');
                submitBtn.textContent = 'Sign In as Asset Team';
            }
        }
    </script>

</body>

</html>