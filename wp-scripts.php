<?php
/*
Plugin Name: WP-Scripts
Version: 2.0.1
Plugin URI: http://082net.com/tag/wp-scripts/
Description: load popular javascripts like jquery, prototype, lightbox on your blog head using 'wp_head'. you can select script on option panel. Requires WP 2.6 or later
Author: Cheon, Young-Min
Author URI: http://082net.com/
*/

require_once(dirname(__FILE__) . '/wp-compat.php');

if (!class_exists('wpInsertScripts')) :
class wpInsertScripts {
	var $hook, $folder, $url, $path, $fullpath, $admin_page;
	var $parents, $groups, $children;
	var $styles, $queue, $style_queue;
	var $registered, $to_do, $done_custom;

	var $version = '2.0.1';
	var $moo_ver = '1.2.1';
	var $overlay_tools = array('thickbox', 'lightbox', 'smoothbox', 'slimbox', 'slimbox2');
	var $require_style = array('sweetTitles', 'humanmsg');
	
	function wpInsertScripts() {
		$this->__construct();
	}

	function __construct() {
		$this->hook = plugin_basename(__FILE__);
		$this->folder = dirname($this->hook);
		$this->url = WP_PLUGIN_URL . '/' . $this->folder;
		$this->path = '/' . PLUGINDIR . '/' . $this->folder;
		$this->fullpath = ABSPATH . ltrim($this->path, '/');
		$this->reset_vars();
		$this->init_options();
		$this->register_hooks();
	}

	function reset_vars() {
		$this->queue = $this->styles = array();
		$this->parents = $this->groups = $this->children = array();
	}

	function register_hooks() {
		global $wp_version;
		add_action('activate_'.$this->hook, array(&$this, '_setup'));

		add_action('wp_print_scripts', array(&$this, '_register'), 96);
		add_action('wp_print_styles', array(&$this, '_register_styles'), 96);

		if (!is_admin())
			add_action('wp_print_scripts', array(&$this, 'default_scripts'), 97);

		if (!is_admin() && !empty($this->queue)) {
			add_action('wp_print_scripts', array(&$this, '_enqueue'), 99);// print scritps on blog
			add_action('wp_print_styles', array(&$this, '_enqueue_styles'), 99);// print styles on blog
		}

		// load header contents
		add_action('admin_menu', array(&$this, 'add_page'));
		// register filters
		if (in_array('lightbox', $this->queue) || in_array('slimbox', $this->queue) || in_array('slimbox2', $this->queue)) {
//			if (in_array('lightbox', $this->queue))
//				add_action('wp_head', array(&$this, 'lightbox_head'), 20);
			add_filter('the_content', array(&$this, 'lightbox_filter'), 99);
			add_filter('comment_text', array(&$this, 'lightbox_filter'), 99);
		} elseif (in_array('thickbox', $this->queue) || in_array('smoothbox', $this->queue) ) {
			if (in_array('thickbox', $this->queue))
				add_action('wp_head', array(&$this, 'thickbox_head'), 20);
			add_filter('the_content', array(&$this, 'thickbox_filter'), 99);
			add_filter('comment_text', array(&$this, 'thickbox_filter'), 99);
			add_filter('register', array(&$this, 'thickbox_register'));
			add_filter('loginout', array(&$this, 'thickbox_loginout'));
		}
	}

	function init_options() {
		$this->queue = get_option('wp_scripts_queue');
		if ( !is_array($this->queue) )
			$this->queue = array();
		$this->style_queue = get_option('wp_scritps_style_queue');
		if ( !is_array($this->style_queue) )
			$this->style_queue = array();
	}

	function _setup() {
//		if (!get_option('wp_scripts_queue')) add_option('wp_scripts_queue', array());
//		if (!get_option('wp_scritps_style_queue')) add_option('wp_scritps_style_queue', array());
	}

