<div class="wrap">
  <h2>34SP.com Developer Tools</h2>
    <?php settings_errors(); ?>
    <h2 class="nav-tab-wrapper">
      <?php if ( !defined( 'TFSP_STAGING' ) || !TFSP_STAGING ){ ?> <a class="nav-tab" href="<?php echo admin_url() ?>tools.php?page=tfsp_cache">Cache</a><?php } ?>
      <a class="nav-tab nav-tab-active" href="<?php echo admin_url() ?>tools.php?page=tfsp_updates">Updates</a>
      <?php if (!is_ssl() && ( !defined( 'TFSP_STAGING' ) || !TFSP_STAGING )){ ?> <a class="nav-tab" href="<?php echo admin_url() ?>tools.php?page=tfsp_setup">Let's Encrypt</a> <?php } ?>
      </h2>


    <div class="content-section">
    <h3>Plugin Updates</h3>

    <h4>WordPress plugin updates are currently set to <?php echo $delay_string ?>.</h4>

    <p><em class="grey">If you want to disable updates entirely, you can choose plugins individually in <a href="<?php echo admin_url(); ?>plugins.php">Plugins</a></em></p>
    <p>
    Choose when you would like 34SP.com to automatically apply your plugin updates.<br>
    You will be emailed when delayed updates are available for your plugins, and when updates are automatically applied.
    </p>

    <p>


        <form action='options.php' method='post'>

            <?php
            settings_fields( 'tfsp_update_section' );
            do_settings_sections( 'tfsp_update_settings' );
            submit_button();
            ?>
        </form>

     </p>
     <hr />
     <h3>WordPress Core Updates</h3>
     <h4>WordPress Core updates are currently set to <?php echo $core_delay_string ?>.</h4>
     <p>
     Choose when you would like 34SP.com to automatically apply your WordPress core updates.<br>
     Note minor updates & security are always applied immediately
     </p>
     <p>
       <form action='options.php' method='post'>

           <?php
           settings_fields( 'tfsp_core_update_section' );
           do_settings_sections( 'tfsp_core_update_settings' );
           submit_button();
           ?>
       </form>
     </p>
     <hr />
     <h3>Notifications</h3>
     <p>Select who should be notified by email, when updates are applied. Default is NONE other options are ALL WordPress administrators</p>
     <p>Alternatively select "Site Contacts" to contact Owners & Technical contacts from 34SP.com Client Control Panel</p>
     <p>
       <form action="options.php" method="post">
        <?php
          settings_fields( 'tfsp_update_notifications_section' );
          do_settings_sections( 'tfsp_update_notifications_settings' );
          submit_button();
        ?>
       </form>
     </p>
  </div>

  <hr class="clear sp-divider">
