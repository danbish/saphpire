<?php
    // require the abstract class that this extends
    require_once( sCORE_INC_PATH . '/classes/cBaseAuth.php' );

    /**
     * Authentication class that uses the cookie based CUTokenAuth system.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.2
     */
    class cCUTokenAuth extends cBaseAuth
    {
        /**
         * Custom logout function.
         *
         * @var string
         */
        protected $sLogoutFunction = 'ClearCookie';

        /**
         * Set the URL to redirect to on successful login
         * and redirect to login.clemson.edu for authentication.
         *
         * @throw   Exception
         */
        protected function LoginRedirect()
        {
            try
            {
                // set cookies to know where to redirect on success
                setcookie( 'AUTHURL', $this->sSuccessUrl, 0, "/", '.clemson.edu', false, true);
                setcookie( 'AUTHREASON', '', 0, "/", '.clemson.edu', false, true);

                // redirect to the login site
                header( 'Location: https://login.clemson.edu' );
                die();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Cookie based authentication.
         *
         * Check for cookie:
         *  - if it exists, verify it.
         *  - if it doesn't exist or it's invalid:
         *      - redirect to login.clemson.edu
         *  - on success, set the user and redirect to the home page
         */
        public function Authenticate()
        {
            try
            {
                // check if we're already authenticated
                if( !$this->IsAuthenticated() )
                {
                    // check for cookie
                    if( isset( $_COOKIE[ 'CUTOKENTNG' ] ) )
                    {
                        // check if the user is valid
                        ini_set( 'soap.wsdl_cache_enabled', 0 );
                        $oClient = new SoapClient( 'http://login.clemson.edu/authdotnet.php?wsdl' );
                        $oResult = $oClient->verifyAuthToken( $_COOKIE[ 'CUTOKENTNG' ] );

                        // if the user is not valid, redirect them
                        if( !empty( $oResult->ERROR ) )
                        {
                            // redirect to login.clemson.edu
                            $this->LoginRedirect();
                        }
                        else
                        {
                            // save the user id
                            $this->SetUser( $oResult->USERID );
                        }
                    }
                    else
                    {
                        // redirect to login.clemson.edu
                        $this->LoginRedirect();
                    }
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Clear all cookies used during authentication.
         *
         * Called during Logout().
         */
        public function ClearCookie()
        {
            // set the time to negative to clear the cookies
            $iNegativeTime = time() - 3600;

            // clear all cookies
            setcookie( 'CUTOKENTNG', "", $iNegativeTime, "/", '.clemson.edu', false, true );
            setcookie( 'AUTHURL', $this->sSuccessUrl, $iNegativeTime, "/", '.clemson.edu', false, true);
            setcookie( 'AUTHREASON', '', $iNegativeTime, "/", '.clemson.edu', false, true);
        }
    }
?>