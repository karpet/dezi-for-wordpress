<?php
/*  
    Copyright (c) 2012 American Public Media Group

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.
*/

//get the plugin settings
$dezi4w_settings = dezi4w_get_option('plugin_dezi4w_settings');

//get a a list of all the available content types so we render out some options
$post_types = dezi4w_get_all_post_types();

#set defaults if not initialized
if ($dezi4w_settings['dezi4w_dezi_initialized'] != 1) {
  
  $options['dezi4w_index_all_sites'] = 0;
  $options['dezi4w_server']['info']['single']= array('host'=>'localhost','port'=>5000, 'path'=>'/', 'username'=>'', 'password'=>'');
  $options['dezi4w_server']['info']['master']= array('host'=>'localhost','port'=>5000, 'path'=>'/', 'username'=>'', 'password'=>'');
  $options['dezi4w_server']['type']['search'] = 'master';
  $options['dezi4w_server']['type']['update'] = 'master';
  
  //by default we index pages and posts, and remove them from index if there status changes.
  $options['dezi4w_content']['index']   = array('page'=>'1', 'post'=>'1');  
  $options['dezi4w_content']['delete']  = array('page'=>'1', 'post'=>'1');  
  $options['dezi4w_content']['private'] = array('page'=>'1', 'post'=>'1');    
  
  
  $options['dezi4w_index_pages'] = 1;
  $options['dezi4w_index_posts'] = 1;
  $options['dezi4w_delete_page'] = 1;
  $options['dezi4w_delete_post'] = 1;
  $options['dezi4w_private_page'] = 1;
  $options['dezi4w_private_post'] = 1;
  $options['dezi4w_output_info'] = 1;
  $options['dezi4w_output_pager'] = 1;
  $options['dezi4w_output_facets'] = 1;
  $options['dezi4w_exclude_pages'] =  array();
  $options['dezi4w_exclude_pages'] = '';  
  $options['dezi4w_num_results'] = 5;
  $options['dezi4w_cat_as_taxo'] = 1;
  $options['dezi4w_dezi_initialized'] = 1;
  $options['dezi4w_max_display_tags'] = 10;
  $options['dezi4w_facet_on_categories'] = 1;
  $options['dezi4w_facet_on_taxonomy'] = 1;
  $options['dezi4w_facet_on_tags'] = 1;
  $options['dezi4w_facet_on_author'] = 1;
  $options['dezi4w_facet_on_type'] = 1;
  $options['dezi4w_enable_dym'] = 1;
  $options['dezi4w_index_comments'] = 1;
  $options['dezi4w_connect_type'] = 'dezi';
  $options['dezi4w_index_custom_fields'] =  array();
  $options['dezi4w_facet_on_custom_fields'] =  array();
  $options['dezi4w_index_custom_fields'] = '';  
  $options['dezi4w_facet_on_custom_fields'] = '';

  
  //update existing settings from multiple option record to a single array
  //if old options exist, update to new system
  $delete_option_function = 'delete_option';
  if (is_multisite()) {
    $indexall = get_site_option('dezi4w_index_all_sites');
    $delete_option_function = 'delete_site_option';
  }
  //find each of the old options function
  //update our new array and delete the record.
  foreach($options as $key => $value ) {
    if( $existing = get_option($key)) {
      $options[$key] = $existing;
      $indexall = FALSE;
      //run the appropriate delete options function
      $delete_option_function($key);
    }
  }
  
  $dezi4w_settings = $options;
  //save our options array
  dezi4w_update_option($options);
}

wp_reset_vars(array('action'));

