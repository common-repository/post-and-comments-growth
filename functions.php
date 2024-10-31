<?php
// functions to create, populate, repopulate database tables

function create_post_table() {
	global $wpdb;
	$post_table = $wpdb->base_prefix."mu_growth_post";
	$post_structure = "CREATE TABLE $post_table (
		id INT(9) NOT NULL AUTO_INCREMENT,
		blog_id INT(9),
		post_id INT(9),
		post_date datetime,
		UNIQUE KEY id (id)
	);";
	$wpdb->query($post_structure);	
}

function create_comment_table() {
	global $wpdb;
	$comment_table = $wpdb->base_prefix."mu_growth_comment";
	$comment_structure = "CREATE TABLE $comment_table (
		id INT(9) NOT NULL AUTO_INCREMENT,
		blog_id INT(9),
		comment_id INT(9),
		comment_date datetime,
		UNIQUE KEY id (id)
	);";
	$wpdb->query($comment_structure);
}

function populate_post_table() {
    global $wpdb;
	//get all the blog ids and put them in an array
	$blog_ids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
	foreach ($blog_ids as &$blog_id) {
		$query_blog_posts = "
		SELECT ID, post_date FROM ".$wpdb->base_prefix.$blog_id."_posts WHERE post_status='publish' AND post_type='post'";
		$this_blog_posts = $wpdb->get_results($query_blog_posts);
		foreach ($this_blog_posts as &$this_blog_post) {
			$insert_blog_post = 
			"INSERT INTO ".$wpdb->base_prefix."mu_growth_post (blog_id, post_id, post_date)
			VALUES (".$blog_id.",".$this_blog_post->ID.",'".$this_blog_post->post_date."')";
			$wpdb->query($insert_blog_post);
		}
		unset($this_blog_post);
	}
	unset($blog_id);
}

function populate_comment_table() {
    global $wpdb;
	//get all the blog ids and put them in an array
	$blog_ids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
	foreach ($blog_ids as &$blog_id) {
		$query_blog_comments = "
		SELECT comment_ID, comment_date FROM ".$wpdb->base_prefix.$blog_id."_comments WHERE comment_approved=1";
		$this_blog_comments = $wpdb->get_results($query_blog_comments);
		foreach ($this_blog_comments as &$this_blog_comment) {
			$insert_blog_comment = 
			"INSERT INTO ".$wpdb->base_prefix."mu_growth_comment (blog_id, comment_id, comment_date)
			VALUES (".$blog_id.",".$this_blog_comment->comment_ID.",'".$this_blog_comment->comment_date."')";
			$wpdb->query($insert_blog_comment);
		}
		unset($this_blog_comment);
	}
	unset($blog_id);
}

function repopulate_post_table() {
	global $wpdb;
	$wpdb->query("TRUNCATE TABLE ".$wpdb->base_prefix."mu_growth_post");
	populate_post_table();
}

function repopulate_comment_table() {
	global $wpdb;
	$wpdb->query("TRUNCATE TABLE ".$wpdb->base_prefix."mu_growth_comment");
	populate_comment_table();
}

function pivot_post_table() {
	global $wpdb;
	$post_tables = $wpdb->get_results("
	SELECT substring(post_date, 1, 10) AS date, post_count FROM ".$wpdb->base_prefix."mu_growth_pivot_post");
	$comment_tables = $wpdb->get_results("
	SELECT substring(comment_date, 1, 10) AS date, comment_count FROM ".$wpdb->base_prefix."mu_growth_pivot_comment");	
	$total_count = count($post_tables) + count($comment_tables);
	echo "postdata.addRows(".$total_count.");\n";	
	$row = 0;
	foreach ($post_tables as &$post_table) {
	$this_post_date = date($post_table->date);
	echo "postdata.setValue(".$row.", 0, new Date(".date('Y, n, j', strtotime($post_table->date))."));\n";
	echo "postdata.setValue(".$row.", 1, ".$post_table->post_count.");\n";	
	$row++;	
	}
	unset($post_table);
	
	foreach ($comment_tables as &$comment_table) {
	$this_comment_date = date($comment_table->date);
	echo "postdata.setValue(".$row.", 0, new Date(".date('Y, n, j', strtotime($comment_table->date))."));\n";
	echo "postdata.setValue(".$row.", 2, ".$comment_table->comment_count.");\n";	
	$row++;	
	}
	unset($comment_table);	
}

function insert_pivot_post_table() {
	global $wpdb;
	$post_table = $wpdb->base_prefix."mu_growth_pivot_post";
	$post_structure = "CREATE TABLE $post_table (
		id INT(9) NOT NULL AUTO_INCREMENT,
		post_date datetime,
		post_count INT(9),
		UNIQUE KEY id (id)
	);";
	$wpdb->query($post_structure);
	
	$comment_table = $wpdb->base_prefix."mu_growth_pivot_comment";
	$comment_structure = "CREATE TABLE $comment_table (
		id INT(9) NOT NULL AUTO_INCREMENT,
		comment_date datetime,
		comment_count INT(9),
		UNIQUE KEY id (id)
	);";
	$wpdb->query($comment_structure);	
}

