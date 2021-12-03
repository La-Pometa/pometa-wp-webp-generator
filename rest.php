<?php



function content_render_thumbnail_source($object, $field_name, $request) {

		$image_id = get_array_value($object,"featured_media",false);
		$sizes=false;

		if ( $image_id) {
			$data = wp_get_attachment_metadata($image_id);

			if ( !$data ){
				/*- La imatge no existeix -*/

				$image_not_found = apply_filters("wpheadless/rest/image/not-found",false, array("object"=>$object,"request"=>$request));
				if ( !$image_not_found) {
					return false;

				}
				$image_id = $image_not_found;	
				$data = wp_get_attachment_metadata($image_id);
				if ( !$data) {
					return false;
				}
			}

			$data = pometaimages_wp_get_attachment_metadata($data,$image_id,"rest");

			if (isset($data["sizes"])){unset($data["sizes"]);}
			if (isset($data["image_meta"])){unset($data["image_meta"]);}
	

		//	return $data;

			$file = get_array_value($data,"file",false);
			$directory = dirname($file);
			$full_width = get_array_value($data,"width",0);
			$full_height = get_array_value($data,"height",0);
			$full_file = get_array_value($data,"file",0);
			$full_mimetype = get_array_value($data,"mime-type",0);
			
			$data["title"] = get_the_title($image_id);
			$data["alt"] =get_post_meta($image_id, '_wp_attachment_image_alt', true);

			$the_sizes = get_array_value($data,"sizes",array());

			foreach($the_sizes as $size_id => $size_info ) {
				$data["sizes"][$size_id]["source_url"]=pometaimages_get_url($directory).basename(get_array_value($size_info,"file",""));
				unset($data["sizes"][$size_id]["file"]);
			}



			if ( get_array_value(get_array_value($data,"sizes",array()),"full",false)===false) {
				$data["sizes"]["full"]=array(
					"width"=>$full_width,
					"height"=>$full_height,
					"source_url"=>pometaimages_get_url().$full_file,
					"mime-type"=>($full_mimetype?"NOMIMETYPE":"")
				);
			}

			if ( get_array_value(get_array_value($data,"sizes",array()),"full_webp",false)===false) {
				$data["sizes"]["full_webp"]=get_array_value($data["sizes"],"full",array());
				$data["sizes"]["full_webp"]["source_url"]=$data["sizes"]["full"]["source_url"].".webp";
				$data["sizes"]["full_webp"]["mime-type"]="image/webp";
				
			}
			
			$sizes = $data;
		}


		if (isset($sizes["sizes"])){unset($sizes["sizes"]);}
		if (isset($sizes["image_meta"])){unset($sizes["image_meta"]);}

		return $sizes;

}
add_action("init","pometaimages_rest_images");

function pometaimages_rest_images() {


	global $wp_post_types;
	$posttypes = array_keys($wp_post_types);

	if (is_array($posttypes)) {
		foreach ($posttypes as $post => $cpt) {
			if ($cpt == "attachment") {
				continue;
			}

			register_rest_field(
				$cpt,
				'featured_source',
				array(
					'get_callback'    => "content_render_thumbnail_source",
					'update_callback' => null,
					'schema'          => null,
				)
			);

		}
	}

}