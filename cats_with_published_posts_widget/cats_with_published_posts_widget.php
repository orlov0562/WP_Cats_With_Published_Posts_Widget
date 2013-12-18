<?php
/**
 * Plugin Name: Cats with published posts widget
 * Plugin URI:  http://www.it-rem.ru/
 * Description: The widget show categories which contains only categories that have published posts
 * Author:      Vitaliy S. Orlov
 * Author URI:  http://www.it-rem.ru/
 * Donate URI:  http://www.it-rem.ru/
 * Version:     1.0.0
 * Last change: 13/12/2013
 * License:     GPLv3
 */

class cats_with_published_posts_widget extends WP_Widget {


    /** constructor -- name this the same as the class above */
    public function cats_with_published_posts_widget() {
        parent::WP_Widget(false, $name = 'Cats with published posts only');
    }

    /** @see WP_Widget::widget -- do not rename this */
    public function widget($args, $instance) {
   	    extract( $args );
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Categories' ) : $instance['title'], $instance, $this->id_base);
		$cache = ! empty( $instance['cache'] ) ? '1' : '0';
		$cache_time = !empty( $instance['cache_time'] ) ? intval($instance['cache_time']) : '0';

		echo $before_widget;
		if ( $title ) echo $before_title . $title . $after_title;

        if (!$categories = $this->get_categories($cache, $cache_time))
        {
          echo '<p>No categories</p>';
        }
        else
        {
          echo '<ul>';
          foreach ($categories as $category)
          {
              echo '<li class="cat-item cat-item-'.$category->cat_ID.'" title="View all posts filed under '.esc_attr($category->name).'">';
              echo '<a href="'.esc_url( get_category_link($category->cat_ID) ).'">'.$category->name.'</a>';
              echo '</li>';
          }
          echo '<ul>';
        }

		echo $after_widget;
    }

    private function get_categories($use_cache, $cache_time=60)
    {
        global $wpdb;

        $cache_filepath = dirname(__FILE__).'/cache.ser';

        if ($use_cache AND file_exists($cache_filepath) AND (filemtime($cache_filepath)+$cache_time)>time())
        {
            if ($cache = file_get_contents(dirname(__FILE__).'/cache.ser'))
            {
                if ($categories = unserialize($cache))
                {
                    return $categories;
                }
            }
        }

        $args = array(  'type'                     => 'post',
                        'child_of'                 => 0,
                        'parent'                   => '',
                        'orderby'                  => 'name',
                        'order'                    => 'ASC',
                        'hide_empty'               => 1,
                        'hierarchical'             => 1,
                        'exclude'                  => '',
                        'include'                  => '',
                        'number'                   => '',
                        'taxonomy'                 => 'category',
                        'pad_counts'               => false
        );

        if ($categories = get_categories($args))
        {
            foreach ($categories as $k=>$category)
            {
                $sql = 'SELECT `object_id` FROM `'.$wpdb->prefix.'term_relationships` WHERE
                            `term_taxonomy_id` = %d
                ';

                $psql = $wpdb->prepare($sql, $category->term_taxonomy_id);

                if (!$posts = $wpdb->get_results($psql))
                {
                    unset($categories[$k]);
                    continue;
                }

                $sub_sql = array();
                foreach($posts as $post) $sub_sql[] = '`ID`="'.intval($post->object_id).'"';

                if ($sub_sql)
                {
                    $sql = 'SELECT `ID` FROM `'.$wpdb->prefix.'posts` WHERE
                                `post_status`="publish"
                            AND
                            ('.implode(' OR ',$sub_sql).')
                            LIMIT 1
                    ';
                    if (!$wpdb->get_var($sql)) unset($categories[$k]);
                }

            }
        }

        if ($use_cache) file_put_contents($cache_filepath, serialize($categories));

        return $categories;
    }

 	public function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = esc_attr( $instance['title'] );
		$cache = intval( $instance['cache'] );
		$cache_time = intval( $instance['cache_time'] );
        ?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

        <br><strong>Caching:</strong><br><br>

		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('cache'); ?>" name="<?php echo $this->get_field_name('cache'); ?>"<?php checked( $cache ); ?> />
		<label for="<?php echo $this->get_field_id('cache'); ?>"><?php _e( 'Use cache for categories' ); ?></label><br />

		<p><label for="<?php echo $this->get_field_id('cache_time'); ?>"><?php _e( 'Update cache every, sec:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('cache_time'); ?>" name="<?php echo $this->get_field_name('cache_time'); ?>" type="text" value="<?php echo $cache_time; ?>" /></p>

        <br>
        <?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['cache'] = !empty($new_instance['cache']) ? 1 : 0;
		$instance['cache_time'] = intval($new_instance['cache_time']);
		return $instance;
	}

} // end class example_widget
add_action('widgets_init', create_function('', 'return register_widget("cats_with_published_posts_widget");'));
?>