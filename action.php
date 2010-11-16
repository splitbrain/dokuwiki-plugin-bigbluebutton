<?php
/**
 * DokuWiki Plugin bigbluebutton (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';
require_once DOKU_PLUGIN.'bigbluebutton/syntax.php';

class action_plugin_bigbluebutton extends DokuWiki_Action_Plugin {

    function register(&$controller) {

       $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handle_dokuwiki_started');

    }

    function handle_dokuwiki_started(&$event, $param) {
        if(!isset($_REQUEST['bigbluebutton'])) return;

        $helper = plugin_load('helper','bigbluebutton');
        $room  = $_REQUEST['bigbluebutton'];
        $setup = $helper->loadRoomSetup($room);
        if(!count($setup)){
            msg('No such room setup',-1);
            return;
        }

        $perm = $helper->checkPermission($room);
        if(!$perm){
            msg('Sorry, you have no permission to join this room');
            return;
        }

        $name = $_REQUEST['bbbnickname'];
        if(!$name) $name = $_SERVER['REMOTE_USER'];
        if(!$name) $name = 'guest'.rand(1,9999);

        $bbb = new BigBlueButton($this->getConf('apiurl'),
                                 $this->getConf('salt'));

        $url = $bbb->joinRoomURL($room, $name, ($perm > 3), $setup);

        if($url) send_redirect($url);
    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
