<?php
/*
Plugin Name: BingImport
Plugin URI: 
Description: Import images from Bing
Author: Stephan Gerlach
Version: 0.4
Author URI: http://www.computersniffer.com

Copyright (C) 2012  Stephan Gerlach

*/


class BingImport {

	function BingImport( ) {

		add_filter( 'media_upload_tabs', array( &$this, 'create_new_tab') );
		add_filter( 'media_upload_bingimport', array( &$this, 'media_upload_bingimport') );
		add_action('admin_menu', array( &$this, 'addMenu' ) );
		
	}
	
	function addMenu() {
		add_options_page( 'BingImport Settings', 'BingImport Settings','manage_options', 'BingImportOptions', array( &$this, 'BingImportOptions' ) );	
	}
	
	function BingImportOptions() {
		
	
        if (isset($_POST['bingImport_save'])) {
            unset($_POST['bingImport_save']);
          
            $opt = get_option('bingImport_options');
            
            
            if (!(!$opt) || $opt=='') {
                update_option('bingImport_options',$_POST);
            }
           
            else {
                add_option('bingImport_options',$_POST);
            }
            
        }
        
       
        $opt = get_option('bingImport_options');
        if (!$opt) {
            $opt = '';
           $opt['images_per_page'] = 30;
        }
       
		
		echo '<div class="wrap">';
		echo '<div id="icon-options-general" class="icon32"></div><h2>BingImport Settings</h2>';
		echo '<form action="" method="post">';
		echo '<table class="form-table">
<tbody>
<tr valign="top">
<th><label for="bing_api_key">Bing&#0153; API key</label></th>
<td>
<input name="bing_api_key" type="text" id="bing_api_key" value="'.$opt['bing_api_key'].'" class="regular-text"></td>
</tr>

<tr valign="top">
<th><label for="images_per_page">Number of images to display</label></th>
<td><input name="images_per_page" type="text" id="images_per_page" value="'.$opt['images_per_page'].'" class="small-text"> images</td>
</tr>
</tbody></table>';
 		echo '<br /><input type="submit" name="bingImport_save" value="Save" />';
		echo '</form>';
		echo '</div>';
	}
	
	function menu() {
		$page = add_media_page( __( 'Import from Bing&#0153;', 'import-media-from-bing' ), __( 'Import from Bing&#0153;', 'import-media-from-bing' ), 'upload_files', __FILE__, array( &$this, 'page' ) );
		add_action( 'admin_print_scripts-' . $page, array( &$this, 'scripts' ) );
	}
	
	
	function scripts() {
	//	wp_enqueue_script('jquery');
	}
	
	
	function page() {
		echo '<div class="wrap">';
		echo '<h2>' . __( 'Import from Bing&#0153;', 'import-media-from-bing' ) . '</h2>';
		
		self::form();
		echo '</div>';
	}

	function create_new_tab( $tabs ) {
		$tabs['bingimport'] = __( 'Import from Bing&#0153;', 'import-media-from-bing' );
	    return $tabs;
	}
	
	
	
	function media_upload_bingimport() {
	
		    $errors = false;
			if ( isset( $_POST['send'] ) ) {
	
				// Build output
				$html = '';
				$size = $_POST['size'];
				if ( ! empty( $_POST['altsize'] ) )
					$size = explode( ',', $_POST['altsize'] );
	
				foreach( $_POST['srcs'] as $k => $id ) {
					$html .= wp_get_attachment_image( $id, $size );
					$html .= ' ';
				}
				// Return it to TinyMCE
				return media_send_to_editor( $html );
			}
			return wp_iframe( array( &$this, 'media_bingimport_tab_content' ), 'media', $errors );
		
		
	}
	
	function media_bingimport_tab_content( $errors ) {
		global $type;
		

		media_upload_header();
		$post_id = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;

			self::form( array('action' => $form_action_url, 'post_id' => $post_id ) );
	}
	
	
	
