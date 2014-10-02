<?php
    // require the interface this class implements
    require_once( sCORE_INC_PATH . '/classes/ifAuth.php' );

    /**
     * Base authentication class.
     *
     * Implements functionality that all subclasses will use.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
     */
    class cBaseAuth implements ifAuth
    {
        /**
         * URL to redirect to on successful login.
         *
         * @var string
         */
        protected $sSuccessUrl;

        /**
         * Custom logout function.
         *
         * Set this to a class method that
         * should be called during Logout().
         *
         * @var string
         */
        protected $sLogoutFunction = null;

        /**
         * Session key for current user.
         *
         * @var unknown_type
         */
        protected static $sUserSessionKey = 'sUser';

        /**
         * Extending class must implement this.
         *
         * On successful authentication, call SetUser.
         */
        public function Authenticate() {}

        /**
         * Creates an instance of this object and sets the
         * url to redirect to on successful authentication.
         *
         * @param   string     $sSuccessUrl URL to redirect to on successful authentication.
         *
         * @throws  Exception  Thrown if URL provided is not a string.
         */
        public function __construct( $sSuccessUrl = null )
        {
            try
            {
                // check if a url was provided
                if( !empty( $sSuccessUrl ) )
                {
                    // check if the url is a string
                    if( !is_string( $sSuccessUrl ) )
                    {
                        throw new Exception( 'URL provided is not a string.' );
                    }

                    // set the success url to the value provided
                    $this->sSuccessUrl = $sSuccessUrl;
                }
                else
                {
                    // set the success url to the base url
                    $this->sSuccessUrl = $this->GetProtocol() . '://' . GetHost();
                }
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
            // initialize the return value
            $bAuthenticated = false;

            // check if a user has been authenticated
            if( isset( $_SESSION[ self::$sUserSessionKey ] ) )
            {
                $bAuthenticated = true;
            }

            return $bAuthenticated;
        }

        /**
         * Returns the current user.
         *
         * @return null | string    If a user has not been authenticated, null is returned.
         *                          Otherwise, the current user is returned.
         */
        public static function GetUser()
        {
            $sUser = null;

            // if the user has been authenticated, get it
            if( !empty( $_SESSION[ self::$sUserSessionKey ] ) )
            {
                $sUser = $_SESSION[ self::$sUserSessionKey ];
            }

            return $sUser;
        }

        /**
         * Sets the current user in the session.
         *
         * @param   string      $sUser
         *
         * @throws  Exception   Thrown if anything other than a string is sent in.
         */
        public function SetUser( $sUser )
        {
            // check if the user provided is a string
            if( is_string( $sUser ) )
            {
                // set the username in the session
                $_SESSION[ self::$sUserSessionKey ] = $sUser;
            }
            else
            {
                throw Exception( 'User provided is not a string.' );
            }
        }

        /**
         * Clear all user data from the session.
         *
         * If any extra processing is required to log the user
         * out of the system, set $this->sLogoutFunction to a
         * method of the subclass.
         *
         * All parameters passed into this function will be
         * passed into the logout function provided.
         *
         * @throws  Exception  Rethrows anything thrown from a lower level
         */
        public function Logout()
        {
            try
            {
                // check if someone is logged in
                if( $this->IsAuthenticated() )
                {
                    // check if a logout function was provided
                    if( !empty( $this->sLogoutFunction ) )
                    {
                        $this->{$this->sLogoutFunction}( func_get_args() );
                    }

                    // clear out the session
                    session_unset();
                }
            }
            catch( Exception $oException )
            {
                throw BubbleException( $oException );
            }
        }

        /**
         * Checks if the current connection is secured through HTTPS.
         *
         * @return boolean
         */
        public function IsSecure()
        {
            // initialize the return value
            $bIsSecure = false;

            // check if this request was secure
            if( isset( $_SERVER[ 'HTTPS' ] ) && !empty( $_SERVER[ 'HTTPS' ] ) )
            {
                $bIsSecure = true;
            }

            return $bIsSecure;
        }

        /**
         * Returns the current protocol.
         *
         * @return  string  http | https
         */
        public function GetProtocol()
        {
            // initialize  the protocol
            $sProtocol = 'http';

            // check if this is secure
            if( $this->IsSecure() )
            {
                $sProtocol = 'https';
            }

            return $sProtocol;
        }
    }
?>