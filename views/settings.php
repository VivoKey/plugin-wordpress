<?php
//   require('lib/helper/MY_Plugin.php');
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to manage options for this site.'));
}

?>
<div class="wrap">

<h2>VivoKey OpenID Connect Plugin</h2>

<a name="settings"></a>
 <form action="options.php" method="post" name="settings">
      <?php settings_fields($this->get_option_name());?>
      <?php do_settings_sections('VivoKey-connect');?>

      <p class="submit"><input type="submit" value="<?php esc_attr_e('Save Changes');?>"
         class="button button-primary" id="submit" name="submit"></p>
   </form>
</div>