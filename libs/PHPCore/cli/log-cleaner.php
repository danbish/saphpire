<?php
    // get the configuration file
    require_once( '../config.php' );

    try
    {
        // get the log directory
        $sLogDirectory = cLogger::GetLogDirectory();

        // get the names of the log files
        $aLogFiles = cLogger::GetLogFiles();

        // set max time for logs to be kept
        $iMaxDays = 10;

        // set max file size
        $iMaxSize = 512000;

        // clear out stuff that's over ten days old
        $iLogfileCount = count( $aLogFiles );
        for( $i = 0; $i < $iLogfileCount; ++$i )
        {
            // try to clear out the log files
            $bSuccess = cLogger::ClearXmlLogBefore( $aLogFiles[ $i ], $iMaxDays );

            // check if the clear was successful
            if( !$bSuccess )
            {
                throw new Exception(
                    'Could not clear entries older than '
                    . $iMaxDays
                    . ' days in file '
                    . $aLogFiles[ $i ]
                    . '.'
                );
            }
        }

        // clear out anything that is over the max file size
        for( $i = 0; $i < $iLogfileCount; ++$i )
        {
            // reset the counter
            $iDayCounter = 0;

            // continue clearing until we're under the filesize limit
            while( filesize( $sLogDirectory . '/' . $aLogFiles[ $i ] ) >= $iMaxSize )
            {
                // clear out anything older that the max days - $i
                $bSuccess = cLogger::ClearXmlLogBefore( $aLogFiles[ $i ], $iMaxDays - $iDayCounter );

                // check if the clear was successful
                if( !$bSuccess )
                {
                    throw new Exception(
                        'Could not clear entries older than '
                        . ( $iMaxDays - $iDayCounter  )
                        . ' days in file '
                        . $aLogFiles[ $i ]
                        . '.'
                    );
                }

                // increment the day counter
                ++$iDayCounter;
            }
        }
    }
    catch( Exception $oException )
    {
        BubbleException( $oException );
    }
?>