# save form settings if we get the update action
# we do saving here instead of using options.php because we need to use
# dezi4w_update_option instead of update option.
# As it stands we have 27 options instead of making 27 insert calls (which is what update_options does)
# Lets create an array of all our options and save it once.
if (isset($_POST['action']) && $_POST['action'] == 'update') {   
  //lets loop through our setting fields $_POST['settings']
  foreach ($dezi4w_settings as $option => $old_value ) {
    $value = $_POST['settings'][$option];

    switch ($option) {
      case 'dezi4w_dezi_initialized':
        $value = trim($old_value);
        break;
      
      case 'dezi4w_server':
        //remove empty server entries
        $s_value = &$value['info'];
      
      
        foreach ($s_value as $key => $v) {
          //lets rename the array_keys
          if(!$v['host']) unset($s_value[$key]);
        }
        break;
    }
    if ( !is_array($value) ) $value = trim($value); 
    $value = stripslashes_deep($value);
    $dezi4w_settings[$option] = $value;
  }
  // if we are in single server mode set the server types to master
  // and configure the master server to the values of the single server
  if ($dezi4w_settings['dezi4w_connect_type'] =='dezi_single'){
    $dezi4w_settings['dezi4w_server']['info']['master']= $dezi4w_settings['dezi4w_server']['info']['single'];
    $dezi4w_settings['dezi4w_server']['type']['search'] = 'master';
    $dezi4w_settings['dezi4w_server']['type']['update'] = 'master';
  }
  // if this is a multi server setup we steal the master settings
  // and stuff them into the single server settings in case the user
  // decides to change it later 
  else {
    $dezi4w_settings['dezi4w_server']['info']['single']= $dezi4w_settings['dezi4w_server']['info']['master'];
  }
  //lets save our options array
  dezi4w_update_option($dezi4w_settings);

  //we need to make call for the options again 
  //as we need them to come out in an a sanitised format
  //otherwise fields that need to run dezi4w_filter_list2str will come up with nothin
  $dezi4w_settings = dezi4w_get_option('plugin_dezi4w_settings');
  ?>
  <div id="message" class="updated fade"><p><strong><?php _e('Success!', 'dezi4wp') ?></strong></p></div>
  <?php
}

# checks if we need to check the checkbox
function dezi4w_checkCheckbox( $fieldValue, $option = array(), $field = false) {
    $option_value = $option;
    if (is_array($option) && $field && isset($option[$field])) {
        $option_value = $option[$field];
    }
    if ( $fieldValue == '1' || $option_value == '1') {
        echo 'checked="checked"';
    }
}

function dezi4w_checkConnectOption($optionType, $connectType) {
    if ( $optionType === $connectType ) {
        echo 'checked="checked"';
    }
}



# check for any POST settings
if ($_POST['dezi4w_ping']) {
    if (dezi4w_ping_server()) {
?>
<div id="message" class="updated fade"><p><strong><?php _e('Ping Success!', 'dezi4wp') ?></strong></p></div>
<?php
    } else {
?>
    <div id="message" class="updated fade"><p><strong><?php _e('Ping Failed!', 'dezi4wp') ?></strong></p></div>
<?php
    }
} elseif ($_POST['dezi4w_deleteall']) {
    dezi4w_delete_all();
?>
    <div id="message" class="updated fade"><p><strong><?php _e('All Indexed Pages Deleted!', 'dezi4wp') ?></strong></p></div>
<?php
} elseif ($_POST['dezi4w_optimize']) {
    dezi4w_optimize();
?>
    <div id="message" class="updated fade"><p><strong><?php _e('Index Optimized!', 'dezi4wp') ?></strong></p></div>
<?php
} elseif ($_POST['dezi4w_init_blogs']) {
    dezi4w_copy_config_to_all_blogs();
    ?>
        <div id="message" class="updated fade"><p><strong><?php _e('Dezi for Wordpress Configured for All Blogs!', 'dezi4wp') ?></strong></p></div>

<?php } ?>
<div class="wrap">
<h2><?php _e('Dezi For WordPress', 'dezi4wp') ?></h2>

<form method="post" action="options-general.php?page=dezi-for-wordpress/dezi-for-wordpress.php">
<h3><?php _e('Configure Dezi', 'dezi4wp') ?></h3>

