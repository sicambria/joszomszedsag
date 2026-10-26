<nav>
    <div class="nav-wrapper container">
        <a href="/" class="brand-logo">
            @if (config('site.logo'))
                <img src="{{ config('site.logo') }}" />
            @else
                @include('helpers.header_logo')
            @endif
        </a>
        <ul>
            <li><a href="#"><a class="waves-effect red btn-large login-button" href="{{ route('login') }}">{{ __('Bejelentkezés') }}</a></a></li>
        </ul>
    </div>
</nav>