	function add_page()	{
		if (function_exists('add_options_page')) {
			$page = add_options_page(__("Wp-Scripts", 'wp-scripts'), __("Wp-Scripts", 'wp-scripts'), 9, 'wp-scripts-plugin', array(&$this, 'option_page'));
			add_action("admin_head-$page", array(&$this, 'default_scripts'));
			add_action("admin_print_scripts-$page", array(&$this, 'admin_script'));
			add_action("admin_print_styles-$page", array(&$this, 'admin_style'));
			$this->admin_page = $page;
		}
	}

	function _all_deps( $handles, $recursion = false ) {
		if ( !$handles = (array) $handles )
			return false;

		foreach ( $handles as $handle ) {
			$handle = explode('?', $handle);
			$handle = $handle[0];

			if ( isset($this->to_do[$handle]) ) // Already grobbed it and its deps
				continue;

			$keep_going = true;
			if ( !isset($this->registered[$handle]) )
				$keep_going = false; // Script doesn't exist
			elseif ( $this->registered[$handle]->deps && array_diff($this->registered[$handle]->deps, array_keys($this->registered)) )
				$keep_going = false; // Script requires deps which don't exist (not a necessary check.  efficiency?)
			elseif ( $this->registered[$handle]->deps && !$this->all_deps( $this->registered[$handle]->deps, true ) )
				$keep_going = false; // Script requires deps which don't exist

			if ( !$keep_going ) { // Either script or its deps don't exist.
				if ( $recursion )
					return false; // Abort this branch.
				else
					continue; // We're at the top level.  Move on to the next one.
			}

			$this->to_do[$handle] = true;
		}

		if ( !$recursion ) // at the end
			$this->to_do = array_keys( $this->to_do );
		return true;
	}

	function find_parent($handles, $recursion=false) {
		if ( !$handles = (array) $handles )
			return false;
		if ( !$this->parents )
			return false;

		$parent = array();
		foreach ($handles as $handle) {
			if ( isset($this->registered[$handle]) &&
					$this->registered[$handle]->deps && 
					$_parent = $this->find_parent($this->registered[$handle]->deps, true) )
				$parent = array_merge($parent, (array)$_parent);
			else 
				$parent[] = $handle;
		}
		$parent = array_unique($parent);

		return array_intersect($parent, array_keys($this->parents));
	}

	function default_scripts() {
		global $wp_scripts;
		$admin = array('ajaxcat', 'password-strength-meter', 'xfn', 'postbox', 'slug', 'post', 'page', 'link', 'comment', 'media-upload', 'word-count', 'wp-gears', 'theme-preview', 'inline-edit-post', 'inline-edit-tax', 'plugin-install', 'farbtastic', 'dashboard');

		$this->registered =& $wp_scripts->registered;
		$this->custom_scripts();
		if (empty($this->registered))
			return false;

		foreach ( (array) $this->registered as $handle => $info ) {
			// skip scripts for admin only.
			if ( in_array($handle, $admin) || preg_match('|^admin-|', $handle))
				continue;

			// require style? should registered style with same handle.
//			$styles = apply_filters('wp_scripts_require_style', $this->overlay_tools);
			if (in_array($handle, $this->require_style))
				$this->styles[] = $handle;

			if (in_array($handle, $this->overlay_tools)) {
				$this->styles[] = $handle;
				$this->img_overlay[$handle] = $info;
				continue;
			}

			if ( empty($info->deps) ) {
				$this->parents[$handle] = $info;
			} elseif (!$info->src) {
				$this->groups[$handle] = $info;
			} else {
				$this->children['__rest'][$handle] = $info;
			}
		}

		if ($this->parents && $this->children) {
			$parents = array_keys($this->parents);
			foreach ($this->children['__rest'] as $handle=>$info) {
				if ( $parent = $this->find_parent($info->deps)) {
					foreach ($parent as $key)
						$this->children[$key][$handle] = $info;
					unset($this->children['__rest'][$handle]);
				}
			}
			$this->children = array_filter($this->children);
			uasort($this->children, array(&$this, 'cmp_count'));
		}

//		$this->custom_scripts();
		$this->styles = array_unique($this->styles);
	}

