<?php
/*
 * Plugin Name:       Live Widget Luftdaten.info
 * Plugin URI:        https://github.com/ftpproxy/luftdaten-wordpress-plugin
 * Description:       Plugin with widget to show live data from a sensor.community sensor
 * Version:           1.4.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Andreas Schoenberg, Bleeptrack
 * Author URI:        https://github.com/ftpproxy/
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       live-widget-luftdaten
 * Domain Path:       /languages
*/


class LuftdatenAmpel extends WP_Widget {

	// constructor
	function __construct() {
		parent::__construct(false, $name = __('Luftdaten Ampel', 'live-widget-luftdaten') );
	}

	// widget form creation
	function form($instance) {
		//print_r($instance); // for easy debugging
	?>
			<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget title', 'live-widget-luftdaten'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id('neuersensor'); ?>"><?php _e('Sensor IDs - comma separated', 'live-widget-luftdaten'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('neuersensor'); ?>" name="<?php echo $this->get_field_name('neuersensor'); ?>" type="text" value="<?php echo $instance['neuersensor']; ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id('unit'); ?>"><?php _e('Show unit?', 'live-widget-luftdaten'); ?></label>
				<input class="checkbox" type="checkbox" <?php checked( $instance[ 'unit' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'unit' ); ?>" name="<?php echo $this->get_field_name( 'unit' ); ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id('addtextcheck'); ?>"><?php _e('Show additional text?', 'live-widget-luftdaten'); ?></label>
				<input class="checkbox" type="checkbox" <?php checked( $instance[ 'addtextcheck' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'addtextcheck' ); ?>" name="<?php echo $this->get_field_name( 'addtextcheck' ); ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id('addtext'); ?>"><?php _e('Additional text', 'live-widget-luftdaten'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('addtext'); ?>" name="<?php echo $this->get_field_name('addtext'); ?>" type="text" value="<?php echo $instance['addtext']; ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id('timestamp'); ?>"><?php _e('Show timestamp?', 'live-widget-luftdaten'); ?></label>
				<input class="checkbox" type="checkbox" <?php checked( $instance[ 'timestamp' ], 'on' ); ?> id="<?php echo $this->get_field_id( 'timestamp' ); ?>" name="<?php echo $this->get_field_name( 'timestamp' ); ?>" />
			</p>

	<?php
	}

	// widget update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;


      	foreach ($new_instance as $key => $value) {
      		//$instance[$key] = strip_tags($value);
      		$expl = explode("_", $key );
      		if(count($expl)>1){
      			$instance[$expl[0]]->updateItem(trim($expl[1]),trim($value));
      		}else{
      			$instance[$key] = strip_tags($value);
      		}
      	}

      	//wenn id für neuen Sensor, dann anlegen
      	if($new_instance['neuersensor']!=$old_instance['neuersensor']){
      		$instance = array();
      		$instance['title']=$new_instance['title'];
      		$instance['neuersensor']=$new_instance['neuersensor'];
      		$comma_separated = explode(",", $new_instance['neuersensor'] );

      		foreach ($comma_separated as $value) {
      			$instance[trim($value)] = new Sensor( trim($value) );
      		}

      	}

      	$instance[ 'unit' ] = $new_instance[ 'unit' ];
      	$instance[ 'addtextcheck' ] = $new_instance[ 'addtextcheck' ];
      	$instance[ 'timestamp' ] = $new_instance[ 'timestamp' ];

     	return $instance;
	}


	function widget($args, $instance) {

		if(!isset($instance['unit'])){
			$unit = '';
		}else{
			$unit = $instance[ 'unit' ] ? 'µg/m³' : '';
		}

		if(!isset($instance['addtextcheck'])){
			$addtextcheck = false;
		}else{
			$addtextcheck = $instance[ 'addtextcheck' ] ? true : false;
		}

		if(!isset($instance['timestamp'])){
			$timestamp = false;
		}else{
			$timestamp = $instance[ 'timestamp' ] ? true : false;
		}


		$p1g=50;
		$p2g=20;

		$red = "A30303";
		$yellow = "F4EC00";
		$green = "03A350";

		$p1arr = $this->Gradient3($green,$yellow,$red,$p1g+1);
		$p2arr = $this->Gradient3($green,$yellow,$red,$p2g+1);


		extract( $args );
		echo $before_widget;
		$title = apply_filters('widget_title', $instance['title']);
		if ( $title ) {
      		echo $before_title . $title . $after_title;
   		}

   		$v1 = 0;
   		$v2 = 0;
   		$count = 0;
   		$tstmp = 0;

   		foreach ($instance as $key => $value) {
			if(strcmp($key,'title')&&strcmp($key,'neuersensor')&&strcmp($key,'unit')&&strcmp($key,'addtext')&&strcmp($key,'addtextcheck')&&strcmp($key,'timestamp')){
			//var_dump($value);
				$sensordata = $this->getData($value);
				//var_dump($sensordata);
				if(isset($sensordata['P1']) && isset($sensordata['P2'])){

					//$tstmp = strtotime($sensordata['timestamp']." +2 hours");
					$tstmp = strtotime(get_date_from_gmt($sensordata['timestamp']));
					$v1 = $v1 + $sensordata['P1'];
					$v2 = $v2 + $sensordata['P2'];
					$count = $count+1;
				}
			}
		}

		if($count>0){
		$ampelvalue1 = $v1/$count;
		$ampelvalue2 = $v2/$count;

		$ampelcol1 = round($ampelvalue1,0);
		if($ampelcol1>$p1g) $ampelcol1=$p1g;
		$ampelcol2 = round($ampelvalue2,0);
		if($ampelcol2>$p2g) $ampelcol2=$p2g;

		if(round($ampelvalue1,1)>=$p1g || round($ampelvalue2,1)>=$p2g){
   			$bordercol = $red;
   		}else{
   			$bordercol = "000";
   		}

   		?>


   		<div class="feinstaubampel" style="margin: auto">
   		<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="173" height="153" viewbox="-5 -5 170 148.56406460551017 ">

   			<path fill="#<?= $bordercol ?>" d="M-5 69.28203230275508L38 -5L122 -5L165 69.28203230275508L122 143.56406460551017L38 143.56406460551017Z"></path>

   			<path fill="#<?= $p1arr[$ampelcol1]?>" d="M0 69.28203230275508L40 0L120 0L160 69.28203230275508L120 138.56406460551017L40 138.56406460551017Z"></path>
   			<path fill="#<?= $p2arr[$ampelcol2]?>" d="M0 69.28203230275508L160 69.28203230275508L120 138.56406460551017L40 138.56406460551017Z"></path>

   			<line x1="0" y1="69" x2="160" y2="69" style="stroke:#<?= $bordercol ?>;stroke-width:3" />

   			<text x="80" y="15" fill="black" text-anchor="middle" style="font-size:12px;" fill-opacity="0.5">PM10 <?= $unit ?></text>
              <text x="80" y="132" fill="black" text-anchor="middle" style="font-size:12px;" fill-opacity="0.5">PM2.5 <?= $unit ?></text>

               <text x="80" y="55" fill="black" text-anchor="middle" style="font-size:28px;font-weight:bold;">
                   <?= round($ampelvalue1,1) ?>
              </text>
              <text x="80" y="105" fill="black" text-anchor="middle" style="font-size:28px;font-weight:bold;">
              <?= round($ampelvalue2,1) ?>
              </text>
   		</svg>
   		<?php
   			if($addtextcheck){
   				echo '<p>'.$instance['addtext'].'</p>';
   			}
   			if($timestamp){
   				echo '<p class="la-timestamp">'.date("d.m. H:i",$tstmp).'</p>';
   			}
   		?>
   		</div>

   		<?php
   		}else{
   			echo '<h2>No PM Data</h2>';
   		}
		echo $after_widget;

	}

	function printBackend($items,$id){
		foreach ($items as $key => $value) {
			?>

			<p>
				<label for="<?php echo $this->get_field_id($id.'_'.$key); ?>"><?php printf(/* translators: %s: label of the field */ __( 'Label %s', 'live-widget-luftdaten' ), $key); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id($id.'_'.$key); ?>" name="<?php echo $this->get_field_name($id.'_'.$key); ?>" type="text" value="<?php echo $value; ?>" />
			</p>

			<?php
		}
	}

	function getData($sensor){
		$v = array();

		$results = $sensor->fetchJson();


		$count = 0;

		if($results!=null){

			foreach ($results as $item) {


			    foreach($item->sensordatavalues as $values){
			    	$count++;
			    	if (!array_key_exists($values->value_type, $v)) {
			    		$v[$values->value_type]=$values->value;
			    	}else{
			    		$v[$values->value_type]=$v[$values->value_type]+($values->value-$v[$values->value_type])/$count;
			    	}
			    }

			    $v['timestamp'] = $item->timestamp;

			}
		}

		return $v;

	}

	function Gradient3($from, $to1, $to2, $steps){
		$arr1 = $this->Gradient($from,$to1,$steps/2);
		$arr2 = $this->Gradient($to1,$to2,$steps/2);
		return array_merge($arr1,$arr2);

	}

	function Gradient($HexFrom, $HexTo, $ColorSteps) {
	  $FromRGB['r'] = hexdec(substr($HexFrom, 0, 2));
	  $FromRGB['g'] = hexdec(substr($HexFrom, 2, 2));
	  $FromRGB['b'] = hexdec(substr($HexFrom, 4, 2));



	  $ToRGB['r'] = hexdec(substr($HexTo, 0, 2));
	  $ToRGB['g'] = hexdec(substr($HexTo, 2, 2));
	  $ToRGB['b'] = hexdec(substr($HexTo, 4, 2));

	  $StepRGB['r'] = ($FromRGB['r'] - $ToRGB['r']) / ($ColorSteps - 1);
	  $StepRGB['g'] = ($FromRGB['g'] - $ToRGB['g']) / ($ColorSteps - 1);
	  $StepRGB['b'] = ($FromRGB['b'] - $ToRGB['b']) / ($ColorSteps - 1);



	  for($i = 0; $i <= $ColorSteps; $i++) {

	    $RGB['r'] = floor($FromRGB['r'] - ($StepRGB['r'] * $i));
	    $RGB['g'] = floor($FromRGB['g'] - ($StepRGB['g'] * $i));
	    $RGB['b'] = floor($FromRGB['b'] - ($StepRGB['b'] * $i));

	    if($RGB['r']<0) $RGB['r']=0;
	    if($RGB['g']<0) $RGB['g']=0;
	    if($RGB['b']<0) $RGB['b']=0;

	    if($RGB['r']>255) $RGB['r']=255;
	    if($RGB['g']>255) $RGB['g']=255;
	    if($RGB['b']>255) $RGB['b']=255;

	    $HexRGB['r'] = sprintf('%02x', ($RGB['r']));
	    $HexRGB['g'] = sprintf('%02x', ($RGB['g']));
	    $HexRGB['b'] = sprintf('%02x', ($RGB['b']));

	    $GradientColors[] = implode(NULL, $HexRGB);
	  }
	  return $GradientColors;
	}


}


///////


class LuftdatenWidget extends WP_Widget {

