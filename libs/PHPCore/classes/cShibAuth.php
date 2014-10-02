<?php
    // require the abstract class that this extends
    require_once( sCORE_INC_PATH . '/classes/cBaseAuth.php' );

    // require the request class so we can check if the user is logged in
    require_once( sCORE_INC_PATH . '/classes/cRequest.php' );

    /**
     * Authentication class that utilizies Shibboleth.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
     */
    class cShibAuth extends cBaseAuth
    {
        /**
         * Custom logout function.
         *
         * @var string
         */
        protected $sLogoutFunction = 'ClearShibData';

        /**
         * Set the URL to redirect to on successful login
         * and redirect to login.clemson.edu for authentication.
         *
         * @throws  Exception
         */
        protected function LoginRedirect()
        {
            try
            {
                // set cookies to know where to redirect on success
                setcookie( 'AUTHURL', $this->sSuccessUrl, 0, "/", '.clemson.edu', false, true);
                setcookie( 'AUTHREASON', '', 0, "/", '.clemson.edu', false, true);

                // redirect to the login site
                header( 'Location: ' . $this->GetProtocol() . '://' . GetHost() . '/Shibboleth.sso/Login?target=' . $this->sSuccessUrl );
                die();
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Checks if a user is currently saved in the session.
         *
         * @return boolean
         */
        public function IsAuthenticated()
        {
            try
            {
                // initialize return value
                return isset( $_SERVER[ 'cn' ] );
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Extending class must implement this.
         *
         * On successful authentication, call SetUser.
         */
        public function Authenticate()
        {
            try
            {
                // check if we're already authenticated
                if( !$this->IsAuthenticated() )
                {
                    $this->LoginRedirect();
                }

                if( isset( $_SERVER[ 'cn' ] ) )
                {
                    $this->SetUser( $_SERVER[ 'cn' ] );
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * @TODO: figure out how to logout with shib
         */
        public function ClearShibData()
        {

        }
    }
?>