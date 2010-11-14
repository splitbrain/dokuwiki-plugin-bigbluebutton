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

    function getMenuSort() { return FIXME; }
    function forAdminOnly() { return false; }

    function handle() {
    }

    function html() {
        ptln('<h1>' . $this->getLang('menu') . '</h1>');
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
