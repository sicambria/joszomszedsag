@extends('layout')
@section('title', 'Regisztráció')
@section('content')
    <div class="container">
        <div class="row">
            <div class="col s12">
                <div class="card-panel hoverable">
                    <div class="card-content">
                        <div class="row">
                            <h4 class="card-title center-align">{{ __('Regisztráció') }}</h4>
                        </div>
                        <div class="row">
                            <form method="POST" action="{{ route('register') }}">
                                @csrf
                                <div class="input-field col s12 m6">
                                    <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus>
                                    <label for="first_name">{{ __('Keresztnév') }}</label>
                                    @error('first_name')<span class="red-text">{{ $message }}</span>@enderror
                                </div>
                                <div class="input-field col s12 m6">
                                    <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required>
                                    <label for="last_name">{{ __('Vezetéknév') }}</label>
                                    @error('last_name')<span class="red-text">{{ $message }}</span>@enderror
                                </div>
                                <div class="input-field col s12 m6">
                                    <input id="display_name" type="text" name="display_name" value="{{ old('display_name') }}" required>
                                    <label for="display_name">{{ __('Megjelenített név') }}</label>
                                    @error('display_name')<span class="red-text">{{ $message }}</span>@enderror
                                </div>
                                <div class="input-field col s12 m6">
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                                    <label for="email">{{ __('E-Mail cím') }}</label>
                                    @error('email')<span class="red-text">{{ $message }}</span>@enderror
                                </div>
                                <div class="input-field col s12 m6">
                                    <input id="password" type="password" name="password" required autocomplete="new-password">
                                    <label for="password">{{ __('Jelszó') }}</label>
                                    @error('password')<span class="red-text">{{ $message }}</span>@enderror
                                </div>
                                <div class="input-field col s12 m6">
                                    <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password">
                                    <label for="password-confirm">{{ __('Jelszó megerősítése') }}</label>
                                </div>
                                <div class="input-field col s12 center-align">
                                    <button type="submit" class="btn btn-large red z-depth-1">{{ __('Regisztráció') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
