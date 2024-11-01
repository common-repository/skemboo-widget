<?php
/**
Plugin Name: Skemboo Widget
Plugin URI: http://skemboo.com/skemboo
Description: Widget para mostrar tus &uacute;ltimos proyectos en Skemboo
Author: Fabbian &Aacute;lvarez
Version: 0.1.8
Author URI: http://mrfabbianz.com/
*/

define('skemboo_VERSION', (float) 1.0);
define('skemboo_META','skemboo-widget');
define('skemboo_CACHE_DURATION',43200);
define('skemboo_SKEMBOO_RSS_URI','http://www.skemboo.com/%s/rss/');
define('skemboo_UA_FF','Mozilla/5.0 (Windows; U; Windows NT 5.1; es-ES; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 GTB5');
define('skemboo_UA', 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ).' skemboo-widget/'.skemboo_VERSION );
wp_register_style(skemboo_META,WP_PLUGIN_URL.'/'.skemboo_META.'/'.skemboo_META.'.css',array(),'1.0');


function buscar_proyectos($configuracion){
	
	$username = (string) ((isset($configuracion['username']) && !empty($configuracion['username']) ) ? $configuracion['username'] : '' );;
	$limite = (int) ((isset($configuracion['limite']) && !empty($configuracion['limite']) ) ? $configuracion['limite'] : 5 );
	$ordenar = ((isset($configuracion['ordenar']) && !empty($configuracion['ordenar']) ) ? $configuracion['ordenar'] : 'recientes' );
	$enlace = ((isset($configuracion['enlace']) && !empty($configuracion['enlace']) ) ? $configuracion['enlace'] : 'proyecto' );
	$tamanio = ((isset($configuracion['tamanio']) && !empty($configuracion['tamanio']) ) ? $configuracion['tamanio'] : 'miniatura' );	
	
	$url = ($username) ? sprintf(skemboo_SKEMBOO_RSS_URI, $username) : 'http://feeds.feedburner.com/SkembooProyectosRecientes';	
	
	if (!class_exists('SimplePie')){	
		$file = ABSPATH . WPINC . DIRECTORY_SEPARATOR.'class-feed.php';	
		
		if(!file_exists($file))	return false;		
		
		include_once(ABSPATH . WPINC . DIRECTORY_SEPARATOR.'class-feed.php');	
	}
		
	$feed = new SimplePie();	
	$feed->set_feed_url($url);
	$feed->set_cache_class('WP_Feed_Cache');
	$feed->set_cache_duration(skemboo_CACHE_DURATION);	 
	$feed->set_useragent(skemboo_UA);	
	$feed->init();
	$feed->handle_content_type();
	
	if ($feed){
		$items = $feed->get_items();

		if ($ordenar == 'azar'){
			shuffle($items);				
		}	
		echo '<ol class="imagenes_'.$tamanio.'">';
		$n = 1;


		if (isset($configuracion['nueva_ventana']) && $configuracion['nueva_ventana'] == 1){
			$abrir_en = 'target="_blank"';	
		}
		else{
			$abrir_en = '';
		}
				
		foreach ($items as $item) {
			if ($n<=$limite){

				$link = $item->get_link();
				$title = $item->get_title();
				preg_match("'< \s*img\s.*?src\s*=\s*([\"\'])?(?(1) (.*?)\\1 | ([^\s\>]+))'isx", $item->get_description(), $resultado );
				$img = ( $resultado[3] ) ? $resultado[3] : $resultado[2];
				if($tamanio == 'pequenia'){ 
					$img2 = str_replace('/g_','/m_',$img); 
				}
				else if($tamanio == 'thumb'){ 
					$img2 = str_replace('/g_','/t_',$img); 
				}
				else{ 
					$img2 = str_replace('/g_','/p_',$img);
				}
				
				if($enlace == 'imagen') $link2 = $img;
				else $link2 = $link;
				
				
				
				echo '<li class="proyecto_skemboo_widget"><a href="'.$link2.'" rel="skembox" '.$abrir_en.' ><img src="'.$img2.'" alt="'.$title.'" title="'.$title.'" /></a>';
				if (isset($configuracion['titulos']) && $configuracion['titulos'] == 1){
					echo ' <a href="'.$link.'" '.$abrir_en.' class="titulo_skemboo_widget">'.$item->get_title().'</a>';	
				}
				echo '</li>';
				$n++;
			}
}
		echo '</ol><div style="clear:both"></div>';
		
		if (isset($configuracion['enlace_perfil']) && $configuracion['enlace_perfil']){
			echo '<p class="skemboo_perfil"><a href="http://www.skemboo.com/'.$username.'/" '.$abrir_en.' ><img src="http://www.skemboo.com/'.$username.'/icono/" /> Mi perfil en Skemboo</a></p>';
		}
		unset($items,$feed);
	} 				
}


class skembooWidget extends WP_Widget 
{	
	function skembooWidget() {		
		$options = array('classname' => 'widget_skemboo', 'description' => 'Los proyectos recientes de tu Skemboo' );
        $this->WP_Widget('skemboo','Skemboo',$options);	
    }

