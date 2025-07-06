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
        'version' => '10.0.0',
    ],
    'simple-datatables/dist/style.min.css' => [
        'version' => '10.0.0',
        'type' => 'css',
    ],
];
