<?php


class VideoElement extends OxyEl
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
        if (e.target.dataset.mediaproperty == "oxy-fg-video_fg_attachment_id") {
          e.target.dataset.mediatype = Date.now();
          e.target.dataset.mediacontent = 'video';
          e.target.dataset.mediatitle = 'Select Video File';
          e.target.dataset.mediabutton = 'Select Video File';
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
    return 'Self-Hosted Video';
  }

  function slug()
  {
    return "fg-video";
  }

  function icon()
  {
    return CT_FW_URI . '/toolbar/UI/oxygen-icons/add-icons/video.svg';
  }

  function render($options, $defaults, $content)
  {
    $mime = explode('/', get_post_mime_type($options['fg_attachment_id']))[0];
    if (
      $options['fg_attachment_id'] != "" &&
      $mime == 'video'
    ) {
      $url = wp_get_attachment_url($options['fg_attachment_id']);
      
      $loop = filter_var($options['fg_loop'], FILTER_VALIDATE_BOOLEAN) ? 'loop' : "";
      $controls = filter_var($options['fg_controls'], FILTER_VALIDATE_BOOLEAN) ? "controls" : "";
      $autoplay = filter_var($options['fg_autoplay'], FILTER_VALIDATE_BOOLEAN) ? "autoplay" : "";
      $muted = filter_var($options['fg_muted'], FILTER_VALIDATE_BOOLEAN) ? "muted" : "";
      $inline = filter_var($options['fg_inline'], FILTER_VALIDATE_BOOLEAN) ? "playsinline" : "";
      if ($options['fg_poster'] != "") {
        $poster = 'poster="'.$options['fg_poster'].'"';
      }
      
      $html = "<video src='$url' $loop $controls $autoplay $muted $inline $poster></video>";

    } else {
      $url = $this->icon();
      $html = kniff_file_get_contents($url);
    }

    echo $html;
  }

  function controls()
  {

    // Option controls can be accessed in the render() function via $options['field_slug']
    $media_control = $this->addOptionControl(
      array(
        "type" => 'mediaurl', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Select Video',
        "slug" => "fg_attachment_id",
      )
    );
    $media_control->options['attachment'] = true;
    $media_control->rebuildElementOnChange();

    $poster = $this->addOptionControl(
      array(
        "type" => 'mediaurl', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Select Poster Image',
        "slug" => "fg_poster",
      )
    );
    $poster->rebuildElementOnChange();

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
        "type" => 'checkbox', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Autoplay Video',
        "slug" => "fg_autoplay",
        "value" => 'true'
      )
    );
    $autoplay->rebuildElementOnChange();

    $muted = $this->addOptionControl(
      array(
        "type" => 'checkbox', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Mute sound',
        "slug" => "fg_muted",
        "value" => 'true'
      )
    );
    $muted->rebuildElementOnChange();

    $inline = $this->addOptionControl(
      array(
        "type" => 'checkbox', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Inline Video',
        "slug" => "fg_inline",
        "value" => 'true'
      )
    );
    $inline->rebuildElementOnChange();

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
        "name" => 'Loop Video',
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

new VideoElement();


