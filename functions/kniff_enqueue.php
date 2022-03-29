<?php

function enqueue_template_part_files($buffer)
{
  // modify buffer here, and then return the updated code
  global $kniff_enqueue_files;
  $header = "";
  $footer = "";

  foreach ($kniff_enqueue_files['styles'] as $style_id => $stylesheet) {
    $header .= '<link id="css-'.$style_id.'" rel="stylesheet" href="' . $stylesheet[0] . '" type="text/css" media="' . $stylesheet[1] . '"/>';
  }

  foreach ($kniff_enqueue_files['templates'] as $template) {
    try {
      $footer .= kniff_file_get_contents($template[0]);
    } catch (Exception $ex) {
      $footer .= '<pre>' . print_r($ex, true) . '</pre>';
    }
  }

  foreach ($kniff_enqueue_files['scripts'] as $script_id => $script) {
    $html = '<script id="js-'.$script_id.'" src="' . $script[0] . '"></script>';
    if ($script[1]) {
      $footer .= $html;
    } else {
      $header .= $html;
    }
  }


  if ($header != "") {
    $buffer = str_replace(
      '</head>',
      $header . '</head>',
      $buffer
    );
  }

  if ($footer != "") {
    $buffer = str_replace(
      "<!-- WP_FOOTER -->",
      "<!-- KNIFF_ENQUEUE -->$footer<!-- /KNIFF_ENQUEUE -->\n<!-- WP_FOOTER -->",
      $buffer
    );
  }

  return $buffer;
}

function kniff_enqueue_script($id, $url, $version = false, $footer = true)
{
  if (!$url) return;
  global $kniff_enqueue_files;
  $wp_scripts = wp_scripts();

  if (
    !array_key_exists($id, $wp_scripts->registered) &&
    !array_key_exists($id, $kniff_enqueue_files['scripts'])
  ) {
    $url = apply_filters('script_loader_src', $url, $id);
    if ($version !== false) {
      $url .= "?$version";
    }
    $kniff_enqueue_files['scripts'][$id] = [$url, $footer];
  }
}

function kniff_enqueue_template($id, $url)
{
  if (!$url) return;
  global $kniff_enqueue_files;

  if (!array_key_exists($id, $kniff_enqueue_files['templates'])) {
    $kniff_enqueue_files['templates'][$id] = [$url];
  }
}

function kniff_enqueue_style($id, $url, $version = false, $media = "all")
{
  if (!$url) return;
  global $kniff_enqueue_files;
  $wp_styles = wp_styles();

  if (
    !array_key_exists($id, $wp_styles->registered) &&
    !array_key_exists($id, $kniff_enqueue_files['styles'])
  ) {
    $url = apply_filters('style_loader_src', $url, $id);
    if ($version !== false) {
      $url .= "?$version";
    }
    $kniff_enqueue_files['styles'][$id] = [$url, $media];
  }
}

function kniff_enqueue_buffer_start()
{
  global $kniff_enqueue_files;
  $kniff_enqueue_files = [
    'styles' => [],
    'scripts' => [],
    'templates' => []
  ];
  ob_start("enqueue_template_part_files");
}

function kniff_enqueue_buffer_end()
{
  global $kniff_enqueue_files;
  if ($kniff_enqueue_files) {
    ob_end_flush();
  }
}

add_action('template_redirect', 'kniff_enqueue_buffer_start', 3);
add_action('shutdown', 'kniff_enqueue_buffer_end', -1);