	function cmp_count($a, $b) {
		return count($b) - count($a);
	}

	function custom_scripts() {
		if ( !$this->done_custom ) {
			$path = $this->fullpath . '/js/custom/';
			if (file_exists($path . '/_custom.php'))
				include_once($path . '/_custom.php');
			$this->done_custom = true;
		}
	}

	function _add($handle, $src='', $deps=false, $ver=false) {
		global $wp_scripts;
		if ( isset($this->customs[$handle]) || isset($wp_scripts->registered[$handle]) )
			return;
		if ( $deps && !is_array($deps) )
			$deps = array($deps);
		if ( !preg_match('|https?://|', $src) )
			$src = $this->url . '/js/custom/' . trim($src, '/');
		if ( !$ver )
			$ver = $this->version;
		$this->customs[$handle] = (object) compact('handle', 'src', 'deps', 'ver');
		wp_register_script($handle, $src, $deps, $ver);
	}

	function _add_style($handle, $src='', $deps=false, $ver=false) {
		global $wp_styles;
		if ( !isset($this->customs[$handle]) )
			return;
		$this->styles[] = $handle;
		if ( isset($wp_styles->registered[$handle]) )
			return;
		if ( $deps && !is_array($deps) )
			$deps = array($deps);
		if ( !preg_match('|https?://|', $src) )
			$src = $this->url . '/js/custom/' . trim($src, '/');
		if ( !$ver )
			$ver = $this->version;
		wp_register_style($handle, $src, $deps, $ver);
	}

	function save_changes() {
		if ( $_POST['option_page'] != $this->admin_page )
			return;
		check_admin_referer(  $this->admin_page . '-options' );
		$this->queue = $_POST['wp_scripts_queue'];
		$this->style_queue = array();
		foreach ($this->queue as $queue) {
			if (in_array($queue, $this->styles))
				$this->style_queue[] = $queue;
		}
		update_option('wp_scritps_style_queue', $this->style_queue);
		update_option('wp_scripts_queue', $this->queue);
		echo '<div id="message" class="updated fade"><p><strong>'.__('Settings saved.', 'wp-scritps').'</strong></p></div>';
	}

