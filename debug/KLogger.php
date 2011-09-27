<?php
	
	/* Finally, A light, permissions-checking logging class. 
	 * 
	 * Author	: Kenneth Katzgrau < katzgrau@gmail.com >
	 * Date	: July 26, 2008
	 * Comments	: Originally written for use with wpSearch
	 * Website	: http://codefury.net
	 * Version	: 1.0
	 *
	 * Usage: 
	 *		$log = new KLogger ( "log.txt" , KLogger::INFO );
	 *		$log->LogInfo("Returned a million search results");	//Prints to the log file
	 *		$log->LogFATAL("Oh dear.");				//Prints to the log file
	 *		$log->LogDebug("x = 5");					//Prints nothing due to priority setting
	*/
	
	class KLogger
	{
		
		const DEBUG 	= 1;	// Most Verbose
		const INFO 		= 2;	// ...
		const WARN 		= 3;	// ...
		const ERROR 	= 4;	// ...
		const FATAL 	= 5;	// Least Verbose
		const OFF 		= 6;	// Nothing at all.
		
		const LOG_OPEN 		  = 1;
		const OPEN_FAILED   = 2;
		const LOG_CLOSED 	  = 3;

		/* Public members: Not so much of an example of encapsulation, but that's okay. */
		public $Log_Status 	= KLogger::LOG_CLOSED;
		public $DateFormat	= "Y-m-d G:i:s";
		public $MessageQueue;
	
		private $log_file;
		private $priority = KLogger::INFO;
		private $lastline = NULL;
		
		private $file_handle;
		
		public function __construct( $filepath , $priority, $overwrite = false )
		{
			if ( $priority == KLogger::OFF ) return;
			
			$this->log_file = $filepath;
			$this->MessageQueue = array();
			$this->priority = $priority;
			
			if ( file_exists( $this->log_file ) )
			{
				if ( !is_writable($this->log_file) )
				{
					$this->Log_Status = KLogger::OPEN_FAILED;
					$this->MessageQueue[] = "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.";
					return;
				}
			}
			
			$mode = 'a';
			if ( $overwrite ) {
          $mode = 'w';
      }
      
			if ( $this->file_handle = fopen( $this->log_file , "$mode" ) )
			{
				$this->Log_Status = KLogger::LOG_OPEN;
				$this->MessageQueue[] = "The log file was opened successfully.";
			}
			else
			{
				$this->Log_Status = KLogger::OPEN_FAILED;
				$this->MessageQueue[] = "The file could not be opened. Check permissions.";
			}
			
			return;
		}
		
		public function __destruct()
		{
			if ( $this->file_handle )
				fclose( $this->file_handle );
		}
		
		public function LogInfo($line)
		{
			$this->Log( $line , KLogger::INFO );
		}
		
		public function LogDebug( $line, $args = array() )
		{
/*
   Added by Bianka Martinovic, info@webbird.de; some more info about
   call stack on debug level
*/
        $bt = debug_backtrace();
        $class     = isset( $bt[1]['class'] )    ? $bt[1]['class'] : NULL;
        $function  = isset( $bt[1]['function'] ) ? $bt[1]['function'] : NULL;
        $file      = basename($bt[1]['file']);
        $code_line = $bt[1]['line'];
        $line      = "[$function()] $line [ $file:$code_line ]";
/*
   Added by Bianka Martinovic, info@webbird.de;
   option to dump some additional data
*/

        if ( $args ) {
            $dump = print_r( $args, 1 );
            $dump = preg_replace( "/\r?\n/", "\n          ", $dump );
            $line .= "\n"
                  .  print_r( $dump, 1 )
                  .  "\n";
            
        }

			$this->Log( $line , KLogger::DEBUG );
		}
		
		public function LogWarn($line)
		{
			$this->Log( $line , KLogger::WARN );	
		}
		
		public function LogError($line)
		{
			$this->Log( $line , KLogger::ERROR );		
		}

		public function LogFatal($line)
		{
			$this->Log( $line , KLogger::FATAL );
		}
		
		public function Log($line, $priority)
		{
			if ( $this->priority <= $priority )
			{
				$status = $this->getTimeLine( $priority );
				$this->WriteFreeFormLine ( "$status $line \n" );
				$lastline = "$status $line";
			}
		}
		
		public function WriteFreeFormLine( $line )
		{
			if ( $this->Log_Status == KLogger::LOG_OPEN && $this->priority != KLogger::OFF )
			{
			    if (fwrite( $this->file_handle , $line ) === false) {
			        $this->MessageQueue[] = "The file could not be written to. Check that appropriate permissions have been set.";
			    }
			}
		}
		
		public function getLastLog()
		{
		    return $lastline;
		}
		
		private function getTimeLine( $level )
		{
			$time = gmdate( $this->DateFormat );
		
			switch( $level )
			{
				case KLogger::INFO:
					return "$time - INFO  -->";
				case KLogger::WARN:
					return "$time - WARN  -->";				
				case KLogger::DEBUG:
					return "$time - DEBUG -->";				
				case KLogger::ERROR:
					return "$time - ERROR -->";
				case KLogger::FATAL:
					return "$time - FATAL -->";
				default:
					return "$time - LOG   -->";
			}
		}
		
	}


?>