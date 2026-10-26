const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Vendored bundles (materialize, jquery plugins) must go through
 | .scripts()/.styles() — never .js()/.sass() — so webpack doesn't
 | re-process already-minified code. Run `npm run production` to
 | refresh public/mix-manifest.json after any resources/ change.
 |
 */
mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/front.js', 'public/js/')
    .js('resources/js/plugins/dashboard/script.js', 'public/js/dashboard/')
    .scripts([
        'resources/js/plugins/materialize/js/bin/materialize.min.js',
    ], 'public/js/vendor-materialize.js')
    .scripts(['resources/js/plugins/jquery/jquery.anchorScroll.min.js'], 'public/js/plugins.js')
    .sass('resources/sass/app.scss', 'public/css')
    .sass('resources/sass/materialize/materialize.scss', 'public/css')
    .sass('resources/sass/dashboard/style.scss', 'public/css/dashboard')
    .version();
