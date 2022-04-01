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

  function customCSS($options, $selector)
  {
    $obj_fit = $options["oxy-fg-video_fg_obj_size"];
    $obj_position = $options["oxy-fg-video_fg_obj_position"];
    if ($obj_fit == "cover" || $obj_fit == "contain") {
      return "$selector video {" .
        "object-fit: $obj_fit;" .
        "object-position: $obj_position;" .
        "position: absolute;" .
        "top: 0;" .
        "left: 0;" .
        "width: 100%;" .
        "height: 100%;" .
        "}";
    } else {
      return "$selector video {" .
        "object-fit: initial;" .
        "position: static;" .
        "width: 100%;" .
        "height: auto;" .
        "}";
    }
  }

  function render($options, $defaults, $content)
  {
    $mime = explode('/', get_post_mime_type($options['fg_attachment_id']))[0];
    if (
      $options['fg_attachment_id'] != "" &&
      $mime == 'video'
    ) {
      $url = wp_get_attachment_url($options['fg_attachment_id']);
      $meta = wp_get_attachment_metadata($options['fg_attachment_id']);

      $width = isset($meta['width']) ? "width='{$meta['width']}'" : '';
      $height = isset($meta['height']) ? "height='{$meta['height']}'" : '';

      $loop = filter_var($options['fg_loop'], FILTER_VALIDATE_BOOLEAN) ? 'loop' : "";
      $trigger = $options['fg_trigger'];
      $muted = ($trigger == "autoplay" || filter_var($options['fg_muted'], FILTER_VALIDATE_BOOLEAN)) ? "muted" : "";
      $inline = ($trigger == "autoplay" || !filter_var($options['fg_mobile_full'], FILTER_VALIDATE_BOOLEAN)) ? "playsinline" : "";
      if ($options['fg_poster'] != "") {
        $poster = 'poster="' . $options['fg_poster'] . '"';
      }

      $html = "<video src='$url' $width $height $loop $trigger $muted $inline $poster></video>";
      
      // lazy only if not in builder
      $lazy = filter_var($options['fg_lazy'], FILTER_VALIDATE_BOOLEAN) && !isset($_GET['action']);

      if ($lazy) {
        $base = plugin_dir_url(__DIR__ . "../");
        kniff_enqueue_script('lazyload', $base . 'js/' . 'lazyload.min.js');
        $html = str_replace('src', 'data-src', $html);
        $html = str_replace('poster', 'data-poster', $html);
        $html = str_replace('<video', '<video class="lazy"', $html);
      }

      $html = str_replace(' >', '>', $html);
      // replace multiple spaces with one
      $html = preg_replace('!\s+!', ' ', $html);
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
        "name" => 'Select Preview Image',
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

    $loop = $this->addOptionControl(
      array(
        "type" => 'checkbox', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Loop Video',
        "value" => 'true',
        "slug" => "fg_loop",
      )
    );
    $loop->rebuildElementOnChange();

    $obj_size_control = $this->addOptionControl(
      array(
        "type" => 'buttons-list',
        "name" => 'Video Fit',
        "slug" => 'fg_obj_size',
        "value" => array(
          'none' => 'Auto',
          'contain' => 'Contain',
          'cover' => 'Cover'
        ),
        "default" => 'none',
      )
    )->rebuildElementOnChange();

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
    
    $trigger = $this->addOptionControl(
      array(
        "type" => 'buttons-list',
        "name" => 'Video Start',
        "slug" => 'fg_trigger',
        "value" => array(
          'autoplay' => 'Autoplay',
          'controls' => 'Play Button',
        ),
        "default" => 'autoplay',
      )
    )->rebuildElementOnChange();

    $muted = $this->addOptionControl(
      array(
        "type" => 'checkbox', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Mute sound',
        "slug" => "fg_muted",
        "value" => 'true',
        "condition" => 'fg_trigger=controls'
      )
    );
    $muted->rebuildElementOnChange();

    $developerSection = $this->addControlSection("advanced_slug", __("Developer Settings"), "assets/icon.png", $this);

    $lazy_load_control = $developerSection->addOptionControl(
      array(
        "type" => 'checkbox',
        "name" => 'Lazyload',
        "value" => 'true',
        "slug" => 'fg_lazy',
      )
    );

    $inline = $developerSection->addOptionControl(
      array(
        "type" => 'checkbox', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Open Video Fullscreen on Mobile devices',
        "slug" => "fg_mobile_full",
        "value" => 'true',
        "condition" => 'fg_trigger=controls'
      )
    );
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
