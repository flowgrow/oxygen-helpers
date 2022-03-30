<?php

add_action('wp_head', function () {
  // **************** START UMAMI ******************** //
  // $umami_id = '5b44a57d-b236-4d0f-b1cf-ce78cb3617ae';
  // $umami_url = home_url('/').'ruby/ruby.js';
  // echo "<script async defer data-website-id='$umami_id' src='$umami_url'></script>";
  // **************** END UMAMI ******************** //

  echo "<script>document.documentElement.classList.add('js')</script>";
  echo "<meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1'>";
});

add_filter('body_class', function ($classes) {
  return array_merge($classes, array('running-text'));
});
