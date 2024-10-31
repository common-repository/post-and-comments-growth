<?php
/*
Plugin Name: Post and Comment Growth
Version: 1.0.1
Plugin URI: 
http://blogs.ubc.ca/support/plugins/post-and-comment-growthpost-and-comment-growth
Description: Display post and comment growth
Author: Michael Ha @ OLT
Author URI: http://blogs.ubc.ca/oltmha/
*/

add_action('admin_menu', 'wpmugrowth_menu');

function wpmugrowth_menu() {
add_submenu_page('wpmu-admin.php', 'Post and Comment Growth', 'Post and Comment Growth', 10, 'wpmugrowth', 'wpmugrowth_view');
}

function wpmugrowth_view() {
echo '<div class="wrap">';
echo '<h2>Post and Comment Growth</h2>';
echo '<p>Click <a href="wpmu-admin.php?page=wpmugrowth&refresh=true">here</a> to refresh all database tables</p>';

if ( $_GET['refresh'] == 'true' ) {
require_once('refresh.php');
}
require_once('functions.php');
update_graph();
require_once('startup.php');
set_time_limit(0);

?>
    <script type='text/javascript' src='http://www.google.com/jsapi'></script>
    <script type='text/javascript'>
      google.load('visualization', '1', {'packages':['annotatedtimeline']});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var postdata = new google.visualization.DataTable();
        postdata.addColumn('date', 'Date');
        postdata.addColumn('number', 'Posts');
        postdata.addColumn('number', 'Comments');
		<?php pivot_post_table(); ?>
        var postchart = new google.visualization.AnnotatedTimeLine(document.getElementById('post_div'));
        postchart.draw(postdata, {displayAnnotations: true});
      }
    </script>
    <div id='post_div' style='margin:20px; height: 440px;'></div>
<?php
echo '</div>';
}
?>
