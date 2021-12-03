<?php


/*-





-*/


add_action("after_setup_theme", 'pometaimages_theme_setup_add_miniimage');           // Afegir format 'mini'
add_filter("wp_get_attachment_image", 'pometaimages_wp_get_attachment_image', 999000, 5);     // retornar html <picture> amb webp
add_filter("pometaimages/html/format/", 'pometaimages_html_format_vue');                      // Determinar format de retorn
add_filter("rest_prepare_attachment", 'pometaimages_filter_attachment_content', 11, 3);    // Rest-api: modificar html <picture>
add_filter("rest_prepare_attachment", 'pometaimages_filter_attachment_sizes', 11, 3);      // Rest-api: modificar sizes 
//  add_filter("pometaimages/get/image"         , 'pometaimages_get_image_filter',50);                  // 
add_filter("wp_generate_attachment_metadata", 'pometaimages_wp_generate_attachment_metadata', 50, 3); // crear versions webp
add_filter("wp_get_attachment_metadata", 'pometaimages_wp_get_attachment_metadata', 20, 2);


add_shortcode('image', 'pometaimges_image_shortcode');
add_filter('image_send_to_editor', 'pometaimages_custom_image_template', 1, 8);



function pometaimages_formats()
{

    $res = array(
        "jpg",
        "jpeg",
        "png"
    );

    return $res;
}


function pometaimges_image_shortcode($atts = array())
{


    $id = get_array_value($atts, "id", false);
    $class = get_array_value($atts, "class", false);
    $size = get_array_value($atts, "size", false);
    $lazy = get_array_value($atts, "lazy", "true");
    $width = get_array_value($atts, "width", false);
    $height = get_array_value($atts, "height", false);
    $format = apply_filters("pometaimages/html/format/", "wordpress");

    $args = array(
        "size" => $size,
        "format" => $format
    );


    if ($width) {
        $args["width"] = $width;
    }
    if ($height) {
        $args["height"] = $height;
    }
    if ($class) {
        $args["class"] = $class;
    }
    $args["lazy"] = $lazy;



    //  $format = "wordpress";

    $html = pometaimages_get_image($id, $args);



    return $html;
}

function pometaimages_custom_image_template($html, $id, $caption, $title, $align, $url, $size, $alt)
{
    /*
    $html - default HTML, you can use regular expressions to operate with it
    $id - attachment ID
    $caption - image Caption
    $title - image Title
    $align - image Alignment
    $url - link to media file or to the attachment page (depends on what you selected in media uploader)
    $size - image size (Thumbnail, Medium, Large etc)
    $alt - image Alt Text
    */

    /*
     * First of all lets operate with image sizes
     */
    list($img_src, $width, $height) = image_downsize($id, $size);
    $hwstring = image_hwstring($width, $height);

    $out = '[image id="' . $id . '" size="' . $size . '"  alt="' . $alt . '" ' . $hwstring . ']';

    return $out; // the result HTML
}


function pometaimages_wp_generate_attachment_metadata($data, $post_id, $from = "")
{
    $data = pometaimages_wp_get_attachment_metadata($data, $post_id, $from = "upload");
    return $data;
}

function pometaimages_theme_setup_add_miniimage()
{
    add_image_size('mini', 140, 140, false);
}


function pometaimages_is_rest()
{

    $is_request = false;
    if (defined('REST_REQUEST') && REST_REQUEST) {
        $is_request = true;
    }

    return $is_request;
}

