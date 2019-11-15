<?php

/**
 * Get a user's badgeos suggested achievements
 *
 * @since  1.0.0
 * @return array       An array of all the achievement objects that matched our parameters, or empty if none
 */
function badgeos_get_suggested_achievements(){

    global $posts;

    $type = null;
    $current_post_id = null;
    foreach ($posts as $post) {
        $current_post_id = $post->ID;
        $type = $post->post_type;
    }

    // Fetching achievement types
    $param = array(
        'posts_per_page'   => -1, // All achievements
        'offset'           => 0,  // Start from first achievement
        'post_type'=>'achievement-type', // set post type as achievement to filter only achievements
        'orderby' => 'menu_order',
        'order' => 'ASC',
    );

    if ( badgeos_achievement_type_exist( $type ) ) {
        $param['name'] = $type;
    }

    $achievement_types = get_posts($param);

    $achievements_all = array();
    
    // Build achievement type array
    foreach ( $achievement_types as $achievement_type){
        $args = array(
            'posts_per_page'   => -1, // unlimited achievements
            'offset'           => 0,  // start from first row
            'post_type'        => sanitize_title( $achievement_type->post_name ), // Filter only achievement type posts from title
            'post__not_in'        => badgeos_get_user_skipped_achievements(), // excluding skipped achievements of user
            'post_status'        => 'publish',
            'suppress_filters' => false,
            'achievement_relationsihp' => 'any',
            'orderby' => ' menu_order',
            'order' => 'ASC',
        );

        $result = get_posts( $args );
        foreach($result as $res){
            $achievements_all[] = $res->ID;
        }
    }

    // Build an array of skipped achievements
    $user_achievements = badgeos_get_user_achievements();
    foreach($user_achievements as $user_achievement){
        if(($key = array_search($user_achievement->ID, $achievements_all)) !== false) {
            unset($achievements_all[$key]);
        }
    }

    foreach($achievements_all as $k => $id){

        //check skipped achievements by current logged in user
        $post_data = array(
            'author' => get_current_user_id(),
            'post_type'        => 'submission',
            'meta_query' => array(
                'relation' => 'AND', //Optional, defaults to AND
                array(
                    'key' => '_badgeos_submission_achievement_id',
                    'value' => array ($id),
                    'compare' => 'IN'
                )
            ),
            'fields'=>'ids'
        );

        $value = get_posts( $post_data );

        if(!empty($value)) {
            foreach ($value as $submission_id) {
                $status = get_post_meta($submission_id, '_badgeos_submission_status', true);
                $achievement_id = get_post_meta($submission_id, '_badgeos_submission_achievement_id', true);
                if ($achievement_id == $id) {
                    if ($status == 'denied') {
                        unset($achievements_all[$k]);
                    }
                }
            }
        }

        //Check completed step with all achievement type option based on specific achievement type
        $compeleted_step = check_all_completed_step_achievements_for_achievement_type($id);
        if($compeleted_step){
            unset($achievements_all[$k]);
        }

        //Remove current post achievement id, remining achievements display under suggested achievements list
        if($current_post_id == $id){
            unset($achievements_all[$k]);
        }
    }

    // Return result
    return (array) $achievements_all;
}

/**
 * Get a user's badgeos suggested ranks
 *
 * @since  1.0.0
 * @return array       An array of all the rank objects that matched our parameters, or empty if none
 */
