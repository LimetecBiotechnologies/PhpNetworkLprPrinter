<?php

namespace Limetec\PhpNetworkLprPrinter;

/*
 * Class PhpLprPrinter
 * Print your files via PHP with LPR network printer
 * See http://www.faqs.org/rfcs/rfc1179.html to understand RFC 1179 - Line printer daemon protocol
 *
 * (C) Copyright 2011 Pedro Villena <craswer@gmail.com>
 * Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
 */
class PhpNetworkLprPrinter {
    /**
     * Printer's host. Initialize by constructor
     *
     * @var    string
     * @access protected
     * @since  1.0
     */
    private $_host;

    /**
     * Printer's Port. Default port 515 (see constructor), but it can change with the function setPort
     *
     * @var    integer
     * @access protected
     * @since  1.0
     */
    private $_port;

    /**
     * Max seconds to connect to the printer. Default 20, but it can change with the function setTimeOut
     *
     * @var    integer
     * @access protected
     * @since  1.0
     */
    private $_timeout = 30;

    /**
     * Username for printer
     *
     * @var    string
     * @access protected
     * @since  1.0
     */
    private $_username = 'PhpNetworkLprPrinter';

    /**
     * Error number if connection fails
     *
     * @var    integer
     * @access protected
     * @since  1.0
     */
    private $_error_number;

    /**
     * Error message if connection fails
     *
     * @var    integer
     * @access protected
     * @since  1.0
     */
    private $_error_msg;

    /**
     * Debug message
     *
     * @var    array
     * @access protected
     * @since  1.0
     */
    private $_debug = [];

    /**
     * Class constructor.
     *
     * @param string  $host The printer's host
     * @param integer $port The printer's port
     *
     * @since 1.0
     */
    public function __construct ($host, $port = 515) {
        $this->_host = $host;
        $this->_port = $port;
    }

    /**
     * Sets the port
     *
     * @access public
     *
     * @param integer $port Printer's port
     *
     * @since  1.0
     */
    public function setPort (integer $port): void {
        $this->_port = $port;
        $this->setMessage('Setting port: '.$this->_port);
    }

    /**
     * Sets the time out in seconds
     *
     * @access public
     *
     * @param integer $timeout Timeout in seconds
     *
     * @since  1.0
     */
    public function setTimeOut (integer $timeout): void {
        $this->_timeout = $timeout;
        $this->setMessage('Setting time out: '.$this->_timeout);
    }

    /**
     * Gets the error number
     *
     * @access public
     * @return integer    Error number
     * @since  1.0
     */
    public function getErrNo (): int {
        return $this->_error_number;
    }

    /**
     * Gets the error message
     *
     * @access public
     * @return string Error message
     * @since  1.0
     */
    public function getErrStr (): string {
        return $this->_error_msg;
    }

    /**
     * Gets the debug message
     *
     * @access public
     * @return array Debug message array
     * @since  1.0
     */
    public function getDebug (): array {
        return $this->_debug;
    }

    /**
     * Print any waiting jobs
     *
     * @access private
     *
     * @param string $queue
     *
     * @return boolean cfA control String
     * @since  1.0
     */
    public function printWaitingJobs (string $queue): bool {
        //Connecting to the network printer
        $connection = $this->connect();

        //If fail, exit with false
        if (!$connection) {
            $this->setError('Error in connection. Please change HOST or PORT.');

            return false;
        }

        //Print any waiting job
        fwrite($connection, \chr(1).$queue.'\n');
        $this->setMessage('Print any waiting job...');

        //Checking errors
        if (\ord(fread($connection, 1)) !== 0) {
            $this->setError('Error while start print jobs on queue '.$queue);
            //Close connection
            fclose($connection);

            return false;
        }

        //Close connection
        fclose($connection);

        return true;
    }