function pometaimages_wp_get_attachment_image($html, $attachment_id, $size, $icon, $attr)
{
    if (!pometaimages_is_rest()) {
        return $html;
    }

    $format = apply_filters("pometaimages/html/format/", "wordpress");


    if ($format == "vue") {
        return pometaimages_get_image($attachment_id, array("size" => $size, "format" => $format));
    }

    $meta = wp_get_attachment_metadata($attachment_id);

    $srcset = get_array_value($attr, "srcset", "");
    $src = get_array_value($attr, "src", "");
    $alt = get_array_value($attr, "alt", "");
    $sizes = get_array_value($attr, "sizes", "");
    $srcset_webp = "";
    $has_srcset = false;
    $srcset_alt = "";
    $srcset_alt_webp = "";

    $ext_from = array(
        ".png",
        ".jpg",
        ".jpeg",
    );
    $ext_to = array(
        ".png.webp",
        ".jpg.webp",
        ".jpeg.webp",
    );
    $ext_from_space = array(
        ".png ",
        ".jpg ",
        ".jpeg ",
    );
    $ext_to_space = array(
        ".png.webp ",
        ".jpg.webp ",
        ".jpeg.webp ",
    );

    if ($srcset) {
        $has_srcset = true;
        $srcset = str_replace(array(".webp "), array(" "), $srcset);
        $srcset_webp = str_replace($ext_from_space, $ext_to_space, $srcset);
    }

    $src_webp = str_replace($ext_from, $ext_to, $src);


    if (!$has_srcset) {
        $array = explode('.', $src);
        $extension = end($array);
        $srcset_alt = '<source src="' . $src . '" type="image/' . $extension . '">';
        $srcset_alt_webp = '<source src="' . $src_webp . '" type="image/webp">';
    }


    $img = $html;
    $img = preg_replace('/srcset=\\"[^\\"]*\\"/', '', $img);
    $img = preg_replace('/sizes=\\"[^\\"]*\\"/', '', $img);

    //$img_src = preg_match('/src="([^"]*)"/', $img, $matches);
    //$img = preg_replace('/src=\\"[^\\"]*\\"/', "src=\"${matches[1]}.webp\"", $img); 


    $picture = '' .
        '<picture>' .
        ($srcset_webp ? '<source srcset="' . $srcset_webp . '" size="' . $sizes . '">' : '') .
        ($srcset ? '<source srcset="' . $srcset . '" size="' . $sizes . '">' : '') .
        ($srcset_alt_webp ? $srcset_alt_webp : '') .
        ($srcset_alt ? $srcset_alt : '') .
        '' . $img . '' .
        '</picture>' .
        '';

    $html = $picture;


    return $html;
}



$pometaimages_wp_get_attachment_metadata_prevent_state = false;
function pometaimages_wp_get_attachment_metadata_prevent_on()
{
    global $pometaimages_wp_get_attachment_metadata_prevent_state;
    $pometaimages_wp_get_attachment_metadata_prevent_state = true;
}
function pometaimages_wp_get_attachment_metadata_prevent_off()
{
    global $pometaimages_wp_get_attachment_metadata_prevent_state;
    $pometaimages_wp_get_attachment_metadata_prevent_state = false;
}
function pometaimages_wp_get_attachment_metadata_prevent_is()
{
    global $pometaimages_wp_get_attachment_metadata_prevent_state;
    return $pometaimages_wp_get_attachment_metadata_prevent_state;
}


$pometaimages_wp_get_attachment_metadata_path = false;
$pometaimages_wp_get_attachment_metadata_cache_time = 0;

