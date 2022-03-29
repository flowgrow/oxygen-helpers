<?php


class LottieElement extends OxyEl
{

  function init()
  {
    // include only for builder
    if (!isset($_GET['oxygen_iframe'])) {
      add_action('wp_footer', array($this, 'fixing_js'));
    }
  }

  function fixing_js()
  { ?>
    <script type="text/javascript">
      document.querySelector('body').addEventListener('click', function(e) {
        if (e.target.dataset.mediaproperty == "oxy-fg-lottie_fg_attachment_id") {
          e.target.dataset.mediatype = Date.now();
          e.target.dataset.mediacontent = 'application';
          e.target.dataset.mediatitle = 'Select Lottie File';
          e.target.dataset.mediabutton = 'Select Lottie File';
        }
      }, true)
    </script>
  <?php }

  function afterInit()
  {
    // Do things after init, like remove apply params button and remove the add button.
    $this->removeApplyParamsButton();
    // $this->removeAddButton();
  }

  function name()
  {
    return 'Lottie Element';
  }

  function slug()
  {
    return "fg-lottie";
  }

  function icon()
  {
    $base = plugin_dir_url(__DIR__ . "../");
    return $base . '/icons/lottie.svg';
  }

  function render($options, $defaults, $content)
  {
    $mime = explode('/', get_post_mime_type($options['fg_attachment_id']))[0];
    if (
      $options['fg_file_location'] == 'media' &&
      $options['fg_attachment_id'] != "" &&
      $mime == 'application'
    ) {
      $url = wp_get_attachment_url($options['fg_attachment_id']);
    } elseif (
      $options['fg_file_location'] == 'url' &&
      $options['fg_json_url'] != ""
    ) {
      $url = $options['fg_json_url'];
    } else {
      $url = $this->icon();
      $html = kniff_file_get_contents($url);
    }

    $exploded = explode('.', $url);
    $len_exploded = count($exploded);

    $ext = $exploded[$len_exploded - 1];

    $is_json = $ext == 'json';
    $is_dotlottie = $ext == 'lottie';

    if ($is_json || $is_dotlottie) {
      $html = "";
      $base = plugin_dir_url(__DIR__ . "../");

      $js_filename = $is_json ? 'lottie-player' : 'dotlottie-player';

      if (isset($_GET['action'])) {
        $this->El->inlineJS(
          <<<EX
          if (jQuery('#{$js_filename}').length == 0) {
            jQuery('<script id="{$js_filename}" src="{$base}js/{$js_filename}.min.js">').appendTo('body');
          }
          EX
        );
      } else {
        kniff_enqueue_script($js_filename, $base . 'js/' . "$js_filename.min.js");
      }

      $speed = $options['fg_speed'];
      $bounce = $options['fg_play_mode'] == 'yoyo' ? "mode='bounce'" : '';
      $loop = filter_var($options['fg_loop'], FILTER_VALIDATE_BOOLEAN) ? 'loop' : "";
      $controls = filter_var($options['fg_controls'], FILTER_VALIDATE_BOOLEAN) ? "controls" : "";
      $trigger = $options['fg_trigger'] != "none" ? $options['fg_trigger'] : '';

      $dot = $is_dotlottie ? "dot" : "";

      $html .= "<{$dot}lottie-player src='$url' background='transparent' speed='$speed' $bounce $loop $controls $trigger></>";
    }

    echo $html;
  }

  function controls()
  {

    $location = $this->addOptionControl(
      array(
        "type" => 'buttons-list',
        "name" => 'File Location',
        "slug" => 'fg_file_location'
      )
    );

    $location->setValue(
      array(
        'media' => 'Media',
        'url' => 'URL',
      )
    );
    $location->setDefaultValue('media');
    $location->rebuildElementOnChange();

    $json_url = $this->addOptionControl(
      array(
        "type" => 'textfield', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Lottie json URL',
        "slug" => "fg_json_url",
        "condition" => "fg_file_location=url"
      )
    );
    $json_url->rebuildElementOnChange();

    // Option controls can be accessed in the render() function via $options['field_slug']
    $img_control = $this->addOptionControl(
      array(
        "type" => 'mediaurl', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Select Lottie json',
        "slug" => "fg_attachment_id",
        "condition" => "fg_file_location=media"
      )
    );
    $img_control->options['attachment'] = true;
    $img_control->rebuildElementOnChange();

    $play_mode = $this->addOptionControl(
      array(
        "type" => 'buttons-list',
        "name" => 'Play Mode',
        "slug" => 'fg_play_mode'
      )
    );

    $play_mode->setValue(
      array(
        'normal' => 'Normal',
        'yoyo' => 'Bounce',
      )
    );
    $play_mode->setDefaultValue('normal');
    $play_mode->rebuildElementOnChange();

    $speed = $this->addOptionControl(
      array(
        "type" => 'slider-measurebox',
        "unit" => 'none',
        "slug" => 'fg_speed',
        "name" => 'Speed',
        "value" => 1,
      )
    );
    $speed->setRange(0, 10, 0.1);
    $speed->rebuildElementOnChange();

    $width = $this->addStyleControl(
      array(
        "type"       => "measurebox",
        "name"     => __("Width"),
        "property"   => "width",
        "unit" => "px",
      )
    );

    $this->addStyleControl(
      array(
        "type"       => "measurebox",
        "name"     => __("Height"),
        "property"   => "height",
        "unit" => "px",
      )
    );

    $autoplay = $this->addOptionControl(
      array(
        "type" => 'buttons-list', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Animation Trigger',
        "slug" => "fg_trigger",
      )
    );
    $autoplay->setValue(
      array(
        'autoplay' => 'Autoplay',
        'hover' => 'Hover',
        'none' => 'None'
        // 'click' => 'Click'
      )
    );
    $autoplay->setDefaultValue('autoplay');
    $autoplay->rebuildElementOnChange();

    $controls = $this->addOptionControl(
      array(
        "type" => 'checkbox', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Show controls',
        "slug" => "fg_controls",
        "value" => 'false'
      )
    );
    $controls->rebuildElementOnChange();

    $loop = $this->addOptionControl(
      array(
        "type" => 'checkbox', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Loop',
        "value" => 'true',
        "slug" => "fg_loop",
      )
    );
    $loop->rebuildElementOnChange();
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

new LottieElement();