	function option_page() {
		load_plugin_textdomain('wp-scripts', false, $this->folder . '/lang');
		$this->save_changes();
?>
<div class="wrap"> 
<?php if (function_exists('screen_icon')) screen_icon(); ?>
  <h2><?php _e('Wp-Scripts', 'wp-scripts') ?></h2>
	<span id="debug"></span>
  <form id="wp_scripts_option" name="wp_scripts_option" method="post" action=""> 
	<?php settings_fields($this->admin_page); ?>

	<h3><?php _e('General Scripts', 'wp-scripts') ?></h3>
	<table class="editform form-table">
	<?php if (!empty($this->parents)) { ?>
	<tr valign="top">
	<th scope="row"><?php _e('Independeces:', 'wp-scripts'); ?></th> 
	<td>
	<?php $i=1; $count=sizeof($this->parents); foreach ($this->parents as $handle => $info) { ?>
	<?php $checked = in_array($handle, $this->queue) ? ' checked' : ''; $checked_attr = $checked ? ' checked="checked"' : ''; ?>
	<label for="<?php echo $handle; ?>" class="check<?php echo $checked; ?>">
	<input type="checkbox" id="<?php echo $handle; ?>" name="wp_scripts_queue[]" value="<?php echo $handle; ?>"<?php echo $checked_attr; ?> /> <?php echo $handle; ?><!--  (<?php echo basename($info->src); ?>) --></label>&nbsp;&nbsp;&nbsp;
	<?php if (!($i%3) && $i < $count) echo '<br />'; $i++; ?>
	<?php } ?>
	</td>
	</tr>
	<?php } ?>

	<?php if (!empty($this->groups)) { ?>
	<tr valign="top">
	<th scope="row"><?php _e('Groups:', 'wp-scripts'); ?></th> 
	<td>
	<?php $i=1; $count=count($this->groups); foreach ($this->groups as $handle => $info) { ?>
	<?php $checked = in_array($handle, $this->queue) ? ' checked' : ''; $checked_attr = $checked ? ' checked="checked"' : ''; ?>
	<label for="<?php echo $handle; ?>" class="check<?php echo $checked; ?>">
	<input type="checkbox" deps="<?php echo join(',', $info->deps); ?>" id="<?php echo $handle; ?>" name="wp_scripts_queue[]" value="<?php echo $handle; ?>"<?php echo $checked_attr; ?> /> <?php echo $handle; ?><!--  (<?php echo join(',', $info->deps); ?>) --></label>
	<?php if ( !($i%3) && $i < $count ) echo '<br />'; $i++ ?>
	<?php } ?>
	</td>
	</tr>
	<?php } ?>

	<?php if (!empty($this->img_overlay)) { ?>
	<tr valign="top">
	<th scope="row"><?php _e('Overlay Image Tool:', 'wp-scripts'); ?></th> 
	<td>
	<label class="check"><input type="radio" name="wp_scripts_queue[]" value="" /> <?php _e('None', 'wp-scripts'); ?> </label>
	<?php $i=2; $count=count($this->img_overlay); foreach ($this->img_overlay as $handle => $info) { ?>
	<?php $checked = in_array($handle, $this->queue) ? ' checked' : ''; $checked_attr = $checked ? ' checked="checked"' : ''; ?>
	<label for="<?php echo $handle; ?>" class="check<?php echo $checked; ?>">
	<input type="radio" deps="<?php echo join(',', $info->deps); ?>" id="<?php echo $handle; ?>" name="wp_scripts_queue[]" value="<?php echo $handle; ?>"<?php echo $checked_attr; ?> /> <?php echo $handle; ?><!--  (<?php echo join(',', $info->deps); ?>) --></label>
	<?php if ( !($i%5) && $i < $count ) echo '<br />'; $i++ ?>
	<?php } ?>
	</td>
	</tr>
	<?php } ?>

	</table>

	<?php if (!empty($this->children)) { ?>
	<h3><?php _e('Dependent Scripts', 'wp-scripts') ?></h3>
	<table class="editform form-table">
	<?php foreach ($this->children as $parent=>$handles) { ?>
	<tr valign="top">
	<!-- <th scope="row"><?php _e('Dependencies:', 'wp-scripts'); ?></th> --> 
	<th scope="row"><?php echo ($parent == '__rest' ? __('Others','wp-scripts') : $parent); ?></th> 
	<td>
	<?php $i=1; $count=count($handles); foreach ($handles as $handle => $info) { ?>
	<?php $checked = in_array($handle, $this->queue) ? ' checked' : ''; $checked_attr = $checked ? ' checked="checked"' : ''; ?>
	<label for="<?php echo $handle; ?>" class="check<?php echo $checked; ?>">
	<input type="checkbox" deps="<?php echo join(',', $info->deps); ?>" id="<?php echo $handle; ?>" name="wp_scripts_queue[]" value="<?php echo $handle; ?>"<?php echo $checked_attr; ?> /> <?php echo $handle; ?><!--  (<?php echo join(',', $info->deps); ?>) --></label>
	<?php if ( !($i%3) && $i < $count ) echo '<br />'; $i++ ?>
	<?php } ?>
	</td>
	</tr>
	<?php } ?>
	</table>
	<?php } ?>

	<?php if (!empty($this->customs) && $dev) { ?>
	<h3><?php _e('User custom', 'wp-scripts') ?></h3>
	<table class="editform form-table">
	<tr valign="top">
	<th scope="row"><?php _e('Custom:', 'wp-scripts'); ?></th> 
	<td>
	<?php $i=1; $count=count($this->customs); foreach ($this->customs as $handle => $info) { $deps_attr = $info->deps ? join(',', $info->deps) : false; ?>
	<?php $checked = in_array($handle, $this->queue) ? ' checked' : ''; $checked_attr = $checked ? ' checked="checked"' : ''; ?>
	<label for="<?php echo $handle; ?>" class="check<?php echo $checked; ?>">
	<input type="checkbox"<?php if ($deps_attr) echo 'deps="'.$deps_attr.'"'; ?> id="<?php echo $handle; ?>" name="wp_scripts_queue[]" value="<?php echo $handle; ?>"<?php echo $checked_attr; ?> /> <?php echo $handle; ?><?php //if ($deps_attr) echo " ({$deps_attr})"; ?></label>
	<?php if ( !($i%3) && $i < $count ) echo '<br />'; $i++ ?>
	<?php } ?>
	</td>
	</tr>
	</table>
	<?php } ?>

	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes', 'wp-scripts') ?>" />
	</p>

	</form>
</div><!-- wrap -->
<?php
	}

