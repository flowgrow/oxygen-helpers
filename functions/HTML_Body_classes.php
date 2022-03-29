<?php

add_action('wp_head', function () {
  echo "<script>document.documentElement.classList.add('js')</script>";
});

add_filter('body_class', function ($classes) {
  return array_merge($classes, array('running-text'));
});