	// constructor
	function __construct() {
		parent::__construct(false, $name = __('Luftdaten Live Widget', 'live-widget-luftdaten') );
	}

	// widget form creation
	function form($instance) {
		//print_r($instance); for easy debugging
	?>
			<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget title', 'live-widget-luftdaten'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id('neuersensor'); ?>"><?php _e('Sensor IDs - comma separated', 'live-widget-luftdaten'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('neuersensor'); ?>" name="<?php echo $this->get_field_name('neuersensor'); ?>" type="text" value="<?php echo $instance['neuersensor']; ?>" />
			</p>
	<?php

		foreach ($instance as $key => $value) {
			if(strcmp($key,'title')&&strcmp($key,'neuersensor')){
				$this->printBackend($value->items, $value->id);
			}
		}
	?>

	<?php
	}

	// widget update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

      	foreach ($new_instance as $key => $value) {
      		//$instance[$key] = strip_tags($value);
      		$expl = explode("_", $key );
      		if(count($expl)>1){
      			$instance[$expl[0]]->updateItem(trim($expl[1]),trim($value));
      		}else{
      			$instance[$key] = strip_tags($value);
      		}
      	}

      	//wenn id für neuen Sensor, dann anlegen
      	if($new_instance['neuersensor']!=$old_instance['neuersensor']){
      		$instance = array();
      		$instance['title']=$new_instance['title'];
      		$instance['neuersensor']=$new_instance['neuersensor'];
      		$comma_separated = explode(",", $new_instance['neuersensor'] );

      		foreach ($comma_separated as $value) {
      			$instance[trim($value)] = new Sensor( trim($value) );
      		}

      	}
     	return $instance;
	}