	function _register() {
		global $wp_version;
		// mootools 1.2.1
		wp_register_script('mootools', $this->path.'/js/mootools/mootools.js', false, $this->moo_ver);
		wp_register_script('mootools-Fx', $this->path.'/js/mootools/mootools.Fx.js', array('mootools'), $this->moo_ver);
		wp_register_script('mootools-Drag', $this->path.'/js/mootools/mootools.Drag.js', array('mootools'), $this->moo_ver);
		wp_register_script('mootools-Utilities', $this->path.'/js/mootools/mootools.Utilities.js', array('mootools'), $this->moo_ver);
		wp_register_script('mootools-Interface', $this->path.'/js/mootools/mootools.Interface.js', array('mootools-Fx', 'mootools-Drag'), $this->moo_ver);
		wp_register_script('smoothbox', $this->path.'/js/mootools/smoothbox.js', array('mootools'), '20080623');
		wp_register_script('slimbox', $this->path.'/js/mootools/slimbox.js', array('mootools'), '1.69');
		wp_register_script('slimbox2', $this->path.'/js/jquery/slimbox2.js', array('jquery'), '2.02');

		wp_register_script('lightbox', $this->path.'/js/prototype/lightbox.js', array('scriptaculous-effects', 'scriptaculous-builder'), '2.0.4');
//		wp_register_script('moo.fx.base', $this->path.'/js/moo.fx.base.js', array('prototype'), '2.0');
//		wp_register_script('moo.fx.all', $this->path.'/js/moo.fx.exp.js', array('prototype', 'moo.fx.base'), '2.0');
		/* since 2.7 */
		if ($wp_version < '2.7') {
			wp_register_script( 'jquery-hotkeys', $this->path.'/js/jquery/jquery.hotkeys.js', array('jquery'), '0.0.2' );
			wp_register_script( 'jquery-table-hotkeys', $this->path.'/js/jquery/jquery.table-hotkeys.js', array('jquery', 'jquery-hotkeys'), '20081128' );
			wp_register_script( 'jquery-ui-sortable', $this->path.'/js/jquery/ui.sortable.js', array('jquery-ui-core'), '1.5.2c' );
			wp_register_script( 'jquery-ui-draggable', $this->path.'/js/jquery/ui.draggable.js', array('jquery-ui-core'), '1.5.2' );
			wp_register_script( 'jquery-ui-resizable', $this->path.'/js/jquery/ui.resizable.js', array('jquery-ui-core'), '1.5.2' );
			wp_register_script( 'jquery-ui-dialog', $this->path.'/js/jquery/ui.dialog.js', array('jquery-ui-resizable', 'jquery-ui-draggable'), '1.5.2' );
			wp_register_script('hoverIntent', $this->path.'/js/jquery/hoverIntent.js', array('jquery'), '20081210');
			wp_register_script( 'swfupload', $this->path.'/js/swfupload/swfupload.js', false, '2.2.0-20081031');
			wp_register_script( 'swfupload-degrade', $this->path.'/js/swfupload/plugins/swfupload.graceful_degradation.js', array('swfupload'), '2.2.0-20081031');
			wp_register_script( 'swfupload-swfobject', $this->path.'/js/swfupload/plugins/swfupload.swfobject.js', array('swfupload'), '2.2.0-20081031');
			$scripts->localize( 'swfupload-degrade', 'uploadDegradeOptions', array(
				'is_lighttpd_before_150' => is_lighttpd_before_150(),
			) );
			wp_register_script( 'swfupload-queue', $this->path.'/js/swfupload/plugins/swfupload.queue.js', array('swfupload'), '2.2.0-20081031');
			wp_register_script( 'swfupload-handlers', $this->path.'/js/swfupload/handlers.js', array('swfupload'), '2.2.0-20081201');
			// these error messages came from the sample swfupload js, they might need changing.
			wp_localize_script( 'swfupload-handlers', 'swfuploadL10n', array(
					'queue_limit_exceeded' => __('You have attempted to queue too many files.','wp-scripts'),
					'file_exceeds_size_limit' => sprintf(__('This file is too big. Your php.ini upload_max_filesize is %s.','wp-scripts'), @ini_get('upload_max_filesize')),
					'zero_byte_file' => __('This file is empty. Please try another.','wp-scripts'),
					'invalid_filetype' => __('This file type is not allowed. Please try another.','wp-scripts'),
					'default_error' => __('An error occurred in the upload. Please try again later.','wp-scripts'),
					'missing_upload_url' => __('There was a configuration error. Please contact the server administrator.','wp-scripts'),
					'upload_limit_exceeded' => __('You may only upload 1 file.','wp-scripts'),
					'http_error' => __('HTTP error.','wp-scripts'),
					'upload_failed' => __('Upload failed.','wp-scripts'),
					'io_error' => __('IO error.'),
					'security_error' => __('Security error.','wp-scripts'),
					'file_cancelled' => __('File cancelled.','wp-scripts'),
					'upload_stopped' => __('Upload stopped.','wp-scripts'),
					'dismiss' => __('Dismiss','wp-scripts'),
					'crunching' => __('Crunching&hellip;','wp-scripts'),
					'deleted' => __('Deleted','wp-scripts'),
					'l10n_print_after' => 'try{convertEntities(swfuploadL10n);}catch(e){};'
			) );
		} elseif (!is_admin()) {
			// hoverIntent should be registered outside of admin too.
			wp_register_script('hoverIntent', $this->path.'/js/jquery/hoverIntent.js', array('jquery'), '20081210');
		}
		wp_register_script( 'jquery-ui-droppable', $this->path . '/js/jquery/ui.droppable.js', array('jquery-ui-core', 'jquery-ui-draggable'), '1.5.2' );
		wp_register_script('jquery-easing', $this->path . '/js/jquery/jquery.easing.js', array('jquery'), '1.1.2');

		wp_register_script('humanmsg', $this->path . '/js/jquery/jquery.humanmsg.js', array('jquery', 'jquery-easing'), $this->version);
		wp_register_script('humanundo', $this->path . '/js/jquery/jquery.humanundo.js', array('jquery'), $this->version);

		if (defined('DEV_PLUGIN') && DEV_PLUGIN === true) {
			wp_deregister_script('thickbox');
			wp_register_script('thickbox', $this->path . '/js/jquery/thickbox.js', array('jquery'), '20081210');
		}
	}