$pometaimages_wp_get_attachment_metadata_cache = array();
function pometaimages_wp_get_attachment_metadata($data, $attachment_id, $from = "")
{

    if (pometaimages_wp_get_attachment_metadata_prevent_is()) {
        //  return $data;
    }

    /*-
    $pometaImages = get_array_value($data,"pometaImages",false);
    if ( $pometaImages) {
        global $pometaimages_wp_get_attachment_metadata_cache_time;
        if ( $pometaimages_wp_get_attachment_metadata_cache_time ) {
            $now = time();
            if ( $pometaImages + $pometaimages_wp_get_attachment_metadata_cache_time > $now ) {
                $pometaImages=false;
            }
        }
    }
    if ( $pometaImages ) {
        return $data;
    }
-*/


    global $pometaimages_wp_get_attachment_metadata_cache;
    $do_cache = apply_filters("pometaimages/wp_get_attachment_metadata/cache/enabled", false);
    if ($do_cache) {
        $cache = get_array_value($pometaimages_wp_get_attachment_metadata_cache, $attachment_id, false);
        if ($cache !== false) {
            //   return $cache;
        }
    }


    $update_metadata = false;
    $sizes = get_array_value($data, "sizes", array());
    $file_main = get_array_value($data, "file", "");
    $width_full = get_array_value($data, "width", "");
    $height_full = get_array_value($data, "height", "");
    $media_type_full = get_array_value($data, "mime-type", "");

    $ofile = pometaimages_get_path(dirname($file_main)) . basename($file_main);
    $wfile = $ofile . ".webp";

    $created = false;
    $file_already_exists = false;

    if (!file_exists($wfile)) {
        if (file_exists($ofile)) {
            $created = pometaimages_generate_webp_image($ofile, 100);
        }
    } else {
        $file_already_exists = true;
    }




    $nsizes = array();
    $size_full_webp = array();
    if ($created || (get_array_value($sizes, "full_webp", false) == false && $file_already_exists)) {
        $size_full_webp["mime-type"] = "image/webp";
        $size_full_webp["file"] = basename($wfile);
        //$size_full_webp["source_url"]=pometaimages_get_url(dirname($file_main).basename($wfile));
        $size_full_webp["width"] = get_array_value($data, "width", 0);
        $size_full_webp["height"] = get_array_value($data, "height", 0);
        $nsizes["full_webp"] = $size_full_webp;
    }



    $append = "_webp";
    $webp_exists = false;
    // echo "<br> Checking SIZES: <pre>".print_r($sizes,true)."</pre>";
    if (is_array($sizes)) {
        foreach ($sizes as $size_id => $size_data) {
            //     echo "<br> Checking [".$size_id."]...";
            if (substr($size_id, strlen($size_id) - strlen($append), strlen($append)) == $append) {
                continue;
            }
            //      echo "<br> Checking(2) [".$size_id."]...";

            $size_id_webp = $size_id . "_webp";
            $created = false;
            if (get_array_value($size_data, "file", false) === false) {
                $size_data["file"] = $file_main;
            }

            $size_data_webp = $size_data;
            $ofile = pometaimages_get_path(dirname($file_main)) . $size_data["file"];
            $wfile = $ofile . ".webp";
            $webp_exists = file_exists($wfile);
            if (!$webp_exists) {
                if (file_exists($ofile)) {
                    $created = pometaimages_generate_webp_image($ofile, 100);
                    $webp_exists = file_exists($wfile);
                }
            }
            //   echo "<br> Checking3 [".($webp_exists?"TRUE":"FALSE")."][".$ofile."]...";



            $nsizes[$size_id] = $size_data;

            if ($webp_exists || $created !== false) {
                $size_data_webp["mime-type"] = "image/webp";
                //$size_data_webp["source_url"]=pometaimages_get_url(dirname($file_main)).$size_data_webp["file"].".webp";

                $size_data_webp["file"] = $size_data_webp["file"] . ".webp";
                $nsizes[$size_id_webp] = $size_data_webp;
            }
        }
    }
    $full = get_array_value($nsizes, "full", false);


    if ($full) {
        $file = get_array_value($full, "file", false);
        if ($file === false) {
            $nsizes["full"]["file"] = basename($file_main);
            $update_metadata = true;
        } else {
            if (basename($file) != $file) {
                $nsizes["full"]["file"] = basename($file_main);
                $update_metadata = true;
            }
        }
    }

    if ($full === false) {
        $nsizes["full"] = array(
            "width" => $width_full,
            "height" => $height_full,
            "file" => basename($file_main),
            //"source_url"=>pometaimages_get_url(dirname($file_main)).basename($file_main),
            "mime-type" => $media_type_full
        );
        $nsizes["full_webp"] = $nsizes["full"];
        // $nsizes["full_webp"]["source_url"]=$nsizes["full"]["source_url"].".webp";
        $nsizes["full_webp"]["mime-type"] = "image/webp";
    }

    /*- Afegir el tamany full_webp -*/



    $data["sizes"] = $nsizes;


    // echo "<br> DATA END <pre>".print_r($data,true)."</pre>";


    if ($update_metadata) {
        wp_update_attachment_metadata($attachment_id, $data);
    }


    /*- Afegir ALT i TITLE -*/


    $data["alt"] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    $data["alt_text"] = $data["alt"];
    unset($data["alt"]);
    $data["title"] = get_the_title($attachment_id);


    /*- Afegir SOURCES_URL -*/

    $sizes = get_array_value($data, "sizes", array());
    $file_full = get_array_value($data, "file", "");



    if (is_array($sizes)) {
        foreach ($sizes as $size_id => $size_data) {
            $file = get_array_value($size_data, "file", "");
            $sizes[$size_id]["source_url"] = pometaimages_get_url(dirname($file_full)) . $file;
            //unset($sizes[$size_id]["file"]);
        }
    }



    $data["sizes"] = $sizes;
    pometaimages_wp_get_attachment_metadata_prevent_on();
    $data = apply_filters("pometaimages/wp_get_attachment_metadata/filter", $data, $attachment_id);
    pometaimages_wp_get_attachment_metadata_prevent_off();
    $pometaimages_wp_get_attachment_metadata_cache[$attachment_id] = $data;
    $data["pometaImages"] = time();


    return $data;
}