function populate_pivot_table() {
	global $wpdb;
	$post_tables = $wpdb->get_results("
	SELECT substring(post_date, 1, 10) AS date,
	COUNT(id) AS post_total FROM ".$wpdb->base_prefix."mu_growth_post GROUP BY date ORDER BY date");
	$comment_tables = $wpdb->get_results("
	SELECT substring(comment_date, 1, 10) AS date,
	COUNT(id) AS comment_total FROM ".$wpdb->base_prefix."mu_growth_comment GROUP BY date ORDER BY date");
	for ($i = 0; $i < count($post_tables); $i++) {
	if ( $i == 0 ) {
		$increment_post_total = $post_tables[$i]->post_total;
	} else {
		$increment_post_total = (int)$post_tables[$i]->post_total + $increment_post_total;
	}
	$insert_pivot_post = "INSERT INTO ".$wpdb->base_prefix."mu_growth_pivot_post (post_date, post_count)
		VALUES ('".$post_tables[$i]->date."',".$increment_post_total.")";
	$wpdb->query($insert_pivot_post);	
	}

	for ($i = 0; $i < count($comment_tables); $i++) {
	if ( $i == 0 ) {
		$increment_comment_total = $comment_tables[$i]->comment_total;
	} else {
		$increment_comment_total = (int)$comment_tables[$i]->comment_total + $increment_comment_total;
	}
	$insert_pivot_comment = "INSERT INTO ".$wpdb->base_prefix."mu_growth_pivot_comment (comment_date, comment_count)
		VALUES ('".$comment_tables[$i]->date."',".$increment_comment_total.")";
	$wpdb->query($insert_pivot_comment);	
	}
}

function repopulate_pivot_table() {
	global $wpdb;
	$wpdb->query("TRUNCATE TABLE ".$wpdb->base_prefix."mu_growth_pivot_comment");
	$wpdb->query("TRUNCATE TABLE ".$wpdb->base_prefix."mu_growth_pivot_post");	
	populate_pivot_table();
}

function update_graph() {
    global $wpdb;	
	//get all the blog ids and put them in an array
	$blog_ids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
	$last_post = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."mu_growth_post ORDER BY post_date DESC");
	$last_comment = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."mu_growth_comment ORDER BY comment_date DESC");
	foreach ($blog_ids as &$blog_id) {
		$query_blog_posts = "
		SELECT ID, post_date FROM ".$wpdb->base_prefix.$blog_id."_posts WHERE post_status='publish' AND post_type='post' AND post_date > '".$last_post->post_date."'";
		$this_blog_posts = $wpdb->get_results($query_blog_posts);
		foreach ($this_blog_posts as &$this_blog_post) {
			$insert_blog_post = 
			"INSERT INTO ".$wpdb->base_prefix."mu_growth_post (blog_id, post_id, post_date)
			VALUES (".$blog_id.",".$this_blog_post->ID.",'".$this_blog_post->post_date."')";
			$wpdb->query($insert_blog_post);
			$date_count = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."mu_growth_pivot_post WHERE post_date='".substr($this_blog_post->post_date,0,10)."'");
			$new_count = (int)$date_count->post_count;
			$add_this = $new_count + 1;
			$wpdb->query("UPDATE ".$wpdb->base_prefix."mu_growth_pivot_post SET post_count='".$add_this."' WHERE post_date='".substr($this_blog_post->post_date,0,10)."'");
		}
		unset($this_blog_post);
	}
	unset($blog_id);
	
	foreach ($blog_ids as &$blog_id) {
		$query_blog_comments = "
		SELECT comment_ID, comment_date FROM ".$wpdb->base_prefix.$blog_id."_comments WHERE comment_approved=1 AND comment_date > '".$last_comment->comment_date."'";
		$this_blog_comments = $wpdb->get_results($query_blog_comments);
		foreach ($this_blog_comments as &$this_blog_comment) {
			$insert_blog_comment = 
			"INSERT INTO ".$wpdb->base_prefix."mu_growth_comment (blog_id, comment_id, comment_date)
			VALUES (".$blog_id.",".$this_blog_comment->comment_ID.",'".$this_blog_comment->comment_date."')";
			$wpdb->query($insert_blog_comment);
			$date_count = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."mu_growth_pivot_comment WHERE comment_date='".substr($this_blog_comment->comment_date,0,10)."'");
			$new_count = (int)$date_count->comment_count;
			$add_this = $new_count + 1;
			$wpdb->query("UPDATE ".$wpdb->base_prefix."mu_growth_pivot_comment SET comment_count='".$add_this."' WHERE comment_date='".substr($this_blog_comment->comment_date,0,10)."'");			
		}
		unset($this_blog_comment);
	}
	unset($blog_id);	
}
?>