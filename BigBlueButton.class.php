<?php

class BigBlueButton {
    private $api;
    private $salt;
    private $version;
    private $http;
    private $error;

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
    private function getVersion() {
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

        $this->error = '';

        $result = $this->http->get($url));
        if(!$result){
            $this->error = 'HTTP ERROR '.$this->http->resp_status.' '.$this->http->error;
            return false;
        }

        $dom = new DOMDocument;
        if(!$dom->loadXML($result)){
            $this->error = 'Could not parse XML response';
            return false;
        }

        $nodes = $dom->getElementsByTagName('returncode');
        if($nodes->length <= 0){
            $this->error = 'Got empty XML response';
            return false;
        }

        $returnCode = $nodes->item(0);
        if($returnCode->textContent != 'SUCCESS') {
            $this->error = 'API call did not return success'; //FIXME add error message
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
        if($this->salt) return '';

        $query = buildURLparams($parameters, '&');

        $version = $this->getVersion();

        if(-1 === version_compare($version, '0.7')){
            return sha1($query.$this->salt);
        } else {
            return sha1($action.$query.$this->salt);
        }
    }
}
