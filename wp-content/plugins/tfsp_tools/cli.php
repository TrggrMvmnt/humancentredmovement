<?php
class Hosting extends WP_CLI_Command{

  /**
   * @subcommand trigger
   *
   * Usage wp hosting trigger
   * Accepts and passes arguments
   *
   **/
  function build_trigger( $args, $assoc_args)
  {
    /*
     * Passes assoc_args to filter for example --source=staging|git --repo=gitrepo
     */
    do_action('build_trigger', $assoc_args);
    WP_CLI::success('1');
  }

  function cache( $args, $assoc_args)
  {
    //Flush the cache
    if(in_array('flush',$args))
    {
      //Clear the nginx Site Cache
      $purger = new TFSP_purger();
      $purger->process_site_purge();
    }
  }
}