    function widget($args, $configuracion) {		
        extract( $args );
        
        $title = apply_filters('widget_title', empty( $configuracion['title'] ) ? 'Skemboo' : $configuracion['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php echo $before_title. $title. $after_title; ?>
               <div id="skemboo-widget">
			   <?php buscar_proyectos($configuracion);?>			   
			   </div>   
              <?php echo $after_widget; ?>
        <?php
    }

    function update($new_instance, $old_instance) {	
    	
		$configuracion = $old_instance;
		$configuracion['title'] = strip_tags($new_instance['title']);
		$configuracion['username'] = strip_tags($new_instance['username']);
 		$configuracion['limite'] = strip_tags($new_instance['limite']);
 		
		if ( in_array( $new_instance['ordenar'], array( 'recientes', 'azar' ) ) ) {
			$configuracion['ordenar'] = $new_instance['ordenar'];
		} else {
			$configuracion['ordenar'] = 'recientes';
		}

		if ( in_array( $new_instance['enlace'], array( 'proyecto', 'imagen' ) ) ) {
			$configuracion['enlace'] = $new_instance['enlace'];
		} else {
			$configuracion['enlace'] = 'proyecto';
		}
		 		
		if ( in_array( $new_instance['tamanio'], array( 'miniatura','thumb','pequenia' ) ) ) {
			$configuracion['tamanio'] = $new_instance['tamanio'];
		} else {
			$configuracion['tamanio'] = 'miniatura';
		}

		$checkbox = array( 'enlace_perfil' => 0, 'titulos' => 0, 'nueva_ventana' => 0);
		foreach ( $checkbox as $field => $val ) {
			if ( isset($new_instance[$field]) ){
				$configuracion[$field] = 1;
			} else {
        $configuracion[$field] = 0;
			}	
		}		
 		
		return $configuracion;
    }
    
    
    function form($configuracion) {	
    	$deb = $configuracion;
    	$default_options = array('title' =>'Mi Skemboo','username' => '', 'limite' => 6,
		'tamanio'=>'miniatura','ordenar'=>'ultimos');
    	$configuracion = wp_parse_args( (array) $configuracion, $default_options );
        ?>
            <p>
            	<?php self::_crear_inputs($configuracion,'title','T&iacute;tulo:'); ?>			
			</p>
            <p>
            	<?php self::_crear_inputs($configuracion,'username','Nombre de usuario:'); ?>			
				<br /><small>http://www.skemboo.com/<strong>usuario</strong></small>
			</p>
            <p>
            	<?php self::_crear_inputs($configuracion,'limite','Numero de proyectos:'); ?>
            	<br /><small>max 10</small>			
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('tamanio'); ?>">Tama&ntilde;o de imagenes:</label>
				<select name="<?php echo $this->get_field_name('tamanio'); ?>" id="<?php echo $this->get_field_id('tamanio'); ?>" class="widefat" size="2"  style="height:66px">
					<option value="miniatura" <?php selected( $configuracion['tamanio'], 'miniatura' ); ?>>Miniaturas cuadradas 64x64</option>
					<option value="thumb" <?php selected( $configuracion['tamanio'], 'thumb' ); ?>>Miniaturas irregulares</option>
					<option value="pequenia" <?php selected( $configuracion['tamanio'], 'pequenia' ); ?>>Rectangular 200x150</option>
				</select>
			</p>				
			<p>
				<label for="<?php echo $this->get_field_id('ordenar'); ?>">Ordenar por:</label>
				<select name="<?php echo $this->get_field_name('ordenar'); ?>" id="<?php echo $this->get_field_id('ordenar'); ?>" class="widefat" size="3"  style="height:44px">
					<option value="recientes"<?php selected( $configuracion['ordenar'], 'recientes' ); ?>>M&aacute;s recientes</option>
					<option value="azar"<?php selected( $configuracion['ordenar'], 'azar' ); ?>>Al azar</option>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('enlace'); ?>">Enlace en las imagenes:</label>
				<select name="<?php echo $this->get_field_name('enlace'); ?>" id="<?php echo $this->get_field_id('enlace'); ?>" class="widefat" size="3"  style="height:44px">
					<option value="proyecto"<?php selected( $configuracion['enlace'], 'proyecto' ); ?>>Hacia el proyecto en Skemboo</option>
					<option value="imagen"<?php selected( $configuracion['enlace'], 'imagen' ); ?>>Hacia la imagen grande</option>
				</select>
				<br /><small>Enlazar la imagen puede ser usado en lightbox</small>
			</p>	
			<div id="skemboo-moreoptions">				
			<p>
			<input class="checkbox" type="checkbox" <?php checked($configuracion['titulos'], true) ?> id="<?php echo $this->get_field_id('titulos'); ?>" name="<?php echo $this->get_field_name('titulos'); ?>" />
			<label for="<?php echo $this->get_field_id('titulos'); ?>">Mostrar t&iacute;tulo de los proyectos</label>
			</p>
			
			<p>
				<input class="checkbox" type="checkbox" <?php checked($configuracion['nueva_ventana'], true) ?> id="<?php echo $this->get_field_id('nueva_ventana'); ?>" name="<?php echo $this->get_field_name('nueva_ventana'); ?>" />
				<label for="<?php echo $this->get_field_id('nueva_ventana'); ?>">Abrir enlaces en una nueva ventana</label>
			</p>

			
			<p>
				<input class="checkbox" type="checkbox" <?php checked($configuracion['enlace_perfil'], true) ?> id="<?php echo $this->get_field_id('enlace_perfil'); ?>" name="<?php echo $this->get_field_name('enlace_perfil'); ?>" />
				<label for="<?php echo $this->get_field_id('enlace_perfil'); ?>">Agregar enlace a tu perfil</label>
			</p>
			</div>								
        <?php 
    }
    
	function _crear_inputs($configuracion,$username,$label='label',$type='text',$classname="widefat"){
		
		echo '<label for="'.$this->get_field_id($username).'">'.$label.'</label>';
		echo '<input class="'.$classname.'" id="'.$this->get_field_id($username).'" name="'.$this->get_field_name($username).'" type="'.$type.'" value="'.esc_attr($configuracion[$username]).'" />';
	}    

}

add_action('widgets_init', create_function('', 'return register_widget("skembooWidget");'));

if (is_active_widget('skembooWidget',false,'skemboo')){
	wp_enqueue_style(skemboo_META);
}

?>