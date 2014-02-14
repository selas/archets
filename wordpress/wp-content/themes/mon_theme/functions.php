<?php 

add_action('pre_get_posts', 'display_concerts');
add_action('wp_dashboard_setup', 'add_dashboard_widgets');

function display_concerts($query){
	if($query->is_front_page() && $query->is_main_query()){
		
		$query->set('post_type', array('concert'));

		//10 derniere années
		//$query->set('date_query', array('year' => '2006', 'compare' => '>='));
		//$query->set('date_query', array('year' => '2008', 'compare' => '<='));

		
		//le lieu n'est pas spécifié
		$query->set('meta_query', array(array('key'=> 'wpcf-lieu', 'value'=>false, 'type' =>BOOLEAN)));
		
		//qui possede une image a la une
		//$query->set('meta_query', array(array('key'=> '_thunbnail_id', 'compare'=>'EXITS')));

		return;
	}
}

function dashboard_widget_function(){
	//echo "hello World, this is my first dashboard Widget !";
	$argsConcert = array(
		"post_type"=>"concert", 
		"meta_query"=>array(
			array('key'=> 'wpcf-lieu', 'value'=>false, 'type' =>BOOLEAN)));

	$argsPays = array(
		"post_type"=>"pays", 
		"tax_query"=>array(
			array('taxonomy' => 'pays')));

	$argsAction = array(
		"post_type"=>"action", 
		"posts_per_page"=>-1,
		"tax_query"=>array(
			array(
				'taxonomy' => 'pays', 
				'field'=>'slug', 
				'terms' => 
					array(
						'benin', 
						'congo', 
						'niger'), 
				'operator' => 'NOT IN')
			)
		)
	;
	
	$queryConcert = new WP_Query($argsConcert);
	$queryAction = new WP_Query($argsAction);
	$queryPays = new WP_Query($argsPays);

	$nbConcert = $queryConcert->post_count;
	$nbAction = $queryAction->post_count;
	$nbPays = $queryPays->post_count;

	echo $nbConcert." concerts n'ont pas de lieu spécifié";
	echo "<br />";
	echo $nbAction." actions n'ont pas de pays spécifié";
	echo $nbPays." les pays";
}

function add_dashboard_widgets(){
	wp_add_dashboard_widget('dashboard_widget', 'Mon Dashboard Widget', 'dashboard_widget_function');
}

function geolocalize($post_id) {
	$post_terms = wp_get_post_terms();
	if($post_terms['taxonomy'] === 'pays') {
		$type = 'action';
		$lieu_query = 'wpcf-pays';
	}
	else {
		$lieu_query = 'wpcf-lieu';
		$type = 'concert';
	}
	if(wp_is_post_revision($post_id)) {
		return;
	}
	$post = get_post($post_id);
	if(!in_array($post->post_type, array($type)))
		return;
	$lieu = get_post_meta($post_id, $lieu_query, TRUE);
	if(empty($lieu)) {
		return;
	}
	$lat = get_post_meta($post_id, 'lat', TRUE);
	if(empty($lat)) {
		$address = $lieu . ', France';
		$result = doGeolocation($address);
		if(false === $result) 
			return;
		try {
			$location = $result[0]['geometry']['location'];
			add_post_meta($post_id, 'lat', $location['lat']);
			add_post_meta($post_id, 'lng', $location['lng']);
		}
		catch(Exception $e) {
			return;
		}
	}
}
add_action('save_post', 'geolocalize');


function doGeolocation($address){
	$url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address=".urlencode($address);
	if ($json = file_get_contents($url)) {
		$data = json_decode($json , true ) ;
		if($data['status'] == "OK"){
			return $data['results'] ;
		}	
	}
	return false ;
}


function load_scripts() {
	if(! is_post_type_archive('concert') && ! is_post_type_archive('action')) 
		return;

	wp_register_script('leaflet-js', 'http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.js');
	wp_enqueue_script('leaflet-js');

	wp_register_style('leaflet-css', 'http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.css');
	wp_enqueue_style('leaflet-css');
}
add_action('wp_enqueue_scripts', 'load_scripts');


function getPosWithLatLon($post_type = 'concert') {
	global $wpdb;
	$query = "
		SELECT ID, post_date, post_content, post_title, p1.meta_value as lat, p2.meta_value as lng
		FROM wp_archetsposts, wp_archetspostmeta as p1, wp_archetspostmeta as p2
		WHERE wp_archetsposts.post_type = 'concert'
		AND p1.post_id = wp_archetsposts.ID
		AND p2.post_id = wp_archetsposts.ID
		AND p1.meta_key = 'lat'
		AND p2.meta_key = 'lng'
	";
	return $wpdb->get_results($query);
}


function getMarkerList($post_type = 'concert') {
	$results = getPosWithLatLon($post_type);
	$array = array();
	foreach($results as $result) {

		$array[] = "var marker_" . $result->ID . " = L.marker([" . $result->lat . ", " . $result->lng . "]).addTo(map);";
		$array[] = "var popup_" . $result->ID . " = L.popup().setContent('" . $result->post_title . " <br /> ". $result->post_date ." ');";
		//$array[] = "popup_" . $result->ID . ".post_id = L.popup().setContent('" . $result->post_title . "');";
		$array[] = "marker_" . $result->ID . ".bindPopup(popup_". $result->ID . ");";
	
	}
	return implode(PHP_EOL, $array);
}


function get_content() {
	if( !wp_verify_nonce($_REQUEST['nonce'], 'popup_content')) {
		exit("d'où vient cette requête ?");
	}
	else {
		$post_id = $_REQUEST['post_id'];
		print $post_id;
	}
	die();
}

add_action('wp_ajax_popup_content', 'get_content');
add_action('wp_ajax_nopriv_popup_content', 'get_content');

?>