<div class="dezi_admin clearfix">
    <div class="dezi_adminR">
        <div class="dezi_adminR2" id="dezi_admin_tab2">
            <label><?php _e('Dezi Host', 'dezi4wp') ?></label>
            <input name="settings[dezi4w_server][type][update]" type="hidden" value="master" />
            <input name="settings[dezi4w_server][type][search]" type="hidden" value="master" />
            <p><input type="text" name="settings[dezi4w_server][info][single][host]" value="<?php echo $dezi4w_settings['dezi4w_server']['info']['single']['host']?>" /></p>
            <label><?php _e('Dezi Port', 'dezi4wp') ?></label>
            <p><input type="text" name="settings[dezi4w_server][info][single][port]" value="<?php echo $dezi4w_settings['dezi4w_server']['info']['single']['port']?>" /></p>
            <label><?php _e('Dezi Path', 'dezi4wp') ?></label>
            <p><input type="text" name="settings[dezi4w_server][info][single][path]" value="<?php echo $dezi4w_settings['dezi4w_server']['info']['single']['path']?>" /></p>
                        <label><?php _e('Dezi Username', 'dezi4wp') ?></label>
                        <p><input type="text" name="settings[dezi4w_server][info][single][username]" value="<?php echo $dezi4w_settings['dezi4w_server']['info']['single']['username']?>" /></p>
                        <label><?php _e('Dezi Password', 'dezi4wp') ?></label>
                        <p><input type="password" name="settings[dezi4w_server][info][single][password]" value="<?php echo $dezi4w_settings['dezi4w_server']['info']['single']['password']?>" /></p>
        </div>
        <div class="dezi_adminR2" id="dezi_admin_tab3">
          <table>
            <tr>
            <?php 
              //we are working with multiserver setup so lets
              //lets provide an extra fields for extra host on the fly by appending an empty array
              //this will always give a count of current servers+1
              $serv_count = count($dezi4w_settings['dezi4w_server']['info']);
              $dezi4w_settings['dezi4w_server']['info'][$serv_count] = array('host'=>'','port'=>'', 'path'=>'', 'username'=>'', 'password'=>'');
              foreach ($dezi4w_settings['dezi4w_server']['info'] as $server_id => $server) { 
                      if ($server_id == "single")
                        continue;
                //lets set serverIDs
                $new_id =(is_numeric($server_id)) ? 'slave_'.$server_id : $server_id ;
            ?>
              <td>
              <label><?php _e('ServerID', 'dezi4wp') ?>: <strong><?php echo $new_id; ?></strong></label>
              <p>Update Server: &nbsp;&nbsp;<input name="settings[dezi4w_server][type][update]" type="radio" value="<?php echo $new_id?>" <?php dezi4w_checkConnectOption($dezi4w_settings['dezi4w_server']['type']['update'], $new_id); ?> /></p>
                <p>Search Server: &nbsp;&nbsp;<input name="settings[dezi4w_server][type][search]" type="radio" value="<?php echo $new_id?>" <?php dezi4w_checkConnectOption($dezi4w_settings['dezi4w_server']['type']['search'], $new_id); ?> /></p>
              <label><?php _e('Dezi Host', 'dezi4wp') ?></label>
                  <p><input type="text" name="settings[dezi4w_server][info][<?php echo $new_id ?>][host]" value="<?php echo $server['host'] ?>" /></p>
                <label><?php _e('Dezi Port', 'dezi4wp') ?></label>
                <p><input type="text" name="settings[dezi4w_server][info][<?php echo $new_id ?>][port]" value="<?php echo $server['port'] ?>" /></p>
                <label><?php _e('Dezi Path', 'dezi4wp') ?></label>
                <p><input type="text" name="settings[dezi4w_server][info][<?php echo $new_id ?>][path]" value="<?php echo $server['path'] ?>" /></p>
                        <label><?php _e('Dezi Username', 'dezi4wp') ?></label>
                        <p><input type="text" name="settings[dezi4w_server][info][<?php echo $new_id ?>][username]" value="<?php echo $server['username']?>" /></p>
                        <label><?php _e('Dezi Password', 'dezi4wp') ?></label>
                        <p><input type="password" name="settings[dezi4w_server][info][<?php echo $new_id ?>][password]" value="<?php echo $server['password']?>" /></p>
                </td>
                <?php 
                  }
                ?>
              </tr>
            </table>
        </div>        
    </div>
    <ol>
        <li id="dezi_admin_tab1_btn" class="dezi_admin_tab1">
        </li>
        <li id="dezi_admin_tab2_btn" class="dezi_admin_tab2">
            <h4><input id="deziconnect_single" name="settings[dezi4w_connect_type]" type="radio" value="dezi_single" <?php dezi4w_checkConnectOption($dezi4w_settings['dezi4w_connect_type'], 'dezi_single'); ?> onclick="dezi4w_switch1();" />Single Dezi Server</h4>
            <ol>
                <li>Download, install and configure your own <a href="http://dezi.org/">Dezi</a> instance</li>
            </ol>
        </li>
        <li id="dezi_admin_tab3_btn" class="dezi_admin_tab3">
            <h4><input id="deziconnect_separated" name="settings[dezi4w_connect_type]" type="radio" value="dezi_separated" <?php dezi4w_checkConnectOption($dezi4w_settings['dezi4w_connect_type'], 'dezi_separated'); ?> onclick="dezi4w_switch1();" />Separated Dezi Servers</h4>
            <ol>
                <li>Separate URL's for updates and searches.</li>
            </ol>
        </li>        
    </ol>