function badgeos_get_suggested_ranks(){

    global $posts;
    
    $type = null;
    $current_post_id = null;
    foreach ($posts as $post) {
        $current_post_id = $post->ID;
        $type = $post->post_type;
    }
    
    $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    // Fetching rank types
    $param = array(
        'posts_per_page'   => -1, // All ranks
        'offset'           => 0,  // Start from first rank
        'post_type'=> trim( $settings['ranks_main_post_type'] ), // set post type as rank to filter only ranks
        'orderby' => 'menu_order',
        'order' => 'ASC',
    );

    if ( badgeos_rank_type_exist( $type ) ) {
        $param['name'] = $type;
    }

    $rank_types = get_posts($param);

    $ranks_all = array();
    
    // Build rank type array
    foreach ( $rank_types as $rank_type){
        $args = array(
            'posts_per_page'   => -1, // unlimited ranks
            'offset'           => 0,  // start from first row
            'post_type'        => sanitize_title( $rank_type->post_name ), // Filter only rank type posts from title
            'post__not_in'        => badgeos_get_user_skipped_ranks(), // excluding skipped ranks of user
            'post_status'        => 'publish',
            'suppress_filters' => false,
            'achievement_relationsihp' => 'any',
            'orderby' => ' menu_order',
            'order' => 'ASC',
        );

        $result = get_posts( $args );
        foreach($result as $res){
            $ranks_all[] = $res->ID;
        }
    }

    // Build an array of skipped achievements
    $user_ranks = badgeos_get_user_ranks( array() );
    foreach($user_ranks as $user_rank){
        if( ( $key = array_search( $user_rank->rank_id, $ranks_all ) ) !== false ) {
            unset( $ranks_all[$key] );
        }
    }

    foreach($ranks_all as $k => $id){
        //Remove current post achievement id, remining achievements display under suggested achievements list
        if($current_post_id == $id){
            unset($ranks_all[$k]);
        }
    }

    // Return result
    return (array) $ranks_all;
}

/**
 * Checking Completed step achievement types for all achievements
 *
 * @since  1.0.0
 *
 * @param null $post_id
 * @return bool
 */
function check_all_completed_step_achievements_for_achievement_type($post_id = NULL){

    global $wpdb;
    $step_ids = $wpdb->get_results( $wpdb->prepare( "SELECT p2p_from as step FROM $wpdb->p2p WHERE p2p_to = %d", $post_id ) );

    //Check this achievement type is trigger or submission
    $trigger = get_post_meta($post_id, '_badgeos_earned_by', true);

    //Get trigger type for completed steps
    $triggers = badgeos_get_activity_triggers();
    $types = array();
    foreach($triggers as $key => $value){
        array_push($types , $key);
    }

    if($trigger == 'triggers'){
        //Check trigger type and achievement type
        $all_steps_complete = array();
        foreach($step_ids as $res) {
            $trigger_type = get_post_meta($res->step , '_badgeos_trigger_type', true);
            $achievement_post_type = get_post_meta($res->step , '_badgeos_achievement_type', true);
            if($achievement_post_type && !empty($types)){
                if(in_array($trigger_type,$types)) {
                    if (badgeos_is_completed_achievement_types($achievement_post_type)) {
                        array_push($all_steps_complete, true);
                    } else {
                        array_push($all_steps_complete, false);
                    }
                }
            }
        }

        if(!in_array(false,$all_steps_complete)){
            return true;
        }else{
            return false;
        }
    }
    return false;
}


/**
 * Allow users to skip achievements
 *
 * @since  1.0.0
 */
function suggested_achievements_skip_ajax(){

    global $post;

    $achievement_id = isset($_REQUEST['achievement_id'])?$_REQUEST['achievement_id']:false;

    // Validate achievement
    if($achievement_id){

        // Getting skipped achievements by user
        $skipped_achievements = badgeos_get_user_skipped_achievements();

        // Update skipped achievement list. Ignore if already skipped
        if(!empty($skipped_achievements)){
            if(!in_array($achievement_id,$skipped_achievements)){
                array_push($skipped_achievements,$achievement_id);
            }
        }else{
            $skipped_achievements = array($achievement_id);
        }

        // Adding skipped achievements for user
        update_user_meta( absint(get_current_user_id() ), '_badgeos_skipped_achievements', $skipped_achievements);

        $message = __( 'Achievement skipped successfully', 'badgeos-suggested-achievements' );

        // Redirecting user page based on achievements
        $post = get_post( absint( $achievement_id ));
        $next= get_adjacent_post( false, '', false );
        if($next){
            $redirect_url = get_post_permalink($next->ID);
        }else{
            $redirect_url = home_url();
        }

    }else{
        $message = __( 'Achievement not available', 'badgeos-suggested-achievements' );
    }

    // Response array
    $response = array(
        'redirect_url'=> $redirect_url,
        'message'=> $message
    );

    // Send back a successful response
    wp_send_json_success( $response );
}
add_action( 'wp_ajax_suggested_achievements_skip_ajax', 'suggested_achievements_skip_ajax' );

