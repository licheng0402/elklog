<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 10:11
 */
namespace ElkLog;

use Yii;
use yii\log\Target;

class ElkTarget extends Target
{
    /**
     * @var string 服务名称
     */
    protected $serverName;

    /**
     * @var string 日志数据上传的topic名称
     */
    protected $topic;

    /**
     * 请求跟踪码传输方式
     */
    protected $traceType = 'request';

    /**
     * 服务版本
     */
    protected $serviceVersion;

    /**
     * 对象初始化
     */
    public function init()
    {
        parent::init();
        // 服务名称，需要配置
        $this->serverName = '服务名称';
        // 可选 request  header
        $this->traceType = 'request';
        //服务版本号
        $this->serviceVersion = '';
    }

    /**
     * 写入log
     */
    public function export()
    {
        if ($this->messages){
            echo '<pre>';
            foreach ($this->messages as $msg){
                $messages = $this->formatMessage($msg);
                // kafka 入队
                $this->messageToKafka($messages);
                echo $messages."<br>";

            }
        }
    }

    /**
     * 格式化消息
     *
     * @param array $message
     *
     * @return false|string
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        $file = isset($message[4][0]['file'])?$message[4][0]['file']:'-';
        $line = isset($message[4][0]['line'])?$message[4][0]['line']:'-';
        $logData = array(
            '@timestamp' => $this->getTime($timestamp), // 写日志的时间
            'category' => $category, // 日志种类
            'level' => self::getLevelName($level), // 日志级别
            'message' => $text, // 日志内容字符串
            'file' => $file, // 日志是在哪个文件中记录的
            'line' => $line, // 日志是在哪行记录的
            'traceId' => $this->getTraceId(), // 日志跟踪码
            'serverName' => $this->serverName, //服务名称
            'userIp' => Yii::$app->request->userIP,//客户端ip
            'hostName' => Yii::$app->request->hostName,//hostName
        );
        return json_encode($logData,true);
    }

    /**
     * 获取消息级别
     *
     * @param $level
     *
     * @return mixed|string
     */
    protected static function getLevelName($level)
    {
        static $levels = [
            Logger::LEVEL_WARNING => 'warning',
            Logger::LEVEL_ERROR => 'error',
            Logger::LEVEL_INFO => 'info',
            Logger::LEVEL_TRACE => 'debug',
            Logger::LEVEL_PROFILE => 'profile',
        ];
        return isset($levels[$level]) ? $levels[$level] : 'unknown';
    }

    /**
     * 格式化时间
     *
     * @param float $timestamp
     *
     * @return false|string
     */
    protected function getTime($timestamp)
    {
        $parts = explode('.', sprintf('%F', $timestamp));
        return date(DATE_W3C, $parts[0]);
    }

    /**
     * 获取跟踪码
     *
     * @return array|mixed|string
     */
    protected function getTraceId()
    {
        $traceId = '';
        if ($this->traceType == 'header'){
            $headers = Yii::$app->request->headers;
            $traceId = $headers->get('TraceId')?$headers->get('TraceId'):'';
        }elseif ($this->traceType == 'request'){
            $traceId = Yii::$app->request->get('traceId','');
        }
        return $traceId;
    }

    public function messageToKafka($message){
        //todo 重写该方法，做kafka入队处理
    }

}
