const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */
const path = require('path');

mix.override(webpackConfig => {
    webpackConfig.module.rules.forEach(rule => {
        if (rule.loaders) {        // найдём устаревшие правила
            rule.use     = rule.loaders;  // скопируем в современное поле
            delete rule.loaders;          // и удалим старое
        }
    });
});


mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css');

// Допиливаем Webpack, чтобы Babel обрабатывал laravel-echo и pusher-js
mix.webpackConfig({
    module: {
        rules: [
            {
                test: /\.m?js$/,
                include: [
                    path.resolve(__dirname, 'resources/js'),
                    path.resolve(__dirname, 'node_modules/laravel-echo'),
                    path.resolve(__dirname, 'node_modules/pusher-js'),
                ],
                use: {
                    loader: 'babel-loader',
                    options: mix.config.babel(),
                },
            },
        ],
    },
});