</div>
<hr />
<h3><?php _e('Indexing Options', 'dezi4wp') ?></h3>
<table class="form-table">
  <?php 
  foreach ($post_types as $post_key => $post_type) {?>
    <tr valign="top">
        <th scope="row" style="width:200px;"><?php _e('Index '.ucfirst($post_type), 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_content][index][<?php echo $post_type?>]" value="1" <?php echo dezi4w_checkCheckbox(FALSE, $dezi4w_settings['dezi4w_content']['index'], $post_type); ?> /></td>

        <th scope="row" style="width:200px;"><?php _e('Remove '.ucfirst($post_type).' on Delete', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_content][delete][<?php echo $post_type?>]" value="1" <?php echo dezi4w_checkCheckbox(FALSE, $dezi4w_settings['dezi4w_content']['delete'], $post_type); ?> /></td>

        <th scope="row" style="width:200px;"><?php _e('Remove '.ucfirst($post_type).' on Status Change', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_content][private][<?php echo $post_type?>]" value="1" <?php echo dezi4w_checkCheckbox(FALSE, $dezi4w_settings['dezi4w_content']['private'], $post_type); ?> /></td>
    </tr>
  <?php }?>

    <tr valign="top">
        <th scope="row" style="width:200px;"><?php _e('Index Comments', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_index_comments]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_index_comments']); ?> /></td>
    </tr>
        
    <?php
    //is this a multisite installation
    if (is_multisite() && is_main_site()) {
    ?>
    
    <tr valign="top">
        <th scope="row" style="width:200px;"><?php _e('Index all Sites', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_index_all_sites]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_index_all_sites']); ?> /></td>
    </tr>
    <?php
    }
    ?>
    <tr valign="top">
        <th scope="row"><?php _e('Index custom fields (comma separated names list)') ?></th>
        <td><input type="text" name="settings[dezi4w_index_custom_fields]" value="<?php print( dezi4w_filter_list2str($dezi4w_settings['dezi4w_index_custom_fields'], 'dezi4wp')); ?>" /></td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php _e('Excludes Posts or Pages (comma separated ids list)') ?></th>
        <td><input type="text" name="settings[dezi4w_exclude_pages]" value="<?php print(dezi4w_filter_list2str($dezi4w_settings['dezi4w_exclude_pages'], 'dezi4wp')); ?>" /></td>
    </tr>
</table>
<hr />
<h3><?php _e('Result Options', 'dezi4wp') ?></h3>
<table class="form-table">
    <tr valign="top">
        <th scope="row" style="width:200px;"><?php _e('Output Result Info', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_output_info]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_output_info']); ?> /></td>
        <th scope="row" style="width:200px;float:left;margin-left:20px;"><?php _e('Output Result Pager', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_output_pager]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_output_pager']); ?> /></td>
    </tr>
 
    <tr valign="top">
        <th scope="row" style="width:200px;"><?php _e('Output Facets', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_output_facets]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_output_facets']); ?> /></td>
        <th scope="row" style="width:200px;float:left;margin-left:20px;"><?php _e('Category Facet as Taxonomy', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_cat_as_taxo]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_cat_as_taxo']); ?> /></td>
    </tr>

    <tr valign="top">
        <th scope="row" style="width:200px;"><?php _e('Categories as Facet', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_facet_on_categories]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_facet_on_categories']); ?> /></td>
        <th scope="row" style="width:200px;float:left;margin-left:20px;"><?php _e('Tags as Facet', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_facet_on_tags]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_facet_on_tags']); ?> /></td>
    </tr>
    
    <tr valign="top">
        <th scope="row" style="width:200px;"><?php _e('Author as Facet', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_facet_on_author]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_facet_on_author']); ?> /></td>
        <th scope="row" style="width:200px;float:left;margin-left:20px;"><?php _e('Type as Facet', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_facet_on_type]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_facet_on_type']); ?> /></td>
    </tr>

     <tr valign="top">
         <th scope="row" style="width:200px;"><?php _e('Taxonomy as Facet', 'dezi4wp') ?></th>
         <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_facet_on_taxonomy]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_facet_on_taxonomy']); ?> /></td>
      </tr>
      
    <tr valign="top">
        <th scope="row"><?php _e('Custom fields as Facet (comma separated ordered names list)') ?></th>
        <td><input type="text" name="settings[dezi4w_facet_on_custom_fields]" value="<?php print( dezi4w_filter_list2str($dezi4w_settings['dezi4w_facet_on_custom_fields'], 'dezi4wp')); ?>" /></td>
    </tr>

    <!--
    <tr valign="top">
        <th scope="row" style="width:200px;"><?php _e('Enable Spellchecking', 'dezi4wp') ?></th>
        <td style="width:10px;float:left;"><input type="checkbox" name="settings[dezi4w_enable_dym]" value="1" <?php echo dezi4w_checkCheckbox($dezi4w_settings['dezi4w_enable_dym']); ?> /></td>
    </tr>
    -->           
    <tr valign="top">
        <th scope="row"><?php _e('Number of Results Per Page', 'dezi4wp') ?></th>
        <td><input type="text" name="settings[dezi4w_num_results]" value="<?php _e($dezi4w_settings['dezi4w_num_results'], 'dezi4wp'); ?>" /></td>
    </tr>   
    
    <tr valign="top">
        <th scope="row"><?php _e('Max Number of Tags to Display', 'dezi4wp') ?></th>
        <td><input type="text" name="settings[dezi4w_max_display_tags]" value="<?php _e($dezi4w_settings['dezi4w_max_display_tags'], 'dezi4wp'); ?>" /></td>
    </tr>