/**
 * Allow users to skip ranks
 *
 * @since  1.0.0
 */
function suggested_ranks_skip_ajax_callback( ) {

    global $post;

    $rank_id = isset( $_REQUEST[ 'rank_id' ] ) ? $_REQUEST[ 'rank_id' ] : false;

    // Validate rank
    if( $rank_id ) {

        // Getting skipped achievements by user
        $skipped_ranks = badgeos_get_user_skipped_ranks();

        // Update skipped achievement list. Ignore if already skipped
        if( ! empty( $skipped_ranks ) ) {
            if( ! in_array( $rank_id, $skipped_ranks ) ) {
                array_push( $skipped_ranks, $rank_id );
            }
        } else {
            $skipped_ranks = array( $rank_id );
        }

        // Adding skipped achievements for user
        update_user_meta( absint( get_current_user_id() ), '_badgeos_skipped_ranks', $skipped_ranks );

        $message = __( 'Rank skipped successfully', 'badgeos-suggested-achievements' );

        // Redirecting user page based on achievements
        $post = get_post( absint( $rank_id ));
        $next= get_adjacent_post( false, '', false );
        if( $next ) {
            $redirect_url = get_post_permalink( $next->ID );
        } else {
            $redirect_url = home_url();
        }

    } else {
        $message = __( 'Rank not available', 'badgeos-suggested-achievements' );
    }

    // Response array
    $response = array(
        'redirect_url'=> $redirect_url,
        'message'=> $message
    );

    // Send back a successful response
    wp_send_json_success( $response );
}
add_action( 'wp_ajax_suggested_ranks_skip_ajax', 'suggested_ranks_skip_ajax_callback' );

/**
 * Allow users to skip achievements
 *
 * @since  1.0.0
 */
function suggested_achievements_unskip_ajax(){

    global $post;

    $achievement_id = isset($_REQUEST['achievement_id'])?$_REQUEST['achievement_id']:false;

    // Validate achievement
    if($achievement_id){

        // Getting skipped achievements by user
        $skipped_achievements = badgeos_get_user_skipped_achievements();

        // Update skipped achievement list. Ignore if already skipped
        if(!empty($skipped_achievements)){
            if( in_array($achievement_id,$skipped_achievements)){
                
                $key = array_search($achievement_id, $skipped_achievements);
                if ($key !== false) {
                    unset($skipped_achievements[$key]);
                }
            }
        }

        // Adding skipped achievements for user
        update_user_meta( absint(get_current_user_id() ), '_badgeos_skipped_achievements', $skipped_achievements);

        $message = __( 'Achievement is removed from skipped list successfully', 'badgeos-suggested-achievements' );

        // Redirecting user page based on achievements
        $post = get_post( absint( $achievement_id ));
        $next= get_adjacent_post( false, '', false );
        if($next){
            $redirect_url = get_post_permalink($next->ID);
        }else{
            $redirect_url = home_url();
        }

    }else{
        $message = __( 'Achievement not available', 'badgeos-suggested-achievements' );
    }

    // Response array
    $response = array(
        'redirect_url'=> $redirect_url,
        'message'=> $message
    );

    // Send back a successful response
    wp_send_json_success( $response );
}
add_action( 'wp_ajax_suggested_achievements_unskip_ajax', 'suggested_achievements_unskip_ajax' );


/**
 * Allow users to skip ranks
 *
 * @since  1.0.0
 */
