<?php

/*
Plugin Name: Kniff Oxygen Helpers
Author: Florian Groh
Author URI: https://kniff.at
GitHub Plugin URI: flowgrow/oxygen-helpers
Primary Branch: main
Description: Adding custom elements and functionality to Oxygen.
Version: 1.0.1
*/

add_action('plugins_loaded', 'my_oxygen_elements_init');

function my_oxygen_elements_init()
{

    if (!class_exists('OxygenElement')) {
        return;
    }

    foreach (glob(plugin_dir_path(__FILE__) . "elements/*.php") as $filename) {
        include $filename;
    }

    foreach (glob(plugin_dir_path(__FILE__) . "functions/*.php") as $filename) {
        include $filename;
    }
}


// Allow SVG
add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {

    global $wp_version;
    if ($wp_version !== '4.7.1') {
        return $data;
    }

    $filetype = wp_check_filetype($filename, $mimes);

    return [
        'ext'             => $filetype['ext'],
        'type'            => $filetype['type'],
        'proper_filename' => $data['proper_filename']
    ];
}, 10, 4);

function cc_mime_types($mimes)
{
    $mimes['svg'] = 'image/svg+xml';
    $mimes['json'] = 'applictaion/json';
    $mimes['lottie'] = 'application/zip';
    return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');


function kniff_add_allow_upload_extension_exception($data, $file, $filename, $mimes, $real_mime = null)
{
    if ($data['ext'] != false && $data['type'] != false) return $data;

    $f_sp = explode(".", $filename);
    $f_exp_count  = count($f_sp);

    if ($f_exp_count <= 1) {
        // Filename type is "XXX" (There is not file extension). 
        return $data;
    } else {
        $f_ext  = $f_sp[$f_exp_count - 1];
    }

    if ($f_ext == "json") {
        $data['ext'] = "json";
        $data['type'] = "application/json";
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'kniff_add_allow_upload_extension_exception', 10, 5);

