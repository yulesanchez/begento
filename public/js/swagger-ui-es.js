/**
 * Traducción al español de la interfaz de Swagger UI.
 *
 * Swagger UI no soporta i18n, así que se reescriben las cadenas conocidas del
 * DOM (texto, placeholders y atributos title/aria-label) después de cada
 * render. El diccionario es idempotente: una cadena ya traducida deja de
 * coincidir con la clave original y no se vuelve a traducir.
 */
(function () {
    'use strict';

    var translations = new Map(Object.entries({
        // Barra superior y filtros
        'Filter by tag or path': 'Filtrar por etiqueta o ruta',
        'Filter': 'Filtrar',
        'Explore': 'Explorar',
        'Servers': 'Servidores',
        'Schemas': 'Esquemas',
        'Models': 'Modelos',
        'Authorize': 'Autorizar',
        'Available authorizations': 'Autorizaciones disponibles',
        'Close': 'Cerrar',
        'Logout': 'Salir',
        'Download': 'Descargar',
        'Skip to operations': 'Ir a las operaciones',
        'Switch to light mode': 'Cambiar a modo claro',
        'Switch to dark mode': 'Cambiar a modo oscuro',
        'Copy path to clipboard': 'Copiar ruta al portapapeles',
        'Copy to clipboard': 'Copiar al portapapeles',
        'Copied': 'Copiado',
        'Loading...': 'Cargando...',
        // Operaciones
        'Try it out': 'Probar',
        'Execute': 'Ejecutar',
        'Cancel': 'Cancelar',
        'Clear': 'Limpiar',
        'Reset': 'Restablecer',
        'Deprecated': 'Obsoleto',
        // Petición
        'Parameters': 'Parámetros',
        'No parameters': 'Sin parámetros',
        'Request body': 'Cuerpo de la petición',
        'Request URL': 'URL de la petición',
        'Request content type': 'Tipo de contenido de la petición',
        'Media type': 'Tipo de contenido',
        'Media Type': 'Tipo de contenido',
        'Example Value': 'Valor de ejemplo',
        'Schema': 'Esquema',
        'Code': 'Código',
        'Edit Value': 'Editar valor',
        'Send empty value': 'Enviar valor vacío',
        'File': 'Archivo',
        'required': 'obligatorio',
        'Required': 'Obligatorio',
        // Respuestas
        'Responses': 'Respuestas',
        'Response body': 'Cuerpo de la respuesta',
        'Response headers': 'Cabeceras de la respuesta',
        'Links': 'Enlaces',
        'No links': 'Sin enlaces',
        'Undocumented': 'No documentado',
        'Server response': 'Respuesta del servidor',
        'Headers': 'Cabeceras',
        'Headers:': 'Cabeceras:',
        // Tablas de esquemas
        'Name': 'Nombre',
        'Description': 'Descripción',
        'Default value': 'Valor por defecto',
        'Type': 'Tipo',
        'Expand operation': 'Expandir operación',
        'Collapse operation': 'Contraer operación',
    }));

    function translateTextNodes(root) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                var parent = node.parentElement;
                if (!parent || parent.closest('script, style, code, pre, textarea')) {
                    return NodeFilter.FILTER_REJECT;
                }

                return translations.has(node.nodeValue.trim())
                    ? NodeFilter.FILTER_ACCEPT
                    : NodeFilter.FILTER_REJECT;
            }
        });

        var nodes = [];
        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }

        nodes.forEach(function (node) {
            var original = node.nodeValue;
            node.nodeValue = original.replace(original.trim(), translations.get(original.trim()));
        });
    }

    function translateAttributes(root) {
        root.querySelectorAll('[placeholder], [title], [aria-label]').forEach(function (element) {
            ['placeholder', 'title', 'aria-label'].forEach(function (attribute) {
                var current = element.getAttribute(attribute);
                if (current && translations.has(current.trim())) {
                    element.setAttribute(attribute, translations.get(current.trim()));
                }
            });
        });
    }

    function translate(root) {
        if (!root) {
            return;
        }

        translateTextNodes(root);
        translateAttributes(root);
    }

    var scheduled = false;

    function schedule() {
        if (scheduled) {
            return;
        }

        scheduled = true;
        window.requestAnimationFrame(function () {
            scheduled = false;
            translate(document.body);
        });
    }

    function start() {
        translate(document.body);

        new MutationObserver(schedule).observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
