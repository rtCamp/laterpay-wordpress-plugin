<?php

class LaterPay_Core_Logger
{

    const DEBUG     = 100;
    const INFO      = 200;
    const NOTICE    = 250;
    const WARNING   = 300;
    const ERROR     = 400;
    const CRITICAL  = 500;
    const ALERT     = 550;
    const EMERGENCY = 600;

    /**
     * contains all debugging levels.
     *
     * @var array
     */
    protected $levels = array(
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    );

    /**
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $name;


    /**
     * The handler stack
     *
     * @var LaterPay_Core_Logger_Handler_Interface[]
     */
    protected $handlers;

    /**
     * Processors that will process all log records
     *
     * To process records of a single handler instead, add the processor on that specific handler
     *
     * @var callable[]
     */
    protected $processors;

    /**
     * @param string $name       The logging channel
     * @param LaterPay_Core_Logger_Handler_Interface[] $handlers   Optional stack of handlers, the first one in the array is called first, etc.
     * @param callable[] $processors Optional array of processors
     */
    public function __construct($name = 'default', array $handlers = array(), array $processors = array()) {
        $this->name = $name;
        $this->handlers = $handlers;
        $this->processors = $processors;
        $this->timezone = new DateTimeZone( date_default_timezone_get() ?: 'UTC' );
    }

    /**
     * Add a log record at the DEBUG level.
     *
     * @param string $message The log message
     * @param array  $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function debug( $message, array $context = array() ) {
        return $this->add_record( self::DEBUG, $message, $context );
    }

    /**
     * Add a log record at the ERROR level.
     *
     * @param string $message The log message
     * @param array  $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function error( $message, array $context = array() ) {
        return $this->add_record( self::ERROR, $message, $context );
    }

    /**
     * Adds a log record at the INFO level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function info( $message, array $context = array() ) {
        return $this->add_record( self::INFO, $message, $context );
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function notice( $message, array $context = array() ) {
        return $this->add_record( self::NOTICE, $message, $context );
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function warning( $message, array $context = array() ) {
        return $this->add_record( self::WARNING, $message, $context );
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function critical( $message, array $context = array() ) {
        return $this->add_record( self::CRITICAL, $message, $context );
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function alert( $message, array $context = array() ) {
        return $this->add_record( self::ALERT, $message, $context );
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emergency( $message, array $context = array() ) {
        return $this->add_record( self::EMERGENCY, $message, $context );
    }
    
    /**
     * Add a record to the log.
     *
     * @param integer $level
     * @param string  $message
     * @param array   $context
     *
     * @return boolean
     */
    public function add_record( $level, $message, array $context = array() ) {

        if ( !$this->handlers ) {
            $this->push_handler( new LaterPay_Core_Logger_Handler_Null( ) );
        }

        $date_time = new DateTime();
        $date_time->setTimezone( $this->timezone );

        $record = array(
            'message'       => (string) $message,
            'context'       => $context,
            'level'         => $level,
            'level_name'    => self::get_level_name( $level ),
            'channel'       => $this->name,
            'datetime'      => $date_time,
            'extra'         => array(),
        );

        // check if any handler will handle this message
        $handler_key = null;
        foreach ( $this->handlers as $key => $handler ) {
            if ( $handler->is_handling( $record ) ) {
                $handler_key = $key;
                break;
            }
        }
        // none found
        if ( $handler_key === null ) {
            return false;
        }

        // found at least one, process message and dispatch it
        foreach ( $this->processors as $processor ) {

            $record = call_user_func($processor, $record);
        }
        while ( isset( $this->handlers[$handler_key] ) && $this->handlers[ $handler_key]->handle( $record ) === false) {
            $handler_key++;
        }

        return true;

    }

    /**
     * @return string
     */
    public function get_name(){
        return $this->name;
    }

    /**
     * Pushes a handler on to the stack.
     *
     * @param LaterPay_Core_Logger_Handler_Interface $handler
     */
    public function push_handler( LaterPay_Core_Logger_Handler_Interface $handler) {
        array_unshift( $this->handlers, $handler );
    }

    /**
     * Pops a handler from the stack
     *
     * @return LaterPay_Core_Logger_Handler_Interface
     */
    public function pop_handler() {
        if ( ! $this->handlers ) {
            throw new \LogicException('You tried to pop from an empty handler stack.');
        }
        return array_shift( $this->handlers );
    }

    /**
     * @return LaterPay_Core_Logger_Handler_Interface[]
     */
    public function get_handlers() {
        return $this->handlers;
    }

    /**
     * Adds a processor on to the stack.
     *
     * @param callable $callback
     */
    public function push_processor( $callback ) {
        if ( ! is_callable( $callback ) ) {
            throw new \InvalidArgumentException('Processors must be valid callables (callback or object with an __invoke method), '.var_export($callback, true).' given');
        }
        array_unshift( $this->processors, $callback );
    }

    /**
     * Removes the processor on top of the stack and returns it.
     *
     * @return callable
     */
    public function pop_processor() {
        if ( ! $this->processors ) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }

        return array_shift( $this->processors );
    }

    /**
     * @return callable[]
     */
    public function get_processors() {
        return $this->processors;
    }

    /**
     * Checks whether the Logger has a handler that listens on the given level
     *
     * @param  integer $level
     * @return Boolean
     */
    public function is_handling( $level ) {
        $record = array(
            'level' => $level,
        );

        foreach ( $this->handlers as $handler ) {
            if ( $handler->is_handling( $record ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the name of the logging level.
     *
     * @param integer $level
     *
     * @return string $level_name
     */
    public function get_level_name( $level ) {
        return $this->levels [$level ];
    }

}
