<?php
/*
Plugin Name: Simple user management for Wordpress MU
Description: Allows site administrators to manage which blogs belong to which users. The normal admin interface is bloated and (for me at least) very rarely works.
Version: 1.1
Author: Chris Taylor
Author URI: http://www.stillbreathing.co.uk
Plugin URI: http://www.stillbreathing.co.uk/wordpress/simple-user-admin/
Date: 2009-13-08
*/

// when the admin menu is built
add_action('admin_menu', 'simple_user_management_add_admin');
add_action('admin_head', 'simple_user_management_show_css');

// add the admin menu button
function simple_user_management_add_admin() {
	add_submenu_page('wpmu-admin.php', 'Simple User Admin', 'Simple User Admin', 10, 'simple_user_management', 'simple_user_management');
}

// show the CSS
function simple_user_management_show_css()
{
	echo '
	<style type="text/css">
	fieldset {
		background: #F1F1F1;
		border: 1px solid #CCC;
		padding: 1em;
	}
	</style>
	';
}

// show the admin screen
function simple_user_management()
{

	echo '
	<div class="wrap">
	<h2>' . __("Simple user management") . '</h2>
	';

	// if no action is being performed
	if (@$_POST["userquery"] == "" && @$_POST["blogquery"] == "" && @$_GET["user"] == "" && @$_GET["blog"] == "")
	{
		simple_user_management_show_search_forms();
	} else {
		// if searching users
		if (@$_POST["userquery"] != "" && @$_GET["user"] == "" && @$_GET["blog"] == "")
		{
			echo '
			<h3>' . ("Searching for:") . ' ' . $_POST["userquery"] . '</h3>
			';
			
			// if users have been found
			if ($results = simple_user_management_search_users($_POST["userquery"]))
			{
				// show the table of users
				simple_user_management_show_user_table($results);
				
			} else {
				echo '
				<p>' . __("No results found. Please search again.") . '</p>
				';
				
				// show the search forms
				simple_user_management_show_search_forms();
			}
		}
		// if searching blogs
		if (@$_POST["blogquery"] != "" && @$_GET["user"] == "" && @$_GET["blog"] == "")
		{
			echo '
			<h3>' . ("Searching for:") . ' ' . $_POST["blogquery"] . '</h3>
			';
			
			// if blogs have been found
			if ($results = simple_user_management_search_blogs($_POST["blogquery"]))
			{
				// show the table of blogs
				simple_user_management_show_blog_table($results);
				
			} else {
			
				echo '
				<p>' . __("No blogs found for your search. Please search again.") . '</p>
				';
				
				// show the search forms
				simple_user_management_show_search_forms();
			}
		}
		// if managing a user
		if (@$_GET["user"] != "")
		{
			// get the user
			$user = simple_user_management_get_user((int)$_GET["user"]);
			
			// show the user name
			echo '
			<h3>' . __("Managing:") . ' ' . $user->display_name . '</h3>
			';
			
			// if adding this user to a blog
			if (@$_POST["blog"] != "" && @$_POST["role"] != "")
			{
				if ( simple_user_management_add_user_to_blog((int)$_GET["user"], (int)$_POST["blog"], $_POST["role"]) )
				{
					simple_user_management_show_message( __( "The user has been added to the specified blog") . ' (User ID = ' . $_GET["user"] . ', Blog ID = ' . $_POST["blog"] . ', Role = ' . $_POST["role"] . ')' );
					
				} else {
					
					simple_user_management_show_message( __( "Sorry, the user could not be added to the specified blog") . ' (User ID = ' . $_GET["user"] . ', Blog ID = ' . $_POST["blog"] . ', Role = ' . $_POST["role"] . ')', 'error' );
					
				}
			}
			
			// if updating all the users roles
			if (@$_POST["blogids"] != "")
			{
				$blogids = explode(",", $_POST["blogids"]);
				foreach($blogids as $blogid)
				{
					if (@$_POST["role_" . $blogid] != "")
					{
						simple_user_management_add_user_to_blog((int)$_GET["user"], (int)$blogid, $_POST["role_" . $blogid]);
					} else {
						simple_user_management_remove_user_from_blog((int)$_GET["user"], (int)$blogid);
					}
				}
				simple_user_management_show_message( __( "The user blog roles have been updated") );
			}
			
			// if the user belongs to blogs
			if ($blogs = get_blogs_of_user((int)$_GET["user"]))
			{
				// show the table of user blogs
				simple_user_management_show_user_blogs_table($user, $blogs);
				
				// if a blog search has been entered
				if (@$_POST["blogquery"] != "")
				{
					// if blogs have been found
					if ($results = simple_user_management_search_blogs($_POST["blogquery"]))
					{
						echo '
						<h4 id="results">' . __("Choose the blog to add this user to, or search again below") . '</h4>
						';
					
						// show the add user to blog form
						simple_user_management_show_add_user_to_blog_form($results);
						
					} else {
					
						echo '
						<p>' . __("No blogs found for your search. Please search again.") . '</p>
						';
						
					}
				}
				
				// show the search blogs form
				echo '
				<h3>' . __("Search for a blog to add this user to") . '</h3>
				';
				print simple_user_management_get_search_blogs_form("&amp;user=" . $_GET["user"]);
				
			} else {
			
				echo '
				<p>' . __("This user does not belong to any blogs. Add them to a blog below.") . '</p>
				';
			}
		}
		// if managing a blog
		if (@$_GET["blog"] != "")
		{
			// get the user
			$blog = simple_user_management_get_blog((int)$_GET["blog"]);
			
			// show the blog name
			echo '
			<h3>' . __("Managing:") . ' ' . $blog->blogname . '</h3>
			';
			
			// if adding this user to a blog
			if (@$_POST["user"] != "" && @$_POST["role"] != "")
			{
				if ( simple_user_management_add_user_to_blog((int)$_POST["user"], (int)$_GET["blog"], $_POST["role"]) )
				{
					simple_user_management_show_message( __( "The user has been added to this blog") . ' (User ID = ' . $_POST["user"] . ', Blog ID = ' . $_GET["blog"] . ', Role = ' . $_POST["role"] . ')' );
					
				} else {
					simple_user_management_show_message( __( "Sorry, the user could not be added to this blog" ) . ' (User ID = ' . $_POST["user"] . ', Blog ID = ' . $_GET["blog"] . ', Role = ' . $_POST["role"] . ')', 'error' );
					
				}
			}
			
			// if updating all the users roles
			if (@$_POST["userids"] != "")
			{
				$userids = explode(",", $_POST["userids"]);
				foreach($userids as $userid)
				{
					if (@$_POST["role_" . $userid] != "")
					{
						simple_user_management_add_user_to_blog((int)$userid, (int)$_GET["blog"], $_POST["role_" . $userid]);
					} else {
						simple_user_management_remove_user_from_blog((int)$userid, (int)$_GET["blog"]);
					}
				}
				simple_user_management_show_message( __( "The user blog roles have been updated" ) );
			}
			
			// if the blog has users
			if ($users = get_users_of_blog((int)$_GET["blog"]))
			{
				// show the table of blog users
				simple_user_management_show_blog_users_table($_GET["blog"], $users);
				
				// if a blog search has been entered
				if (@$_POST["userquery"] != "")
				{
					// if users have been found
					if ($results = simple_user_management_search_users($_POST["userquery"]))
					{
						echo '
						<h4 id="results">' . __("Choose the user to add to this blog, or search again below") . '</h4>
						';
					
						// show the add user to blog form
						simple_user_management_show_add_user_to_blog_form($results);
						
					} else {
					
						echo '
						<p>' . __("No users found for your search. Please search again.") . '</p>
						';
						
					}
				}
				
				// show the search users form
				echo '
				<h3>' . __("Search for a user to add to this blog") . '</h3>
				';
				print simple_user_management_get_search_users_form("&amp;blog=" . $_GET["blog"]);
				
			} else {
			
				echo '
				<p>' . __("This blog does not have any users. Add a user to this blog below.") . '</p>
				';
			}
		}
	}
	
	echo '
	</div>
	';

}

