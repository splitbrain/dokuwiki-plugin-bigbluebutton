<?php
/**
 * DokuWiki Plugin bigbluebutton (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';
require_once DOKU_PLUGIN.'bigbluebutton/BigBlueButton.class.php';

class syntax_plugin_bigbluebutton extends DokuWiki_Syntax_Plugin {
    function getType() {
        return 'substition';
    }

    function getPType() {
        return 'normal';
    }

    function getSort() {
        return 155;
    }


    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{bigbluebutton}}',$mode,'plugin_bigbluebutton');
    }

    function handle($match, $state, $pos, &$handler){
        $data = array();

        return $data;
    }

    function render($mode, &$R, $data) {
        if($mode != 'xhtml') return false;

        #http://groups.google.com/group/bigbluebutton-dev/browse_thread/thread/de2a0098425403e1?pli=1
        $bbb = new BigBlueButton('http://test-install.blindsidenetworks.com/bigbluebutton/api',
                                 '8cd8ef52e8e101574e400365b55e11a6');

        $R->doc .= 'here';

        $room = 'dokuwiki5';
        $R->doc .= '<a href="'.$bbb->joinRoomURL($room,'Andi',true).'">join.</a>';

        dbg($bbb->getAttendees($room));

        return true;
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
