<?php
/**
 * Extend the HestiaCP Pluginable object with our MagicLink object for
 * securing entire website domain from public view unless a magiclink
 * URL is first visited by the user's web browser.
 * 
 * @author Virtuosoft/Stephen J. Carnam
 * @license AGPL-3.0, for other licensing options contact support@virtuosoft.com
 * @link https://github.com/virtuosoft-dev/hcpp-magiclink
 * 
 */

 if ( ! class_exists( 'MagicLink' ) ) {
    class MagicLink extends HCPP_Hooks {
        public $magiclink_folder = "/usr/local/hestia/data/hcpp/magiclink";
        /**
         * Append the Magic Link checkbox and URL input field to the web form
         */
        public function hcpp_edit_web_xpath( $xpath ) {
            global $hcpp;

            // Add "Enable private Magic Link" checkbox and "Magic Link URL" textbox
            $checkboxNode = $xpath->query('//div[@class="form-check u-mb10"][input[@id="v-redirect-checkbox"]]')->item(0);
            if ($checkboxNode) {
                $doc = $xpath->document; // Get the DOMDocument associated with the DOMXPath

                // Create the parent container div with x-data attribute
                $parentDiv = $doc->createElement('div');
                $parentDiv->setAttribute('x-data', '{ magicLinkEnabled: false }');

                // Create the checkbox div
                $checkboxDiv = $doc->createElement('div');
                $checkboxDiv->setAttribute('class', 'form-check u-mb10');

                // Create the checkbox input
                $checkboxInput = $doc->createElement('input');
                $checkboxInput->setAttribute('x-model', 'magicLinkEnabled');
                $checkboxInput->setAttribute('class', 'form-check-input');
                $checkboxInput->setAttribute('type', 'checkbox');
                $checkboxInput->setAttribute('name', 'v-magiclink-checkbox');
                $checkboxInput->setAttribute('id', 'v-magiclink-checkbox');

                // Create the checkbox label
                $checkboxLabel = $doc->createElement('label', 'Require secret Magic Link to visit site');
                $checkboxLabel->setAttribute('for', 'v-magiclink-checkbox');

                // Add help icon link
                $helpIcon = $doc->createElement('a');
                $helpIcon->setAttribute('href', 'https://devstia.com/about-magiclink');
                $helpIcon->setAttribute('target', '_blank');
                $helpIcon->setAttribute('class', 'u-ml5');
                $helpIcon->appendChild($doc->createElement('i'))->setAttribute('class', 'fas fa-question-circle');

                // Append help icon to the label
                $checkboxLabel->appendChild($helpIcon);

                // Append checkbox input and label to the checkbox div
                $checkboxDiv->appendChild($checkboxInput);
                $checkboxDiv->appendChild($checkboxLabel);

                // Create the container div for Magic Link URL
                $containerDiv = $doc->createElement('div');
                $containerDiv->setAttribute('x-cloak', '');
                $containerDiv->setAttribute('x-show', 'magicLinkEnabled');
                $containerDiv->setAttribute('class', 'u-pl30 u-mb10');

                // Create the inner div
                $innerDiv = $doc->createElement('div');
                $innerDiv->setAttribute('class', 'u-mb10');

                // Create the label
                $label = $doc->createElement('label', 'Magic Link URL');
                $label->setAttribute('for', 'v-magiclink-url');
                $label->setAttribute('class', 'form-label');

                // Create the input
                $input = $doc->createElement('input');
                $input->setAttribute('type', 'text');
                $input->setAttribute('class', 'form-control');
                $input->setAttribute('style', 'font-family: monospace;');
                $input->setAttribute('name', 'v-magiclink-url');
                $input->setAttribute('id', 'v-magiclink-url');
                $input->setAttribute('readonly', 'readonly');
                $input->setAttribute('onclick', 'copyToClipboard(this)'); // Add onclick event

                // Determine the magic link URL
                $domain = $_GET['domain'];
                $domain = filter_var($domain, FILTER_SANITIZE_URL);
                $user = $_SESSION["user"];
                if ($_SESSION["look"] != "") {
                    $user = $_SESSION["look"];
                }
                $link = $hcpp->run( "v-invoke-plugin magiclink_get $user $domain" );
                if ($link == '') {
                    $link = 'http://' . $domain . '/' . $hcpp->random_chars( 12 );
                }else{
                    $link = 'http://' . $domain . '/' . $link;
                    $parentDiv->setAttribute('x-data', '{ magicLinkEnabled: true }');
                }
                $input->setAttribute( 'value', $link );

                // Append label and input to the inner div
                $innerDiv->appendChild($label);
                $innerDiv->appendChild($input);

                // Append the inner div to the container div
                $containerDiv->appendChild($innerDiv);

                // Create the small text
                $smallText = $doc->createElement('small', 'Note: click to copy to clipboard after saving form.');

                // Append the small text to the container div
                $containerDiv->appendChild($smallText);

                // Append checkbox div and container div to the parent div
                $parentDiv->appendChild($checkboxDiv);
                $parentDiv->appendChild($containerDiv);

                // Insert the parent div into the DOM as the previous sibling
                $checkboxNode->parentNode->insertBefore($parentDiv, $checkboxNode);

                // Locate the closing body tag and insert the script before it
                $bodyNode = $xpath->query('//body')->item(0);
                if ($bodyNode) {
                    $script = $doc->createElement('script', '
                        function copyToClipboard(element) {
                            navigator.clipboard.writeText(element.value).then(function() {
                                alert("Copied to clipboard: " + element.value);
                            }).catch(function(error) {
                                console.error("Clipboard copy failed:", error);
                            });
                        }

                        document.addEventListener("DOMContentLoaded", function() {
                            const sslCheckbox = document.getElementById("v_ssl");
                            const magicLinkInput = document.getElementById("v-magiclink-url");
                            const updateMagicLinkProtocol = () => {
                                if (sslCheckbox.checked) {
                                    magicLinkInput.value = magicLinkInput.value.replace(/^http:/, "https:");
                                } else {
                                    magicLinkInput.value = magicLinkInput.value.replace(/^https:/, "http:");
                                }
                            };
                            sslCheckbox.addEventListener("change", updateMagicLinkProtocol);
                            updateMagicLinkProtocol(); // Initial check on page load
                        });
                    ');
                    $bodyNode->appendChild($script);
                }
            }

            return $xpath;
        }

        /** 
         * Modify open website link to use magiclink 
         */
        public function hcpp_list_web_html( $html ) {
            global $hcpp;

            // Get the active user
            $user = $_SESSION["user"];
            if ($_SESSION["look"] != "") {
                $user = $_SESSION["look"];
            }
            $links = $hcpp->run( "v-invoke-plugin magiclink_get $user json" );

            // Cycle through the links object
            foreach ( $links as $domain => $link ) {
                $orig = '://' . $domain . '/" target="_blank"';
                $magic = '://' . $domain . '/' . $link . '" target="_blank"';
                $html = str_replace($orig, $magic, $html);
            }
            return $html;
        }

        /**
         * Save the Magic Link URL to a file
         */
        public function hcpp_ob_started( $args ) {
            global $hcpp;
            if ( ! isset( $_REQUEST['save'] ) ||  !isset( $_REQUEST['v-magiclink-url'] ) ) return $args;
            if ( $_REQUEST['save'] != 'save' ) return $args;

            // Access session variablesariables
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            // Validate token
            if ( !isset($_SESSION['token']) || $_SESSION['token'] !== $_REQUEST['token'] ) return $args;

            // Get user and domain
            $user = $_SESSION["user"];
            if ($_SESSION["look"] != "") {
                $user = $_SESSION["look"];
            }
            $domain = $_REQUEST['v_domain'];
            $domain = filter_var($domain, FILTER_SANITIZE_URL);

            if ( isset( $_REQUEST['v-magiclink-checkbox'] ) && $_REQUEST['v-magiclink-checkbox'] == 'on' ) {
                $link = $_REQUEST['v-magiclink-url'];
                $link = filter_var($link, FILTER_SANITIZE_URL);
                $link = $hcpp->getRightMost( $link, '/' );
                $hcpp->run( "v-invoke-plugin magiclink_update $user $domain $link" );
            } else {
                $hcpp->run( "v-invoke-plugin magiclink_update $user $domain" );
            }
            return $args;
        }

        /**
         * Process protected commands
         */
        public function hcpp_invoke_plugin( $args ) {
            global $hcpp;

            // Update magiclink file
            if ( $args[0] == 'magiclink_update' ) {
                if ( isset( $args[1] ) ) {
                    $username = preg_replace( "/[^a-zA-Z0-9-_]+/", "", $args[1] ); // Sanitized username
                }
                if ( isset( $args[2] ) ) {
                    $domain = preg_replace( "/[^a-zA-Z0-9-_.]+/", "", $args[2] ); // Allow periods in domain
                }
    
                if ( !file_exists( $this->magiclink_folder ) ) {
                    mkdir( $this->magiclink_folder, 0700, true );
                }
                $magiclink_file = "{$this->magiclink_folder}/{$username}_{$domain}";
                if ( isset( $args[3] ) ) {
                    $magiclink = preg_replace( "/[^a-zA-Z0-9-_]+/", "", $args[3] ); // Sanitized magiclink
                }
                if ( is_null( $magiclink ) ) {
                    unlink ( $magiclink_file );
                }else{
                    file_put_contents( $magiclink_file, $magiclink );
                    chown( $magiclink_file, $username );
                    chgrp( $magiclink_file, $username );
                    chmod( $magiclink_file, 0600 );
                    $hcpp->log( "Magic Link: {$magiclink_file} created with ${magiclink}" );
                }
            }

            // Return magiclink file
            if ( $args[0] == 'magiclink_get' ) {
                if ( isset( $args[1] ) ) {
                    $username = preg_replace( "/[^a-zA-Z0-9-_]+/", "", $args[1] ); // Sanitized username
                }
                if ( ! isset( $username ) ) {
                    return $args;
                }
                if ( isset( $args[2] ) ) {
                    $domain = preg_replace( "/[^a-zA-Z0-9-_.]+/", "", $args[2] ); // Allow periods in domain
                }
    
                if ( !file_exists( $this->magiclink_folder ) ) {
                    mkdir( $this->magiclink_folder, 0700, true );
                }

                if ( isset( $domain ) && $domain != 'json' ) {

                    // Return specific domain magiclink
                    $magiclink_file = "{$this->magiclink_folder}/{$username}_{$domain}";
                    if ( file_exists( $magiclink_file ) ) {
                        echo file_get_contents( $magiclink_file );
                    } else {
                        echo "";
                    }
                }else{

                    // Return all magiclinks for the given user as json array
                    $magiclink_files = glob( "{$this->magiclink_folder}/{$username}_*" );
                    $magiclinks = [];
                    foreach ( $magiclink_files as $file ) {
                        $filename = basename( $file );
                        $domain = preg_replace( "/^{$username}_/", "", $filename );
                        $domain = preg_replace( "/[^a-zA-Z0-9-_.]+/", "", $domain ); // Allow periods in domain
                        if ( file_exists( $file ) ) {
                            $magiclinks[$domain] = file_get_contents( $file );
                        }
                    }
                    echo json_encode($magiclinks, JSON_PRETTY_PRINT);
                }
            }
            return $args;
        }
    }
    global $hcpp;
    $hcpp->register_plugin( MagicLink::class );
}