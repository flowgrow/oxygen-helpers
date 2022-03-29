<?php


class InlineSVGElement extends OxyEl
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
        if (e.target.dataset.mediaproperty == "oxy-fg-inline-svg_fg_attachment_id") {
          e.target.dataset.mediatype = Date.now();
          e.target.dataset.mediacontent = 'image/svg+xml';
          e.target.dataset.mediatitle = 'Select SVG';
          e.target.dataset.mediabutton = 'Select SVG';
        }
      }, true)
    </script>
<?php }

  function afterInit()
  {
    // Do things after init, like remove apply params button and remove the add button.
    // $this->removeApplyParamsButton();
    // $this->removeAddButton();
  }

  function name()
  {
    return 'Inline SVG';
  }

  function slug()
  {
    return "fg-inline-svg";
  }

  function icon()
  {
    $base = plugin_dir_url(__DIR__ . "../");
    return $base . '/icons/inline-svg.svg';
  }

  function render($options, $defaults, $content)
  {
    $url = $this->icon();
    if (
      $options['fg_attachment_id'] &&
      get_post_mime_type($options['fg_attachment_id']) == 'image/svg+xml'
    ) {
      $url = wp_get_attachment_url($options['fg_attachment_id']);
    }

    $html = kniff_file_get_contents($url);
    echo $html;
  }

  function controls()
  {
    // Option controls can be accessed in the render() function via $options['field_slug']
    $img_control = $this->addOptionControl(
      array(
        "type" => 'mediaurl', // types: textfield, dropdown, checkbox, buttons-list, measurebox, slider-measurebox, colorpicker, icon_finder, mediaurl
        "name" => 'Select SVG Image',
        "slug" => "fg_attachment_id",
      )
    );
    $img_control->options['attachment'] = true;
    $img_control->rebuildElementOnChange();

    $fill_control = $this->addStyleControl(
      array(
        "control_type"       => "colorpicker",
        "name"     => __("Fill Color"),
        "selector" => "svg *",
        "property" => 'fill'
      )
    );

    $stroke_control = $this->addStyleControl(
      array(
        "control_type"       => "colorpicker",
        "name"     => __("Stroke Color"),
        "selector" => "svg > *",
        "property" => 'stroke'
      )
    );

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

new InlineSVGElement();