// show a message
function simple_user_management_show_message( $message, $type="updated" )
{
	echo '
	<div id="message" class="' . $type . ' fade">
		<p><strong>' . $message . '</strong></p>
	</div>
	';
}

// add a user to a blog
function simple_user_management_add_user_to_blog($userid, $blogid, $role)
{
	return add_user_to_blog( $blogid, $userid, $role );
}

// remove a user from a blog
function simple_user_management_remove_user_from_blog($userid, $blogid)
{
	return remove_user_from_blog ( $userid, $blogid );
}

// get the role of a user
function simple_user_management_get_user_role($user, $blogid, $id)
{
	global $wpdb;
	$capabilities = $user->{$wpdb->base_prefix . $blogid . '_capabilities'};
	if (!$capabilities || !is_array($capabilities)) $capabilities = array();
	if ( !isset( $wp_roles ) )
		$wp_roles = new WP_Roles();
	$r = "";
	foreach ( $wp_roles->role_names as $role => $name )
	{
		$r .= '<option value="' . $role . '"';
		if ( ( $blogid == @$_POST["blog"] && $role == @$_POST["role"] ) || ( $user->ID == @$_POST["user"] && $role == @$_POST["role"] ) || $role == @$_POST["role_" . $id] || ( array_key_exists( $role, $capabilities ) && @$_POST["role"] == "" && @$_POST["role_" . $id] == "" ) )
		{
			$r .= ' selected="selected"';
		}
		$r .= '>' . ucfirst(__($role)) . '</option>';
	}
	$r .= '<option value="">' . __("Remove from blog") . '</option>
	';
	return $r;
}

