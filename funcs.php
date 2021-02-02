<?php

function mpd_CheckFiles()
{
    global $pth;
    $file = $pth['folder']['content'].'morepagedata.json';

    if(!is_file($file) || filesize($file)<10) {
        if(!file_put_contents($file, '{"var":[""],"display":[""],"type":[""],"hr":[""],"br":[1],"options":[""],"help":[""],"template":[""]}')) {
            e('notwritable','file',$file);
        }
    }
    if(!is_file($file)) e('missing','file',$file);
    if(!is_writable($pth['folder']['plugins'].'morepagedata/config/')) e('notwritable','folder',$pth['folder']['plugins'].'morepagedata/config/');
}

/**
 * receive the input given in morepagedata backend
 *
 */
function mpd_receivePostData()
{
    global $pth,$pd_router,$plugin_tx;
    $o = '';

    $newmpd['new']           =isset($_POST['mpd']['new'])     ? $_POST['mpd']['new']           : array();
    $newmpd['var']           =isset($_POST['mpd']['var'])     ? $_POST['mpd']['var']           : array();
    $newmpd['display']       =isset($_POST['mpd']['display']) ? $_POST['mpd']['display']       : array();
    $newmpd['type']          =isset($_POST['mpd']['type'])    ? $_POST['mpd']['type']          : array();
    $newmpd['add']           =isset($_POST['mpd']['add'])     ? $_POST['mpd']['add']           : array();
    $newmpd['up']            =isset($_POST['mpd']['up'])      ? $_POST['mpd']['up']            : array();
    $newmpd['delete']        =isset($_POST['mpd']['delete'])  ? $_POST['mpd']['delete']        : array();
    $newmpd['hr']            =isset($_POST['mpd']['hr'])      ? $_POST['mpd']['hr']            : array();
    $newmpd['br']            =isset($_POST['mpd']['br'])      ? $_POST['mpd']['br']            : array();
    $newmpd['options']       =isset($_POST['mpd']['options']) ? $_POST['mpd']['options']       : array();
    $newmpd['help']          =isset($_POST['mpd']['help'])    ? $_POST['mpd']['help']          : array();
    $newmpd['template']      =isset($_POST['mpd']['template'])? $_POST['mpd']['template']      : array();

    // load the old data to check for existing var
    $mpd = json_decode(file_get_contents($pth['folder']['content'].'morepagedata.json'),true);
    if($mpd == null) $mpd = array();

    // a little clean up
    foreach ($newmpd['var'] as $key=>$value) {
        $newmpd['var'][$key]        = preg_replace("/[^a-z_\-A-Z0-9]/", '', $value);
        $newmpd['hr'][$key]         = isset($newmpd['hr'][$key])? 1:0;
        $newmpd['br'][$key]         = isset($newmpd['br'][$key])? 1:0;
        if(!isset($newmpd['options'][$key]))  $newmpd['options'][$key] = '';
        if(!isset($newmpd['display'][$key]))  $newmpd['display'][$key] = '';
        if(!isset($newmpd['template'][$key])) $newmpd['template'][$key]= '';
        if(!isset($newmpd['help'][$key]))     $newmpd['help'][$key]    = '';
        $newmpd['options'][$key] = stsl($newmpd['options'][$key]);
        $newmpd['help'][$key]    = stsl($newmpd['help'][$key]);
        $newmpd['display'][$key] = stsl($newmpd['display'][$key]);

        // check if new variables are already in use by the system
        // select only new variables for the checking
        if(isset($newmpd['new'][$key]) && $newmpd['var'][$key]) {
            // initializing
            $refuse_var = $usedasmpd = $tmpadded = 0;
            // general check for collision with momentarily set variables
            if(isset(${$newmpd['var'][$key]})) {$refuse_var ++;}
            // check for collision with any pagedata variables
            foreach (getPageDataFields() as $pd_var) {
            	if($pd_var == $newmpd['var'][$key]) {$refuse_var ++;}
            }
            // check for collision with other morepagedata variables
            foreach ($mpd['var'] as $key2 => $existing_var) {
            	if($existing_var == $newmpd['var'][$key]) {
                    $usedasmpd ++;
                    if($mpd['template'][$key2]) {
                        $tplarray = explode(',', $mpd['template'][$key2]);
                        $i = 0;
                        foreach ($tplarray as $value) {
                        	if(trim($value) == $newmpd['template'][$key]) $i++;
                        }
                        if(!$i) {
                            $mpd['template'][$key2].=','.$newmpd['template'][$key];
                            $tmpadded ++;
                        }
                    }
                }
            }
            if($refuse_var || $usedasmpd) {
                if($newmpd['var'][$key]) { //no notice if var is empty
                    $o .= '<p class="cmsimplecore_warning"><b>$'.$newmpd['var'][$key] . '</b> ';
                    $o .=  $usedasmpd
                       ?   $plugin_tx['morepagedata']['error_already_morepagedata_variable']
                       :   $plugin_tx['morepagedata']['error_variable_already_used'].'</p>';
                    $o .=  $tmpadded
                       ?   "\n" . $plugin_tx['morepagedata']['error_template_enabling_added'].'</p>'
                       :   '</p>';
                }
                unset($newmpd['var'][$key],$newmpd['hr'][$key],$newmpd['options'][$key],$newmpd['help'][$key],$newmpd['display'][$key],$newmpd['type'][$key]);
            }
        }
    }

    ksort($newmpd['hr']); // not really necessary, but makes it look nicer


    // DELETE
    //============
    $deletekey = array_search(TRUE, $newmpd['delete']);
    if($deletekey) {

        // first delete fields in pagedata.php
        $deletekey--;
        if(isset($newmpd['var'][$deletekey])) {

                $attr = $newmpd['var'][$deletekey];

            if (function_exists('XH_saveContents')) {
                 // Code for post 1.6
                $pd_router->removeInterest($attr);
                XH_saveContents();

            } else {
                 // Code for pre 1.6
                $attr = $newmpd['var'][$deletekey];
                $key = array_search($attr, $pd_router->model->params);
                if ($key !== FALSE) {unset($pd_router->model->params[$key]);}
                for ($i = 0; $i < count($pd_router->model->data); $i++) {
                unset($pd_router->model->data[$i][$attr]);
                }
                unset($pd_router->model->temp_data[$attr]);
                $pd_router->model->save();
            }
        }

        // now delete fields in morepagedata arrays
        foreach ($newmpd as $key=>$value) {
            unset($newmpd[$key][$deletekey]);
        }
    }


    // ADD
    //==========
    $addkey = array_search(TRUE, $newmpd['add']);
    if($addkey !== false) {
        foreach ($newmpd as $key=>$value) {
            if($key == 'br') {
                array_splice($newmpd[$key],($addkey),0,1);
            } else {
                array_splice($newmpd[$key],($addkey),0,'');
            }
        }
    } 


    // Move UP
    //===============
    $upkey = array_search(TRUE, $newmpd['up']);
    if($upkey > 0 || $upkey === 0) {
        foreach ($newmpd as $key=>$value) {
            // extract values
            $moving_up = array_slice($newmpd[$key],$upkey,1);
            // delete extracted values in the original array
            array_splice($newmpd[$key],$upkey,1);
            // add extracted value higher into the original array
            if($upkey > 0) array_splice($newmpd[$key],($upkey - 1),0,$moving_up);
            if($upkey === 0) array_splice($newmpd[$key],count($newmpd),0,$moving_up);
        }
    }

    // clean the array of data which is not needed in the final file
    unset($newmpd['add'],$newmpd['new'],$newmpd['up'],$newmpd['delete']);

    // IMPORT + add to existing data
    //================================
    if(isset($_POST['mpd_import']) && !empty($mpd)) {
        // adding imported data to existing data
        foreach ($mpd as $key=>$value) {
            foreach ($newmpd[$key] as $key2=>$value2) {
            	$mpd[$key][] = $newmpd[$key][$key2];
            }
        }
        $newmpd = $mpd;
    }

    // SAVE to file
    //==============
    file_put_contents($pth['folder']['content'].'morepagedata.json',json_encode($newmpd));

    return $o;
}



 /**
 * look for morepagedata.csv in template folders
 */