	function form( $args = array() ) {
		$action = '';
		$tab = true;
		
		$opt = get_option('bingImport_options');
     	
		$upload_dir = wp_upload_dir();
	
		
		echo '<div style="margin: 1em;">';
		echo '<h3 class="media-title">'. __( 'Import images from Bing&#0153; to the Media Library', 'import-media-from-bing' ) .'</h3>';
		
		if (in_array('Import Image',$_POST)) {
			
			$cc = array_search('Import Image',$_POST);
			
			$upload_dir = wp_upload_dir();
			
			$img = file_get_contents($_POST['img_'.$cc]);
			
			
		//	$parts = explode('/',$_POST['img_'.$cc]);
		//	$fname = array_pop($parts);
			$fname = $_POST['filename_'.$cc];	
			$filename = $upload_dir['path'].'/'.$fname;
			$finame = $upload_dir['url'].'/'.$fname;
			$fh = fopen($filename,'w+');
			fwrite($fh,$img);
			fclose($fh);
			 
			$wp_filetype = wp_check_filetype(basename($filename), null );
			 
			 
			$attachment = array(
				'guid' => $wp_upload_dir['baseurl'] . _wp_relative_upload_path( $filename ), 
     			'post_mime_type' => $wp_filetype['type'],
     			'post_title' => preg_replace('/\.[^.]+$/', '', $_POST['title_'.$cc]),
     			'post_content' => '',
     			'post_status' => 'inherit'
 			);
  			$attach_id = wp_insert_attachment( $attachment, $filename, $_GET['post_id'] );
  			if ($attach_id==0 || $attach_id===false || $attach_id=='') {
  				echo '<div class="error below-h2"><p>Encountered a problem. Please try again.</p></div>';
  			}
  			else {
  				require_once(ABSPATH . 'wp-admin/includes/image.php');
  				$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
  				wp_update_attachment_metadata( $attach_id, $attach_data );
  			
  				echo '<div class="updated below-h2"><p>Image has been successfully imported.</p></div>';
  				
  				
 				self::media_upload_type_form2('bingimport',null,$attach_id);
  				
  			}
  			
  			
		}
		else {
			echo '<form action="" method="post">';
			if (!$opt || trim($opt['bing_api_key'])=='') {
				echo '<div id="message" class="error"><p>Bing&#0153; API Key Missing.</p></div>';
			}
			else {
				echo '<p>Keyword: <input type="text" name="bing-keyword" id="bing-keyword" size="30" value="'.$_POST['bing-keyword'].'" />
				&nbsp;&nbsp;&nbsp;Filter: <select name="bing-filter" id="bing-filter" >';
				$f['All']='';
				$f['Size:Small']='Size:Small';
				$f['Size:Medium']='Medium:Small';
				$f['Size:Large']='Large:Small';
				$f['Aspect:Square']='Aspect:Square';
				$f['Aspect:Wide']='Aspect:Wide';
				$f['Aspect:Tall']='Aspect:Tall';
				$f['Color:Color']='Color:Color';
				$f['Color:Monochrome']='Color:Monochrome';
				$f['Style:Photo']='Style:Photo';
				$f['Style:Graphics']='Style:Graphics';
				$f['Face:Face']='Face:Face';
				$f['Face:Portrait']='Face:Portrait';
				$f['Face:Other']='Face:Other';
				
				foreach ($f as $k=>$v) {
					
					echo '<option value="'.$v.'"';
					if ($_POST['bing-filter']==$v) { 
						echo ' selected="selected" ';
					}
					echo '>'.$k.'</option>';
				}
				

				echo '</select>&nbsp;&nbsp;&nbsp;Adult Filter: <select name="bing-adult" id="bing-adult">';
			
				if (!isset($_POST['bing-adult']) || $_POST['bing-adult']=='') {
					$_POST['bing-adult'] = 'Moderate';
				}
			
				$d['Off'] = 'Off';
				$d['Moderate'] = 'Moderate';
				$d['Strict'] = 'Strict';
				
				foreach ($d as $k=>$v) {
					
					echo '<option value="'.$v.'"';
					if ($_POST['bing-adult']==$v) { 
						echo ' selected="selected" ';
					}
					echo '>'.$k.'</option>';
				}
			

				echo '</select></p>';
		
				echo '<p><input type="hidden" name="submitted-upload-media" />
				<input type="submit" class="button-primary" name="new" value="' . __( 'Find images on Bing&#0153;', 'import-media-from-bing' ) . '"/></p>';
				
			
				if (isset($_POST['bing-keyword']) && trim($_POST['bing-keyword'])!='') {
						$opt = get_option('bingImport_options');
	                    $accountKey = $opt['bing_api_key'];
	                    $ServiceRootURL = 'https://api.datamarket.azure.com/Bing/Search/';
	                    $WebSearchURL = $ServiceRootURL . 'Image?$format=json&Query=';
	                    $context = stream_context_create(array(
	
	                        'http' => array(
	                        'request_fulluri' => true,
	                        'header'  => "Authorization: Basic " . base64_encode($accountKey . ":" . $accountKey)
	
	                        )
	
	                    )); 
	
	                    $request = $WebSearchURL . trim(urlencode( '\'' . $_POST["bing-keyword"] . '\''));
	                    $request .='&$top='.trim($opt['images_per_page']).'&Adult='. trim(urlencode( '\'' . $_POST["bing-adult"] . '\''));
	                    
	                    if ($_POST['bing-filter']!='') {
	                    	$request .='&ImageFilters='. trim(urlencode( '\'' . $_POST["bing-filter"] . '\''));	
	                    }
	                    
	                    if (isset($_POST['new'])) {
	                    	$_POST['offset'] = 0;
	                    
	                    }
	                    else if (isset($_POST['pagenav']) ) {
	                    	if ($_POST['pagenav']=='Next Page') {
	                    		$_POST['offset'] += $opt['images_per_page'];
	                    		$_POST['page']++;
	                    	}
	                    	if ($_POST['pagenav']=='Previous Page') {
	                    		$_POST['offset'] -= $opt['images_per_page'];
	                    		$_POST['page']--;
	                    	}
	                    }
	                    
	                    if (isset($_POST['offset']) && $_POST['offset']>0) {
	                    	$request .='&$skip='.$_POST['offset'];	
	                    }
	                    
	                    
	                    
						if (function_exists ( curl_init ) ) {
     						$ch = curl_init();

        					curl_setopt ($ch, CURLOPT_URL, $request);
        					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        					curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
        					curl_setopt ($ch, CURLOPT_HTTPHEADER, array ('Authorization: Basic '. base64_encode($accountKey . ':' . $accountKey)));
 
    				    	$response = curl_exec ($ch);
        
        					curl_close ($ch);
						}
						else {
   							$response = file_get_contents($request, 0, $context);
						}
	                    $jsonobj = json_decode($response);

			   if (strstr($response,'Unauthorized: Access is denied due to invalid credentials')) {

				echo '<div id="message" class="error"><p>Unauthorized: Access is denied due to invalid credentials.</p><p>Please check your API Key.</div>';
                           }
                           else {
                        echo '<p>';
				
						if ($_POST['offset']>0) {
							echo '<input type="submit" value="Previous Page" name="pagenav" />';
						}
				
						echo '<input type="submit" value="Next Page" name="pagenav" />';
			
						echo '</p>'; 
	                    echo '<table>';
						$counter = 0;
						$cc = 0;
	                    foreach($jsonobj->d->results as $value) {                       
							$cc++;
							if ($counter ==0) { echo '<tr>';}
							
	                        echo '<td width="25%" style="text-align: center; vertical-align: bottom;">';
	                        echo'<img src="'.$value->Thumbnail->MediaUrl.'" title="'.$value->Title.'" width="'.$value->Thumbnail->Width.'" height="'.$value->Thumbnail->Height.'" ><br />
	                        <div style="text-align: left;">
	                        Dimension: '.$value->Width.'px x '.$value->Height.'px<br />
	                        Size: ';
	                        if ($value->FileSize < 1028) { 
	                        	$value->FileSize.' B';
	                        }
	                        else {
	                        	$value->FileSize = $value->FileSize/1028;
	                        	
		                        if ($value->FileSize < 1028) { 
		                        	echo  number_format($value->FileSize,0).' KB';
		                        }
		                        else {
		                        	$value->FileSize = $value->FileSize/1028;
		                        	echo number_format($value->FileSize,2).' MB';
		                        }	
	                        }
	                        echo '<br />
	                        Image Type: ';
	                        $parts = explode('/',$value->ContentType);
	                        echo strtoupper($parts[1]);
	                        echo '<br />Source: ';
	                        $parts = explode('//',$value->SourceUrl);
	                        $parts = explode('/',$parts[1]);
	                        echo $parts[0];
	                        $fp = explode('/',$value->MediaUrl);
	                        
	                        echo '<br />Filename: <input type="text" name="filename_'.$cc.'" value="'.urldecode(array_pop($fp)).'" />';
	                        $find = array('"','\'');
	                        $with = array('','');
	                        echo '</div><br style="clear: both;" />
	                        <input type="hidden" value="'.$value->MediaUrl.'" name="img_'.$cc.'"/>
	                        <input type="hidden" value="'.str_replace($find,$with,$value->Title).'" name="title_'.$cc.'"/>
	                        <input type="submit" value="Import Image" name="'.$cc.'"/>
	                        </td>';
	                        
	                        $counter++;
	                        if ($counter==3) { echo '</tr>';$counter=0;}
	
	                    }
	                    echo'</table><br style="clear: both;" /> ';
	                    echo '<input type="hidden" value="';
	                    if (!isset($_POST['offset'])) { echo '0'; }
	                    else { echo $_POST['offset'];}
	                    
	                    echo '" name="offset" />';
	                   
						if ($_POST['offset']>0) {
							echo '<input type="submit" value="Previous Page" name="pagenav" />';
						}
						
						echo '<input type="submit" value="Next Page" name="pagenav" />';
	                }
			}
			
				}

				echo '</form>';	
			}
			echo '</div>';
		}
		
		
		
		
		function media_upload_type_form2($type = 'file', $errors = null, $id = null) {





	$post_id = isset( $_REQUEST['post_id'] )? intval( $_REQUEST['post_id'] ) : 0;



	$form_action_url = admin_url("media-upload.php?type=$type&tab=type&post_id=$post_id");
	$form_action_url2 = admin_url("media-upload.php?type=file&tab=type&post_id=$post_id");
	$form_action_url = apply_filters('media_upload_form_url', $form_action_url, $type);

?>



<form enctype="multipart/form-data" method="post" action="<?php echo esc_attr($form_action_url2); ?>" class="media-upload-form type-form validate" id="<?php echo $type; ?>-form">

<?php submit_button( '', 'hidden', 'save', false ); ?>

<input type="hidden" name="post_id" id="post_id" value="<?php echo (int) $post_id; ?>" />

<?php wp_nonce_field('media-form'); ?>

<script type="text/javascript">

//<![CDATA[

jQuery(function($){

	var preloaded = $(".media-item.preloaded");
	if ( preloaded.length > 0 ) {
		preloaded.each(function(){prepareMediaItem({id:this.id.replace(/[^0-9]/g, '')},'');});
	}

	updateMediaForm();

});

//]]>

</script>

<div id="media-items">

<?php

if ( $id ) {

	if ( !is_wp_error($id) ) {

		add_filter('attachment_fields_to_edit', 'media_post_single_attachment_fields_to_edit', 10, 2);

		echo get_media_items( $id, $errors );

	} else {

		echo '<div id="media-upload-error">'.esc_html($id->get_error_message()).'</div>';

		exit;

	}

}

?>

</div>


</form>

<?php

}

}
new BingImport();