<div id="tfsp_loader"></div>
<div class="wrap">
  <h2>34SP.com Developer Tools</h2>
   <div id="tfsp_response_output" class=""></div>
 
    <?php settings_errors(); ?>
    <h2 class="nav-tab-wrapper">
      <?php if ( !defined( 'TFSP_STAGING' ) || !TFSP_STAGING ){ ?> <a class="nav-tab" href="<?php echo admin_url() ?>tools.php?page=tfsp_cache">Cache</a><?php } ?>
      <a class="nav-tab" href="<?php echo admin_url() ?>tools.php?page=tfsp_updates">Updates</a>
      <?php if (!is_ssl() && ( !defined( 'TFSP_STAGING' ) || !TFSP_STAGING )){ ?> <a class="nav-tab nav-tab-active" href="<?php echo admin_url() ?>tools.php?page=tfsp_setup">Let's Encrypt</a> <?php } ?>
      </h2>



    <div class="content-section">
    <h3>Let's Encrypt</h3>

    <h4>The final setup step to ensure you're using the latest HTTP/2 technology and full end-to-end encryption is to enable your free Let's Encrypt SSL certificate.</h4>

    <p>
    <em class="grey">A Let's Encrypt SSL certificate will take approximately an hour to process. We will update your site url and home url to use HTTPS automatically as soon as your certificate is issued. Visitors will be redirected as soon as this occurs.<br>Please ensure you have no 'absolute' http:// urls in your theme, includes and scripts before enabling Let's Encrypt, as these assets will not load over a secure browsing session.</em>
    </p>

    <p>
    <?php if (!$cert_status){ ?>
    <button class="button button-primary" onclick="tfsp_function_call({}, 'enable_lets_encrypt', 'tfsp_show_response' )">Enable Let's Encrypt Now.</button>
    <?php } else { ?>
    <button disabled = "disabled" class="button button-primary">Let's Encrypt order in progress. This may take up to an hour. </button>
    <?php } ?>
   </p>
   <hr class="clear sp-divider">