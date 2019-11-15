(function( $ ) { 'use strict';

    $('.bos_suggested_achs_link').on( 'hover', function() {
        $(this).next().css( "opacity", 1 );
    });

    
    $('.bos_suggested_rank_skip_link').on( 'click', function() {
        
        var rank_id = $(this).data('index');

        // If no code provided, bail
        if ( !rank_id ) {
            return;
        }

        $('.bos_suggested_rank_ajax_preloader_widget').css( 'display', 'block' );
        // Run our ajax request

        $.ajax( {
            url : BosSuggestedAcsVars.ajax_url,
            data : {
                'action' : 'suggested_ranks_skip_ajax',
                'rank_id' : rank_id
            },
            dataType : 'json',
            async : false,
            success : function( response ) {
                $( '.bos_suggested_rank_ajax_preloader_widget' ).css( 'display', 'none' );
                $( '.bos_suggested_rank_msg').css( 'display', 'block' ).html( response.data.message );
                if( response.data.redirect_url ){
                    window.location.reload();
                }
            },
            error : function() {
                $( '.bos_suggested_rank_ajax_preloader_widget' ).css( 'display', 'none' );
                $( '.bos_suggested_rank_msg' ).css( 'display', 'block' ).html( 'Your request can not be processed at this moment, please contact your site administrator' );
            }
        } );
    });

    $('.bos_suggested_achs_skip_link').on( 'click', function() {
        var achievement_id = $(this).data('index');
        // If no code provided, bail
        if ( !achievement_id ) {
            return;
        }
        $('.bos_suggested_achs_ajax_preloader_widget').css( 'display', 'block' );
        // Run our ajax request
        $.ajax( {
            url : BosSuggestedAcsVars.ajax_url,
            data : {
                'action' : 'suggested_achievements_skip_ajax',
                'achievement_id' : achievement_id
            },
            dataType : 'json',
            async : false,
            success : function( response ) {
                $('.bos_suggested_achs_ajax_preloader_widget').css( 'display', 'none' );
                $('.bos_suggested_achs_msg').css( 'display', 'block' ).html( response.data.message );
                if(response.data.redirect_url){
                    window.location.reload();// = response.data.redirect_url;
                }
            },
            error : function() {
                $('.bos_suggested_achs_ajax_preloader_widget').css( 'display', 'none' );
                $('.bos_suggested_achs_msg').css( 'display', 'block' ).html( 'Your request can not be processed at this moment, please contact your site administrator' );
            }
        } );
    });

    $('.bos_suggested_ranks_unskip_link').on( 'click', function() { 
        
        var rank_id = $(this).data('index');
        // If no code provided, bail
        if ( !rank_id ) {
            return;
        }
        
        $('.bos_suggested_ranks_ajax_preloader').css( 'display', 'block' );
        // Run our ajax request
        $.ajax( {
            url : BosSuggestedAcsVars.ajax_url,
            data : {
                'action' : 'suggested_ranks_unskip_ajax',
                'rank_id' : rank_id
            },
            dataType : 'json',
            async : false,
            success : function( response ) {
                $('.bos_suggested_ranks_ajax_preloader').css( 'display', 'none' );
                $('.bos_suggested_rank_msg').css( 'display', 'block' ).html( response.data.message );
                window.location.reload();
            },
            error : function() {
                $('.bos_suggested_ranks_ajax_preloader').css( 'display', 'none' );
                $('.bos_suggested_rank_msg').css( 'display', 'block' ).html( 'Your request can not be processed at this moment, please contact your site administrator' );
            }
        } );
    });

    $('.bos_suggested_achs_unskip_link').on( 'click', function() { 
        
        var achievement_id = $(this).data('index');
        // If no code provided, bail
        if ( !achievement_id ) {
            return;
        }
        
        $('.bos_suggested_achs_ajax_preloader').css( 'display', 'block' );
        // Run our ajax request
        $.ajax( {
            url : BosSuggestedAcsVars.ajax_url,
            data : {
                'action' : 'suggested_achievements_unskip_ajax',
                'achievement_id' : achievement_id
            },
            dataType : 'json',
            async : false,
            success : function( response ) {
                $('.bos_suggested_achs_ajax_preloader').css( 'display', 'none' );
                $('.bos_suggested_achs_msg').css( 'display', 'block' ).html( response.data.message );
                window.location.reload();
            },
            error : function() {
                $('.bos_suggested_achs_ajax_preloader').css( 'display', 'none' );
                $('.bos_suggested_achs_msg').css( 'display', 'block' ).html( 'Your request can not be processed at this moment, please contact your site administrator' );
            }
        } );
    });
    
})( jQuery );