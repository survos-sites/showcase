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
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.13',
    ],
    'masonry' => [
        'version' => '0.0.2',
    ],
    'masonry-layout' => [
        'version' => '4.2.2',
    ],
    'outlayer' => [
        'version' => '2.1.1',
    ],
    'get-size' => [
        'version' => '3.0.0',
    ],
    'ev-emitter' => [
        'version' => '2.1.2',
    ],
    'fizzy-ui-utils' => [
        'version' => '3.0.0',
    ],
    'desandro-matches-selector' => [
        'version' => '2.0.2',
    ],
    'bootstrap' => [
        'version' => '5.3.7',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.7',
        'type' => 'css',
    ],
    'bootstrap-icons/font/bootstrap-icons.min.css' => [
        'version' => '1.13.1',
        'type' => 'css',
    ],
    '@tabler/core' => [
        'version' => '1.3.2',
    ],
    '@tabler/core/dist/css/tabler.min.css' => [
        'version' => '1.3.2',
        'type' => 'css',
    ],
    'asciinema-player' => [
        'version' => '3.10.0',
    ],
    'asciinema-player/dist/bundle/asciinema-player.css' => [
        'version' => '3.10.0',
        'type' => 'css',
    ],
    'simple-datatables' => [
        'version' => '9.2.2',
    ],
    'simple-datatables/dist/style.min.css' => [
        'version' => '9.2.2',
        'type' => 'css',
    ],
    'twig' => [
        'version' => '1.17.1',
    ],
    'locutus/php/strings/sprintf' => [
        'version' => '2.0.16',
    ],
    'locutus/php/strings/vsprintf' => [
        'version' => '2.0.16',
    ],
    'locutus/php/math/round' => [
        'version' => '2.0.16',
    ],
    'locutus/php/math/max' => [
        'version' => '2.0.16',
    ],
    'locutus/php/math/min' => [
        'version' => '2.0.16',
    ],
    'locutus/php/strings/strip_tags' => [
        'version' => '2.0.16',
    ],
    'locutus/php/datetime/strtotime' => [
        'version' => '2.0.16',
    ],
    'locutus/php/datetime/date' => [
        'version' => '2.0.16',
    ],
    'locutus/php/var/boolval' => [
        'version' => '2.0.16',
    ],
    'stimulus-attributes' => [
        'version' => '1.0.1',
    ],
    'escape-html' => [
        'version' => '1.0.3',
    ],
    'fos-routing' => [
        'version' => '0.0.6',
    ],
    'flag-icons' => [
        'version' => '7.5.0',
    ],
    'flag-icons/css/flag-icons.min.css' => [
        'version' => '7.5.0',
        'type' => 'css',
    ],
    'instantsearch.js' => [
        'version' => '4.79.1',
    ],
    '@algolia/events' => [
        'version' => '4.0.1',
    ],
    'algoliasearch-helper' => [
        'version' => '3.26.0',
    ],
    'qs' => [
        'version' => '6.9.7',
    ],
    'algoliasearch-helper/types/algoliasearch.js' => [
        'version' => '3.26.0',
    ],
    'instantsearch.js/es/widgets' => [
        'version' => '4.79.1',
    ],
    'instantsearch-ui-components' => [
        'version' => '0.11.2',
    ],
    'preact' => [
        'version' => '10.26.9',
    ],
    'hogan.js' => [
        'version' => '3.0.2',
    ],
    'htm/preact' => [
        'version' => '3.1.1',
    ],
    'preact/hooks' => [
        'version' => '10.26.9',
    ],
    '@babel/runtime/helpers/extends' => [
        'version' => '7.27.6',
    ],
    '@babel/runtime/helpers/defineProperty' => [
        'version' => '7.27.6',
    ],
    '@babel/runtime/helpers/objectWithoutProperties' => [
        'version' => '7.27.6',
    ],
    'htm' => [
        'version' => '3.1.1',
    ],
    'instantsearch.css/themes/algolia.min.css' => [
        'version' => '8.5.1',
        'type' => 'css',
    ],
    '@meilisearch/instant-meilisearch' => [
        'version' => '0.27.0',
    ],
    'meilisearch' => [
        'version' => '0.51.0',
    ],
    '@stimulus-components/dialog' => [
        'version' => '1.0.1',
    ],
    '@andypf/json-viewer' => [
        'version' => '2.1.10',
    ],
    'pretty-print-json' => [
        'version' => '3.0.5',
    ],
    'pretty-print-json/dist/css/pretty-print-json.min.css' => [
        'version' => '3.0.5',
        'type' => 'css',
    ],
];