function suggested_ranks_unskip_ajax_callback(){

    global $post;

    $rank_id = isset($_REQUEST['rank_id'])?$_REQUEST['rank_id']:false;

    // Validate achievement
    if( $rank_id ){

        // Getting skipped achievements by user
        $skipped_ranks = badgeos_get_user_skipped_ranks();

        // Update skipped achievement list. Ignore if already skipped
        if( ! empty( $skipped_ranks ) ) {
            if( in_array( $rank_id, $skipped_ranks ) ) {
                
                $key = array_search( $rank_id, $skipped_ranks );
                if ($key !== false) {
                    unset( $skipped_ranks[ $key ] );
                }
            }
        }

        // Adding skipped achievements for user
        update_user_meta( absint( get_current_user_id() ), '_badgeos_skipped_ranks', $skipped_ranks );

        $message = __( 'Rank is removed from skipped list successfully', 'badgeos-suggested-achievements' );

        // Redirecting user page based on achievements
        $post = get_post( absint( $rank_id ));
        $next= get_adjacent_post( false, '', false );
        if($next){
            $redirect_url = get_post_permalink($next->ID);
        }else{
            $redirect_url = home_url();
        }

    }else{
        $message = __( 'Rank is not available', 'badgeos-suggested-achievements' );
    }

    // Response array
    $response = array(
        'redirect_url'=> $redirect_url,
        'message'=> $message
    );

    // Send back a successful response
    wp_send_json_success( $response );
}
add_action( 'wp_ajax_suggested_ranks_unskip_ajax', 'suggested_ranks_unskip_ajax_callback' );


/**
 * Get skipped ranks of the user
 *
 * @since  1.0.0
 */
function badgeos_get_user_skipped_ranks( $user_id = 0 ){

    if(empty($user_id))
        $user_id = get_current_user_id();

    $skipped_items = get_user_meta( absint( $user_id ), '_badgeos_skipped_ranks', true );

    return $skipped_items;
}

/**
 * Get skipped achievements of the user
 *
 * @since  1.0.0
 */
function badgeos_get_user_skipped_achievements($user_id=0 ){

    if(empty($user_id))
        $user_id = get_current_user_id();

    $skipped_items = get_user_meta( absint( $user_id ), '_badgeos_skipped_achievements', true );

    return $skipped_items;
}

/**
 * Getting Completed achievement types for a logged in User
 *
 * @since  1.0.0
 *
 * @param null $post_type
 * @return bool
 */
function badgeos_is_completed_achievement_types($post_type = NULL){

    // Arguments for fetching achievements
    $args = array(
        'posts_per_page'   => -1, // unlimited achievements
        'offset'           => 0,  // start from first row
        'post_type'        => $post_type, // Filter only achievement type posts from title
        'post_status'        => 'publish',
        'suppress_filters' => false,
        'achievement_relationsihp' => 'any',
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'fields' => 'ids',
    );

    $all_achievements = get_posts( $args );

    if(empty($all_achievements))
        return false;

    $user_achievements = badgeos_get_user_achievements();
    $earned_ids = array();

    foreach ( $user_achievements as $user_achievement )
        $earned_ids[] = $user_achievement->ID;

    foreach($all_achievements as $all_achievement){

        if((!in_array($all_achievement, $earned_ids))) {
            return false;
        }
    }
    return true;
}

/**
 * Check if the achievement type exist
 *
 * @param int|string $name Post ID or slug fo one achievement post types
 * @since  1.0.1
 * @return bool       Return true if achievement type exist, otherwise false
 */
function badgeos_achievement_type_exist( $name = 0 ) {
	$args = array(
		'posts_per_page' => 1,
		'post_type'      => 'achievement-type',
	);

	if ( is_numeric( $name ) && ( $name = absint( $name ) ) ) {
		$args['p'] = $name;
	} else {
		// Try it as slug
		$args['name'] = $name;
	}

	$achievement_type = get_posts( $args );

	return ! empty( $achievement_type );
}

/**
 * Check if the rank type exist
 *
 * @param int|string $name Post ID or slug fo one rank post types
 * @since  1.0.1
 * @return bool       Return true if rank type exist, otherwise false
 */
function badgeos_rank_type_exist( $name = 0 ) {
    
    $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    
    $args = array(
		'posts_per_page' => 1,
		'post_type'      => trim( $settings['ranks_main_post_type'] ),
	);

	if ( is_numeric( $name ) && ( $name = absint( $name ) ) ) {
		$args['p'] = $name;
	} else {
		// Try it as slug
		$args['name'] = $name;
	}

	$rank_type = get_posts( $args );

	return ! empty( $rank_type );
}
