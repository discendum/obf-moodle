<?php

require_once __DIR__ . '/../lib.php';

/**
 * Class for handling the communication to Open Badge Factory API.
 */
class obf_client {

    private static $client = null;
    private $transport = null;

    /**
     * Returns the id of the client stored in Moodle's config.
     * 
     * @return string The client id.
     */
    public static function get_client_id() {
        return get_config('local_obf', 'obfclientid');
    }

    /**
     * Returns the url of the OBF API.
     * 
     * @return string The url.
     */
    public static function get_api_url() {
        return OBF_API_URL;
    }

    /**
     * Returns the client instance.
     * 
     * @return obf_client The client.
     */
    public static function get_instance($transport = null) {
        if (is_null(self::$client)) {
            self::$client = new self();

            if (!is_null($transport)) {
                self::$client->set_transport($transport);
            }
        }

        return self::$client;
    }

    public function set_transport($transport) {
        $this->transport = $transport;
    }

    /**
     * Tests the connection to OBF API.
     * 
     * @return int Returns the error code on failure and -1 on success.
     */
    public function test_connection() {
        try {
            // TODO: does ping check certificate validity?
            $this->api_request('/ping');
            return -1;
        } catch (Exception $exc) {
            return $exc->getCode();
        }
    }

    /**
     * Deauthenticates the plugin.
     */
    public function deauthenticate() {
        @unlink($this->get_cert_filename());
        @unlink($this->get_pkey_filename());
        
        unset_config('obfclientid', 'local_obf');
    }
    
    /**
     * Tries to authenticate the plugin against OBF API.
     * 
     * @param string $signature The request token from OBF.
     * @return boolean Returns true on success.
     * @throws Exception If something goes wrong.
     */
    public function authenticate($signature) {
        $signature = trim($signature);
        $token = base64_decode($signature);
        $curl = $this->get_transport();
        $curlopts = $this->get_curl_options();
        $apiurl = self::get_api_url();

        // We don't need these now, we haven't authenticated yet.
        unset($curlopts['SSLCERT']);
        unset($curlopts['SSLKEY']);

        $pubkey = $curl->get($apiurl . '/client/OBF.rsa.pub', array(), $curlopts);

        // CURL-request failed
        if ($pubkey === false) {
            throw new Exception(get_string('pubkeyrequestfailed', 'local_obf') .
                    ': ' . $curl->error);
        }

        // Server gave us an error
        if ($curl->info['http_code'] !== 200) {
            throw new Exception(get_string('pubkeyrequestfailed', 'local_obf') . ': ' .
            get_string('apierror' . $curl->info['http_code'], 'local_obf'));
        }

        $decrypted = '';

        // Get the public key
        $key = openssl_pkey_get_public($pubkey);

        // Couldn't decrypt data with provided key
        if (openssl_public_decrypt($token, $decrypted, $key,
                        OPENSSL_PKCS1_PADDING) === false) {
            throw new Exception(get_string('tokendecryptionfailed', 'local_obf'));
        }

        $json = json_decode($decrypted);

        // Yay, we have the client-id. Let's store it somewhere.
        set_config('obfclientid', $json->id, 'local_obf');

        // Create a new private key
        $config = array('private_key_bits' => 2048, 'private_key_type', OPENSSL_KEYTYPE_RSA);
        $privkey = openssl_pkey_new($config);

        // Export the new private key to a file for later use
        openssl_pkey_export_to_file($privkey, $this->get_pkey_filename());

        $csrout = '';
        $dn = array('commonName' => $json->id);

        // Create a new CSR with the private key we just created
        $csr = openssl_csr_new($dn, $privkey);

        // Export the CSR into string
        if (openssl_csr_export($csr, $csrout) === false) {
            throw new Exception(get_string('csrexportfailed', 'local_obf'));
        }

        $postdata = json_encode(array('signature' => $signature, 'request' => $csrout));
        $cert = $curl->post($apiurl . '/client/' . $json->id . '/sign_request',
                $postdata, $curlopts);

        // Fetching certificate failed
        if ($cert === false) {
            throw new Exception(get_string('certrequestfailed', 'local_obf') . ': ' . $curl->error);
        }

        $httpcode = $curl->info['http_code'];

        // Server gave us an error
        if ($httpcode !== 200) {
            $jsonresp = json_decode($cert);
            $extrainfo = is_null($jsonresp) ? get_string('apierror' . $httpcode,
                            'local_obf') : $jsonresp->error;

            throw new Exception(get_string('certrequestfailed', 'local_obf') . ': ' . $extrainfo);
        }

        // Everything's ok, store the certificate into a file for later use.
        file_put_contents($this->get_cert_filename(), $cert);

        return true;
    }

