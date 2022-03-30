<?php

class OxygenElementOverride extends OxygenElement
{
  function ajax_render_callback()
  {
    define("OXY_ELEMENTS_API_AJAX", true);
    oxygen_vsb_ajax_request_header_check();

    $component_json = file_get_contents('php://input');
    $component      = json_decode($component_json, true);
    $options = $this->component->set_options(['ct_options' => json_encode($component['component']['options'])]);
    $options['tag'] = isset($options['html_tag']) ? $options['html_tag'] : $this->params['html_tag'];
    $this->params["shortcode_options"] = $options;

    if (isset($this->params['php_callback'])) {
      $processed_options = $this->unprefix_options($options);
      $processed_options = $this->base64_decode_options($processed_options);
      call_user_func_array($this->getParam('php_callback'), array($this->unprefix_options($options), $this->unprefix_options($this->defaults), false));
    }
    die();
  }
}

class OxyElOverride extends OxyEl
{

  public $El;

  function __construct()
  {

    $name = $this->name();
    $slug = $this->name2slug($name);

    if ($this->slug()) {
      $slug = $this->slug();
    }

    $this->custom_init();

    // store a slug to class name reference in the global space
    global $oxy_el_slug_classes;

    if (!is_array($oxy_el_slug_classes)) {
      $oxy_el_slug_classes = array();
    }

    $oxy_el_slug_classes[$slug] = get_class($this);


    $options = array();
    if (method_exists($this, 'options')) {
      $options = $this->options();
    }

    $server_side_render = true;
    if (isset($options['server_side_render'])) {
      $server_side_render = $options['server_side_render'];
    }

    if (method_exists($this, 'button_priority')) {
      $options['button_priority'] = $this->button_priority();
    }

    $this->El = new OxygenElementOverride(__($name), $slug, '', $this->icon(), $this->button_place(), $options, $this->has_js);

    $this->El->setTag($this->tag());

    if (method_exists($this, 'init')) {
      $this->init();
    }

    if (method_exists($this, 'defaultCSS')) {
      $this->El->pageCSS(
        $this->defaultCSS()
      );
    }

    if (method_exists($this, 'customCSS')) {
      add_filter(
        "oxygen_id_styles_filter-" . $this->El->get_tag(),
        function ($styles, $states, $selector) {
          // doesn't work with states or media for now only 'original' options
          $styles .= $this->customCSS($states['original'], $selector);
          return $styles;
        },
        10,
        3
      );
    }

    if (method_exists($this, 'enableFullPresets') && $this->enableFullPresets() == true) {
      add_filter("oxygen_elements_with_full_presets", function ($elements) {
        if (!is_array($elements)) {
          $elements = array();
        }
        $elements[] = $this->El->get_tag();
        return $elements;
      });
    }

    $this->controls();
    $this->El->controlsReady();

    if ($server_side_render) {
      $this->El->PHPCallback(
        array($this, 'render'),
        $this->class_names()
      );
    } else {
      $this->El->HTML(
        $this->render(),
        $this->class_names()
      );
    }

    $this->El->set_prefilled_components($this->prefilledComponentStructure());

    /**
     * Keep it very last one
     */

    if (method_exists($this, 'afterInit')) {
      $this->afterInit();
    }
  }
}

class ResponsiveImageElement extends OxyElOverride
{

  function init()
  {
    add_action("oxygen_default_classes_output", array($this->El, "generate_defaults_css"));
  }

  // function enableFullPresets()
  // {
  //   return true;
  // }

  function afterInit()
  {
    // Do things after init, like remove apply params button and remove the add button.
    // $this->removeApplyParamsButton();
    // $this->removeAddButton();
  }

  function name()
  {
    return 'Responsive Image';
  }

  function slug()
  {
    return "fg-responsive-image";
  }

  function icon()
  {
    return CT_FW_URI . '/toolbar/UI/oxygen-icons/add-icons/image.svg';
  }