$pometaimages_get_image_file_cache = array();
$pometaimages_get_image_file_url = false;
$pometaimages_get_image_file_path = false;


function pometaimages_get_path($directory = "")
{
    $path = "";
    global $pometaimages_get_image_file_path;
    if ($pometaimages_get_image_file_path == false) {
        $i = wp_get_upload_dir();
        $pometaimages_get_image_file_path = get_array_value($i, "basedir", "");
    }
    $path = $pometaimages_get_image_file_path . "/" . $directory . "/";

    return $path;
}

function pometaimages_get_url($directory = "")
{
    $url = "";
    global $pometaimages_get_image_file_url;
    if ($pometaimages_get_image_file_url == false) {
        $i = wp_get_upload_dir();
        $pometaimages_get_image_file_url = get_array_value($i, "baseurl", "");
    }
    $url = $pometaimages_get_image_file_url . "/" . $directory . "/";

    return $url;
}

function pometaimages_get_image($attachment_id, $args = array())
{

    $data = false;
    $image = false;
    $size = get_array_value($args, "size", "mini");
    $format = get_array_value($args, "format", "wordpress");
    $width = get_array_value($args, "width", false);
    $image_alt = get_array_value($args, "alt", false);
    $height = get_array_value($args, "height", false);
    $class = get_array_value($args, "class", "");
    $lazy = get_array_value($args, "lazy", "true");

    $check_size = false;

    if ($size && (empty($width) || empty($height))) {
        $check_size = true;
    }


    $data =  wp_get_attachment_metadata($attachment_id);
    if (!$data) {
        /*         $attachment_id = 14805; */
        $attachment_id = apply_filters("wpheadless/rest/image/not-found", $attachment_id, array("image_id" => $attachment_id));
        $data = wp_get_attachment_metadata($attachment_id);
        $check_size = true;
    }


    $sizes = get_array_value($data, "sizes", array());


    //Comprovar si està en cache
    global $pometaimages_get_image_file_cache;
    $cache = get_array_value($pometaimages_get_image_file_cache, $attachment_id . "_" . $format, false);
    if ($cache !== false) {
        //   return $cache;
    }

    //   echo "<br> SIZES(".$attachment_id."): <pre>".print_r($data,true)."</pre>";

    //echo "<br> Rendering format...[".$format."]";

    if ($format == "vue") {
        if (!$width || !$height) {
            $width = get_array_value($data, "width", 200);
            $height = get_array_value($data, "height", 600);
        }
        $width_full = $width;
        $height_full = $height;
        $file_full = get_array_value($data, "file", 0);
        $media_type_full = get_array_value($data, "mime-type", 0);


        if ($image_alt === false) {
            $image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        }

        $image_title = get_the_title($attachment_id);
        $image_class = false;

        $args = array(
            "id" => $attachment_id,
            "title" => $image_title,
            "alt" => $image_alt,
        );

        $sizes_array = $sizes;


        /*- Afegir 'source_url', treure 'file' -*/

        foreach ($sizes_array as $size_id => $size_info) {
            if ($check_size && $size == $size_id) {
                $width = get_array_value($size_info, "width", -1);
                $height = get_array_value($size_info, "height", -1);
            }

            $file = get_array_value($size_info, "file", false);
            $sizes_array[$size_id]["source_url"] = pometaimages_get_url(dirname($file_full)) . basename($file);
            unset($sizes_array[$size_id]["file"]);
        }



        /*- Afegir Full & Full_WebP -*/

        if (get_array_value($sizes_array, "full", false) === false) {
            $sizes_array["full"] = array(
                "width" => $width_full,
                "height" => $height_full,
                "source_url" => pometaimages_get_url() . $file_full,
                "mime-type" => $media_type_full
            );
        }

        if (get_array_value($sizes_array, "full_webp", false) === false) {
            $sizes_array["full_webp"] = $sizes_array["full"];
            $sizes_array["full_webp"]["source_url"] = $sizes_array["full"]["source_url"] . ".webp";
            $sizes_array["full_webp"]["mime-type"] = "image/webp";
        }

        if (!$height) {
            $height = 800;
        }
        if (!$width) {
            $width = 500;
        }


        /*- Retornar Etiqueta + Tamanys -*/

        $data_string = json_encode($sizes_array);
        $datos = wp_get_attachment_metadata($attachment_id);
        $datos["sizes"] = $sizes_array;

        $datos = apply_filters("pometaimages/wp_get_attachment_metadata/filter", $datos, $attachment_id);


        unset($datos["sizes"]);
        unset($datos["image_meta"]);
        $datos = json_encode($datos);

        // $image = '<responsive-image class="'.$class.'" :width="'.$width.'" :height="'.$height.'"  :src-id="'.$attachment_id.'" title="'.$image_title.'"  alt="'.$image_alt.'" :sizes=\''.$data_string.'\'></responsive-image>';
        $image = "<responsive-image class='" . $class . "' size='" . $size . "' :lazy='" . $lazy . "' :image-data='" . $datos . "'></responsive-image>";
    } else {

        $image = wp_get_attachment_image($attachment_id, $size);
    }






    //$pometaimages_get_image_file_cache[$attachment_id."_".$format]=$image;

    return $image;
}
/*-
function pometaimages_get_image_filter($attachment_id, $params = array()) {
    $attachment_id = get_array_value($params,"id",false);
    $size = get_array_value($params,"size","thumbnail");
    $format = get_array_value($params,"format","wordpress");

    return pometaimages_get_image($attachment_id,$params);


   // return pometaimages_get_image($attachment_id,array("size"=>$size,"format"=>$format));
}
-*/



