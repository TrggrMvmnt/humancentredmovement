
<div id="tfsp_loader"></div>
<div class="wrap">

  <h2>34SP.com Developer Tools</h2>

    <div id="tfsp_response_output" class=""></div>

    <h2 class="nav-tab-wrapper">
      <?php if ( !defined( 'TFSP_STAGING' ) || !TFSP_STAGING ){ ?> <a class="nav-tab nav-tab-active" href="<?php echo admin_url() ?>tools.php?page=tfsp_cache">Cache</a><?php } ?>
      <a class="nav-tab" href="<?php echo admin_url() ?>tools.php?page=tfsp_updates">Updates</a>
      <?php if (!is_ssl() && ( !defined( 'TFSP_STAGING' ) || !TFSP_STAGING )){ ?> <a class="nav-tab" href="<?php echo admin_url() ?>tools.php?page=tfsp_setup">Let's Encrypt</a> <?php } ?>
      </h2>


    <div class="content-section">
    <h3>Cache</h3>

    <h4>Caching is <span id="nginx_status_display"><?php echo ($cache_status == true ? "Enabled" : "Disabled"); ?></span></h4>

       <hr class="clear sp-divider">

    <p>
    Clear a URL from the cache<br>
    <em class="grey">Enter the URL/Permalink of the page you wish to clear from the cache</em><br><br>
   <label for="clear_url">URL to clear </label><br>
   <input name="clear_url" type="text" id="clear_url" class="regular-text"><br><br>

  <button class="button button-primary" onclick="tfsp_function_call({'cache_url': document.getElementById('clear_url').value}, 'clear_nginx_cache_url', 'tfsp_show_response' )">Clear URL from cache</button>
  </p>

   <hr class="clear sp-divider">
        <p>Clear full cache<br>
        <em class="grey">Clearing your cached site will slow your site down until the cache has fully rebuilt. Clear individual URLs where possible. <?php if ($cloudflare_enabled){ ?>Clearing URLs from the local cache will also clear them from the CloudFlare cache. <?php } ?> </em></p>
  <p>
    <button class="button button-primary" onclick="tfsp_function_call({}, 'clear_nginx_cache_site', 'tfsp_show_response' )">Clear the cache</button>
  </p>
     <hr class="clear sp-divider">

    <p>Whitelist pages from the cache<br>

    <em class="grey">Whitelisted pages will never be cached. Add the URL/Permalink of the pages you wish to whitelist. The domain name may be stripped.
    Wildcards (*) may be used to whitelist paths. E.g. https://example.com/2016/03/*
    </em></p>

  <label for="whitelist_cache">URLs to whitelist (One per line)</label><br>
       <textarea style="height:100px;width:350px;" name="whitelist_cache" id="whitelist_cache" class="regular-text"><?php foreach($wl_urls as $url){ echo $url . "\n"; } ?> </textarea><br><br>

    <button class="button button-primary" onclick="tfsp_function_call({'whitelist_cache':document.getElementById('whitelist_cache').value }, 'set_whitelist', 'tfsp_show_response' )">Save Changes</button>
  </p>

    <hr class="clear sp-divider">
<p>
Disable Caching<br>
        <em class="grey">
  During development, you may need to disable the local cache entirely. Caching should not be left disabled on a production site. Disabling or Enabling the cache requires the back-end services to reload, and visitors to the site may see an error during this time. This may take up to 30seconds. <?php if ($cloudflare_enabled){ ?><br>This will not affect CloudFlare caching. To disable or manage the CloudFlare cache, please use the 34SP.com account control panel. <?php } ?><br></em>
  <br>
    <button id = "nginx_cache_status_submit" data-status="<?php if ($cache_status){ echo "0"; } else {echo "1";} ?>" class="button button-primary varnish-toggle-sp" onclick="tfsp_function_call({'json': true, 'status': jQuery(this).attr('data-status')}, 'set_nginx_cache_status', 'tfsp_show_nginx_response' )"> <span id="nginx_status_button"><?php if ($cache_status){ echo "Disable"; } else {echo "Enable";} ?></span> cache</button>
 </p>


 </div>
 <!--
  <hr class="clear sp-divider">

PageSpeed<br>
        <em class="grey">
Sets optimisation and caching level for PageSpeed. Modifying Pagespeed requires the back-end services to reload, and visitors to the site may see an error during this time. This may take up to 30seconds.</em>
  <br>
  <select id = "pagespeed_cache_level">
  <option <?php if ($pagespeed_status == 0){echo 'selected'; } ?> value="0">Disabled</option>
  <option <?php if ($pagespeed_status == 1){echo 'selected'; } ?> value="1">Limited</option>
  <option <?php if ($pagespeed_status == 2){echo 'selected'; } ?> value="2">Recommended</option>
  <option <?php if ($pagespeed_status == 3){echo 'selected'; } ?> value="3">Full</option>
  </select>
</p>
<p>
    <button id = "pagespeed_submit" class="button button-primary varnish-toggle-sp" onclick="tfsp_function_call({'json': true, 'pagespeed_level': jQuery('#pagespeed_cache_level').val()}, 'set_pagespeed_status', 'tfsp_show_nginx_response' )"> <span id="pagespeed_button">Save Changes</button>
 </p>


 </div>

  <hr class="clear sp-divider">
-->