  function customCSS($options, $selector)
  {
    $obj_fit = $options["oxy-fg-responsive-image_fg_obj_size"];
    $obj_position = $options["oxy-fg-responsive-image_fg_obj_position"];
    if ($obj_fit == "cover" || $obj_fit == "contain") {
      return "$selector img {" .
        "object-fit: $obj_fit;" .
        "object-position: $obj_position;" .
        "position: absolute;" .
        "top: 0;" .
        "left: 0;" .
        "width: 100%;" .
        "height: 100%;" .
        "}" .
        "$selector figcaption {" .
        "position: absolute;" .
        "bottom: 0;" .
        "left: 0;" .
        "width: 100%;" .
        "padding: 2rem;" .
        "background: black;" .
        "color: white;" .
        "}";
    } else {
      return "$selector img {" .
        "object-fit: initial;" .
        "position: static;" .
        "width: 100%;" .
        "height: auto;" .
        "}";
    }
  }

  function get_image_array_from_id($attachment_id)
  {
    $image = array();
    $image['id'] = $attachment_id;
    $image['alt'] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    $image['caption'] = wp_get_attachment_caption($attachment_id);
    $image['title'] = get_the_title($attachment_id);
    $meta = wp_get_attachment_metadata($attachment_id);

    $mime = get_post_mime_type($attachment_id);
    $exploded = explode('/', $mime);

    $image['mime_type'] = $mime;
    $image['type'] = $exploded[0];
    $image['subtype'] = $exploded[1];
    $image['url'] = wp_get_attachment_url($attachment_id);

    if (isset($meta['width'])) {
      $image['srcset'] = [];
      $image['width'] = $meta['width'];
      $image['height'] = $meta['height'];
      $image['sizes'] = array();
      $basepath = wp_get_upload_dir()['basedir'];
      $year_month_dir = dirname($meta['file']);
      $baseurl = dirname($image['url']);

      // First add the full size src
      $image['sizes']['full'] = trailingslashit($basepath) . $meta['file'];
      $image['srcset'][] = trailingslashit($baseurl) . basename($meta['file']) . ' ' . $image['width'] . 'w';

      // sort from large to small
      usort($meta['sizes'], fn ($a, $b) => $b['width'] - $a['width']);
      $last_size = $image['width'];
      $eps = 0.1;
      $ratio = $image['width'] / $image['height'];
      foreach ($meta['sizes'] as $key => $elem) {
        // only take smaller versions, that have the same ratio as full
        if (abs($ratio - $elem['width'] / $elem['height']) > $eps) continue;

        // only take versions, that are at least 200px in width apart
        if ($last_size - $elem['width'] < 200) continue;

        // update last_size
        $last_size = $elem['width'];

        // absolute path for getting local file content
        $path = trailingslashit($basepath) . trailingslashit($year_month_dir) . $elem['file'];

        // url for srcset
        $url = trailingslashit($baseurl) . $elem['file'];

        $image['sizes'][$key] =  $path;
        $image['srcset'][] = $url . ' ' . $elem['width'] . 'w';
      }
      $image['srcset'] = join(', ', $image['srcset']);
    } elseif (($full = wp_get_attachment_image_src($image['id'], 'full')) !== false && $full[0] != 0) {
      $image['srcset'] = [];
      $upload_dir = wp_get_upload_dir();
      $image['sizes']['full'] =  str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $full[0]);
      $image['srcset'][] = $full[0] . ' ' . $full[1] . 'w';
      $image['width'] = $full[1];
      $image['height'] = $full[2];

      $additional_sizes = ['large', 'medium'];
      foreach ($additional_sizes as $key) {
        $url = wp_get_attachment_image_src($image['id'], $key);
        $image['sizes'][$key] = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url[0]);
        $image['srcset'][] = $url[0] . ' ' . $url[1] . 'w';
      }
      $image['srcset'] = join(', ', $image['srcset']);
    }

    return $image;
  }

  function get_base64($image)
  {
    if (isset($image['sizes']['tiny'])) {
      $file_data = kniff_file_get_contents($image['sizes']['tiny']);
    }

    if ((!isset($file_data) || strlen($file_data) > 5000) && ($image['width'] && $image['height'])) {
      $file_data = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $image['width'] . ' ' . $image['height'] . '" style="background:#eee"></svg>';
      $image['mime_type'] = 'image/svg+xml';
    }

    if (isset($file_data) && strlen($file_data) > 10) {
      $b64 = base64_encode($file_data);
      return 'data:' . $image['mime_type'] . ';base64,' . $b64;
    }

    return false;
  }

  function is_width_unit_relative($width_unit)
  {
    return $width_unit == NULL || $width_unit == '%' || $width_unit == 'vw';
  }

  function is_height_unit_relative($height_unit)
  {
    return $height_unit == '%' || $height_unit == 'vh';
  }

  function get_sizes_attr_for_mediaquery($query_name, $image, $options, $new_id = true)
  {
    global $media_queries_list;
    $p = "oxy-fg-responsive-image_";

    if ($options[$p . "fg_sizes"] == 'custom') {
      return $options[$p . "fg_custom_sizes"];
    }

    $container_width = floatval($options['width']);
    $container_height = floatval($options['height']);

    if ($container_width <= 0) return "";

    $wu = $options['width-unit'];
    $hu = $options['height-unit'];
    $wu_relative = $this->is_width_unit_relative($wu);
    $hu_relative = $this->is_height_unit_relative($hu);
    $query_max_width = PHP_INT_MAX;

    if ($query_name == "over-page-width") {
      $query_min_width = $media_queries_list['page-width']['maxSize'] + 1;
      $query_and = "(min-width: {$query_min_width}px) ";
    } elseif (isset($media_queries_list[$query_name])) {
      $query_max_width = $media_queries_list[$query_name]['maxSize'];
      $query_and = "(max-width: $query_max_width) ";
    } else {
      $query_and = "";
    }

    /* Default sizes == ($obj_size == 'auto' || $obj_size == 'contain')*/
    $out = $query_and . ($wu_relative ? $container_width . "vw" : $container_width . $wu);

    $obj_size = $options[$p . 'fg_obj_size'];
    if ($obj_size == 'cover') {

      if ($container_height <= 0) return $out;

      $img_ratio = round($image['width'] / $image['height'], 3);

      if ($query_and != "") {
        $query_and .= 'and ';
      }

      if ($wu_relative == $hu_relative) {

        if ($wu_relative) {
          $wu = 'vw';
          $hu = 'vh';
        }

        $container_ratio = $container_width / $container_height;
        $screen_ratio = round($img_ratio / $container_ratio * 1000);
        $out = $query_and . "(max-aspect-ratio: $screen_ratio / 1000) calc($img_ratio * {$container_height}$hu)";
        if ($new_id) $out .= ", {$container_width}$wu";
      } elseif ($wu_relative && !$hu_relative) {
        $max_width = ceil(($container_height * $img_ratio) / ($container_width / 100));

        if ($max_width < intval($query_max_width)) {
          $the_query = "(max-width: {$max_width}px)";
        } else {
          $the_query = "(max-width: {$query_max_width})";
        }
        $px_width = ceil($img_ratio * $container_height);
        $out = "$the_query {$px_width}$hu";
        if ($new_id) $out .= ", {$container_width}vw";
      } elseif (!$wu_relative && $hu_relative) {
        $min_height = ($container_width / $img_ratio) / ($container_height / 100);
        $out = $query_and . "(min-height: {$min_height}px) calc($img_ratio * {$container_height}vh)";
        if ($new_id) $out .= ", {$container_width}$wu";
      }
    }

    return $out;
  }

  function get_picture_sources($oxy_states, $lazy = true)
  {
    $p = "oxy-fg-responsive-image_";
    global $media_queries_list;
    $media = $oxy_states['media'];
    if ($media == null) $media = [];
    $empty_state = [
      'sizes' => [],
      'media' => '',
      'srcset' => '',
      'width' => '',
      'height' => '',
      'object-fit' => '',
      'container-width' => '',
      'container-height' => '',
    ];
    $states = ['all' => $empty_state];

    $options = $oxy_states['original'];

    if ($options[$p . 'fg_img_select'] == "select") {
      $id = $options[$p . 'fg_attachment_id'];
    } else {
      $id = get_post_thumbnail_id();
    }

    $image = $this->get_image_array_from_id($id);


    $states['all']['width'] = $image['width'];
    $states['all']['height'] = $image['height'];
    $states['all']['srcset'] = $image['srcset'];
    $states['all']['src'] = $image['url'];
    $states['all']['alt'] = $image['alt'] !== '' ? $image['alt'] : $image['title'];
    $states['all']['object-fit'] = $options[$p . 'fg_obj_size'];
    $states['all']['container-width'] = $options['width'];
    $states['all']['container-height'] = $options['height'];

    $is_sensitive = get_field('sensitive_content', $id);
    if ($is_sensitive) {
      $states['all']['data-sensitive'] = true;
    }

    if ($lazy) {
      $b64 = $this->get_base64($image);
      if ($b64 != false) {
        $states['all']['b64'] = $b64;
      }
    }

    $has_custom_sizes = ($options[$p . "fg_sizes"] == 'custom');

    if (
      !filter_var($options[$p . 'fg_container_full_width'], FILTER_VALIDATE_BOOLEAN) &&
      $this->is_width_unit_relative($options['width-unit']) &&
      !$has_custom_sizes
    ) {
      $original_width = $options['width'];
      $original_unit = $options['width-unit'];
      $options['width'] = ($options['width'] / 100) * floatval($media_queries_list['page-width']['maxSize']);
      $states['all']['container-width'] = $options['width'];
      $options['width-unit'] = 'px';

      $states['all']['sizes'][] = $this->get_sizes_attr_for_mediaquery('over-page-width', $image, $options);
      if (!isset($media['page-width'])) {
        $media = array_merge(['page-width' => [
          'original' => [
            'width' => $original_width,
            'height' => $options['height'],
            'width-unit' => $original_unit,
            'height-unit' => $options['height-unit'],
            $p . 'fg_obj_size' => $options[$p . 'fg_obj_size']
          ]
        ]], $media);
      }
    } else {
      $states['all']['sizes'][] = $this->get_sizes_attr_for_mediaquery('none', $image, $options);
    }

    $states = array_merge($states, array_fill_keys(array_keys($media), $empty_state));

    $prev_query = 'all';
    foreach ($media as $query_name => $options) {
      $options = $options['original'];
      if ($options[$p . 'fg_img_select'] == "select") {
        $id = $options[$p . 'fg_attachment_id'];
      } else {
        $id = get_post_thumbnail_id();
      }

      $get_new_sizes = !$has_custom_sizes && $options[$p . 'fg_obj_size'] != $states[$prev_query]['object-fit'];
      $get_new_sizes |= $options['width'] != $states[$prev_query]['container-width'];

      if (!$id) {
        $image = $this->get_image_array_from_id($id);
        $query_max_width = $media_queries_list[$query_name]['maxSize'];
        $states[$query_name]['media'] = "(max-width: $query_max_width)";
        $states[$query_name]['width'] = $image['width'];
        $states[$query_name]['height'] = $image['height'];
        $states[$query_name]['srcset'] = $image['srcset'];
        $states[$query_name]['src'] = $image['url'];
        $states[$query_name]['object-fit'] = $options[$p . 'fg_obj_size'];
        $states[$query_name]['container-width'] = $options['width'];
        $states[$query_name]['container-height'] = $options['height'];

        $is_sensitive = get_field('sensitive_content', $id);
        if ($is_sensitive) {
          $states[$query_name]['data-sensitive'] = true;
        }

        if ($lazy) {
          $b64 = $this->get_base64($image);
          if ($b64 != false) {
            $states[$query_name]['b64'] = $b64;
          }
        }

        $get_new_sizes |= $states[$query_name]['object-fit'] == "cover";

        if ($get_new_sizes) {
          $states[$query_name]['sizes'][] = $this->get_sizes_attr_for_mediaquery('none', $image, $options);
          $states[$prev_query]['sizes'] = join(', ', array_reverse(array_filter($states[$prev_query]['sizes'])));
        }

        $prev_query = $query_name;
      } elseif ($get_new_sizes) {
        $states[$prev_query]['sizes'][] = $this->get_sizes_attr_for_mediaquery($query_name, $image, $options, false);
      }
    }
    $states[$prev_query]['sizes'] = join(', ', array_reverse(array_filter($states[$prev_query]['sizes'])));

    foreach ($states as $query_name => &$out) {
      unset($out['object-fit']);
      unset($out['container-width']);
      unset($out['container-height']);
      $out = array_filter($out);
    }

    return array_filter($states);
  }

  function render($options, $defaults, $content)
  {

    kniff_enqueue_script('gsap', 'https://unpkg.co/gsap@3/dist/gsap.min.js');

    $base = plugin_dir_url(__DIR__ . "../");
    
    // get protected component value from $this->El
    // https://stackoverflow.com/a/66277441
    $component = (fn () => $this->component)->call($this->El);
    $image = $this->get_image_array_from_id($options['fg_attachment_id']);
    $lazy = filter_var($options['fg_lazy'], FILTER_VALIDATE_BOOLEAN);

    if ($lazy) kniff_enqueue_script('lazyload', $base . 'js/' . 'lazyload.min.js');

    if (isset($_GET['action'])) {
      $lazy = false; // INSIDE BUILDER
      // somehow it doesn't render the customCSS() output, so we have to get it ourselves...
      $params = (fn () => $this->params)->call($this->El);
      $selector = "#{$params['shortcode_options']['selector']}";
      $styles = "";
      $styles .= kniff_file_get_contents(__DIR__ . '/' . basename(__FILE__, '.php') . '.css');
      $styles .= $this->customCSS($component->states['original'], $selector);
      $media = $component->states['media'];
      global $media_queries_list;
      foreach ($media as $query_name => $ops) {
        $ops = $ops['original'];
        $mw = $media_queries_list[$query_name]['maxSize'];
        $styles .= "@media (max-width: $mw) {" . $this->customCSS($ops, $selector) . "}";
      }

      echo '<style type="text/css">' . $styles . '</style>';
    }

    if ($options['fg_img_select'] == "id" && $options['fg_attachment_id'] == "") {
      $html = '<img src="http://via.placeholder.com/1600x900" width="1600" height="900" alt="Placeholder Image" />';
    } else {

      $ct_states = $component->states;
      $ct_states['original'] = array_merge($options, $ct_states['original']);

      // array_reverse because we want <img /> last
      $sources = array_reverse($this->get_picture_sources($ct_states, $lazy));

      //echo '<pre>'.print_r($sources, true).'</pre>';

      $out = [];
      $noscript = [];
      foreach ($sources as $key => $source) {
        $tag = $key == "all" ? "img" : "source";
        if (isset($source['b64'])) {
          //lazy
          $b64 = $source['b64'];
          $source['data-srcset'] = $source['srcset'];
          $source['data-src'] = $source['src'];
          unset($source['b64']);
          unset($source['srcset']);
          unset($source['src']);

          $with_js = "<$tag src='$b64'";
          if ($tag == "img") $with_js .= " class='lazy'";

          $no_js = "<$tag";
          foreach ($source as $attr => $value) {
            $with_js .= " $attr='$value'";
            $no_js .= ' ' . str_replace('data-', '', $attr) . "='$value'";
          }

          $with_js .= ' />';
          $no_js .= ' />';

          $out[] = $with_js;
          $noscript[] = $no_js;
        } else {
          $tmp = "<$tag";
          if ($tag == "img") $tmp .= " loading='eager'";
          foreach ($source as $attr => $value) {
            $tmp .= " $attr='$value'";
          }
          $tmp .= ' />';
          $out[] = $tmp;
        }
      }

      $html = '<picture>' . join('', $out) . '</picture>';
      if (count($noscript) > 0) {
        $html .= '<noscript><picture>' . join('', $noscript) . '</picture></noscript>';
      }
    }

    if ($options['fg_caption'] != "none") {
      if ($options['fg_caption'] == "custom") {
        $caption = $options['fg_custom_caption'];
      } else {
        $caption = $image['caption'];
      }

      $html = "<figure>" . $html . "<figcaption>" . $caption . "</figcaption></figure>";
    }

    echo $html;
  }

  function controls()
  {

    $img_select = $this->addOptionControl(
      array(
        "name" => "",
        "type" => 'buttons-list',
        "slug" => 'fg_img_select'
      )
    );

    $img_select->setValue(
      array(
        'id' => 'Select',
        'featured' => 'Featured Image',
      )
    );
    $img_select->setDefaultValue('id');
    $img_select->whitelist();
    $img_select->rebuildElementOnChange();

    // Option controls can be accessed in the render() function via $options['field_slug']
    $img_control = $this->addOptionControl(
      array(
        "type" => 'mediaurl', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Select Image',
        "slug" => "fg_attachment_id",
        "condition" => "fg_img_select=id"
      )
    );
    $img_control->options['attachment'] = true;
    $img_control->whitelist();

    $width = $this->addStyleControl(
      array(
        "type"       => "measurebox",
        "name"     => __("Width"),
        "property"   => "width",
        "unit" => "%",
      )
    );
    $width->setValue(100);

    $this->addStyleControl(
      array(
        "type"       => "measurebox",
        "name"     => __("Height"),
        "property"   => "height",
        "unit" => "auto",
      )
    );

    $obj_size_control = $this->addOptionControl(
      array(
        "type" => 'buttons-list',
        "name" => 'Image Fit',
        "slug" => 'fg_obj_size'
      )
    );

    $obj_size_control->setValue(
      array(
        'none' => 'Auto',
        'contain' => 'Contain',
        'cover' => 'Cover'
      )
    );
    $obj_size_control->whitelist();
    $obj_size_control->setDefaultValue('none');
    $obj_size_control->rebuildElementOnChange();

    $obj_position_control = $this->addOptionControl(
      array(
        "type" => 'textfield',
        "name" => 'Object Position',
        "slug" => 'fg_obj_position',
        "condition" => "fg_obj_size!=none"
      )
    );
    $obj_position_control->setValue('center center');
    $obj_position_control->rebuildElementOnChange();

    $sizes_control = $this->addOptionControl(
      array(
        "type" => 'buttons-list',
        "name" => 'Sizes Attribute',
        "slug" => 'fg_sizes'
      )
    );
    $sizes_control->setValue(
      array(
        'auto' => 'Auto',
        'custom' => 'Custom'
      )
    );

    $custom_sizes_control = $this->addOptionControl(
      array(
        "type" => 'textfield',
        "name" => 'Custom Sizes Attribute',
        "slug" => 'fg_custom_sizes',
        "condition" => "fg_sizes=custom"
      )
    );
    $custom_sizes_control->setValue('100vw');

    $caption_control = $this->addOptionControl(
      array(
        "type" => 'buttons-list',
        "name" => 'Caption',
        "slug" => 'fg_caption'
      )
    );

    $caption_control->setValue(
      array(
        'none' => 'None',
        'from_attachment' => 'From Media Library',
        'custom' => 'Custom'
      )
    );
    $caption_control->rebuildElementOnChange();

    $custom_caption = $this->addOptionControl(
      array(
        "type" => 'textfield',
        "name" => 'Custom Caption',
        "slug" => 'fg_custom_caption',
        "condition" => "fg_caption=custom"
      )
    );
    $custom_caption->rebuildElementOnChange();
    $custom_caption->whitelist();

    $lazy_load_control = $this->addOptionControl(
      array(
        "type" => 'checkbox',
        "name" => 'Lazyload',
        "slug" => 'fg_lazy',
        "value" => 'true'
      )
    );
    $lazy_load_control->setDefaultValue(false);


    $full_width = $this->addOptionControl(
      array(
        "type" => 'checkbox',
        "name" => 'Container is Full-Width',
        "slug" => 'fg_container_full_width'
      )
    );
    $full_width->setDefaultValue(false);
  }

  function defaultCSS()
  {
    if (function_exists('kniff_file_get_contents')) {
      return kniff_file_get_contents(__DIR__ . '/' . basename(__FILE__, '.php') . '.css');
    } else {
      return file_get_contents(__DIR__ . '/' . basename(__FILE__, '.php') . '.css');
    }
  }
}