// get the roles as a select list
function simple_user_management_get_roles()
{
	global $wpdb;
	if ( !isset( $wp_roles ) )
		$wp_roles = new WP_Roles();
	$r = "";
	foreach ( $wp_roles->role_names as $role => $name )
	{
		$r .= '<option value="' . $role . '">' . ucfirst( __( $role ) ) . '</option>';
	}
	return $r;
}

// get a blog
function simple_user_management_get_blog($id)
{
	return get_blog_details( $id, true );
}

// get a user
function simple_user_management_get_user($id)
{
	return get_userdata( $id );
}

// show the add user to blog form
function simple_user_management_show_add_user_to_blog_form($users)
{
	echo '
	<form action="wpmu-admin.php?page=simple_user_management&amp;blog=' . $_GET["blog"] . '" method="post">
	<fieldset>
	<p><label for="blog">Choose user:</label>
	<select name="user" id="user">
	';
	foreach ($users as $user)
	{
		echo '
		<option value="' . $user->id . '">' . $user->display_name . ' (' . $user->user_login . ', ' . $user->user_email . ')</option>
		';
	}
	echo '
	</select></p>
	<p><label for="role">Add user as:</label>
	<select name="role" id="role">
	' . simple_user_management_get_roles() . '
	</select>
	</p>
	<p><button type="submit" name="saveuserroles" class="button">' . __("Add user to blog") . '</button></p>
	</fieldset>
	</form>
	';
}

// show a table of user blogs
function simple_user_management_show_user_blogs_table($user, $results)
{
	echo '
	<form action="wpmu-admin.php?page=simple_user_management&amp;user=' . $_GET["user"] . '" method="post">
	<table class="widefat" cellspacing="0">
	<thead>
		<tr>
			<th>' . __("Blog ID") . '</th>
			<th>' . __("Blog name") . '</th>
			<th>' . __("Domain") . '</th>
			<th>' . __("Path") . '</th>
			<th>' . __("Role") . '</th>
		</tr>
	</thead>
	<tbody>
	';
	$blogids = "";
	foreach ($results as $blog)
	{
		$blogids .= $blog->userblog_id . ",";
		echo '
		<tr>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;blog=' . $blog->userblog_id . '">' . $blog->userblog_id . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;blog=' . $blog->userblog_id . '">' . stripslashes($blog->blogname ) . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;blog=' . $blog->userblog_id . '">' . $blog->domain . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;blog=' . $blog->userblog_id . '">' . $blog->path . '</a></td>
			<td>
			<select name="role_' . $blog->userblog_id . '">' . simple_user_management_get_user_role($user, $blog->userblog_id, $blog->userblog_id) . '</select>
			</td>
		</tr>
		';
	}
	echo '
	</tbody>
	</table>
	<p><button type="submit" name="saveuserroles" class="button">' . __("Save user roles") . '</button>
	<input type="hidden" name="blogids" value="' . trim($blogids, ",") . '" /></p>
	</form>
	';
}

// show a table of blog users
function simple_user_management_show_blog_users_table($blog, $results)
{
	echo '
	<form action="wpmu-admin.php?page=simple_user_management&amp;blog=' . $_GET["blog"] . '" method="post">
	<table class="widefat" cellspacing="0">
	<thead>
		<tr>
			<th>' . __("User ID") . '</th>
			<th>' . __("Username") . '</th>
			<th>' . __("Display name") . '</th>
			<th>' . __("Email") . '</th>
			<th>' . __("Role") . '</th>
		</tr>
	</thead>
	<tbody>
	';
	$blogids = "";
	foreach ($results as $user)
	{
		$user = simple_user_management_get_user( $user->user_id );
		$userids .= $user->ID . ",";
		echo '
		<tr>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;user=' . $user->ID . '">' . $user->ID . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;user=' . $user->ID . '">' . stripslashes($user->user_login ) . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;user=' . $user->ID . '">' . stripslashes( $user->display_name ) . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;user=' . $user->ID . '">' . $user->user_email . '</a></td>
			<td>
			<select name="role_' . $user->ID . '">' . simple_user_management_get_user_role($user, (int)$_GET["blog"], $user->ID) . '</select>
			</td>
		</tr>
		';
	}
	echo '
	</tbody>
	</table>
	<p><button type="submit" name="saveuserroles" class="button">' . __("Save user roles") . '</button>
	<input type="hidden" name="userids" value="' . trim($userids, ",") . '" /></p>
	</form>
	';
}

