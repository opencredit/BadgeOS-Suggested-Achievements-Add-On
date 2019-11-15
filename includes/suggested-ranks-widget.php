<?php

if ( ! defined( 'ABSPATH' ) ) exit;

//widget displays suggested ranks for the logged in user
class suggested_ranks_widget extends WP_Widget {

	//process the new widget
	public function __construct() {

		$widget_ops = array(
			'classname' => 'suggested_ranks_class',
			'description' => __( 'Displays suggested ranks for logged in user', 'badgeos-suggested-achievements' )
		);

		parent::__construct( 'suggested_ranks_widget', __( 'BadgeOS Suggested Ranks', 'badgeos-suggested-achievements' ), $widget_ops );
	}

	//build the widget settings form
	public function form( $instance ) {
		$defaults = array( 'title' => __( 'Suggested Ranks', 'badgeos-suggested-achievements' ), 'number' => '10', 'point_total' => '', 'set_ranks' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$number = $instance['number'];
		$point_total = $instance['point_total'];
		if( ! isset( $point_total ) || !is_array( $point_total ) )
			$point_total = array();
		$set_ranks = ( isset( $instance['set_ranks'] ) ) ? (array) $instance['set_ranks'] : array();
		?>
        <p><label><?php _e( 'Title', 'badgeos-suggested-achievements' ); ?>: <input class="widefat" 
                                                                                    name="<?php echo $this->get_field_name( 'title' ); ?>"
                                                                                    type="text"
                                                                                    value="<?php echo esc_attr( $title ); ?>"/></label>
        </p>
        <p><label><?php _e( 'Number to display (0 = all)', 'badgeos-suggested-achievements' ); ?>: <input
                        class="widefat" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text"
                        value="<?php echo absint( $number ); ?>"/></label></p>

		<p><?php _e( 'Display only the following Point Types:', 'badgeos-suggested-achievements' ); ?></p>
		<?php
			$credit_types = badgeos_get_point_types();
			if ( is_array( $credit_types ) && ! empty( $credit_types ) ) {
				foreach ( $credit_types as $credit_type ) {
		?>
		<p>
			<label>
				<input type="checkbox" value="<?php echo $credit_type->ID; ?>" id="<?php echo $this->get_field_name( 'point_total' ); ?>" name="<?php echo $this->get_field_name( 'point_total' ); ?>[]" <?php echo in_array( $credit_type->ID, $point_total)?'checked':''; ?> /> <?php echo $credit_type->post_title; ?>
            </label>
		</p>
		<?php
				}
			}
		?>
        <p><?php _e( 'Display only the following Rank Types:', 'badgeos-suggested-achievements' ); ?><br/>
			<?php
			//get all registered rank types
			$rank_types = badgeos_get_rank_types_slugs_detailed();
			if ( !empty( $rank_types ) ) {
				// Loop rank types current user has earned
				
				$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
				
				//loop through all registered ranks
				foreach ( $rank_types as $rank_key => $rank_type ) {

					//hide the step CPT
					if ( $rank_key == trim( $settings['ranks_step_post_type'] ) )
						continue;

					//if rank displaying exists in the saved array it is enabled for display
					$checked = checked( in_array( $rank_key, $set_ranks ), true, false );

					echo '<label for="' . $this->get_field_name( 'set_ranks' ) . '_' . esc_attr( $rank_key ) . '">'
						. '<input type="checkbox" name="' . $this->get_field_name( 'set_ranks' ) . '[]" id="' . $this->get_field_name( 'set_ranks' ) . '_' . esc_attr( $rank_key ) . '" value="' . esc_attr( $rank_key ) . '" ' . $checked . ' />'
						. ' ' . esc_html( ucfirst( $rank_type['plural_name'] ) )
						. '</label><br />';

				}
			}
			
			?>
        </p>
		<?php
	}

	//save and sanitize the widget settings
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		$instance['point_total'] = ( ! empty( $new_instance['point_total'] ) ) ? $new_instance['point_total'] : '';
		$instance['set_ranks'] = array_map( 'sanitize_text_field', $new_instance['set_ranks'] );

		return $instance;
	}

	//display the widget
	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'] );
		
