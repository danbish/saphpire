<?php
    // require_once('../includes/File/ANSI.php');
    require_once( '../SSH2.php' );

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

    $ssh = new SSH2( 'devsislin01.clemson.edu' );
    if ( !$ssh->Login( $sUser, $sPass ) )
    {
        exit( 'Login Failed' );
    }

    // $ansi = new File_ANSI();

    echo "<pre>";
    // $ssh->enablePTY();

    // $ssh->setTimeout(1);
    echo $ssh->Cmd( 'uptime' );
    // $ssh->write( 'uptime'. "\n" );
    // echo $ssh->read();

    // $ansi->appendString( $ssh->read() );
    // echo $ansi->getScreen();

    echo "</pre>";
?>