	function _register_styles() {
		wp_register_style('humanmsg', $this->path . '/c/humanmsg.css', false, $this->version);
		wp_register_style('slimbox', $this->path . '/c/slimbox.css', false, $this->version);
		wp_register_style('slimbox2', false, array('slimbox'), $this->version);
		wp_register_style('smoothbox', $this->path . '/c/smoothbox.css', false, $this->version);
		wp_register_style('lightbox', $this->path . '/c/lightbox.css', false, $this->version);
//		wp_deregister_style('thickbox');
//		wp_register_style('thickbox', $this->path . '/c/thickbox.css', false, $this->version);
	}

	function _enqueue() {
		global $wp_scripts;
		foreach ($this->queue as $handle)
			wp_enqueue_script($handle);
	}

	function _enqueue_styles() {
		if ( empty($this->style_queue) )
			return;
		foreach ($this->style_queue as $handle)
				wp_enqueue_style($handle);
	}

	function admin_script() {
		wp_enqueue_script('admin-wp-scripts', $this->path . '/js/_wp-scripts-admin.js', array('jquery'), $this->version);
	}

	function admin_style() {
?>
<style type="text/css">
label.checked {
background-color: #ccd;
}
label.tmpchecked {
background-color: #ffff33;
}
label.check {
padding: 2px 3px;
}
</style>
<?php
	}


