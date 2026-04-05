@extends('layouts.app')

@section('title', 'Register')

@push('styles')
    <style>
        .register-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .register-card {
            width: min(860px, 100%);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background:
                radial-gradient(120% 120% at 0% 0%, rgba(60, 156, 184, 0.2), transparent 45%),
                linear-gradient(180deg, rgba(6, 39, 42, 0.9), rgba(8, 47, 50, 0.88));
            box-shadow: 0 20px 55px rgba(0, 0, 0, 0.35);
            padding: 34px;
            backdrop-filter: blur(8px);
        }

        .register-head {
            text-align: center;
            margin-bottom: 22px;
        }

        .register-head h1 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.2rem);
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .register-head p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, 0.74);
            font-size: 0.98rem;
        }

        .register-alert {
            margin-bottom: 16px;
            border: 1px solid rgba(248, 113, 113, 0.45);
            background: rgba(127, 29, 29, 0.2);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .register-alert ul {
            margin: 0;
            padding-left: 18px;
            color: #fecaca;
            font-size: 0.9rem;
        }

        .register-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .register-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .register-field.full {
            grid-column: 1 / -1;
        }

        .register-field label {
            font-size: 0.92rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.84);
        }

        .register-input {
            width: 100%;
            height: 52px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.07);
            color: #f8fafc;
            padding: 0 14px;
            font-size: 0.98rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        select.register-input {
            color-scheme: dark;
            appearance: none;
            background-image: linear-gradient(45deg, transparent 50%, #cbd5e1 50%), linear-gradient(135deg, #cbd5e1 50%, transparent 50%);
            background-position: calc(100% - 22px) calc(50% - 3px), calc(100% - 16px) calc(50% - 3px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            padding-right: 42px;
        }

        select.register-input option {
            background: #0d2f35;
            color: #e2e8f0;
        }

        .register-input::placeholder {
            color: rgba(255, 255, 255, 0.45);
        }

        .register-input:focus {
            outline: none;
            border-color: rgba(56, 189, 248, 0.95);
            box-shadow: 0 0 0 3px rgba(14, 116, 144, 0.28);
            background: rgba(255, 255, 255, 0.1);
        }

        .register-actions {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .register-submit {
            border: none;
            border-radius: 12px;
            background: linear-gradient(180deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            font-weight: 700;
            padding: 13px 24px;
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .register-submit:hover {
            transform: translateY(-1px);
            filter: brightness(1.06);
        }

        .register-login-link {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.92rem;
        }

        .register-login-link a {
            color: #bfdbfe;
            font-weight: 700;
            text-underline-offset: 2px;
        }

        .register-error-dialog {
            position: fixed;
            inset: 0;
            display: grid;
            place-items: center;
            z-index: 50;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }

        .register-error-dialog.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .register-error-dialog__backdrop {
            position: absolute;
            inset: 0;
            background: transparent;
        }

        .register-error-dialog__panel {
            position: relative;
            max-width: 420px;
            width: 100%;
            background: linear-gradient(180deg, #0d3c43, #071b20);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 24px;
            box-shadow: 0 26px 80px rgba(0, 0, 0, 0.35);
            padding: 26px;
            color: #f8fafc;
            z-index: 1;
            transform: translateY(-12px);
            transition: transform 0.2s ease;
        }

        .register-error-dialog.active .register-error-dialog__panel {
            transform: translateY(0);
        }

        .register-error-dialog__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .register-error-dialog__header h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .register-error-dialog__close {
            border: none;
            background: rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            font-size: 1rem;
            width: 34px;
            height: 34px;
            border-radius: 12px;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: background 0.2s ease;
        }

        .register-error-dialog__close:hover {
            background: rgba(255, 255, 255, 0.16);
        }

        .register-error-dialog__body p {
            margin: 0 0 0.8rem;
            color: rgba(255, 255, 255, 0.86);
        }

        .register-error-dialog__body ul {
            margin: 0;
            padding-left: 1.3rem;
            list-style: disc;
        }

        .register-error-dialog__body li {
            margin-bottom: 0.6rem;
            line-height: 1.6;
            color: #e2e8f0;
        }

        .register-error-dialog__actions {
            margin-top: 1.5rem;
            display: flex;
            justify-content: flex-end;
        }

        .register-error-dialog__button {
            border: none;
            background: linear-gradient(180deg, #38bdf8, #0ea5e9);
            color: #0f172a;
            padding: 0.9rem 1.4rem;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .register-error-dialog__button:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        @media (max-width: 768px) {
            .register-card {
                padding: 24px 16px;
                border-radius: 18px;
            }

            .register-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .register-field.full {
                grid-column: auto;
            }

            .register-input {
                height: 50px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="register-shell">
        <div class="register-card">
            <div class="register-head">
                <h1>Create your account</h1>
                <p>Register to access the attendance system</p>
            </div>

            @if ($errors->any())
                <div id="registerErrorDialog" class="register-error-dialog active" role="alert" aria-live="assertive">
                    <div class="register-error-dialog__backdrop"></div>

                    <div class="register-error-dialog__panel">
                        <div class="register-error-dialog__header">
                            <h2>Registration error</h2>
                            <button type="button" class="register-error-dialog__close" aria-label="Close error dialog">&times;</button>
                        </div>

                        <div class="register-error-dialog__body">
                            <p>We could not complete your registration because of these errors:</p>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="register-error-dialog__actions">
                            <button type="button" class="register-error-dialog__button">OK</button>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const dialog = document.getElementById('registerErrorDialog');
                        if (!dialog) {
                            return;
                        }

                        const closeButton = dialog.querySelector('.register-error-dialog__close');
                        const okButton = dialog.querySelector('.register-error-dialog__button');
                        const backdrop = dialog.querySelector('.register-error-dialog__backdrop');

                        const closeDialog = function () {
                            dialog.classList.remove('active');
                        };

                        if (closeButton) {
                            closeButton.addEventListener('click', closeDialog);
                        }

                        if (okButton) {
                            okButton.addEventListener('click', closeDialog);
                        }

                        if (backdrop) {
                            backdrop.addEventListener('click', closeDialog);
                        }

                        document.addEventListener('keydown', function (event) {
                            if (event.key === 'Escape') {
                                closeDialog();
                            }
                        });
                    });
                </script>
            @endif

            <form method="POST" action="{{ route('register.store') }}">
                @csrf
                <input type="hidden" name="device_id" id="registerDeviceId" value="" style="display: none;">

                <div class="register-grid">
                    <div class="register-field">
                        <label>Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Enter your full name" required
                               class="register-input">
                    </div>

                    <div class="register-field">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required
                               class="register-input">
                    </div>

                    <div class="register-field">
                        <label>Employee ID</label>
                        <input type="text" name="eid" value="{{ old('eid') }}" placeholder="Used for login" required
                               class="register-input">
                    </div>

                    <div class="register-field">
                        <label>Designation</label>
                        <input type="text" name="designation" value="{{ old('designation') }}" placeholder="Optional"
                               class="register-input">
                    </div>

                    <div class="register-field">
                        <label>Department</label>
                        <input type="text" name="department" value="{{ old('department') }}" placeholder="Optional"
                               class="register-input">
                    </div>

                    <div class="register-field">
                        <label>Role</label>
                        <select name="role_id" class="register-input" required>
                            @foreach(($selfRegisterRoles ?? collect()) as $role)
                                <option value="{{ $role->id }}" @selected((int) old('role_id', 3) === (int) $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="register-field">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="At least 8 characters" required
                               class="register-input">
                    </div>

                    <div class="register-field">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" placeholder="Retype password" required
                               class="register-input">
                    </div>
                </div>

                <div class="register-actions">
                    <button type="submit" class="register-submit">Register</button>
                    <p class="register-login-link">
                        Already have an account?
                        <a href="{{ route('login') }}">Log in</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
@endsection
