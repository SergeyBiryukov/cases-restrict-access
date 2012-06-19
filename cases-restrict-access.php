<?php
/*
Plugin Name: Cases. Kernel. Restrict Access
Plugin URI: http://itau.ru/
Description: Настройка прав доступа для ACM Cases.
Author: Sergey Biryukov
Author URI: http://profiles.wordpress.org/sergeybiryukov/
Version: 0.2.1
*/ 

function cases_get_case_members( $case_id ) {
	$case_members = array();

	foreach ( array( 'initiator', 'participant', 'responsible' ) as $role ) {
		$member_id = get_post_meta( $case_id, $role, true );
		if ( !empty( $member_id ) )
			$case_members = array_merge( $case_members, explode( ',', $member_id ) );
	}

	foreach ( $case_members as $key => $member_id )
		$case_members[ $key ] = get_post_meta( $member_id, 'email', true );

	return $case_members;
}

function cases_is_case_member( $user_id, $case_id ) {
	if ( user_can( $user_id, 'manage_options' ) )
		return true;

	$case = get_post( $case_id );
	if ( $user_id == $case->post_author )
		return true;

	$user = get_userdata( $user_id );
	if ( ! $user )
		return false;

	return in_array( $user->user_email, cases_get_case_members( $case_id ) );
}

function cases_map_meta_cap( $caps, $cap, $user_id, $args ) {

	switch ( $cap ) {
		case 'edit_post' :
		case 'read_post' :
		case 'delete_post' :
			$post_id = ( ! empty( $args ) ) ? array_shift( $args ) : 0;
			if ( empty( $post_id ) )
				break;

			$post = get_post( $post_id );
			if ( 'cases' == $post->post_type && ! cases_is_case_member( $user_id, $post_id ) )
				$caps[] = 'do_not_allow';
			break;
		case 'edit_posts' :
			$post_id = ( ! empty( $_GET['p'] ) ) ? (int) $_GET['p'] : 0;
			if ( empty( $post_id ) )
				break;

			$post = get_post( $post_id );
			if ( 'cases' == $post->post_type && ! cases_is_case_member( $user_id, $post_id ) )
				$caps[] = 'do_not_allow';
			break;
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'cases_map_meta_cap', 10, 4 );

function cases_posts_join( $posts_join ) {
	global $wpdb;

	if ( false === strpos( $posts_join, $wpdb->postmeta ) )
		$posts_join .= " LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )";
	
	return $posts_join;
}

function cases_posts_where( $posts_where ) {
	global $wpdb;

	$user = get_userdata( get_current_user_id() );
	$member_id = get_person_id_by_email( $user->user_email );

	$posts_where .= $wpdb->prepare(
		" AND ( post_author = %d OR ( meta_key IN ( 'initiator', 'participant', 'responsible' ) AND meta_value REGEXP( '^([0-9,]+,)*%d(,[0-9,]+)*$' ) ) )",
		$user->ID, $member_id
	);

	return $posts_where;
}

function cases_posts_groupby( $posts_groupby ) {
	global $wpdb;

	if ( empty( $posts_groupby ) )
		$posts_groupby = "$wpdb->posts.ID";
	
	return $posts_groupby;
}

function cases_restrict_queries( $wp_query ) {
	if ( current_user_can( 'manage_options' ) )
		return;

	if ( 'cases' != $wp_query->get( 'post_type' ) || $wp_query->is_single() )
		return;

	add_filter( 'posts_join', 'cases_posts_join' );
	add_filter( 'posts_where', 'cases_posts_where' );
	add_filter( 'posts_groupby', 'cases_posts_groupby' );
}
add_action( 'pre_get_posts', 'cases_restrict_queries' );

function cases_restrict_case_view() {
	global $post;

	if ( is_single() && 'cases' == $post->post_type && ! cases_is_case_member( get_current_user_id(), $post->ID ) )
		wp_die( __( 'Sorry, you do not have the right to access this post.' ) );
}
add_action( 'wp', 'cases_restrict_case_view' );

function cases_disable_comments( $comment_post_ID ) {
	$post = get_post( $comment_post_ID );

	if ( 'cases' == $post->post_type && ! cases_is_case_member( get_current_user_id(), $post->ID ) )
		wp_die( __( 'Sorry, you do not have the right to access this post.' ) );
}
add_action( 'pre_comment_on_post', 'cases_disable_comments' );

function cases_hide_view_link_in_posts_list( $actions, $post ) {
	if ( 'cases' == $post->post_type && ! cases_is_case_member( get_current_user_id(), $post->ID ) )
		unset( $actions['view'] );

	return $actions;
}
add_filter( 'page_row_actions', 'cases_hide_view_link_in_posts_list', 10, 2 );
?>