	function widget($args, $instance) {
		extract( $args );
		echo $before_widget;
		$title = apply_filters('widget_title', $instance['title']);
		if ( $title ) {
      		echo $before_title . $title . $after_title;
   		}

		foreach ($instance as $key => $value) {
			if(strcmp($key,'title')&&strcmp($key,'neuersensor')){
				$this->printData($value);
			}
		}

		echo $after_widget;

	}

	function printBackend($items,$id){
		foreach ($items as $key => $value) {
			?>

			<p>
				<label for="<?php echo $this->get_field_id($id.'_'.$key); ?>"><?php _e('Label '.$key, 'live-widget-luftdaten'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id($id.'_'.$key); ?>" name="<?php echo $this->get_field_name($id.'_'.$key); ?>" type="text" value="<?php echo $value; ?>" />
			</p>

			<?php
		}
	}

	function printData($sensor){
		$v = array();

		$results = $sensor->fetchJson();

		$count = 0;

		if($results!=null){

			foreach ($results as $item) {

			    foreach($item->sensordatavalues as $values){
			    	$count++;
			    	if (!array_key_exists($values->value_type, $v)) {
			    		$v[$values->value_type]=$values->value;
			    	}else{
			    		$v[$values->value_type]=$v[$values->value_type]+($values->value-$v[$values->value_type])/$count;
			    	}
			    }

			}
		}

		echo '<div id="'.$sensor->id.'-name">'.$sensor->items['name'].'</div><ul>';

   		foreach ($v as $key => $value) {
   			//var_dump($sensor->items["P2"]);
   			if(strcmp($key,'pressure')==0){
   				$value=$value/100;
   			}
   			echo '<li id="'.$sensor->id.'-'.$key.'">'.$sensor->items[trim($key)].': '.round($value,1).$this->getUnit($key).'</li>';

   		}

		echo '</ul>';

	}

	function getUnit($key){
		if(strcmp($key,'P1')==0 || strcmp($key,'P2')==0){
			return 'µg/m³';
		}elseif(strcmp($key,'temperature')==0){
			return '°C';
		}elseif(strcmp($key,'humidity')==0){
			return '%';
		}elseif(strcmp($key,'pressure')==0){
            return 'hPa';
        }
		return '';
	}


}

////

class Sensor{
	public $id = 260;
	public $items = array();
	public $pos = array();
	public $valid = 0;

