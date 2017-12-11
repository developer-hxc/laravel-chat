<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Workerman\Worker;

class WebSocket extends Command
{
    private $worker;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workerman {action : workerman的启动与停止命令} {--count=1 : 当前Worker实例启动多少个进程，不设置时默认为1} {--d : 守护进程运行} {--port=0.0.0.0:1234 : 端口号} {--name=none : 当前Worker实例的名称，方便运行status命令时识别进程。}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动websocket协议';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->globalWorkerMan();
        $this->worker = new Worker('websocket://'.$this->option('port'));
        $this->worker->count = $this->option('count');
        $this->worker->name = $this->option('name');
        $this->worker->uidConnections = [];
        $this->onMessage();
        $this->onClose();
        Worker::runAll();
    }


    public function globalWorkerMan()
    {
        global $argv;
        $action = $this->argument('action');
        if(!in_array($action,['start','stop','restart','reload','status','connections'])){
            $this->error('没有此方法');
            exit;
        }
        $argv[0] = 'workerman';
        $argv[1] = $action;
        $argv[2] = $this->option('d')?'-d':'';
    }

    /**
     * @param $connection
     * @param $uid
     */
    public function addUidConnections($connection,$uid)
    {
        if(!isset($connection->uid)){
            $connection->uid = $uid;
            $this->worker->uidConnections[$uid] = $connection;
        }
    }

    /**
     * 接收客户端数据回调函数
     */
    public function onMessage()
    {
        $this->worker->onMessage = function($connection,$data){
            $data_arr = json_decode($data,true);
            if(isset($data_arr['uid'])){
                $this->addUidConnections($connection,$data_arr['uid']);
            }
            if(isset($data_arr['message'])&&isset($data_arr['uid'])){
                $this->broadcast($data_arr['message'],$data_arr['uid']);
            }
        };
    }

    /**
     * 当客户端断开连接时回调函数
     */
    public function onClose()
    {
        $this->worker->onClose = function($connection){
            if(isset($connection->uid)){
                unset($this->worker->uidConnections[$connection->uid]);
            }
        };
    }

    /**
     * 广播，发送给所有人
     * @param $message
     */
    public function broadcast($message,$uid)
    {
        $data = [
            'message' => $message,
            'uid'     => $uid,
            'time'    => date('Y-m-d H:i:s')
        ];
        foreach ($this->worker->uidConnections as $connection){
            $connection->send(json_encode($data));
        }
    }
}
