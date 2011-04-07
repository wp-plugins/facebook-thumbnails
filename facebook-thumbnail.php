<?php
/*
Plugin Name: Facebook Thumbnails
Plugin URI: http://signpostmarv.name/facebook-thumbnails/
Description: Zero-configuration Facebook Thumbnails plugin. Modifies blog & post output to prevent Facebook from making assumptions as to what thumbnails to include.
Author: SignpostMarv
Version: 0.2
*/

class Marvulous_FB_Thumbnail{
	protected function __construct(){
		$class = get_class($this);
		add_action('init', array($class,'action_init'));
		add_action('the_post', array($class, 'action_the_post'));
		add_action('wp_footer', array($class, 'action_wp_footer'));
		if(class_exists('DOMDocument',false)){
	//		$this->get_image_mode = 'DOMDocument';
		}
	}

	private $get_image_mode = 'regex';
	public function get_image_mode(){
		return $this->get_image_mode;
	}

	public static function i(){
		static $instance;
		if(isset($instance) === false){
			$instance = new self;
		}
		return $instance;
	}

	protected static $active = false;
	public static function action_init(){
		if(isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit') !== false){
			self::$active = true;
			ob_start(); // we're starting output buffering here because we need to incercept blog output
		}
	}

	protected static $posts = array();
	public static function action_the_post($post){
		self::$posts[] = $post;
	}

	public static function action_wp_footer(){
		if(self::$active){ // if request has come from Facebook,
			ob_end_clean(); // clear the output buffer
			foreach(self::$posts as $post){
				echo '<article><header><h1>',esc_html($post->post_title),'</h1><p>written by <em>',esc_html(the_author_meta('display_name',$post->post_author)),'</em>',get_avatar($post->post_author),'</p>';
				if(function_exists('has_post_thumbnail') && function_exists('get_the_post_thumbnail')){ // some versions of WordPress don't support post thumbnails
					if(has_post_thumbnail($post->ID)){
						echo get_the_post_thumbnail($post->ID);
					}
				}
				echo '</header>';
				$content = trim(do_shortcode($post->post_excerpt));
				$use_excerpt = (boolean)strlen($content);
				$content = $use_excerpt ? $content : trim(do_shortcode($post->post_content)); // if the post excerpt wasn't empty, use it, otherwise use the full post content
				echo '<section>',esc_html(strip_tags($content)),'</section>'; // this strips ALL html, including image tags
				$images = self::get_post_images($content); // which is why we get them here
				if(count($images)){
					echo '<section><ul>';
					foreach($images as $src){
						echo '<li><img src="',esc_attr($src),'"></li>';
					}
					echo '</ul></section>';
				}
				echo '</article>';
			}
			exit; // this is to prevent other plugins from adding output which may confuse facebook
		}
	}

	protected static function get_post_images($post){
		$images = array();
		switch(self::i()->get_image_mode()){
			case 'DOMDocument':
			break;
			case 'regex':
				$matches = null;
				if(preg_match_all('/<img\s+([A-z_]+\=[\\\'"].+[\\\'"]\s+)*src=[\\\'"]?([^\\\'"]+)[\\\'"]?\s*([A-z_]+\=[\\\'"].+[\\\'"]\s+)*\/?>/Si',$post,$matches) > 0){
					$images = $matches[2];
				}
			break;
		}
		return $images;
	}
}

Marvulous_FB_Thumbnail::i();
?>