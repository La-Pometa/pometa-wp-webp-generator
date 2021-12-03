<?php


add_filter('get_post_metadata', 'pometaimages_wpbakery_post_meta_background_images',100,4);

function pometaimages_wpbakery_post_meta_background_images($meta_value, $post_id, $meta_key, $single ) {

    if($meta_key != '_wpb_shortcodes_custom_css')  {
        return $meta_value;

    }


    $create_image_if_not_exists=apply_filters("pometaimages/vendors/js_composer/background/create-webp-if-not-exists",false);

    // Detectar si hi ha imatge de fondo, i modificar el style per afegir la versió amb webp

    $meta_type = 'post';
    $meta_cache = wp_cache_get($post_id, $meta_type . '_meta');
    if ( !$meta_cache ) {
        $meta_cache = update_meta_cache( $meta_type, array( $post_id ) );
        $meta_cache = $meta_cache[$post_id];
    }

    if ( isset($meta_cache[$meta_key]) ) {
        if ( $single ) {
            $meta_value = maybe_unserialize( $meta_cache[$meta_key][0] );
        } else {
            $meta_value = array_map('maybe_unserialize', $meta_cache[$meta_key]);
        }
    }




    $str = "background-image: url(";

    if ( strpos($meta_value,$str)!==false) {

        // Eliminar "?id=" de les images
        $meta_value = preg_replace('/(url\(\S+\.(jpg|jpeg|png|svg|gif))\?id=\d+(\))/', '$1$3', $meta_value);

        $css = parseCss($meta_value);

        $default=array(
            "background-image"=>array("selector"=>"url(","apply"=>array("duplicate_css")),
            "background"=>array("selector"=>"url(","apply"=>array("duplicate_css")),
        );
        $possible_attribute = apply_filters("pometaimages/vendors/js_composer/background/attributes",$default);
        $possible_attribute_keys = array_keys($possible_attribute);

        foreach($css as $selector => $rules) {
            foreach($rules as $attribute => $value ) {
                if ( in_array($attribute,$possible_attribute_keys)) {
                    $css[$selector]["check"]=1;
                }
            }
        }

        $final_css = array();


        foreach($css as $selector => $rules) {
            $check = get_array_value($rules,"check",false);

            if ( !$check ) {
                $final_css[$selector]=$rules;
                continue;
            }
            unset($rules["check"]);


            $formats = pometaimages_formats();
            $exts="";$ext_from=$ext_to=array();
            if ( $formats) {
                foreach($formats as $ext) {
                    $exts.=($exts?"|":"").$ext;
                    $ext_from[]=".".$ext;
                    $ext_to[]=".".$ext.".webp";
                }
            }
            $preg = "/(url\(\S+\.('".$exts."'))\\";
            

            foreach($rules as $attribute => $value ) {
                if ( !in_array($attribute,$possible_attribute_keys)) {
                    continue;
                }
                if ( in_array($attribute,$possible_attribute_keys)) {
                    $apply = get_array_value(get_array_value($possible_attribute,$attribute,array()),"apply",array());

                    if ( $create_image_if_not_exists ) {
                        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $value, $match);
                        if ( count($match)){
                            pometaimages_create_if_not_exists($match[0][0]);
                        }


                    }

                    foreach($apply as $action) {
                        switch($action) {
                            case "duplicate_css":
                                if ( $exts ) {
                                    $webpselector = "html.webp-support ".$selector;
                                    $defselector = "html:not(.webp-support) ".$selector;
                                    $final_css[$defselector]=$rules;
                                    $final_css[$webpselector]=$final_css[$defselector];
                                    $final_css[$webpselector][$attribute]= str_replace($ext_from, $ext_to, $final_css[$webpselector][$attribute]);
                                    unset($rules[$selector]);
                                }
                                break;
                            case "duplicate_url":
                                $final_css[$selector]=$rules;
                                $final_css[$selector][$attribute]= str_replace($ext_from, $ext_to, $final_css[$selector][$attribute]).", ".$final_css[$selector][$attribute];
                                $final_css[$selector][$attribute]=str_replace("!important","",$final_css[$selector][$attribute])."!important";

                                break;

                        }
                    }


                }



            }
        }

        $css_string = "";
        foreach($final_css as $selector => $rules) {
            $css_string .= $selector."{";
            foreach($rules as $attribute => $value ) {
                $css_string.=$attribute.":".$value.";";
            }
            $css_string.="}";
        }
        $meta_value = $css_string;

    }


    return $meta_value;
}





function parseCss($string){
    $css = $string;
    preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@,]+)\{([^\}]*)\}/', $css, $arr);
    $result = array();
    foreach ($arr[0] as $i => $x){
        $selector = trim($arr[1][$i]);
        $rules = explode(';', trim($arr[2][$i]));
        $rules_arr = array();
        foreach ($rules as $strRule){
            
            if (!empty($strRule)){
                $rule = explode(":", $strRule);
                if ( count($rule)>2) {$i=2;$l=count($rule);while($i<$l){$rule[1].=":".$rule[$i];$i++;}}
                $rules_arr[trim($rule[0])] = trim($rule[1]);
            }
        }
        
        $selectors = explode(',', trim($selector));
        foreach ($selectors as $strSel){
            $result[$strSel] = $rules_arr;
        }
    }
    return $result;
}

function pometaimages_create_if_not_exists($url) {


    $site_url = site_url()."/";
    $file="";
    $filewebp="";

    if (substr($url,0,strlen($site_url)) == $site_url) {
        $url = str_replace($site_url,"",$url);
        $file = pometaimages_get_path("").$url;

        //@TODO: arreglar url
        $file=str_replace("//","/",$file);
        $file=str_replace("/wp-content/uploads/wp-content/uploads/","/wp-content/uploads/",$file);

        $filewebp = $file.".webp";

    }
    if ( $filewebp && !file_exists($filewebp)){
        if ( file_exists($file)){
            pometaimages_generate_webp_image($file);
        }
    }

}



add_action("wp_head","pometaimages_jscomposer_detect_css_webp",0);


function pometaimages_jscomposer_detect_css_webp() {
    
    ?>
    <script type="text/javascript" rel="webp-support">
    function canUseWebP() {
        var elem = document.createElement('canvas');
        if (!!(elem.getContext && elem.getContext('2d'))) {
            return elem.toDataURL('image/webp').indexOf('data:image/webp') == 0;
        }
        return false;
    }
    if ( canUseWebP()) {
        var root = document.getElementsByTagName( 'html' )[0];
        root.className += ' webp-support';
    }
    </script>
    <?php
}