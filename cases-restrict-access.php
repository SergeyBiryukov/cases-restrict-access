<?php
/*
Plugin Name: Cases. Kernel. Restrict Access
Plugin URI: http://itau.ru/
Description: Настройка прав доступа для ACM Cases.
Author: Sergey Biryukov
Author URI: http://profiles.wordpress.org/sergeybiryukov/
Version: 0.1.1
*/ 

function cases_get_person_email_by_id( $person_id ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare(
		"SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'email' AND post_id = %d", $person_id
	) );
}

function cases_get_case_members( $case_id ) {
	$case_members = array();

	foreach ( array( 'initiator', 'participant', 'responsible' ) as $role ) {
		$member = get_post_meta( $case_id, $role, true );
		if ( !empty( $member ) )
			$case_members[ $role ] = $member;
	}

	foreach ( $case_members as $role => $member_id )
		$case_members[ $role ] = cases_get_person_email_by_id( $member_id );

	return $case_members;
}

function cases_is_case_member( $user_id, $case_id ) {
	if ( user_can( $user_id, 'manage_options' ) )
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