// show a table of users
function simple_user_management_show_user_table($users)
{
	echo '
	<table class="widefat" cellspacing="0">
	<thead>
		<tr>
			<th>' . __("User ID") . '</th>
			<th>' . __("Display name") . '</th>
			<th>' . __("User login") . '</th>
			<th>' . __("Email") . '</th>
		</tr>
	</thead>
	<tbody>
	';
	foreach ($users as $user)
	{
		echo '
		<tr>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;user=' . $user->id . '">' . $user->id . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;user=' . $user->id . '">' . stripslashes($user->display_name) . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;user=' . $user->id . '">' . $user->user_login . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;user=' . $user->id . '">' . $user->user_email . '</a></td>
		</tr>
		';
	}
	echo '
	</tbody>
	</table>
	';
}

// search blogs
function simple_user_management_search_blogs($query)
{
	global $wpdb;
	$sql = "select blog_id, domain, path
			from " . $wpdb->blogs . "
			where blog_id = " . $wpdb->escape((int)$query) . "
			or domain like '%" . $wpdb->escape($query) . "%'
			or path like '%" . $wpdb->escape($query) . "%';";
	return $wpdb->get_results($sql);
}

// show a table of blogs
function simple_user_management_show_blog_table($blogs)
{
	echo '
	<table class="widefat" cellspacing="0">
	<thead>
		<tr>
			<th>' . __("Blog ID") . '</th>
			<th>' . __("Domain") . '</th>
			<th>' . __("Path") . '</th>
			<th>' . __("Visit") . '</th>
		</tr>
	<thead>
	<tbody>
	';
	foreach ($blogs as $blog)
	{
		echo '
		<tr>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;blog=' . $blog->blog_id . '">' . $blog->blog_id . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;blog=' . $blog->blog_id . '">' . $blog->domain . '</a></td>
			<td><a href="wpmu-admin.php?page=simple_user_management&amp;blog=' . $blog->blog_id . '">' . $blog->path . '</a></td>
			<td><a href="http://' . $blog->domain . $blog->path . '">http://' . $blog->domain . $blog->path . '</a></td>
		</tr>
		';
	}
	echo '
	</tbody>
	</table>
	';
}

// search users
function simple_user_management_search_users($query)
{
	global $wpdb;
	$sql = "select id, display_name, user_login, user_email
			from " . $wpdb->users . "
			where id = " . $wpdb->escape((int)$query) . "
			or display_name like '%" . $wpdb->escape($query) . "%'
			or user_login like '%" . $wpdb->escape($query) . "%'
			or user_email like '%" . $wpdb->escape($query) . "%';";
	return $wpdb->get_results($sql);
}

// show the search forms
function simple_user_management_show_search_forms()
{
	echo '
	<div style="float:left;width:45%;margin-right:1%;">
		<h3>' . __("Search users") . '</h3>
		' . simple_user_management_get_search_users_form() . '
	</div>
	<div style="float:left;width:45%">
		<h3>' . __("Search blogs") . '</h3>
		' . simple_user_management_get_search_blogs_form() . '
	</div>
	';
}

// show the search blogs form
function simple_user_management_get_search_blogs_form($qs = "")
{
	return '
		<form action="wpmu-admin.php?page=simple_user_management' . $qs . '#results" method="post">
			<fieldset>
			<p><label for="blogquery">' . __("Search for:") . '</label>
			<input type="text" name="blogquery" id="blogquery" /></p>
			<p>' . __("You can search on blog IDs, domains and paths.") . '</p>
			<p class="submit"><input type="submit" name="submit" value="' . ("Search &raquo;") . '" /></p>
			</fieldset>
		</form>
	';
}

// show the search users form
function simple_user_management_get_search_users_form($qs = "")
{
	return '
		<form action="wpmu-admin.php?page=simple_user_management' . $qs . '#results" method="post">
			<fieldset>
			<p><label for="userquery">' . __("Search for:") . '</label>
			<input type="text" name="userquery" id="userquery" /></p>
			<p>' . __("You can search on user IDs, user logins, display names and email addresses.") . '</p>
			<p class="submit"><input type="submit" name="submit" value="' . ("Search &raquo;") . '" /></p>
			</fieldset>
		</form>
	';
}
?>