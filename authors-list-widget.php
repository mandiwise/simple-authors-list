<?php

/*
Plugin Name: A Very Simple Author List Widget
Plugin URI: http://mandiwise.com/wordpress/author-list-widget/
Description: This plugin generates a sidebar widget that allows you to list all of the authors on a multi-author site with their descriptions (i.e. bios) and links to respective archives.
Version: 1.0
Author: Mandi Wise
Author URI: http://mandiwise.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
  
*/

// * The widget functions *

class authors_list_widget extends WP_Widget {

	// - constructor -

	function __construct() {
		$widget_ops = array( 'classname' => 'authors_list_widget', 'description' => 'A list of post author names with their descriptions and links to archives'	);
		parent::__construct('authors_list_widget', __('Author Names and Bios'), $widget_ops);
		
		add_action ('wp_enqueue_scripts', array( $this, 'load_authors_list_styles') );
		add_action( 'init', array( $this, 'plugin_textdomain' ) );
	}
	
	// - load styles -

	public function load_authors_list_styles() {
		wp_enqueue_style( 'authors_list_styles', WP_PLUGIN_URL . '/simple-authors-list/style.css' );
	}

	// - load text domain for translation -

	public function plugin_textdomain() {
        load_plugin_textdomain( 'authors_list_locale', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
    }
	
	// - outputs the content of the widget -
 	
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
 		
 		// - the widget options -
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
		$exclude = empty( $instance['exclude'] ) ? '' : $instance['exclude'];
		$show_gravatar = $instance['show_gravatar'];
 		
 		echo $before_widget;
 		
 		// - if the title is set -
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		
		// - if users are excluded -
		if ( $exclude ) {
			
			$excludedusers = explode(',', $exclude);
				
			$excludesql = array();			
			foreach($excludedusers as $excludeduser) {
				$excludesql[] = "AND user_login <> ('$excludeduser')";
			}
			$userstring = implode(' ', $excludesql);
			
    	}
    	else { $userstring= ''; }
    	
    	// - pick your Gravatar size -
		if ( $instance['show_gravatar'] ) {
			if ( $instance['show_gravatar'] == '16' )
				$gravsize = 16;
			elseif ( $instance['show_gravatar'] == '32' )
				$gravsize = 32;
			elseif ( $instance['show_gravatar'] == '48' )
				$gravsize = 48;
			elseif ( $instance['show_gravatar'] == '0' )
				$gravsize = 0;
			else
				$gravsize = 16;
		}

		// - author list awesomeness here -
		
		global $wpdb;
		$authors = $wpdb->get_results("SELECT ID, user_login from $wpdb->users WHERE 1=1 $userstring ORDER BY display_name");
		
		echo "<ul class=\"authors-list\">";
		foreach($authors as $author) {
			echo "<li id=\"";
			the_author_meta('user_login', $author->ID);
			echo "\">";
		    if( $instance['show_gravatar'] > '0' ) {
				echo "<a href=\"".get_bloginfo('url')."/?author=";
				echo $author->ID;
				echo "\">";
				echo get_avatar($author->ID, $gravsize);
				echo "</a>";
			}
			echo "<p class=\"name\">";
			echo "<a href=\"".get_bloginfo('url')."/?author=";
			echo $author->ID;
			echo "\">";
			the_author_meta('first_name', $author->ID);
			echo " ";
			the_author_meta('last_name', $author->ID);
			echo "</a>";
			echo "</p>";
			if ( get_the_author_meta('description', $author->ID) ) {
				echo "<p class=\"bio\">";
				the_author_meta('description', $author->ID);
				echo "</p>";
			}
			echo "</li>";
		}
		echo "</ul>";

		echo $after_widget;
	}	
	
	// - processes widget options to be saved -
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '', 'exclude' => '', 'show_gravatar' => '') );
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['exclude'] = strip_tags($new_instance['exclude']);
		$instance['show_gravatar'] = strip_tags($new_instance['show_gravatar']);
		
		if ( !in_array( $instance['show_gravatar'], array( 16, 32, 48, 0 ) ) )
			$instance['show_gravatar'] = 16;

		return $instance;
	}
	
	// - outputs the options form on the admin side - 
	
	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'exclude' => '', 'show_gravatar' => '') );
		$title = esc_attr($instance['title']);
		$exclude = esc_attr( $instance['exclude'] );
		$show_gravatar = esc_attr( $instance['show_gravatar'] );
	?>
	
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'List heading:', 'authors_list_locale' ) ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('show_gravatar'); ?>"><?php _e( 'Show Gravatar:', 'authors_list_locale' ) ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('show_gravatar'); ?>" name="<?php echo $this->get_field_name('show_gravatar'); ?>">            
				<?php 
					$options = array( 16 => __('Small (16px)', 'authors_list_locale'), 32 => __('Medium (32px)', 'authors_list_locale'), 48 => __('Large (48px)', 'authors_list_locale'), 0 => __('No Gravatar', 'authors_list_locale') );
					foreach ( $options as $option => $display) : 
				?>
					<option value="<?php echo $option; ?>"<?php if ( $show_gravatar == $option ) echo ' selected="selected"' ?>><?php echo $display; ?></option>
				<?php endforeach; ?>            
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e( 'Exclude these users:', 'authors_list_locale' ) ?></label><br />
			<input class="widefat" id="<?php echo $this->get_field_id('exclude'); ?>" name="<?php echo $this->get_field_name('exclude'); ?>" type="text" value="<?php echo esc_attr($exclude); ?>" />
			<small><?php _e( 'User logins, separated by commas (no spaces).', 'authors_list_locale' ); ?></small>
		</p>
		
	<?php }
 
}

add_action( 'widgets_init', create_function('', 'return register_widget("authors_list_widget");') );

?>