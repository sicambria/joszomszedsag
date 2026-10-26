@extends('dashboard.layout')
@section('site_title', __('Új körlevél'))
@section('page_title')
    <div class="page-title-icon"><i class="pe-7s-mail icon-gradient bg-white"></i></div>
    <div>
        {{ __('Új körlevél') }}
        <div class="page-title-subheading">{{ __('Körlevél küldése szerepkör-csoportoknak') }}</div>
    </div>
@endsection

@section('content')
    <h3>{{ __('Új körlevél') }}</h3>
    <form method="POST" action="{{ route('bulk_mail_store') }}">
        @csrf
        <div class="input-field">
            <label>{{ __('Címzettek') }}</label>
            <select name="groups[]" multiple required>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                @endforeach
            </select>
            @error('groups')<span class="red-text">{{ $message }}</span>@enderror
        </div>
        <div class="input-field">
            <label for="subject">{{ __('Tárgy') }}</label>
            <input id="subject" type="text" name="subject" value="{{ old('subject') }}" required maxlength="255">
            @error('subject')<span class="red-text">{{ $message }}</span>@enderror
        </div>
        <div class="input-field">
            <label for="mail">{{ __('Üzenet') }}</label>
            <textarea id="mail" name="mail" required>{{ old('mail') }}</textarea>
            @error('mail')<span class="red-text">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Küldés') }}</button>
    </form>
@endsection
