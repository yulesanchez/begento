<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentación de la API · Begento</title>
    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset('default', 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset('default', 'favicon-32x32.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset('default', 'favicon-16x16.png') }}" sizes="16x16"/>
    <style>
        html {
            box-sizing: border-box;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }
    </style>
</head>

<body>
<div id="swagger-ui"></div>

<script src="{{ l5_swagger_asset('default', 'swagger-ui-bundle.js') }}"></script>
<script src="{{ l5_swagger_asset('default', 'swagger-ui-standalone-preset.js') }}"></script>
<script>
    window.onload = function () {
        window.ui = SwaggerUIBundle({
            dom_id: '#swagger-ui',
            url: @json(route('l5-swagger.default.docs')),
            operationsSorter: @json(config('l5-swagger.documentations.default.operations_sort')),
            validatorUrl: @json(config('l5-swagger.documentations.default.validator_url')),
            requestInterceptor: function (request) {
                request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                return request;
            },
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: 'StandaloneLayout',
            docExpansion: @json(config('l5-swagger.defaults.ui.display.doc_expansion', 'none')),
            deepLinking: true,
            filter: {{ config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' }},
            persistAuthorization: @json((bool) config('l5-swagger.defaults.ui.authorization.persist_authorization'))
        });
    };
</script>
<script src="{{ asset('js/swagger-ui-es.js') }}?v={{ md5_file(public_path('js/swagger-ui-es.js')) }}"></script>
</body>
</html>