		$point_total = $instance['point_total'];
		if( ! isset( $point_total ) || !is_array( $point_total ) )
			$point_total = array();

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		};

		//user must be logged in to view earned badges and points
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			//display user's points if widget option is enabled
			$credit_types = badgeos_get_point_types();
			if ( is_array( $credit_types ) && ! empty( $credit_types ) ) {
				foreach ( $credit_types as $credit_type ) {
					if( in_array( $credit_type->ID, $point_total ) ) {
						$earned_credits = badgeos_get_points_by_type( $credit_type->ID, $user_id );
						echo '<p class="badgeos-total-points">' . sprintf( __( 'My Total %s: %s', 'badgeos' ), $credit_type->post_title, '<strong>'. number_format( $earned_credits ) . '</strong>' ) . '</p>';
					}
				}
			}
			if ( $instance['point_total'] == 'on' )
				echo '<p class="badgeos-total-points">' . sprintf( __( 'My Total Points: %s', 'badgeos' ), '<strong>'. number_format( badgeos_get_users_points() ) . '</strong>' ) . '</p>';

			$ranks = badgeos_get_suggested_ranks();
			//load widget setting for rank types to display
			$set_ranks = ( isset( $instance['set_ranks'] ) ) ? $instance['set_ranks'] : '';
				
			if( is_array( $set_ranks ) && count( $set_ranks ) > 0 ) {
				if ( is_array( $ranks ) && ! empty( $ranks ) ) {
					$number_to_show = absint( $instance['number'] );
					$thecount = 0;

					wp_enqueue_script( 'badgeos-achievements' );
					wp_enqueue_style( 'badgeos-widget' );
				
					echo '<div class="bos_suggested_rank_msg" style="display:none"></div><div class="bos_suggested_rank_ajax_preloader_widget" style="display:none;text-align: center;"><img src="'.$GLOBALS['badgeos_reports_addon']->directory_url.'/css/ajax-loader.gif"></div><ul class="widget-ranks-listing">';
					foreach ( $ranks as $rank ) {
						
						//verify rank type is set to display in the widget settings
						//if $set_ranks is not an array it means nothing is set so show all ranks
						if ( ! is_array( $set_ranks ) || in_array( get_post_type($rank), $set_ranks ) ) {

							//exclude step CPT entries from displaying in the widget
							$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
							if ( get_post_type( $rank ) != trim( $settings['ranks_step_post_type'] ) ) {

								$permalink = get_permalink( $rank );
								$title = get_the_title( $rank );
								$img = badgeos_get_rank_image( $rank );
								$thumb = $img ? '<a class="badgeos-item-thumb" href="' . esc_url( $permalink ) . '">' . $img . '</a>' : '';
								$class = 'widget-badgeos-item-title';
								$item_class = $thumb ? ' has-thumb' : '';

								echo '<li id="widget-ranks-listing-item-' . absint( $rank ) . '" class="widget-ranks-listing-item' . esc_attr( $item_class ) . '">';
								echo $thumb;

								echo '<div class="bos_suggested_achs_container">
										<a data-index="'.$rank.'" class="bos_suggested_achs_link widget-badgeos-item-title ' . esc_attr( $class ) . '" href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a>
										<div class="overlay">
											<a href="javascript:;" data-index="'.$rank.'" class="bos_suggested_rank_skip_link icon" style="text-decoration: none;" title="'.__( 'Skip Rank', 'badgeos-suggested-achievements' ).'">
												&nbsp;
											</a>
										</div>
									</div>';
								echo '</li>';
								$thecount++;
								if ( $thecount == $number_to_show && $number_to_show != 0 )
									break;
							}
						}
					}
					echo '</ul><!-- widget-ranks-listing -->';
				
				} else {
					echo '<div class="bos_suggested_rank_msg">'.__( 'No ranks available to display.', 'badgeos' ).'</div>';
				}
			} else {
				echo '<div class="bos_suggested_rank_msg">'.__( 'No ranks type selected to display.', 'badgeos' ).'</div>';
			}
		} else {

			//user is not logged in so display a message
			_e( 'You must be logged in to view suggested ranks', 'badgeos' );

		}

		echo $args['after_widget'];
	}

}