function searchMorepagedata_csv()
{
    global $plugin_tx,$pth;

    $o = '';

    $handle = opendir($pth['folder']['templates']);
    $mpd_csv = array();
    if ($handle) {
        while(false !== ($file = readdir($handle))) {
            if(is_dir($pth['folder']['templates'].$file) && strpos($file, '.') !== 0) {
                if(is_file($pth['folder']['templates'].$file.'/morepagedata.csv')) {

                    $mpd_csv = file($pth['folder']['templates'].$file.'/morepagedata.csv');
                    foreach($mpd_csv as $line_nr => $line_content) {
                        $line_content = trim(trim($line_content),';');
                        $value_pair_array = explode(';', $line_content);
                        foreach($value_pair_array as $key => $value) {
                            list($mpd_name,$mpd_value) = explode('=',$value,2);
                            $mpd_foundvar[$line_nr][trim($mpd_name)]=trim($mpd_value,' $');
                        }
                    }
                    //display the found data and make it importable
                    //=====================================================
                    $o .= tag('br').'<form method="POST" action="">';
                    $o .= '<table class="mpd_configtable" cellpadding="1" cellspacing="3">'
                       .  '<colgroup>'
                       .  '<col width="40%">'
                       .  '<col width="25%">'
                       .  '<col width="25%">'
                       .  '<col width="">'
                       .  '<col width="">'
                       .  '</colgroup>'."\n";

                    $o .= '<tr style="color:#070;"><th colspan="3">'.$plugin_tx['morepagedata']['found_mpd_in_template']
                       .': <span style="color:red">'.$file.'</span></td></tr>';
                    $o .= '<tr><th><small>'
                       .  $plugin_tx['morepagedata']['field_display']
                       .  '</small></th><th><small>'
                       .  $plugin_tx['morepagedata']['field_var']
                       .  '</small></th><th><small>'
                       .  $plugin_tx['morepagedata']['field_type']
                       .  '</small></th></tr>'."\n";
                    foreach ($mpd_foundvar as $key=>$value) {
                        $o .= '<tr>'
                           .  '<td>'.$value['display'].'</td>'
                           .  '<td>$'.$value['var'].'</td>'
                           .  '<td>'.$value['type'].'</td>'
                           .  '</tr>';
                        $o .= tag('input type="hidden" value="'.$file.'" name="mpd[template]['.$key.']"')
                           .  tag('input type="hidden" value="true" name="mpd[new]['.$key.']"')
                           .  tag('input type="hidden" value="'.$value['var'].'" name="mpd[var]['.$key.']"')
                           .  tag('input type="hidden" value="'.$value['display'].'" name="mpd[display]['.$key.']"')
                           .  tag('input type="hidden" value="'.$value['type'].'" name="mpd[type]['.$key.']"');
                        if(isset($value['extra'])) {
                            if($value['type']=='option_list') {
                                $o .= tag('input type="hidden" value="'.$value['extra'].'" name="mpd[options]['.$key.']"');
                            } else {
                                $o .= tag('input type="hidden" value="'.$value['extra'].'" name="mpd[help]['.$key.']"');
                            }

                        }
                    }

                    $o .= '<tr style="text-align:right;"><th colspan="3">'
                       .  tag('input type="hidden" name="mpd_import" value="true"')
                       .  tag('input type="submit" value=" &nbsp; '.$plugin_tx['morepagedata']['import_mpd_fields'].' &nbsp; "')
                       .  '</th></tr>';
                    $o .= '</table>'."\n".'</form>'."\n";
                }
            }
        }
    }
    closedir($handle);

    return $o;
}