	function Sensor($sensorid){
		$this->id = $sensorid;
		$this->items['name']="Sensor ".$this->id;
		$results = $this->fetchJson();
		//echo "Results: ".$this->id;

		//echo '<pre>';
		//print_r($results);
		//echo '</pre>';

		if(isset($results[0])){
			foreach($results[0]->sensordatavalues as $values){

			    $name=$values->value_type;
			    //if (!array_key_exists($name, $instance)) {
			    	$this->items[$name]=$name;
			    //}

			}

			$pos = $results[0]->location;
			//var_dump($pos);
			foreach($results[0]->location as $key => $value){


			    //if (!array_key_exists($name, $instance)) {
			    	$this->pos[$key]=$value;
			    //}

			}
			$this->valid = 1;
		}
	}

	function updateItem($key,$value){
		$this->items[$key]=$value;
	}





	function fetchJson(){



 		if ( false === ( $request = get_transient( 'LuftdatenJSON-'.$this->id ) ) ) {
 			$request = wp_remote_get( 'https://data.sensor.community/airrohr/v1/sensor/'.$this->id.'/' );
 			set_transient('LuftdatenJSON-'.$this->id,$request,120);
 		}



		if( is_wp_error( $request ) ) {
			return false; // Bail early
		}

		$body = wp_remote_retrieve_body( $request );

		$data = json_decode( $body );

		return $data;
	}
}


// register widget
function lda_register_widgets() {
	register_widget("LuftdatenAmpel");
	register_widget("LuftdatenWidget");
}

add_action('widgets_init', 'lda_register_widgets');

///shortcodes

// [feinstaublive title="Titel" sensorIDs="260,262"]
function feinstaublive($atts) {
	//echo "atts:";
	//var_dump($atts);

    $widget_name = "LuftdatenWidget";

    if(isset($atts['title'])){
    	$title = $atts['title'];
    }else{
    	$title = "Feinstaub Live Info";
    }


    $attr = array(
    	'title' => $title,
    	);

    if(isset($atts['sensorids'])){
    	$comma_separated = explode(",", $atts['sensorids'] );

      	foreach ($comma_separated as $value) {
      		$attr[trim($value)] = new Sensor( trim($value) );
      	}

    }


    ob_start();
    the_widget($widget_name,$attr);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;

}
add_shortcode('feinstaublive','feinstaublive');

function feinstaubampel($atts) {


    $widget_name = "LuftdatenAmpel";

    if(isset($atts['title'])){
    	$title = $atts['title'];
    }else{
    	$title = "Feinstaubampel";
    }


    $attr = array(
    	'title' => $title,
    	);

    if(isset($atts['sensorids'])){
    	$comma_separated = explode(",", $atts['sensorids'] );

      	foreach ($comma_separated as $value) {
      		$attr[trim($value)] = new Sensor( trim($value) );
      	}

    }


    ob_start();
    the_widget($widget_name,$attr);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;

}
add_shortcode('feinstaubampel','feinstaubampel');


function feinstaubkarte($atts) {

    if(isset($atts['title'])){
    	$title = $atts['title'];
    }else{
    	$title = "Feinstaubkarte";
    }

    $lons = 0;
    $lats = 0;
    $k = 0;


    $attr = array(
    	'title' => $title,
    	);


    if(isset($atts['sensorids'])){
    	$comma_separated = explode(",", $atts['sensorids'] );

      	foreach ($comma_separated as $value) {
            if( strpos($value,"“") !== false ||  strpos($value,"″") !== false ){
                echo 'Please check your shortcode for wrong ticks! ';
            }
      		$sensor = new Sensor();
	    		$sensor->Sensor( trim($value) );


      		if($sensor->valid){
      			$lons += $sensor->pos['longitude'];
      			$lats += $sensor->pos['latitude'];
      			$k++;
      		}
      	}
    }

    $zoom = 11;
    if(isset($atts['zoom'])){
    	$zoom = $atts['zoom'];
    }

    if($k == 0){
        echo 'Sorry, no valid sensors :(';
    }else{

        echo '<iframe id="feinstaubkarte" style="width:90%;height:500px; margin: auto;"src="//maps.sensor.community/#' . $zoom . '/' . $lats/$k . '/' . $lons/$k . '"></iframe>';
    }


    /*ob_start();
    the_widget($widget_name,$attr);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;*/
    //var_dump($attr);

}
add_shortcode('feinstaubkarte','feinstaubkarte');


?>
