<?php
/**
 * Morepagedata
 * Backend
 * @author svasti
 * (c) 2015
 * version 1.1
 */


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

define('MOREPAGEDATA_VERSION', '1.1');
if (function_exists('XH_registerStandardPluginMenuItems')) {
    XH_registerStandardPluginMenuItems(true);
}
/**
 * Plugin administration
 */
if (function_exists('XH_wantsPluginAdministration') && XH_wantsPluginAdministration('morepagedata')
    || isset($morepagedata)
) {
    initvar('admin');
    initvar('action');
    include 'funcs.php';
    $o .= print_plugin_admin('on');

    // check if data file exist, if not try to create it
    mpd_checkFiles();

    if (!$admin || $admin=='plugin_main') {
        if(isset($_POST['save_mpd']) || isset($_POST['mpd_import']) || isset($_POST['mpd_old']))  {
            // receiving and saving changes
            $o .= mpd_receivePostData();
        }

        // read the stored data, $raw_mpd is re-used in detecting values of former versions
        $raw_mpd = file_get_contents($pth['folder']['plugins'].'morepagedata/config/config2.php');
        $mpd = json_decode($raw_mpd,true);

        // Plugin main view
        $o .= $mpd_copyright;

        // CMS is put into edit mode as 1.6 error message insist on edit mode for changing content, and
        // page data are content since 1.6
        $o .= '<form method="POST" action="' . $sn
           .  '?&morepagedata&amp;admin=plugin_main&amp;action=plugin_tx&amp;edit">'
           .  "\n".tag('input type="submit" value="'.ucfirst($tx['action']['save']).'"'). ' '
           .  tag('input type="image" src="'.$pth['folder']['plugins'].$plugin
           .  '/images/add.gif" style="width:12px;height:12px" name="mpd[add][0]" value="add" alt="Add entry" title="'
           .  $plugin_tx['morepagedata']['title_add_on_top'].'"');


        // start table for entering config data
        //=====================================
        $o .= '<table id="mpd" class="mpd_configtable" cellpadding="1" cellspacing="3">'
           .  "\n";

        // headline
        //=========
        $o .= '<tr class="mpd_darker">'

           .  '<td style="width:12px" >'
           .  '</td>'

           .  '<th title="'
           .  $plugin_tx['morepagedata']['title_var_display']
           .  '">'
           .  $plugin_tx['morepagedata']['field_display']
           .  '</td>'

           . '<th title="'
           .  $plugin_tx['morepagedata']['title_var_name']
           .  '">'
           .  $plugin_tx['morepagedata']['field_var']
           .  '</td>'

           .  '<th title="'
           .  $plugin_tx['morepagedata']['title_var_type']
           .  '">'
           .  $plugin_tx['morepagedata']['field_type']
           .  '</td>'

           .  '<th title="'
           .  $plugin_tx['morepagedata']['title_br']
           .  '">'
           .  'br'
           .  '</td>'

           .  '<th style="width:2em;text-align:left;" title="'
           .  $plugin_tx['morepagedata']['title_hr']
           .  '">'
           .  'hr'
           .  '</td>'

           .  '</tr>'."\n";


        // prepare option list for variable type
        foreach ($mpd['var'] as $key=>$value) {

            $mpd_type_select = '';
            foreach (array(
                'checkbox',
                'color_picker',
                'image_folder',
                'input_field',
                'option_list',
                'plugin_call',
                'select_hiddenpage',
                'template_image',
                'slide_show',
                ) as $value) {

            	$selected = '';
            	if($mpd['type'][$key] == $value) {$selected = ' selected';}
            	$mpd_type_select .= "<option value='$value'$selected>$value</option>";
            }

            $mpd_brchecked = $mpd['br'][$key]? ' checked="checked"':'';
            $mpd_hrchecked = $mpd['hr'][$key]? ' checked="checked"':'';


            // input data 1st row
            //===================
            $mpd_up_title =  $key === 0
                          ?  $plugin_tx['morepagedata']['title_top_var_up']
                          :  $plugin_tx['morepagedata']['title_up'];
            $o .= '<tr>'."\n"

               // up button
               .  '<td style=position:relative;line-height:1;"><div style="position:absolute;top:0;">'
               .  tag('input type="image" src="'.$pth['folder']['plugins'].$plugin
               .  '/images/up.gif" style="width:11px;height:16px;" value="true" name="mpd[up]['.$key.']"  alt="up" title="'
               .  $mpd_up_title
               .  '"')
               .  tag('br')

               // delete button
               . tag('input type="image" src="'.$pth['folder']['plugins'].$plugin
               . '/images/delete.gif" style="width:12px;height:12px;" name="mpd[delete]['.($key + 1).']" title="'
               . $plugin_tx['morepagedata']['title_delete'].'" value=TRUE alt="Delete entry"')

               // Add button
               .  tag('input type="image" src="'.$pth['folder']['plugins'].$plugin
               .  '/images/add.gif" style="width:12px;height:12px;" value="true" name="mpd[add]['.($key + 1).']" alt="Add entry" title="'
               .  $plugin_tx['morepagedata']['title_add'].'"')
               .  '</div></td>'

               // display text in pagedata view
               .  '<td>'
               .  tag('input type="text" style="width:96%;" value="'.$mpd['display'][$key].'" name="mpd[display]['.$key.']"')
               .  '</td>'
               .  '<td>';

            // variable name
            // make $mpd['var'] read only so that the pagedata doesn't get messed up
            $o .= $mpd['var'][$key]
                ? '<p class="mpd_variable">'.$mpd['var'][$key].'</p>'
                . tag('input type="hidden" value="'.$mpd['var'][$key].'" name="mpd[var]['.$key.']"')
                : tag('input type="text" value="'.$mpd['var'][$key].'" name="mpd[var]['.$key.']"')
                . tag('input type="hidden" value="true" name="mpd[new]['.$key.']"')
                . '</td>';

            // choose type = functionality of variable
            $o .= '<td><select name="mpd[type]['.$key.']" id="mpd_type['.$key
               .  ']" style="width:96%" OnChange="
                    if(this.options[this.selectedIndex].value == \'option_list\'
                       || this.options[this.selectedIndex].value == \'plugin_call\') {
                        document.getElementById(\'options['.$key.']\').style.display = \'table-row\';
                    } else {
                        document.getElementById(\'options['.$key.']\').style.display = \'none\';
                    }
                    if(this.options[this.selectedIndex].value == \'color_picker\' || this.options[this.selectedIndex].value == \'slide_show\') {
                        document.getElementById(\'help['.$key.']\').placeholder = \''.$plugin_tx['morepagedata']['placeholder_automatic_help'].'\';
                    } else {
                        document.getElementById(\'help['.$key.']\').placeholder = \''.$plugin_tx['morepagedata']['placeholder_help_field'].'\';
                    }
                    ; ">'.$mpd_type_select.'</select></td>'

                // select br after variable in pagedata view
               .  '<td>'
               .  tag('input type="checkbox" value="1" '.$mpd_brchecked
               .  ' style="height:16px;"  name="mpd[br]['.$key.']" title="'
               .  $plugin_tx['morepagedata']['title_br'].'"')

                // select hr after variable in pagedata view
               .  '<td style="white-space:nowrap">'
               .  tag('input type="checkbox" value="1" '.$mpd_hrchecked
               .  ' style="height:16px;" name="mpd[hr]['.$key.']" title="'
               .  $plugin_tx['morepagedata']['title_hr'].'"')

                // buttons to show hidden lines
               .  tag('img src="'.$pth['folder']['plugins'].$plugin
               .  '/images/help.png" style="width:16px;height:16px;cursor:pointer;"
                    OnClick="
                    if(document.getElementById(\'helpline['.$key.']\').style.display == \'none\') {
                        document.getElementById(\'helpline['.$key.']\').style.display = \'table-row\';
                    } else {
                        document.getElementById(\'helpline['.$key.']\').style.display = \'none\';
                    }
                    " title="'
               .  $plugin_tx['morepagedata']['title_help_line'].'"')
               .  tag('img src="'.$pth['folder']['plugins'].$plugin
               .  '/images/template.gif" style="width:13px;height:16px;cursor:pointer;"
                    OnClick="
                    if(document.getElementById(\'templateline['.$key.']\').style.display == \'none\') {
                        document.getElementById(\'templateline['.$key.']\').style.display = \'table-row\';
                    } else {
                        document.getElementById(\'templateline['.$key.']\').style.display = \'none\';
                    }
                    " title="'
               .  $plugin_tx['morepagedata']['title_template_line'].'"')
               .  '</td></tr>'."\n";

            $optionsline_visibility = ($mpd['type'][$key] == 'option_list'
                                        || $mpd['type'][$key] == 'plugin_call')
                        ? 'style="display:table-row"'
                        : 'style="display:none"';
            $helpline_visibility = $mpd['help'][$key]
                        ? 'style="display:table-row"'
                        : 'style="display:none"';
            $templateline_visibility = $mpd['template'][$key]
                        ? 'style="display:table-row"'
                        : 'style="display:none"';
            $placeholder = ($mpd['type'][$key] == 'color_picker' || $mpd['type'][$key] == 'slide_show')
                        ? $plugin_tx['morepagedata']['placeholder_automatic_help']
                        : $plugin_tx['morepagedata']['placeholder_help_field'];

            // input data 2nd row
            //===================
 			$o .= '<tr '.$optionsline_visibility.' id="options['.$key.']">' . "\n\t"
               .  '<td></td>'
               .  '<td colspan="5">' . "\n\t"
               .  tag('input type="text" style="width: 100%;" value="'.$mpd['options'][$key]
               .  '" name="mpd[options]['.$key.']" placeholder="'.$plugin_tx['morepagedata']['placeholder_option_list'].'"') . '</td>'
               .  '</tr>' . "\n";

            // input data 3nd row
            //===================
 			$o .= '<tr '.$helpline_visibility.' id="helpline['.$key.']">' . "\n\t"
               .  '<td></td>'
               .  '<td class="right_aligned">' . $plugin_tx['morepagedata']['field_help_text'] .': </td>'
               .  '<td colspan="4">' . "\n\t"

               .  '<div class="expandingArea active"><pre><span></span>' . tag('br') . '</pre>'
               .  '<textarea style="width: 100%;" name="mpd[help]['.$key.']" id="help['.$key.']" placeholder="'.$placeholder.'">'
               .  $mpd['help'][$key]
               .  '</textarea>'
               .  '</div>'
               .  '</tr>' . "\n";

            // input data 4rd row
            //===================
 			$o .= '<tr '.$templateline_visibility.' id="templateline['.$key.']">' . "\n\t"
               .  '<td></td>'
               .  '<td class="right_aligned">' . $plugin_tx['morepagedata']['field_template'] .': </td>'
               .  '<td colspan="4">' . "\n\t"
               .  tag('input type="text" style="width: 100%;" value="'.$mpd['template'][$key]
               .  '" name="mpd[template]['.$key.']" id="mpd_template['.$key.']" placeholder="'
               .  $plugin_tx['morepagedata']['placeholder_template_list'].'"') . '</td>'
               .  '</tr>' . "\n";

            // empty line to separate value groups
 			$o .= '<tr class="mpd_darker"><td colspan="6">&nbsp;</td></tr>' . "\n\t";

        }

        $o .= "\n".'</table>'
           .  "\n".tag('input type="hidden" value="true" name="save_mpd"')
           .  "\n".tag('input type="submit" value="'.ucfirst($tx['action']['save']).'"').tag('br')
           .  "\n".'</form>'."\n";

        $bjs .= '<script src="'.$pth['folder']['plugins'].'morepagedata/js/autogrow.js" type="text/javascript"></script>';


        $o .= searchMorepagedata_csv();
        $o .= importOldMorepagedata();

    } else {
        $o .= plugin_admin_common($action, $admin, $plugin);
    }
}

?>