    /**
     * Returns the expiration date of the OBF certificate as a unix timestamp.
     *
     * @return mixed The expiration date or false if the certificate is missing.
     */
    public function get_certificate_expiration_date() {
        $certfile = $this->get_cert_filename();

        if (!file_exists($certfile)) {
            return false;
        }

        $cert = file_get_contents($certfile);
        $ssl = openssl_x509_parse($cert);

        return $ssl['validTo_time_t'];
    }

    public function get_pkey_filename() {
        return __DIR__ . '/../pki/obf.key';
    }

    public function get_cert_filename() {
        return __DIR__ . '/../pki/obf.pem';
    }

    /**
     * Get a single badge from the API.
     *
     * @param type $badgeid
     * @throws Exception If the request fails
     * @return array The badge data.
     */
    public function get_badge($badgeid) {
        return $this->api_request('/badge/' . self::get_client_id() . '/' . $badgeid);
    }

    /**
     * Get issuer data from the API.
     *
     * @throws Exception If the request fails
     * @return array The issuer data.
     */
    public function get_issuer() {
        return $this->api_request('/client/' . self::get_client_id());
    }

    /**
     * Get badge categories from the API.
     *
     * @return array The category data.
     */
    public function get_categories() {
        return $this->api_request('/badge/' . self::get_client_id() . '/_/categorylist');
    }

    /**
     * Get all the badges from the API.
     *
     * @return array The badges data.
     */
    public function get_badges() {
        return $this->api_request('/badge/' . self::get_client_id(), 'get',
                        array('draft' => 0),
                        function ($output) {
                    return '[' . implode(',',
                                    array_filter(explode("\n", $output))) . ']';
                });
    }

    /**
     * Get badge assertions from the API.
     *
     * @param string $badgeid The id of the badge.
     * @param string $email The email address of the recipient.
     * @return array The event data.
     */
    public function get_assertions($badgeid = null, $email = null) {
        $params = array('api_consumer_id' => OBF_API_CONSUMER_ID);

        if (!is_null($badgeid)) {
            $params['badge_id'] = $badgeid;
        }

        if (!is_null($email)) {
            $params['email'] = $email;
        }

        // When getting assertions via OBF API the returned JSON isn't valid.
        // Let's use a closure that converts the returned string into valid JSON
        // before calling json_decode in $this->curl.
        return $this->api_request('/event/' . self::get_client_id(), 'get',
                        $params,
                        function ($output) {
                    return '[' . implode(',',
                                    array_filter(explode("\n", $output))) . ']';
                });
    }

    /**
     * Get single assertion from the API.
     *
     * @param string $eventid The id of the event.
     * @return array The event data.
     */
    public function get_event($eventid) {
        return $this->api_request('/event/' . self::get_client_id() . '/' . $eventid,
                        'get');
    }

    /**
     * Deletes all client badges. Use with caution.
     */
    public function delete_badges() {
        $this->api_request('/badge/' . self::get_client_id(), 'delete');
    }