	function wp_head() {
	}

	function thickbox_head() {
		$imgurl = get_option('siteurl') . '/wp-includes/js/thickbox';
?>
<script type="text/javascript">//<![CDATA[
	var tb_pathToImage = "<?php echo $imgurl; ?>/loadingAnimation.gif";
	var tb_closeImage = "<?php echo $imgurl; ?>/tb-close.png";
//]]></script>
<?php 
	}

	function lightbox_head() {
		$imgurl = $this->url . '/c/images';
?>
<script type="text/javascript">//<![CDATA[
	LightboxOptions.fileLoadingImage = "<?php echo $imgurl; ?>/loading.gif";
	LightboxOptions.fileBottomNavCloseImage = "<?php echo $imgurl; ?>/closelabel.png";
//]]></script>
<?php 
	}
/*
	function smoothbox_filter($the_content) {
		return $this->thickbox_filter($the_content, 'smoothbox');
	}

	function append_smoothbox_str($m) {
		return $this->append_thickbox_str($m, 'smoothbox');
	}
*/
	function thickbox_filter($the_content, $append="thickbox") {
		$preg = '#(<a([^>]*)href=("|\')([^"\']*\.)(bmp|gif|jpeg|jpg|png)\3([^>]*)>\s*<img)#i';
		$repl = array(&$this, "append_{$append}_str");
		return preg_replace_callback($preg,$repl,$the_content);
	}

	function append_thickbox_str($m, $append='thickbox') {
		global $post, $id;
		$q = $m[3];
		$pre = $m[2];
		$_post = $m[6];
		$ext = $m[5];
		$src = $m[4];
		$group_rel = $append.'-'.(isset($post->ID) ? $post->ID : 'outofloop');

		$preg = '/class='.$q.'([^'.$q.']+)'.$q.'/i';
		if ( preg_match($preg, $pre.$_post, $m) ) {
			$classes = split(' ', $m[1]);
			if ( !in_array($append, $classes) )// no thickbox class
				$classes[] = $append;
			$pre = preg_replace($preg, '', $pre);
			$_post = preg_replace($preg, '', $_post);
			$_post .= ' class="'.join(' ', $classes).'"';
		} else {
			$_post .= ' class="'.$append.'"';
		}

		$preg = '/rel='.$q.'([^'.$q.']+)'.$q.'/i';
		if ( preg_match($preg, $pre.$_post, $m) ) {
			$pre = preg_replace($preg, '', $pre);
			$_post = preg_replace($preg, '', $_post);
		}
		$_post .= ' rel="'.$group_rel.'"';

		if (strpos($pre.$_post, ' title=') === false)
			$_post .= ' title='.$q.basename($src.$ext).$q;
		return '<a'.$pre.'href='.$q.$src.$ext.$q.$_post.'><img';
	}

