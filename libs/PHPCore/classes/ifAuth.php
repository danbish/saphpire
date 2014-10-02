<?php
    /**
     * Interface for authentication systems.
     *
     * @author  Ryan Masters
     *
     * @package Core_0
     * @version 0.1
     */
    interface ifAuth
    {
        /**
         * Checks if a user has been authenticated
         * and if not, attempts to authenticate.
         */
        public function Authenticate();

        /**
         * Checks if a user has been authenticated.
         *
         * @return boolean
         */
        public function IsAuthenticated();

        /**
         * Returns the currently authenticated user.
         *
         * @return string
         */
        public static function GetUser();

        /**
         * Sets the supplied username as the
         * currently authenticated user.
         *
         * @param string $sUser
         */
        public function SetUser( $sUser );

        /**
         * Removes the authentication from the
         * currently authenticated user.
         */
        public function Logout();
    }
?>