<?php
    require_once( 'sftp-test.php' );

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

    $oTest = new SFTPTest( $sUser, $sPass );

    $oTest->test___construct();
    $oTest->test_init();
    $oTest->test_pwd();
    $oTest->test_LogError();
    $oTest->test__realpath();
    $oTest->test_chdir();
    $oTest->test_nlist();
    $oTest->test_rawlist();
    $oTest->test_list();
    $oTest->test_size();
    $oTest->test_stat();
    $oTest->test_lstat();
    $oTest->test__stat();
    $oTest->test__size();
    $oTest->test_truncate();
    $oTest->test_touch();
    $oTest->test_chown();
    $oTest->test_chmod();
    $oTest->test_chgrp();
    $oTest->test_mkdir();
?>