add_action("wp_footer", "wp_footer_pometa_images");

function wp_footer_pometa_images()
{


    $image = 14834;
    $format = apply_filters("pometaimages/html/format/", "wordpress");

    echo pometaimages_get_image($image, array("size" => "full", "format" => $format));
}

function pometaimages_filter_attachment_sizes($data, $post, $context)
{

    $media_details = get_array_value($data->data, "media_details", array());
    $sizes = get_array_value($media_details, "sizes", array());
    $full_webp =  get_array_value($sizes, "full_webp", false);


    if (!$full_webp) {
        $full =  get_array_value($sizes, "full", false);
        $file = get_array_value($full, "file", false);
        $source_url = get_array_value($full, "source_url", false);
        $file = $file . ".webp";
        $source_url = $source_url . ".webp";
        $data->data["media_details"]["sizes"]["full_webp"] = $full;
        $data->data["media_details"]["sizes"]["full_webp"]["file"] = $file;
        $data->data["media_details"]["sizes"]["full_webp"]["mime-type"] = "image/webp";
        $data->data["media_details"]["sizes"]["full_webp"]["source_url"] = $source_url;
    }

    return $data;
}

function pometaimages_filter_attachment_content($data, $post, $context)
{

    $format = apply_filters("pometaimages/html/format/", "wordpress");
    $image_id = get_array_value($data->data, "id", false);

    if ($image_id) {
        $html = pometaimages_get_image($image_id, array("size" => "full", "format" => $format));
        $data->data["content"]["rendered"] = $html;
    }

    return $data;
}

function pometaimages_html_format_vue($format)
{
    return "vue";
}
