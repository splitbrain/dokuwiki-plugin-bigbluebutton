<?php
/**
 * DokuWiki Plugin bigbluebutton (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'admin.php';

class admin_plugin_bigbluebutton extends DokuWiki_Admin_Plugin {

    function __construct(){
        $this->helper = plugin_load('helper','bigbluebutton');
    }

    function getMenuSort() {
        return 553;
    }

    function forAdminOnly() {
        return false;
    }

    function handle() {
    }

    function html() {
        ptln('<h1>' . $this->getLang('menu') . '</h1>');

        if($_REQUEST['room']) $this->_form($_REQUEST['room']);
    }

    function getTOC(){
        global $conf;
        global $ID;
        $toc = array();

        $files = glob($conf['metadir'].'/_bigbluebutton/*.bbbroom');
        if(is_array($files)) foreach($files as $f){
            $room = basename($f,'.bbbroom');
            $toc[] =  array(
                        'link'  => wl($ID,array('do'=>'admin','page'=>'bigbluebutton',
                                      'room'=>$room,'sectok'=>getSecurityToken())),
                        'title' => $room,
                        'level' => 1,
                        'type'  => 'ul',
                     );

        }
        return $toc;
    }

    function _form($room){
        $bbb = $this->helper->loadRoomSetup($room);

        $form = new Doku_Form();
        $form->addHidden('room',$room);
        $form->startFieldset('Room');
        foreach(array('welcome','number','voicebridge','logout','max','moderators','attendees') as $lbl){
            $form->addElement(form_makeTextField('bbb['.$lbl.']', $bbb[$lbl], $this->getLang($lbl)));
            $form->addElement('<br />');
        }
        $form->endFieldset();
        $form->printForm();
    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
