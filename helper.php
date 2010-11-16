<?php
/**
 * DokuWiki Plugin bigbluebutton (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'bigbluebutton/BigBlueButton.class.php';


class helper_plugin_bigbluebutton extends DokuWiki_Action_Plugin {

    private function getRoomSetupFile($room){
        global $conf;
        $file = utf8_encodeFN(str_replace(':','',cleanID($room)));
        $file = $conf['metadir'].'/_bigbluebutton/'.$file.'.bbbroom';
        return $file;
    }

    public function loadRoomSetup($room){
        $room = $this->getRoomSetupFile($room);
        return confToHash($room);
    }

    public function saveRoomSetup($room,$data){
        $room = $this->getRoomSetupFile($room);
        $out = '';
        foreach($data as $key => $val){
            $out .= "$key\t$val\n";
        }
        io_saveFile($room,$out);
    }

    /**
     * Check the permissions for the current user in the given room
     *
     * Possible return values:
     *
     * 0 - no permission to join
     * 1 - guest permission
     * 2 - normal attendee
     * 3 - moderator
     */
    public function checkPermission($room){
        global $USERINFO;
        $setup = $this->loadRoomSetup($room);

        if( $USERINFO['user'] &&
            $setup['moderators'] &&
            $this->isMember($setup['moderators'],
                            $_SERVER['REMOTE_USER'],
                            $USERINFO['grps'])){
                return 3;
        }

        if($setup['attendees']){
            if($this->isMember($setup['attendees'],
                               $_SERVER['REMOTE_USER'],
                               $USERINFO['grps'])){
                return 2;
            }else{
                return 0;
            }
        }

        return 1;
    }

    /**
     * Match a user and his groups against a comma separated list of
     * users and groups to determine membership status
     *
     * @fixme this should probably be moved to core
     * @param $memberlist string commaseparated list of allowed users and groups
     * @param $user       string user to match against
     * @param $groups     array  groups the user is member of
     * @returns bool      true for membership acknowledged
     */
    function isMember($memberlist,$user,array $groups){
        global $auth;

        // clean user and groups
        if($auth->isCaseSensitive()){
            $user = utf8_strtolower($user);
            $groups = array_map('utf8_strtolower',$groups);
        }
        $user = $auth->cleanUser($user);
        $groups = array_map(array($auth,'cleanGroup'),$groups);

        // extract the memberlist
        $members = explode(',',$memberlist);
        $members = array_map('trim',$members);
        $members = array_unique($members);
        $members = array_filter($members);

        // compare cleaned values
        foreach($members as $member){
            if($auth->isCaseSensitive()) $member = utf8_strtolower($member);
            if($member[0] == '@'){
                $member = $auth->cleanGroup(substr($member,1));
                if(in_array($member, $groups)) return true;
            }else{
                $member = $auth->cleanUser($member);
                if($member == $user) return true;
            }
        }

        // still here? not a member!
        return false;
    }

}

// vim:ts=4:sw=4:et:enc=utf-8:
