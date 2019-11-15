<?php

/**
 * Register the [suggested_ranks_page] shortcode.
 */
function bos_register_suggested_ranks_shortcode() {
	badgeos_register_shortcode( array(
		'name'            => __( 'Suggested Ranks Page', 'badgeos' ),
		'description'     => __( 'This shortcode will list all the skipped ranks. It will give option to remove the rank from the skipped list.', 'badgeos-addon' ),
		'slug'            => 'suggested_skipped_ranks_page',
		'output_callback' => 'bos_suggested_skipped_ranks_shortcode',
		'attributes'      => array(
			'num_per_page' => array(
				'name'        => __( 'Number of Achievement Per Page', 'badgeos-addon' ),
				'description' => __( 'Optional Number of Achievement Per Page to limit the items displayed at a time. Default is 10 achievements per page.', 'badgeos-addon' ),
				'type'        => 'text',
			),
		),
	) );
}
add_action( 'init', 'bos_register_suggested_ranks_shortcode' );

/**
 * Suggested Achievement Page Shortcode.
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function bos_suggested_skipped_ranks_shortcode( $atts = array() ) {
    
    $atts = shortcode_atts( array(
		'num_per_page'    => 10
    ), $atts, 'suggested_achievement_page' );
    
    $skipped = badgeos_get_user_skipped_ranks();
    $page_body = '';
    if( is_array( $skipped ) && count( $skipped ) > 0 ) {
        
        $rec_ids = '';
        $rec_ids_array = array();
        foreach( $skipped as $rec ) {
            if( ! empty( $rec ) ) {
                $rec_ids .= (!empty( $rec_ids )?',':'').$rec;
                $rec_ids_array[] = $rec;
            }
        }
        
        $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        $args = array(
            'posts_per_page' => -1,
            'post_type'      => trim( $settings['ranks_main_post_type'] ),
        );
    
        $rank_types = get_posts( $args );
        $post_types = [];
        foreach ( $rank_types as $rank_type ) {
            $post_types[] = $rank_type->post_name;
        }

        if(! empty($_GET['pag']) && is_numeric($_GET['pag']) ) {
            $paged = $_GET['pag'];
        } else {
            $paged = 1;
        }
        
        //how many posts should we display?
        $posts_per_page = absint( $atts['num_per_page'] ); 
        
        //let's first get ALL of the possible posts
        $args = array(
                'posts_per_page'   => -1,
                'post_type'         => $post_types,
                'post__in'          => $rec_ids_array,
                'fields'           => 'ids'
            );
        
        $all_posts = get_posts($args);
        
        //how many total posts are there?
        $post_count = count($all_posts);
        
        //how many pages do we need to display all those posts?
        $num_pages = ceil($post_count / $posts_per_page);
        
        //let's make sure we don't have a page number that is higher than we have posts for
        if($paged > $num_pages || $paged < 1){
            $paged = $num_pages;
        }
        
        //now we get the posts we want to display
        $args = array(
                'posts_per_page'   => $posts_per_page,
                'orderby'          => 'title',
                'order'            => 'ASC',
                'post_type'         => $post_types,
                'post__in'          => $rec_ids_array,
                'paged'        => $paged
            );
        
        $my_posts = get_posts($args);
        
        //did we find any?
        if(! empty($my_posts)){
        
            $page_body = '<div class="bos_suggested_rank_msg" style="display:none"></div><div class="bos_suggested_ranks_ajax_preloader" style="display:none;text-align: center;"><img src="'.$GLOBALS['badgeos_reports_addon']->directory_url.'/css/ajax-loader.gif"></div><table class="bos_suggested_achs_listing">';
        
            //THE FAKE LOOP
            foreach($my_posts as $key => $my_post){
                //do stuff with your posts
                $permalink = get_permalink( $my_post->ID );
                $title = get_the_title( $my_post->ID );
                $img = badgeos_get_rank_image( $my_post->ID );
                $thumb = $img ? '<a href="' . esc_url( $permalink ) . '">' . $img . '</a>' : '';
                $class = '';
                $item_class = $thumb ? ' has-thumb' : '';

                $page_body .= '<tr>';
                $page_body .= '<td width="10%">'.$thumb.'</td>';
                $page_body .= '<td width="70%"><a data-index="'.$my_post->ID.'" class="bos_suggested_achs_title" href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></td>';
                $page_body .= '<td width="10%"><a href="javascript:;" data-index="'.$my_post->ID.'" class="bos_suggested_ranks_unskip_link" title="'.__( 'Remove from skipped achievements', 'badgeos-suggested-achievements' ).'">'.__( 'Remove', 'badgeos-suggested-achievements' ).'</a></td>';
                $page_body .= '</tr>';
            }
        
            $page_body .= '</table>';
        
            //we need to display some pagination if there are more total posts than the posts displayed per page
            if($post_count > $posts_per_page ){
        
                $page_body .= '<div class="suggested-achs-pagination"><ul>';
        
                if($paged > 1){
                    $page_body .= '<li><a class="first" href="?pag=1">&laquo;</a></li>';
                }else{
                    $page_body .= '<li><span class="first">&laquo;</span></li>';
                }
        
                for($p = 1; $p <= $num_pages; $p++){
                    if ($paged == $p) {
                        $page_body .= '<li><span class="current">'.$p.'</span></li>';
                    }else{
                        $page_body .= '<li><a href="?pag='.$p.'">'.$p.'</a></li>';
                    }
                }
        
                if($paged < $num_pages){
                    $page_body .= '<li><a class="last" href="?pag='.$num_pages.'">&raquo;</a></li>';
                }else{
                    $page_body .= '<li><span class="last">&raquo;</span></li>';
                }
        
                $page_body .= '</ul></div>';
            }
            $page_body .= '</div>';
        }
    }

    return $page_body;
}