    /**
     * Print a text message on network lpr printer
     *
     * @access public
     *
     * @param string $text The name of the property
     * @param string $queue
     *
     * @return boolean    True if success
     * @since  1.0
     */
    public function printText (string $text = '', string $queue = 'raw'): ?bool {

        //Initial data
        $jobid = 001; //TODO: Autoincrement $jobid

        //Print any waiting job
        //$this->printWaitingJobs($queue);

        //Connecting to the network printer
        $connection = $this->connect();

        //If fail, exit with false
        if (!$connection) {
            $this->setError('Error in connection. Please change HOST or PORT.');

            return false;
        }

        //Starting printer
        fwrite($connection, \chr(2).$queue.'\n');
        $this->setMessage('Starting printer...');

        //Checking errors
        if (\ord(fread($connection, 1)) !== 0) {
            $this->setError('Error while start printing on queue');
            //Close connection
            fclose($connection);

            return false;
        }

        //Write control file
        $ctrl = $this->makecfA($jobid);
        fwrite($connection, \chr(2).\strlen($ctrl).' cfA'.$jobid.$this->_username.'\n');

        $this->setMessage('Sending control file...');

        //Checking errors
        if (\ord(fread($connection, 1)) !== 0) {
            $this->setError('Error while start sending control file');
            //Close connection
            fclose($connection);

            return false;
        }

        fwrite($connection, $ctrl.\chr(0));
        //Checking errors
        if (\ord(fread($connection, 1)) !== 0) {
            $this->setError('Error while sending control file');
            //Close connection
            fclose($connection);

            return false;
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $hostname = '';
        } else {
            $hostname = gethostname();
        }

        //Send data string
        fwrite($connection, \chr(3).\strlen($text).' dfA'.$jobid.$hostname.'\n');
        $this->setMessage('Sending data...');

        //Checking errors
        if (\ord(fread($connection, 1)) !== 0) {
            $this->setError('Error while sending control file');
            //Close connection
            fclose($connection);

            return false;
        }

        fwrite($connection, $text.\chr(0));
        //Checking errors
        if (\ord(fread($connection, 1)) !== 0) {
            $this->setError('Error while sending control file');
            //Close connection
            fclose($connection);

            return false;
        }

        $this->setMessage('Data received!!!');

        //Close connection
        fclose($connection);

        return true;
    }

    /**
     * Sets a message in the array $_debug
     *
     * @access public
     *
     * @param string $message Message
     * @param string $type    Message's type, for example 'message' or 'error'
     *
     * @since  1.0
     */
    private function setMessage (string $message = '', string $type = 'message'): void {
        $this->_debug[] = ['message' => $message, 'time' => time(), 'type' => $type];
    }

    /**
     * Sets an error message in the array $_debug
     *
     * @access public
     *
     * @param string $error Error message
     *
     * @since  1.0
     */
    private function setError (string $error = ''): void {
        $this->_debug[] = ['message' => $error, 'time' => time(), 'type' => 'error'];
        $this->_error_msg = $error;
    }

    /**
     * Connect to printer
     *
     * @access    private
     * @since     1.0
     */
    private function connect () {
        $this->setMessage('Connecting... Host: '.$this->_host.', Port: '.$this->_port);

        return stream_socket_client('tcp://'.$this->_host.':'.$this->_port, $this->_error_number, $this->_error_msg, $this->_timeout);
    }

    /**
     * Makes de cfA (control string)
     *
     * @access private
     *
     * @param integer $jobid
     *
     * @return string cfA control String
     * @since  1.0
     */
    private function makecfA (integer $jobid): string {
        $this->setMessage('Setting cfA control String');

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $hostname = '';
        } else {
            $hostname = gethostname();
        }

        $cfa = '';
        $cfa .= 'H'.$hostname.'\n'; //hostname
        $cfa .= 'P'.$this->_username.'\n'; //user
        $cfa .= 'ldfA'.$jobid.$hostname.'\n';
        $cfa .= 'UdfA'.$jobid.$hostname.'\n';

        //TODO: Add more parameters. See http://www.faqs.org/rfcs/rfc1179.html

        return $cfa;
    }
}