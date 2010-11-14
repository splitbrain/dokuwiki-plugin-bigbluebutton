<?php

class BigBlueButton {
    private $api;
    private $salt;
    private $version;
    private $http;

    public function __construct($api, $salt){
        $this->api  = rtrim($api,'/').'/';
        $this->salt = $salt;

        $this->http = new DokuHTTPClient();
    }

    /**
     * Query the version from the BBB server
     *
     * The version is saved in $this->version and will not be queried again
     * for the life time of the object
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param object $node The XML node object
     * @returns array The extracted values
     */
    public function getVersion() {
        if($this->version) return $this->version;

        if( $version = $this->performRequest( '', array() ) ) {
            $values = $this->grabValues( $version->documentElement );
            $version = $values['version'];

            if( false !== $pos = strpos( $version, '-' ) ) {
                $version = substr( $version, 0, $pos );
            }

            $this->version = $version;
        } else {
            $this->version = '0.6';
        }
        return $this->version;
    }

    /**
     * Returns the URL to join a BBB room
     *
     * This function makes sure the room exist, if not it will be created
     *
     * Note: if a user should be a moderator or not has to be decided outside this
     *       library
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param $room string the name of the room
     * @param $name string the name of the user
     * @param $asmoderator bool is the user a moderator?
     * @param $params array these are passed to createRoom()
     * @returns mixed the URL the user needs to be redirected to, false on error
     */
    public function joinRoomURL($room, $name, $asmoderator=false, array $params = array()){
        $info = $this->getRoom($room);
        if(!$info) $info = $this->createRoom($room);
        if(!$info) return false;
        if($asmoderator){
            $pass = $info['moderatorPW'];
        }else{
            $pass = $info['attendeePW'];
        }

        return $this->buildUrl( 'join', array(
            'meetingID' => $room,
            'fullName'  => $name,
            'password'  => $pass,
        ));
    }

    /**
     * Get a List of people in a room
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param $room string the room name
     * @param $params array the parameters as described above
     * @returns mixed array with room info or false on failure
     */
    public function getAttendees($room) {
        $attendees = array();

        $meeting = $this->getRoom($room);
        if(!$meeting) return $attendees;

        $dom = $this->performRequest('getMeetingInfo', array(
                                                        'meetingID' => $room,
                                                        'password'  => $meeting['moderatorPW']));
        if(!$dom) return $attendees;


        foreach($dom->getElementsByTagName('attendee') as $node) {
            $attendees[] = $this->grabValues($node);
        }

        return $attendees;
    }

    /**
     * Creates a new meeting room
     *
     * The following keys can be specified for params:
     *
     *    welcome      - A welcome message
     *    number       - The phone number to join
     *    voicebridge  - The voicebridge for the room
     *    logout       - URL to redirect when leaving the room
     *    max          - Maximum numbers of users allowed
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param $room string the room name
     * @param $params array the parameters as described above
     * @returns mixed array with room info or false on failure
     */
    public function createRoom($room, array $params = array()){

        $request = array(
            'name' => $room,
            'meetingID' => $room,
        );
        if( isset( $params['welcome'] ) ) {
            $request['welcome'] = $params['welcome'];
        }
        if( isset( $params['number'] ) ) {
            $request['dialNumber'] = $params['number'];
        }
        if( isset( $params['voicebridge'] ) ) {
            $request['voiceBridge'] = $params['voicebridge'];
        }
        if( isset( $params['logout'] ) ) {
            $request['logoutURL'] = $params['logout'];
        }else{
            $request['logoutURL'] = DOKU_URL;
        }
        if( isset( $params['max'] ) ) {
            $request['maxParticipants'] = $params['max'];
        }

        $dom = $this->performRequest( 'create', $request );
        if(!$dom) return false;

        $nodes = $dom->getElementsByTagName('response');
        return $this->grabValues($nodes->item(0));
    }

    /**
     * Get a list of meeting rooms at the BBB server
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @returns mixed XML DOM on success, false on error
     */
    public function getRooms() {
        $meetings = array();
        if($dom = $this->performRequest('getMeetings', array('random' => rand(1,9999) ))) {
            foreach($dom->getElementsByTagName('meeting') as $node) {
                $meetings[] = $this->grabValues($node);
            }
        }

        return $meetings;
    }

    /**
     * Get info about a certain room
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @returns mixed room info as array, false on error
     */
    public function getRoom($room) {
        $meetings = $this->getRooms();

        foreach( $meetings as $meeting ) {
            if( $meeting['meetingID'] == $room ) {
                return $meeting;
            }
        }
        return false;
    }

    /**
     * Execute an BBB API request
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param string $action      API action
     * @param array  $parameters  API parameters
     * @returns mixed XML DOM on success, false on error
     */
    private function performRequest($action, array $parameters){
        $url = $this->buildUrl( $action, $parameters );

        $err = 'BBB ('.hsc($action).') ';

        $result = $this->http->get($url);
        if(!$result){
            msg($err.'HTTP ERROR '.$this->http->resp_status.' '.$this->http->error,-1);
            return false;
        }

        $dom = new DOMDocument;
        if(!$dom->loadXML($result)){
            msg($err.'Could not parse XML response',-1);
            return false;
        }

        $nodes = $dom->getElementsByTagName('returncode');
        if($nodes->length <= 0){
            msg($err.'Got empty XML response',-1);
            return false;
        }

        $returnCode = $nodes->item(0);
        if($returnCode->textContent != 'SUCCESS') {
            $error = '';

            $nodes = $dom->getElementsByTagName('messageKey');
            if($nodes->length) $error .= $nodes->item(0)->textContent.'. ';

            $nodes = $dom->getElementsByTagName('message');
            if($nodes->length) $error .= $nodes->item(0)->textContent.'. ';

            if($error){
                msg($err.$error,-1);
            }else{
                msg($err.'API call did not return success',-1);
            }
            return false;
        }

        return $dom;
    }

    /**
     * Helper function to extract raw data from an XML node tree
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @param object $node The XML node object
     * @returns array The extracted values
     */
    private function grabValues( $node ) {
        $values = array();

        foreach( $node->childNodes as $n ) {
            if( $n instanceof DOMElement ) {
                $values[$n->tagName] = $n->textContent;
            }
        }

        return $values;
    }

    /**
     * Construct a checksumed BBB API URL
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param string $action      API action
     * @param array  $parameters  API parameters
     * @returns string The complete API URL
     */
    private function buildUrl($action, array $parameters){
        if($action){
            if($checksum = $this->generateChecksum($action, $parameters)){
                $parameters['checksum'] = $checksum;
            }
        }

        return $this->api.$action.'?'.buildURLparams($parameters,'&');
    }

    /**
     * Generate a BBB checksum
     *
     * @author Louis-Philippe Huberdeau <louis-philippe@huberdeau.info>
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param string $action      API action
     * @param array  $parameters  API parameters
     * @returns string The SHA1 checksum
     */
    private function generateChecksum($action, array $parameters){
        if(!$this->salt) return '';

        $query = buildURLparams($parameters, '&');

        $version = $this->getVersion();

        if(-1 === version_compare($version, '0.7')){
            return sha1($query.$this->salt);
        } else {
            return sha1($action.$query.$this->salt);
        }
    }
}