new ResponsiveImageElement();


// function dont_care() {
    // $img_src = $image['url'];
    // $img_alt = $image['alt'] !== '' ? $image['alt'] : $image['title'];

    // if ($image['width'] && $image['height']) {
    //   $dimensions = 'width="' . $image['width'] . '" height="' . $image['height'] . '"';
    // } else {
    //   $dimensions = "";
    // }

    // $sizes = "100vw";
    // if ($options['fg_sizes'] == "cover" && $image['width'] && $image['height']) {
    //   $w = $image['width'];
    //   $h = $image['height'];
    //   $sizes = '(max-aspect-ratio: ' . $w . '/' . $h . ') calc(' . $w . ' / ' . $h . ' * 100vh), 100vw';
    // } elseif ($options['fg_sizes'] == "custom" && $options['fg_custom_sizes'] != "") {
    //   $sizes = $options['fg_custom_sizes'];
    // }

    // $do_breakpoint = $options['fg_breakpoint'] && $options['fg_media_query'] != "" && $options["fg_mobile_attachment_id"];

    // $img_tag = '<img ' .
    //   'src="' . $img_src . '" ' .
    //   ($image['srcset'] != '' ? 'srcset="' . $image['srcset'] . '" ' : '') .
    //   ($image['srcset'] != '' ? 'sizes="' . $sizes . '" ' : '') .
    //   'alt="' . $img_alt . '" ' .
    //   $dimensions . ' />';

    // if ($lazy) {
    //   $b64 = $this->get_base64($image);
    //   $lazy_img_tag = $img_tag;
    //   if ($b64 != false) {
    //     $lazy_img_tag = str_replace(' src=', ' data-src=', $lazy_img_tag);
    //     $lazy_img_tag = str_replace(' srcset=', ' data-srcset=', $lazy_img_tag);
    //     $lazy_img_tag = str_replace('<img', '<img class="lazy" src="' . $b64 . '"', $lazy_img_tag);
    //   }
    // } else {
    //   $img_tag = str_replace('<img', '<img loading="eager"', $img_tag);
    // }

    // if ($do_breakpoint) {
    //   $mobile_image = $this->get_image_array_from_id($options["fg_mobile_attachment_id"]);
    //   $mobile_sizes = $sizes;

    //   if ($options['fg_sizes'] == 'cover' && $options['fg_mobile_sizes'] == "like_desktop" && $mobile_image['width'] && $mobile_image['height']) {
    //     $w = $mobile_image['width'];
    //     $h = $mobile_image['height'];
    //     $mobile_sizes = '(max-aspect-ratio: ' . $w . '/' . $h . ') calc(' . $w . ' / ' . $h . ' * 100vh), 100vw';
    //   } elseif ($options['fg_mobile_sizes'] == "custom" && $options['fg_mobile_custom_sizes'] != "") {
    //     $mobile_sizes = $options['fg_mobile_custom_sizes'];
    //   }

    //   if ($mobile_image['width'] && $mobile_image['height']) {
    //     $dimensions = 'width="' . $mobile_image['width'] . '" height="' . $mobile_image['height'] . '"';
    //   } else {
    //     $dimensions = "";
    //   }

    //   $source_tag = '<source ' .
    //     'media="(' . $options['fg_media_query'] . ')" ' .
    //     'srcset="' . $mobile_image['srcset'] . '" ' .
    //     'sizes="' . $mobile_sizes . '" ' .
    //     $dimensions . '>';

    //   if ($lazy) {
    //     $b64 = $this->get_base64($mobile_image);
    //     $lazy_source_tag = $source_tag;
    //     if ($b64 != false) {
    //       $lazy_source_tag = str_replace(' srcset', ' data-srcset', $lazy_source_tag);
    //       $lazy_source_tag = str_replace('<source', '<source srcset="' . $b64 . '"', $lazy_source_tag);
    //     }
    //   }

    //   $eager_html = '<picture>' . $source_tag . $img_tag . '</picture>';

    //   if ($lazy) {
    //     $html = '<picture data-lazy>' . $lazy_source_tag . $lazy_img_tag . '</picture>';
    //     $html .= '<noscript>' . str_replace('<img', '<img loading="lazy"', $eager_html) . '</noscript>';
    //   } else {
    //     $html = $eager_html;
    //   }
    // } else {
    //   $eager_html = '<picture>' . $img_tag . '</picture>';

    //   if ($lazy) {
    //     $html = '<picture data-lazy>' . $lazy_img_tag . '</picture>';
    //     $html .= '<noscript>' . str_replace('<img', '<img loading="lazy"', $eager_html) . '</noscript>';
    //   } else {
    //     $html = $eager_html;
    //   }
    // }
    // $mobile_section = $this->addControlSection("mobile_breakpoint", __("Mobile Breakpoint"), "assets/icon.png", $this);
    // $has_mobile_breakpoint = $mobile_section->addOptionControl(
    //   array(
    //     "type" => 'checkbox',
    //     "name" => 'Enable Mobile Breakpoint?',
    //     "slug" => 'fg_breakpoint'
    //   )
    // );

    // $mobile_media_query = $mobile_section->addOptionControl(
    //   array(
    //     "type" => 'textfield',
    //     "name" => 'Media Query',
    //     "slug" => 'fg_media_query',
    //     "condition" => "fg_breakpoint=true"
    //   )
    // );
    // $mobile_media_query->rebuildElementOnChange();

    // $mobile_image = $mobile_section->addOptionControl(
    //   array(
    //     "type" => 'mediaurl',
    //     "name" => 'Mobile Image',
    //     "slug" => "fg_mobile_attachment_id",
    //     "condition" => "fg_breakpoint=true"
    //   )
    // );
    // $mobile_image->options['attachment'] = true;
    // $mobile_image->rebuildElementOnChange();

    // $mobile_sizes_control = $mobile_section->addOptionControl(
    //   array(
    //     "type" => 'buttons-list',
    //     "name" => 'Sizes Attribute',
    //     "slug" => 'fg_mobile_sizes',
    //     "condition" => "fg_breakpoint=true"
    //   )
    // );

    // $mobile_sizes_control->setValue(
    //   array(
    //     'like_desktop' => 'Like Desktop',
    //     'custom' => 'Custom'
    //   )
    // );

    // $mobile_section->addOptionControl(
    //   array(
    //     "type" => 'textfield',
    //     "name" => 'Custom Sizes Attribute',
    //     "slug" => 'fg_mobile_custom_sizes',
    //     "condition" => "fg_mobile_sizes=custom"
    //   )
    // );
  // }
