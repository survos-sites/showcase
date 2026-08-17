<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 *
 * @return array<string, array{    // Import name as key, description of the imported file as value
 *     path: string,               // Logical, relative or absolute path to the file
 *     type?: 'js'|'css'|'json',   // Type of the file, defaults to 'js'
 *     entrypoint?: bool,          // Whether the file is an entrypoint, for 'js' only
 * }|array{
 *     version: string,            // Version of the remote package
 *     package_specifier?: string, // Remote "package-name/path" specifier, defaults to the import name
 *     type?: 'js'|'css'|'json',
 *     entrypoint?: bool,
 * }>
 */
return [
    'app' => ['path' => './assets/app.js', 'entrypoint' => true],
    'admin' => ['path' => './assets/admin.js', 'entrypoint' => true],
    '@symfony/stimulus-bundle' => ['path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js'],
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    '@hotwired/turbo' => ['version' => '8.0.20'],
    'masonry' => ['version' => '0.0.2'],
    'outlayer' => ['version' => '2.1.1'],
    'get-size' => ['version' => '3.0.0'],
    'ev-emitter' => ['version' => '2.1.2'],
    'fizzy-ui-utils' => ['version' => '3.0.0'],
    'bootstrap' => ['version' => '5.3.8'],
    '@popperjs/core' => ['version' => '2.11.8'],
    'bootstrap/dist/css/bootstrap.min.css' => ['version' => '5.3.8', 'type' => 'css'],
    '@tabler/core' => ['version' => '1.4.0'],
    '@tabler/core/dist/css/tabler.min.css' => ['version' => '1.4.0', 'type' => 'css'],
    'simple-datatables' => ['version' => '10.3.0'],
    'locutus/php/strings/sprintf' => ['version' => '2.0.32'],
    'locutus/php/strings/vsprintf' => ['version' => '2.0.32'],
    'locutus/php/math/round' => ['version' => '2.0.32'],
    'locutus/php/math/max' => ['version' => '2.0.32'],
    'locutus/php/math/min' => ['version' => '2.0.32'],
    'locutus/php/strings/strip_tags' => ['version' => '2.0.32'],
    'locutus/php/datetime/strtotime' => ['version' => '2.0.32'],
    'locutus/php/datetime/date' => ['version' => '2.0.32'],
    'locutus/php/var/boolval' => ['version' => '2.0.32'],
    'escape-html' => ['version' => '1.0.3'],
    'fos-routing' => ['version' => '0.0.6'],
    'flag-icons' => ['version' => '7.5.0'],
    'flag-icons/css/flag-icons.min.css' => ['version' => '7.5.0', 'type' => 'css'],
    '@stimulus-components/dialog' => ['version' => '1.0.1'],
    '@andypf/json-viewer' => ['version' => '2.8.0'],
    'reveal.js' => ['version' => '6.0.1'],
    'reveal.js/dist/reveal.css' => ['version' => '6.0.1', 'type' => 'css'],
    'reveal.js/dist/theme/black.css' => ['version' => '6.0.1', 'type' => 'css'],
    'reveal.js/plugin/highlight' => ['version' => '6.0.1'],
    'reveal.js/dist/plugin/highlight/monokai.css' => ['version' => '6.0.1', 'type' => 'css'],
    'debug' => ['version' => '4.4.3'],
    'ms' => ['version' => '2.1.3'],
    'ai' => ['version' => '6.0.5'],
    'markdown-to-jsx' => ['version' => '7.7.17'],
    '@ai-sdk/gateway' => ['version' => '3.0.4'],
    '@ai-sdk/provider-utils' => ['version' => '4.0.2'],
    '@ai-sdk/provider' => ['version' => '3.0.1'],
    'zod/v4' => ['version' => '4.3.4'],
    '@opentelemetry/api' => ['version' => '1.9.0'],
    'react' => ['version' => '19.2.0'],
    '@vercel/oidc' => ['version' => '3.0.5'],
    'eventsource-parser/stream' => ['version' => '3.0.6'],
    'zod/v3' => ['version' => '4.3.4'],
    '@standard-schema/spec' => ['version' => '1.1.0'],
    'chart.js' => ['version' => '4.5.1'],
    '@kurkle/color' => ['version' => '0.3.4'],
    'dexie' => ['version' => '4.4.2'],
    '@tacman1123/twig-browser' => ['version' => '1.0.0'],
    '@tacman1123/twig-browser/src/compat/compileTwigBlocks.js' => ['version' => '1.0.0'],
    '@tacman1123/twig-browser/adapters/symfony' => ['version' => '1.0.0'],
    '@swc/helpers/esm/_sliced_to_array.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_to_consumable_array.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_define_property.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_extends.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_object_destructuring_empty.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_object_spread.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_object_spread_props.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_type_of.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_instanceof.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_object_without_properties.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_call_super.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_class_call_check.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_create_class.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_inherits.js' => ['version' => '0.5.18'],
    '@swc/helpers/esm/_wrap_native_super.js' => ['version' => '0.5.18'],
    '@symfony/ux-live-component' => ['path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js'],
    '@floating-ui/dom' => ['version' => '1.8.0'],
    '@floating-ui/core' => ['version' => '1.8.0'],
    '@floating-ui/utils' => ['version' => '0.2.12'],
    '@floating-ui/utils/dom' => ['version' => '0.2.12'],
    'marked' => ['version' => '18.0.9'],
    'simple-datatables/dist/style.min.css' => ['version' => '10.3.0', 'type' => 'css'],
];
