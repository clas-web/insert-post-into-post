<?php
/*
Plugin Name: Insert Post Into Post
Plugin URI: none
Description: Inserts a post or page into a WordPress page using shortcode.
Author: Crystal Barton
Version: 1.0
Author URI: http://www.uncc.edu
*/

add_filter( 'the_content', array('Insert_Post_Into_Post', 'process_content') );

class Insert_Post_Into_Post
{

	/**
	 * Finds all occurances of the insert-post shortcode, retreives the content, then 
	 * replaces the shortcode with the content.
	 *
	 * @param  string  $content  The content of the current page/post.
	 * @return string  The processed content of the current page/post.
	 */
	static function process_content( $content )
	{
		$matches = NULL;
		$num_matches = preg_match_all("/\[insert-post(.+)\]/", $content, $matches, PREG_SET_ORDER);

		if( ($num_matches !== FALSE) && ($num_matches > 0) )
		{
			for( $i = 0; $i < $num_matches; $i++ )
			{
				$content = str_replace($matches[$i][0], '<div id="post-container">'.self::get_content( $matches[$i][0] ).'</div>', $content);
			}
		}
		
		return $content;
	}

	/**
	 * Process the current shortcode and retreive the associated content.
	 *
	 * @param  string  The shortcode found in the content.
	 * @return string  The retreived content based on the shortcode parameters.
	 */
	static function get_content( $shortcode )
	{
		//
		// Get the domain, path, and post name from the shortcode.
		//
		$domain = NULL;
		$path = NULL;
		$post = NULL;

		$matches = NULL;
		if( preg_match("/domain=\"([^\"]+)\"/", $shortcode, $matches) )
			$domain = trim($matches[1]);
	
		$matches = NULL;
		if( preg_match("/path=\"([^\"]+)\"/", $shortcode, $matches) )
			$path = trim($matches[1]);

		$matches = NULL;
		if( preg_match("/post=\"([^\"]+)\"/", $shortcode, $matches) )
			$post = trim($matches[1]);

		//
		// A blank domain means use the current domain.
		//
		if( empty($domain) )
			$domain = get_current_site()->domain;

		//
		// Remove trailing slash from domain.
		//
		if( $domain[ $domain.length-1 ] === '/' )
			$domain = substr( $domain, 0, $domain.length-1 );
	
		//
		// Add slash before and after site path.
		//
		if( $path[0] != '/' )
			$path = '/'.$path;
		if( $path[ strlen($path)-1 ] != '/' )
			$path = $path.'/';

		//
		// Get the blog details.
		//
		$blog = get_blog_details( array('domain' => $domain, 'path' => $path) );
		if( !$blog ) return 'INSERT POST IN POST ERROR: "Unknown blog"';

		switch_to_blog( $blog->blog_id );

		//
		// Retreive the content.
		//
		$content = '';
		if( empty($post) )  // Retreive the front page contents.
		{
			switch( get_option('show_on_front') )
			{
				case 'page':
					$id = get_option('page_on_front');
					if( empty($id) )
					{
						$content = 'ERROR: no front page set.';
						return;
					}
					$content = self::get_single_post( 'page', $id );
					break;

				case 'posts':
				default:
					$content = self::get_posts( 1 ); //get_option('post_per_page') );
					break;
			}		
		}
		else
		{
			global $wpdb;

			$id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '$post'");

			if( empty($id) )
			{
				$content = 'ERROR: no post was found with name = '.$post;
				break;
			}
		
			if( $id == get_option('page_for_posts') )
				$content = self::get_posts( 1 ); //get_option('post_per_page') );
			else
				$content = self::get_single_post( 'any', $id );
		}

		restore_current_blog();

		//
		// Done.
		//
		return $content;
	}


	/**
	 * Retreive the content for a single page/post.
	 *
	 * @param  string  $post_type  The post type (post|page|any).
	 * @param  string  $id         The id of the page/post.
	 * @return string  The retrieved and formatted contents of page/post.
	 */
	static function get_single_post( $post_type, $id )
	{
		$content = '';
	
		$args = array(
			'p' => $id,
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => 1
		);
		$query = new WP_Query( $args );

		if( $query->have_posts() )
		{
			$query->the_post();
			if( get_the_title() !== 'Home' && get_the_title() !== 'Overview' )
				$content = '<div class="post"><h2 class="entry-title">'.get_the_title().'</h2><div class="entry-content">'.wpautop(get_the_content()).'</div></div>';
			else
				$content = '<div class="post"><div class="entry-content">'.wpautop(get_the_content()).'</div></div>';
		}
	
		wp_reset_query();
	
		return $content;
	}


	/**
	 * Retrieve the content of multiple posts for a blog roll.
	 *
	 * @param  int  $number_of_posts  The number of posts to retreive for the blog roll.
	 * @return string  The retrieved and formatted contents of the posts.
	 */
	static function get_posts( $number_of_posts )
	{
		$content = '';
	
		$args = array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => $number_of_posts
		);
		$query = new WP_Query( $args );

		if( $query->have_posts() )
		{
			while( $query->have_posts() )
			{
				$query->the_post();
				if( get_the_title() !== 'Home' && get_the_title() !== 'Overview' )
					$content = '<div class="post"><h2 class="entry-title">'.get_the_title().'</h2><div class="entry-content">'.wpautop(get_the_content()).'</div></div>';
				else
					$content = '<div class="post"><div class="entry-content">'.wpautop(get_the_content()).'</div></div>';
			}
		}
	
		wp_reset_query();
	
		return $content;
	}
	
}
