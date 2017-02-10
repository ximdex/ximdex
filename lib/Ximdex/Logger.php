<?php
/**
 * Created by PhpStorm.
 * User: drzippie
 * Date: 6/11/14
 * Time: 18:11
 */

namespace Ximdex;


Class Logger
{
    private static $instances = array();
    private static $active = '';
    private $logger = null;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param $logger \Monolog\Logger
     * @param string $loggerInstance
     */
    public static function addLog($logger)
    {
        self::$instances[$logger->getName()] = $logger;
        if (count(self::$instances) == 1) {
            self::$active = $loggerInstance;
        }
    }

    public static function get($loggerInstance = 'XMD') {
        if ( !isset( self::$instances[ $loggerInstance ] ) || !self::$instances[ $loggerInstance ] instanceof \Monolog\Logger ) {
            throw \Exception( 'Logger need to be initilized' );
            return;
        }
        return self::$instances[ $loggerInstance ];
    }

    public static function error($string, $loggerInstance = 'XMD')
    {
        try{
            return self::$instances[$loggerInstance]->addError($string);
        }catch (\Exception $e){
            error_log($e->getMessage());
        }
    }

    public static function warning($string, $loggerInstance = 'XMD')
    {
        return self::$instances[$loggerInstance]->addWarning($string);
    }

    public static function debug($string, $loggerInstance = 'XMD')
    {
        try{
            return self::$instances[$loggerInstance]->addDebug($string);
        }catch (\Exception $e){
            error_log($e->getMessage());
        }
    }

    public static function fatal($string, $loggerInstance = 'XMD')
    {
        try{
            return self::$instances[$loggerInstance]->addWarning($string);
        }catch (\Exception $e){
            error_log($e->getMessage());
        }
    }

    public static function write($string, $loggerInstance = 'XMD'){
        return self::info($string, $loggerInstance = 'XMD');
    }

    public static function info($string, $loggerInstance = 'XMD')
    {
        try{
            return self::$instances[$loggerInstance]->addInfo($string);
        }catch (\Exception $e){
            error_log($e->getMessage());
        }
    }

    public static function logTrace($string, $loggerInstance = 'XMD')
    {
        $trace = debug_backtrace(false);
        $t1 = $trace[1];
        $t2 = $trace[2];

        $trace = array(
            'file' => $t1['file'],
            'line' => $t1['line'],
            'function' => $t2['class'] . $t2['type'] . $t2['function']
        );
        $result = $string . PHP_EOL . sprintf("on %s:%s [%s]\n", $trace['file'], $trace['line'], $trace['function']);
        return self::$instances[$loggerInstance]->addInfo( $result );
    }
}