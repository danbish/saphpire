<?php
    require_once( 'scp-test.php' );

    if ( $_SERVER[ 'REQUEST_METHOD' ] !== 'POST' )
    {
        echo "<form method=post>
                <label>Username:</label><input type='text' name='username' />
                <label>Password:</label><input type='password' name='password' /><br />
                <input type='submit' value='Run Tests' />
              </form>";
        exit();
    }

    $sUser = $_POST[ 'username' ];
    $sPass = $_POST[ 'password' ];

    $oTest = new SCPTest( $sUser, $sPass );

    $oTest->test_put();
    $oTest->test_get();
    $oTest->cleanup();
?>