</table>
<hr />
<?php settings_fields('dezi4w-options-group'); ?>

<p class="submit">
<input type="hidden" name="action" value="update" />
<input id="settingsbutton" type="submit" class="button-primary" value="<?php _e('Save Changes', 'dezi4wp') ?>" />
</p>

</form>
<hr />
<form method="post" action="options-general.php?page=dezi-for-wordpress/dezi-for-wordpress.php">
<h3><?php _e('Actions', 'dezi4wp') ?></h3>
<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php _e('Check Server Settings', 'dezi4wp') ?></th>
        <td><input type="submit" class="button-primary" name="dezi4w_ping" value="<?php _e('Execute', 'dezi4wp') ?>" /></td>
    </tr>

    <?php if(is_multisite()) { ?>
    <tr valign="top">
        <th scope="row"><?php _e('Push Dezi Configuration to All Blogs', 'dezi4wp') ?></th>
        <td><input type="submit" class="button-primary" name="dezi4w_init_blogs" value="<?php _e('Execute', 'dezi4wp') ?>" /></td>
    </tr>
    <?php } ?>
    
    <?php foreach ($post_types as $post_key => $post_type) {
            if (isset($dezi4w_settings['dezi4w_content']['index'][$post_type]) && $dezi4w_settings['dezi4w_content']['index'][$post_type]==1) {
    ?>

      <tr valign="top">
          <th scope="row"><?php _e('Index all '.ucfirst($post_type), 'dezi4wp') ?></th>
          <td><input type="submit" class="button-primary content_load" name="dezi4w_content_load[<?php echo $post_type?>]" value="<?php _e('Execute', 'dezi4wp') ?>" /></td>
      </tr>
    <?php  
      }
    }

    if (count($dezi4w_settings['dezi4w_content']['index'])>0) {
    ?>
      <tr valign="top">
          <th scope="row"><?php _e('Index All Content', 'dezi4wp') ?></th>
          <td><input type="submit" class="button-primary content_load" name="dezi4w_content_load[all]" value="<?php _e('Execute', 'dezi4wp') ?>" /></td>
      </tr>
    <?php }?>
    
    <tr valign="top">
        <th scope="row"><?php _e('Optimize Index', 'dezi4wp') ?></th>
        <td><input type="submit" class="button-primary" name="dezi4w_optimize" value="<?php _e('Execute', 'dezi4wp') ?>" /></td>
    </tr>
        
    <tr valign="top">
        <th scope="row"><?php _e('Delete All', 'dezi4wp') ?></th>
        <td><input type="submit" class="button-primary" name="dezi4w_deleteall" value="<?php _e('Execute', 'dezi4wp') ?>" /></td>
    </tr>
</table>
</form>

</div>