	function get_thickbox_login_url() {
		global $wp_version;
		$url = $this->url.'/tblogin/';
		preg_match("/\d\.\d(\.\d)?/i", $wp_version, $match);
		if (file_exists(dirname(__FILE__).'/tblogin/tb-login-'.$match[0].'.php'))
			$url .= 'tb-login-'.$match[0].'.php';
		else 
			$url .= 'tb-login.php';
		return $url;
	}

	function thickbox_register( $link ) {
		if ( ! is_user_logged_in() && get_option('users_can_register') ) {
			if (!preg_match('|^(.*)?<a(.*)?</a>(.*)?$|i', $link, $m))
				return $link;
			$before = $m[1];
			$after = $m[3];
			$url = site_url('wp-login.php?action=register', 'login');
			$url = add_query_arg('height', '600', $url);
			return $before . '<a href="'.$url.'" class="thickbox">' . __('Register') . '</a>' . $after;
		}
		return $link;
	}

	function thickbox_loginout($link) {
//		$tblogin = $this->get_thickbox_login_url();
		$redirect_to = urlencode(stripslashes($_SERVER['REQUEST_URI']));
		if ( ! is_user_logged_in() ) {
			$url = wp_login_url($redirect_to);
			$url = add_query_arg('height', '600', $url);
			$link = '<a href="'.$url.'" class="thickbox">' . __('Log in') . '</a>';
		} else {
			$link = '<a href="'.wp_logout_url($redirect_to).'">' . __('Log out') . '</a>';
		}
		return $link;
	}

	//powered by WP lightbox 2 (http://zeo.unic.net.my/2006/03/29/lightbox-js-version-20/)
	function lightbox_filter($the_content) {
		global $post;
//		$preg = '/(<a(.*?)href="([^"]*.)(bmp|gif|jpeg|jpg|png)"(.*?)><img)/ie';
//		$repl = '(strstr("\2\5","rel=") ? "\1" : "<a\2href=\"\3\4\"\5 rel=\"lightbox['.$post->ID.']\"><img")';
		$preg = '/(<a(.*?)href=["\']([^"\']*.)(bmp|gif|jpeg|jpg|png)["\'](.*?)><img)/i';
		$repl = array(&$this, 'append_lightbox_str');
		return preg_replace_callback($preg,$repl,$the_content);
	}

	function append_lightbox_str($m) {
		global $post;
		$q = (strstr(strtolower($m[0]), 'href="')) ? '"':"'";
		if (strpos($m[2], "rel=") !== false)
			$m[2] = (strpos($m[2], 'lightbox') !== false)?$m[2]:str_replace('rel='.$q, 'rel='.$q.'lightbox['.$post->ID.'] ', $m[2]);
		elseif (strpos($m[5], "rel=") !== false)
			$m[5] = (strstr($m[5], 'lightbox') !== false)?$m[5]:str_replace('rel='.$q, 'rel='.$q.'lightbox['.$post->ID.'] ', $m[5]);
		else 
			$m[5] .= ' rel='.$q.'lightbox['.$post->ID.']'.$q;
		if (strpos($m[2].$m[5], 'title='.$q) === false)
			$m[5] .= ' title='.$q.basename($m[3].$m[4]).$q;
		return '<a'.$m[2].'href='.$q.$m[3].$m[4].$q.$m[5].'><img';
	}

	function &get_instance() {
		static $instance = array();
		if ( empty( $instance ) ) {
			$instance[] =& new wpInsertScripts();
		}
		return $instance[0];
	}

}//end of class
endif;
// instance of plugin
$WpScripts =& wpInsertScripts::get_instance();

function wp_scripts_add_custom($handle, $src='', $deps=false, $ver=false) {
	global $WpScripts;
	$WpScripts->_add($handle, $src, $deps, $ver);
}

function wp_scripts_add_custom_style($handle, $src='', $deps=false, $ver=false) {
	global $WpScripts;
	$WpScripts->_add_style($handle, $src, $deps, $ver);
}
?>