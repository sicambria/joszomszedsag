{{-- Bespoke replacement for Voyager's menu('user_admin') (removed MOD-02). --}}
@php
    $isActive = fn ($route) => \Illuminate\Support\Facades\Request::routeIs($route) ? 'mm-active' : '';
@endphp
<li class="app-sidebar__heading">{{ __('Segítségkérések') }}</li>
<li><a href="{{ route('admin_new_help_request') }}" class="{{ $isActive('admin_new_help_request') }}">{{ __('Új segítségkérés') }}</a></li>
<li><a href="{{ route('admin_open_help_request') }}" class="{{ $isActive('admin_open_help_request') }}">{{ __('Nyitott segítségkérések') }}</a></li>
<li><a href="{{ route('admin_own_help_request') }}" class="{{ $isActive('admin_own_help_request') }}">{{ __('Elvállalt segítségkérések') }}</a></li>
<li><a href="{{ route('admin_my_help_request') }}" class="{{ $isActive('admin_my_help_request') }}">{{ __('Leadott segítségkéréseim') }}</a></li>
<li class="app-sidebar__heading">{{ __('Képviselők') }}</li>
<li><a href="{{ route('admin_volunteers_list') }}" class="{{ $isActive('admin_volunteers_list') }}">{{ __('Vigyázók') }}</a></li>
<li><a href="{{ route('admin_profile') }}" class="{{ $isActive('admin_profile') }}">{{ __('Profil') }}</a></li>
@if (auth()->user() && auth()->user()->isAdmin())
    <li class="app-sidebar__heading">{{ __('Adminisztráció') }}</li>
    <li><a href="{{ route('bulk_mail_index') }}" class="{{ $isActive('bulk_mail_index') }}">{{ __('Körlevelek') }}</a></li>
@endif
