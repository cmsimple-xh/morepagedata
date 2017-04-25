<?php
/**
 * Morepagedata
 *
 * @author svasti svasti@svasti.de
 */

// Check if PLUGINLOADER is calling
if(!defined('PLUGINLOADER')) {
	die('Plugin '. basename(dirname(__FILE__)) . ' requires a newer version of the Pluginloader. No direct access.');
}

// json function for php 4, supplied by cmb
if (!function_exists('json_encode')) {
    // make sure the class wasn't already included by another plugin
    if (!class_exists('CMB_JSON')) {
	include_once $pth['folder']['plugins'] . 'morepagedata/json.php';
    }
    function json_encode($value)
    {
	$json = CMB_JSON::instance();
	return $json->encode($value);
    }
    function json_decode($string)
    {
	$json = CMB_JSON::instance();
	return $json->decode($string);
    }
}

/**
 * Function provided by cmb
 * for Compatibility with pre 1.6 XH versions
*/
function getPageDataFields()
{
    global $pd_router;

    if (method_exists($pd_router, 'storedFields')) {
        return $pd_router->storedFields();
    } else {
        return $pd_router->model->params;
    }
}


// Get the field arrays
$mpd = is_file($pth['folder']['plugins'].'morepagedata/config/config2.php')
    ? json_decode(file_get_contents($pth['folder']['plugins'].'morepagedata/config/config2.php'),true)
    : array('var'=>array());

// Add used interests to router
foreach ($mpd['var'] as $key=>$value) {
    if ($value) $pd_router -> add_interest($value);
}

// tab for admin-menu
$pd_router -> add_tab($plugin_tx['morepagedata']['pagedata_tab'], $pth['folder']['plugins'].'morepagedata/morepagedata_view.php');


// Set morepagedata contents.
$mpd_imagelist = $slideshoweffect = $mpd_file = $mpd_plugin = '';

foreach ($mpd['var'] as $key=>$value) {
    if (isset($pd_current[$value]) && $pd_current[$value]){
        $$value = $pd_current[$value];
        if ($mpd['type'][$key] == 'plugin_call') $mpd_plugin = $$value;
        if ($mpd['type'][$key] == 'slide_show') $mpd_imagelist = $$value;
    }
}

// add a function call to a page
if ($mpd_plugin && (!$adm OR ($adm && !$edit))) $c[$pd_s] .=  '{{{PLUGIN:'.$mpd_plugin.';}}}';

// in case internal slideshow is wanted
if ($plugin_cf['morepagedata']['slideshow_use_internal_slideshow']) {
    // Make default slide show if defined
    if (!$mpd_imagelist && $plugin_cf['morepagedata']['slideshow_default_images']) {
        list($mpd_slideShowVar,$mpd_imagelist) = explode('=',$plugin_cf['morepagedata']['slideshow_default_images']);
        $mpd_slideShowVar = trim($mpd_slideShowVar);
        $mpd_imagelist = trim($mpd_imagelist);
        ${$mpd_slideShowVar} = $mpd_imagelist;
    }

    function slideShowImages($mpd_imagelist='',$slideshoweffect='')
    {
        global $plugin_cf,$hjs,$pth;

        include_once($pth['folder']['plugins'].'jquery/jquery.inc.php');
        include_jQuery();
        include_jQueryPlugin('cycle', $pth['folder']['plugins'].'morepagedata/js/jquery/jquery.cycle.lite.js');

        $imagearray = explode(',',$mpd_imagelist);
        $o = '';
        foreach ($imagearray as $value) {
            if ($value) $o .= "\n\t"
                          . '<div>'
                          . tag('img src="'.$pth['folder']['base']
                          . trim($plugin_cf['morepagedata']['slideshow_images_path'],'./')
                          . '/'
                          . $value
                          . '" alt="'
                          . $value
                          . '"')
                          . '</div>';
        }

        $hjs .= '<script type="text/javascript">
(function($) {
    $(document).ready(function() {
        $(\'.slideshow\').cycle({
    		fx: \'fade\',
            speed: '.$plugin_cf['morepagedata']['slideshow_speed'].',
            timeout: '.$plugin_cf['morepagedata']['slideshow_time-out'].'
    	});
    })
})(jQuery)
</script>';

        return $o;
    }

    // Produce slide show
    if ($mpd_imagelist) slideShowImages($mpd_imagelist,$slideshoweffect);
} else {
    function slideShowImages() {return NULL;}
}