/**
 * import values of former version
 */
function importOldMorepagedata()
{
    global $plugin_tx,$pth,$mpd_cf,$raw_mpd;

    $o = '';

    if(strpos($raw_mpd, '<?php')!==false) {
        include $pth['folder']['plugins'].'morepagedata/config/config2.php';
        $mpd_fields     = explode(';', $mpd_cf['fields']);
        $mpd_fieldtypes = explode(';', $mpd_cf['fieldtypes']);
        $mpd_fieldnames = explode(';', $mpd_cf['fieldnames']);
        $mpd_details    = explode(';', $mpd_cf['details']);
        $mpd_hr         = explode(';', $mpd_cf['hr']);

        //display found data of old version and make it importable
        //=====================================================
        $o .= tag('br').'<form method="POST" action="">';
        $o .= '<table class="mpd_configtable" cellpadding="1" cellspacing="3">'
           .  '<colgroup>'
           .  '<col width="40%">'
           .  '<col width="25%">'
           .  '<col width="25%">'
           .  '<col width="">'
           .  '<col width="">'
           .  '</colgroup>'."\n";

        $o .= '<tr style="color:#070;"><th colspan="3">'.$plugin_tx['morepagedata']['found_former_version_mpd']
           .': <span style="color:red">'.$file.'</span></td></tr>';
        $o .= '<tr><th><small>'
           .  $plugin_tx['morepagedata']['field_display']
           .  '</small></th><th><small>'
           .  $plugin_tx['morepagedata']['field_var']
           .  '</small></th><th><small>'
           .  $plugin_tx['morepagedata']['field_type']
           .  '</small></th></tr>'."\n";
        foreach ($mpd_fields as $key=>$value) {
            $o .= '<tr>'
               .  '<td>'.$mpd_fieldnames[$key].'</td>'
               .  '<td>$'.$mpd_fields[$key].'</td>'
               .  '<td>'.$mpd_fieldtypes[$key].'</td>'
               .  '</tr>';
            $o .= tag('input type="hidden" value="'.$mpd_fields[$key].'" name="mpd[var]['.$key.']"')
               .  tag('input type="hidden" value="'.$mpd_fieldnames[$key].'" name="mpd[display]['.$key.']"')
               .  tag('input type="hidden" value="'.$mpd_fieldtypes[$key].'" name="mpd[type]['.$key.']"')
               .  tag('input type="hidden" value="'.$mpd_hr[$key].'" name="mpd[hr]['.$key.']"');

            if(isset($mpd_details[$key])) {
                if($mpd_fieldtypes[$key]=='option_list') {
                    $o .= tag('input type="hidden" value="'.$mpd_details[$key].'" name="mpd[options]['.$key.']"');
                } else {
                    $o .= tag('input type="hidden" value="'.$mpd_details[$key].'" name="mpd[help]['.$key.']"');
                }
            }

        }

        $o .= '<tr style="text-align:right;"><th colspan="3">'
           .  tag('input type="hidden" name="mpd_old" value="true"')
           .  tag('input type="submit" value=" &nbsp; '.$plugin_tx['morepagedata']['import_mpd_fields'].' &nbsp; "')
           .  '</th></tr>';
        $o .= '</table>'."\n".'</form>'."\n";
    }

    return $o;
}


$mpd_copyright = "<h2>Morepagedata_XH\n"
               . '<span style="font:normal normal 10pt sans-serif;">' . MOREPAGEDATA_VERSION
               . ' &copy; 2015 by <a href="http://frankziesing.de/cmsimple/">svasti</a> &nbsp;'
                  // button to display copyright notice
               . tag('input type="button" value="license?" style="font-size:80%;" OnClick="
                    if(document.getElementById(\'license\').style.display == \'none\') {
                        document.getElementById(\'license\').style.display = \'inline\';
                    } else {
                        document.getElementById(\'license\').style.display = \'none\';
                    }
                    "')
               . '</span></h2>'."\n"
               . '<p id="license" style="display:none;">'
               . 'This plugin is free software under the terms of the GNU General Public License v. 3 or '
               . 'later.<br><br>'
               . '<small><b>Acknowledgements:</b>'.tag('br')
               . 'Code of <a href="http://jscolor.com/" target="_blank">JSColor</a> by Jan Odvárko (CZ),
                 and of json-functions for php4 by Christoph Becker <a href="http://3-magi.net/" target="_blank">(cmb)</a>'. tag('br')
               . '</small></p>'."\n" ;


?>