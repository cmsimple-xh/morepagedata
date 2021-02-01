<?php
/**
 * Morepagedata - module morepagedata_view
 *
 * Creates the user menu
 *
 * @author svasti svasti@svasti.de
 */

if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


function morepagedata_view($page){
	global $cf, $c, $cl, $h, $hjs, $bjs, $l, $plugin_cf, $plugin_tx, $pth, $sl, $sn, $su, $tx, $u, $pd_router, $txc;

    $filename = $pth['folder']['plugins'].'morepagedata/config/config2.php';
    if (!is_readable($filename) || !is_array($mpd = json_decode(file_get_contents($filename),true))) {
        return '<p>'.$plugin_tx['morepagedata']['pagedata_nothing'].'</p>';
    }

    // load js color picker -- if needed
    if(in_array('color_picker',$mpd['type'])) {
        $hjs .= '<script type="text/javascript" src="'.$pth['folder']['plugins'].'morepagedata/js/jscolor/jscolor.js"></script>';
    }

    // load js for autogrowing input field -- if needed
    if(in_array('input_field',$mpd['type'])) {
        $bjs .= '<script src="'.$pth['folder']['plugins'].'morepagedata/js/autogrow.js" type="text/javascript"></script>';
    }

    // load slideshow image picker -- if needed
    $slideshow_key = array_search('slide_show',$mpd['type']);
    if($slideshow_key !== FALSE) {
        $hjs .= '<script type="text/javascript">
            function addfile(text) {
                var newtext = text;
                document.morepagedata.' . $mpd['var'][$slideshow_key] . '.value += newtext;
            }
        </script>';
    }


  	$help_icon = tag('img src="'.$pth['folder']['plugins']. 'morepagedata/images/help.png" alt="" class="helpicon"');

    $usedtemplate = isset($txc['subsite']['template']) && $txc['subsite']['template']? $txc['subsite']['template'] :$cf['site']['template'];

    //all variable fields set in morepagedata plugin config are gone through
    $t = '';
	foreach($mpd['var'] as $key => $field){

        // check if field is only for specific templates
        $templatecheck = $mpd['template'][$key]? explode(',', $mpd['template'][$key]):array();
        if(!$mpd['template'][$key] || in_array($usedtemplate,$templatecheck)) {

            switch ($mpd['type'][$key]) {

                case 'color_picker':
                    $t .= tag('input type="text"  class="color" name="'
                       .  $field
                       . '" id="'
                       . $field
                       . '" value="'
                       .  $page[$field]
                       . '"');
            	break;
                case 'input_field':

                    $t .=  '<div id="mpd"><div class="expandingArea active"><pre><span></span>'
                       .  tag('br')
                       .  '</pre>'
                       .  '<textarea style="width: 100%;" name="'
                       .  $field
                       .  '" id="'
                       .  $field
                       .  '">'
                       .  $page[$field]
                       .  '</textarea>'
                       .  '</div></div>';
            	break;

                case 'select_hiddenpage':

                    $pages_select = '';
                    $x = 0;
                    $pd = $pd_router->find_all();
                    for ($i = 0; $i < $cl; $i++) {
                        $levelindicator = '';
                        for ($j=1;$j<$l[$i];$j++) {$levelindicator .= '&ndash;&nbsp;';}
                        if($pd[$i]['linked_to_menu']=='0') {
                            $selectedpage = $levelindicator.$h[$i];
                        	$selected = '';
                        	if($page[$field] == $h[$i]) {$selected = ' selected'; $x++;}
                        	$pages_select .= "\n".'<option value="' .$h[$i] . '"'. $selected.'> &nbsp; '.$selectedpage.'</option>';
                        }
                    }
                    $t .= '<select name="' . $field . '" >'
                       .  "\n"
                       . '<option value=""> &nbsp; '
                       . $plugin_tx['morepagedata']['default_selection']
                       . ' &nbsp; </option>'
                       . $pages_select
                       .  "\n"
                       . '</select>';
            	break;

                case 'template_image':
                case 'image_folder':

                    $path = $mpd['type'][$key]=='template_image'
                          ? $pth['folder']['template'].$plugin_cf['morepagedata']['path_template_images']
                          : $pth['folder']['base'].$plugin_cf['morepagedata']['path_image_folder'];

                    if(is_dir($path)){

                        $handle=opendir($path);
                        $images = array();
                        while (false !== ($file = readdir($handle))) {
                            if($file != "." && $file != "..") {
                                $images[] = $file;
                            }
                        }
                        closedir($handle);
                        natcasesort($images);
                        $images_select = '';
                        foreach($images as $file){
                        	$selected = '';
                        	if($page[$field] == $file) {$selected = ' selected';}
                        	$images_select .= "\n" . '<option  value="'. $file . '"' . $selected . '> &nbsp; ' . $file . ' &nbsp; </option>';
                        }
                    } else $images_select='';

                    $t .= "\n" . '<select name="' . $field . '" >'
                       .  "\n" . '<option value=""> &nbsp; ' . $plugin_tx['morepagedata']['default_selection'] . ' &nbsp; </option>'
                       .  "\n" . $images_select
                       .  "\n" . '</select>';
            	break;

                case 'slide_show':
                    $t .=  "\n" . ' <label for = "' . $field . '">' .  $mpd['display'][$key] . '</label>' . "\n".tag('br');
                    $t .= '<textarea class="mpd_slideshow" name="' . $field . '">'
                       .  $page[$field] . '</textarea>'
                       .  "\n" . tag('br');

                    $handle=opendir($pth['folder']['base'].trim($plugin_cf['morepagedata']['slideshow_images_path'],'./'));
                    $images = array();
                    if ($handle) {
                        while (false !== ($file = readdir($handle))) {
                            $fileend = substr($file, -4, 4);
                            if($file != '.'
                                && $file != '..'
                                && !is_dir($pth['folder']['base'].trim($plugin_cf['morepagedata']['slideshow_images_path'],'./') . '/' . $file)
                                && ($fileend == '.jpg' || $fileend == '.gif' || $fileend == '.png'))
                            {
                                $images[] = $file;
                            }
                        }
                    }
                    closedir($handle);
                    natcasesort($images);
                    foreach ($images as $key2=>$value2) {
                        list($width,$height) = getimagesize($pth['folder']['base']
                                             . trim($plugin_cf['morepagedata']['slideshow_images_path'],'./')
                                             . '/'
                                             . $value2);
                        if($width > 100 && $height > 100) {
                            if($width >= $height) {
                                $width = $width * 100 / $height;
                                $height = 100;
                            } 
                            if($height > $width) {
                                $height = $height * 100 / $width;
                                $width = 100;
                            }
                        }
                        
                    	$images[$key2] = '<a href="javascript:;"  onMouseDown="addfile(\''
                                       . $value2.',\');" class="mpd_imagelist">'
                                       . $value2
                                       . '<img src="'
                                       . $pth['folder']['base']
                                       . trim($plugin_cf['morepagedata']['slideshow_images_path'],'./')
                                       . '/'
                                       . $value2
                                       . '" height="'
                                       . $height
                                       . '" width="'
                                       . $width
                                       . '"></a> ';
                    }
                    $mpd_imagelist = implode(' ',$images);
                    $t .= "\n"
                        . $plugin_tx['morepagedata']['select_from']
                        . "\n"
                        . tag('br')
                        .  "\n"
                        . '<small>'
                        . $mpd_imagelist
                        . '</small>';
            	break;

                case 'checkbox':
                    @$checked =($page[$field] == '1')? ' checked':'';
                    $t .= "\n\t\t"
                       .  tag('input type="hidden" name="'.$field.'" value="0"')
                       .  tag('input type="checkbox" name="'.$field.'" value="1"' . $checked);
            	break;

                case 'option_list':
                case 'plugin_call':
                    $options = explode('|', $mpd['options'][$key]);
                    $options_select = '';
                    foreach($options as $j=>$value){
                    	$selected = '';
                        if($value && $j % 2 == 0) {
                        	if($page[$field] == $value) {$selected = ' selected';}
                            if(!$options[($j+1)]) $options[($j+1)] = $value;
                        	$options_select .= "\n"
                                             . '<option value="'
                                             . $value
                                             . '"'
                                             . $selected
                                             . '> &nbsp; '
                                             . $options[($j+1)]
                                             . ' &nbsp; </option>';
                        }
                    }
                    $t .= '<select name="'
                       . $field
                       . '" >'
                       .  "\n"
                       . '<option value=""> &nbsp; '
                       . $plugin_tx['morepagedata']['default_selection']
                       . ' &nbsp; </option>'
                       .  "\n"
                       . $options_select
                       .  "\n"
                       . '</select>';
            	break;
            }

    		if($mpd['type'][$key] == 'color_picker' || $mpd['type'][$key] == 'slide_show') {
                $t .= "\n"
                   .  '<a class="pl_tooltip" href="#">'
                   .  $help_icon
                   .  '<span>'
                   .  $plugin_tx['morepagedata']['hint_'
                   .  $mpd['type'][$key]]
                   . '</span></a>';
            }
    		if($mpd['help'][$key]) {
                $t .= "\n"
                   .  '<a class="pl_tooltip" href="#">'
                   .  $help_icon
                   . '<span>'
                   .  $mpd['help'][$key]
                   . '</span></a>';
            }
            $t .= ($mpd['type'][$key] != 'slide_show')
               ? "\n" . ' <label for = "' . $field . '">'. $mpd['display'][$key] . '</label>' . "\n".tag('br')
               : "\n".tag('br');
            $t .= $mpd['hr'][$key] && $mpd['br'][$key]
               ? "\n" . tag('br') . tag('hr')
               : ($mpd['hr'][$key]
               ? "\n" . tag('hr')
               : '');
            $t .= $mpd['br'][$key]? tag('br') : '';
        }
	}
    if(!$t) return '<p>'.$plugin_tx['morepagedata']['pagedata_nothing'].'</p>';

    $view  = "\n\n<!-- MorePageData Plugin -->\n";
	$view .= "\n".'<form action="'.$sn.'?'.$su.'" method="post" id="morepagedata" name="morepagedata">';
	$view .= "\n".'<p><b>'.$plugin_tx['morepagedata']['pagedata_title'].'</b></p>' . "\n";
    $view .= $t;
	$view .= "\n\t".tag('input name="save_page_data" type="hidden"');
	$view .= "\n\t".'<div style="text-align: right;">';
	$view .= "\n\t\t".tag('input type="submit" value="'.ucfirst($tx['action']['save']).'"').tag('br');
	$view .= "\n\t".'</div>';
	$view .= "\n".'</form>';


	return $view;
}
?>