    /**
     * Exports a badge to Open Badge Factory
     *
     * @param obf_badge $badge The badge.
     */
    public function export_badge(obf_badge $badge) {
        $params = array(
            'name' => $badge->get_name(),
            'description' => $badge->get_description(),
            'image' => $badge->get_image(),
            'css' => $badge->get_criteria_css(),
            'criteria_html' => $badge->get_criteria_html(),
            'email_subject' => $badge->get_email()->get_subject(),
            'email_body' => $badge->get_email()->get_body(),
            'email_footer' => $badge->get_email()->get_footer(),
            'expires' => '',
            'tags' => array(),
            'draft' => $badge->is_draft()
        );

        $this->api_request('/badge/' . self::get_client_id(), 'post', $params);
    }

    /**
     * Issues a badge.
     *
     * @param obf_badge $badge The badge to be issued.
     * @param string[] $recipients The recipient list, array of emails.
     * @param int $issuedon The issuance date as a Unix timestamp
     * @param string $emailsubject The subject of the email.
     * @param string $emailbody The email body.
     * @param string $emailfooter The footer of the email.
     */
    public function issue_badge(obf_badge $badge, $recipients, $issuedon,
                                $emailsubject, $emailbody, $emailfooter) {
        $params = array(
            'recipient' => $recipients,
            'issued_on' => $issuedon,
            'email_subject' => $emailsubject,
            'email_body' => $emailbody,
            'email_footer' => $emailfooter,
            'api_consumer_id' => OBF_API_CONSUMER_ID,
            'log_entry' => array('foo' => 'Just testing')
        );

        if (!is_null($badge->get_expires() && $badge->get_expires() > 0)) {
            $params['expires'] = $badge->get_expires();
        }

        $this->api_request('/badge/' . self::get_client_id() . '/' . $badge->get_id(),
                'post', $params);
    }

    // A wrapper for obf_client::request, prefixing $path with the API url.
    protected function api_request($path, $method = 'get',
                                   array $params = array(),
                                   Closure $preformatter = null) {
        return $this->request(self::get_api_url() . $path, $method, $params,
                        $preformatter);
    }

    /**
     * Makes a CURL-request to OBF API.
     *
     * @param string $url The API path.
     * @param string $method The HTTP method.
     * @param array $params The params of the request.
     * @param Closure $preformatter In some cases the returned string isn't
     *      valid JSON. In those situations one has to manually preformat the
     *      returned data before decoding the JSON.
     * @return array The json-decoded response.
     * @throws Exception In case something goes wrong.
     */
    public function request($url, $method = 'get', array $params = array(),
                            Closure $preformatter = null) {
        $curl = $this->get_transport();
        $options = $this->get_curl_options();
        $output = $method == 'get' ? $curl->get($url, $params, $options) : ($method
                == 'delete' ? $curl->delete($url, $params, $options) : $curl->post($url,
                                json_encode($params), $options));

        if ($output !== false) {
            if (!is_null($preformatter)) {
                $output = $preformatter($output);
            }

            $response = json_decode($output, true);
        }

        $info = $curl->get_info();
        $code = $info['http_code'];

        // Codes 2xx should be ok
        if (is_numeric($code) && ($code < 200 || $code >= 300)) {
            $error = isset($response['error']) ? $response['error'] : '';
            throw new Exception(get_string('apierror' . $code, 'local_obf',
                    $error), $code);
        }

        return $response;
    }

    /**
     * Returns a new curl-instance.
     *
     * @return \curl
     */
    protected function get_transport() {
        if (!is_null($this->transport)) {
            return $this->transport;
        }

        // Use Moodle's curl-object if no transport is defined.
        global $CFG;

        include_once $CFG->libdir . '/filelib.php';

        return new curl();
    }

    /**
     * Returns the default CURL-settings for a request.
     *
     * @return array
     */
    public function get_curl_options() {
        return array(
            'RETURNTRANSFER' => true,
            'FOLLOWLOCATION' => false,
            'SSL_VERIFYHOST' => false, // for testing
            'SSL_VERIFYPEER' => false, // for testing
            'SSLCERT' => $this->get_cert_filename(),
            'SSLKEY' => $this->get_pkey_filename()
        );
    }

}
