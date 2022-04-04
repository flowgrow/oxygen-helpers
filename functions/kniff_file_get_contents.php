<?php

if (!function_exists('kniff_file_get_contents')) {
  function kniff_file_get_contents($url)
  {
    try {
      return file_get_contents($url);
    } catch (\Exception $ex) {
      if (strpos($url, '://') === false) {
        $upload_dir = wp_get_upload_dir();
        $url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $url);
      }

      $urlparts = parse_url($url);
      $domain = $urlparts['host'];
      $split = explode(".", $domain);
      $tld = $split[count($split) - 1];

      $c = curl_init();
      curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($c, CURLOPT_URL, $url);
      if ($tld == "local") {
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
      }
      $contents = curl_exec($c);
      curl_close($c);

      if ($contents) return $contents;
      else return FALSE;
    }
  }
}
