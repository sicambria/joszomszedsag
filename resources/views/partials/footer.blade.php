<footer class="page-footer">
    @include('helpers.footer_logo')
    <div class="container">
        <div class="row">
            <div class="col l4 m4 s12">
                <h5>{{ config('site.organization_name') }}</h5>
                <p>{{ config('site.organization_slogan') }}</p>
                <ul>
                    <li>
                        <span class="d-inline-block">{!! config('site.organization_address') !!}</span>
                    </li>
                    <li>
                        <span class="d-inline-block"><i class="material-icons">phone</i></span>
                        <a href="tel:{{ config('site.organization_phone') }}">{{ config('site.organization_phone') }}</a>
                    </li>
                    <li>
                        <span class="d-inline-block"><i class="material-icons">chat</i></span>
                        <a href="mailto:{{ config('site.organization_email') }}">{{ config('site.organization_email') }}</a>
                    </li>
                </ul>
                
            </div>
            <div class="col l4 m4 s12">
                <ul>
                    <li class="svg-logo"><a href="#!">@include('helpers.footer_svg')</a></li>
                </ul>
            </div>
            <div class="col l4 m4 s12">
                <h5>{{ __('Hasznos linkek') }}</h5>
                <ul>
                    {{ menu('footer') }}
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-copyright">
        <div class="container">
            <a class="left" href="{{route('error_reporting')}}">{{ __('Hiba jelentése') }}</a>
            <div class="right">
                <span>&copy; {{ date('Y') }} - {{ __('Jószomszédság Vigyázó Hálózata') }} </span>
                <a class="right" href="https://github.com/sicambria/joszomszedsag" target="_blank">@include('helpers.logo')</a>
            </div>

        </div>
    </div>
</footer>