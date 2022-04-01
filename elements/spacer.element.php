<?php


class SpacerElement extends OxyEl
{

  function init()
  {
    // include only for builder
  }

  function afterInit()
  {
    // Do things after init, like remove apply params button and remove the add button.
    $this->removeApplyParamsButton();
    // $this->removeAddButton();
  }

  function name()
  {
    return 'Spacer';
  }

  function slug()
  {
    return "fg-spacer";
  }

  function tag()
  {
    return "span";
  }

  function icon()
  {
    $base = plugin_dir_url(__DIR__ . "../");
    return $base . '/icons/spacer.svg';
  }

  function render()
  {
  }

  function customCSS($options, $selector)
  {
    $direction = $options["oxy-fg-spacer_fg_direction"];
    $size = $options["oxy-fg-spacer_fg_size"];

    $width = $direction == 'vertical' ? 1 : $size;
    $height = $direction == 'horizontal' ? 1 : $size;

    $hide_outline = filter_var($options['oxy-fg-spacer_fg_hide_outline'], FILTER_VALIDATE_BOOLEAN);

    if (!$hide_outline && isset($_GET['action'])) {
      $border = "border: 1px dashed rgba(0,0,0,0.2);";
    }

    return "$selector {" .
      "display: block;" .
      "width: {$width}px;" .
      "min-width: {$width}px;" .
      "height: {$height}px;" .
      "min-height: {$height}px;" .
      $border.
      "}";
  }

  function controls()
  {
    $direction = $this->addOptionControl(
      array(
        "type" => 'buttons-list',
        "name" => 'Direction',
        "slug" => 'fg_direction',
        "value" => array(
          'both' => 'Both',
          'horizontal' => 'Horizontal',
          'vertical' => 'Vertical',
        ),
        "default" => 'both',
      )
    )->rebuildElementOnChange();

    $size = $this->addOptionControl(
      array(
        "type" => "measurebox",
        "name" => 'Size (px)',
        "slug" => "fg_size",
      )
    )->rebuildElementOnChange();

    $hide_outline = $this->addOptionControl(
      array(
        "type" => "checkbox",
        "name" => 'Hide Outline in Oxygen',
        "slug" => "fg_hide_outline",
        "value" => "false"
      )
    )->rebuildElementOnChange();
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

new SpacerElement();
