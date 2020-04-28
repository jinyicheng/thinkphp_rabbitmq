<?php

namespace jinyicheng\thinkphp_rabbitmq;

use Closure;
use InvalidArgumentException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Rabbitmq
{
    private static $instance = [];
    private $options;
    private $channel;
    private $AMQPStreamConnection;

    /**
     * Rabbitmq constructor.
     * @param array $options
     */
    private function __construct($options = [])
    {
        $this->options = $options;
        /**
         * 创建连接
         * @var string $this ->options['connection']['host'] RabbitMQ服务器主机IP地址
         * @var string $this ->options['connection']['port'] RabbitMQ服务器端口
         * @var string $this ->options['connection']['user'] 连接RabbitMQ服务器的用户名
         * @var string $this ->options['connection']['password'] 连接RabbitMQ服务器的用户密码
         * @var string $this ->options['connection']['vhost'] 连接RabbitMQ服务器的vhost（服务器可以有多个vhost，虚拟主机，类似nginx的vhost）
         */
        $this->AMQPStreamConnection = new AMQPStreamConnection(
            $this->options['connection']['host'],
            $this->options['connection']['port'],
            $this->options['connection']['user'],
            $this->options['connection']['password'],
            $this->options['connection']['vhost']
        );
        /**
         * 获取信道
         * @var string $this ->options['connection']['channel']['id'] 信道id，不传则获取$channel['']信道，再无则循环$this->channel数组，下标从1到最大信道数找第一个不是AMQPChannel对象的下标，实例化并返回AMQPChannel对象，无则抛出异常No free channel ids
         */
        $this->channel = $this->AMQPStreamConnection->channel(
            $this->options['connection']['channel']['id']
        );
        /**
         * 在信道里创建交换器
         * @var string $this ->options['connection']['channel']['exchange']['name'] 交换器名字
         * @var string $this ->options['connection']['channel']['exchange']['type'] 交换器类型  ''      默认交换机 匿名交换器 未显示声明类型都是该类型
         * fanout  扇形交换器 会发送消息到它所知道的所有队列，每个消费者获取的消息都是一致的
         * headers 头部交换器
         * direct  直连交换器，该交换机将会对绑定键（binding key）和路由键（routing key）进行精确匹配
         * topic   话题交换器 该交换机会对路由键正则匹配，必须是*(一个单词)、#(多个单词，以.分割) 、      user.key .abc.* 类型的key
         * rpc
         * @var bool $this ->options['connection']['channel']['exchange']['passive']
         * @var bool $this ->options['connection']['channel']['exchange']['durable']
         * @var bool $this ->options['connection']['channel']['exchange']['auto_delete']
         */
        $this->channel->exchange_declare(
            $this->options['connection']['channel']['exchange']['name'],
            $this->options['connection']['channel']['exchange']['type'],
            $this->options['connection']['channel']['exchange']['passive'],
            $this->options['connection']['channel']['exchange']['durable'],
            $this->options['connection']['channel']['exchange']['auto_delete']
        );
    }

    /**
     * @param array $options
     * @return self
     */
    public static function getInstance($options = [])
    {
        if ($options === []) $options = config('rabbitmq');
        if ($options === false || $options === []) throw new InvalidArgumentException('rabbitmq配置获取失败');
        /**
         * 创建连接所需参数检查
         */
        if (!isset($options['connection'])) throw new InvalidArgumentException('rabbitmq=>connection（连接）配置获取失败');
        if (!isset($options['connection']['host'])) throw new InvalidArgumentException('rabbitmq=>connection=>host（主机）配置获取失败');
        if (!isset($options['connection']['port'])) throw new InvalidArgumentException('rabbitmq=>connection=>port（端口）配置获取失败');
        if (!isset($options['connection']['user'])) throw new InvalidArgumentException('rabbitmq=>connection=>user（用户名）配置获取失败');
        if (!isset($options['connection']['password'])) throw new InvalidArgumentException('rabbitmq=>connection=>password（密码）配置获取失败');
        if (!isset($options['connection']['vhost'])) throw new InvalidArgumentException('rabbitmq=>connection=>vhost（虚拟机）配置获取失败');
        /**
         * 获取信道所需参数检查，不传则默认设置获取$channel['']信道
         */
        if (!isset($options['connection']['channel'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel（信道）配置获取失败');
        if (!isset($options['connection']['channel']['id'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>id（信道ID）配置获取失败');
        /**
         * 在信道里创建交换机所需参数检查
         */
        if (!isset($options['connection']['channel']['exchange'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>exchange（交换机）配置获取失败');
        if (!isset($options['connection']['channel']['exchange']['name'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>exchange=>name（交换机名称）配置获取失败');
        if (!isset($options['connection']['channel']['exchange']['type'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>exchange=>type（交换器类型）配置获取失败');
        if (!isset($options['connection']['channel']['exchange']['passive'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>exchange=>passive（是否被动）配置获取失败');
        if (!isset($options['connection']['channel']['exchange']['durable'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>exchange=>durable（是否持久化）配置获取失败');
        if (!isset($options['connection']['channel']['exchange']['auto_delete'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>exchange=>auto_delete（是否自动删除）配置获取失败');
        /**
         * 其它参数检查
         */
        if (!isset($options['connection']['channel']['routing_key'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>routing_key（路由键）配置获取失败');
        if ($options['connection']['channel']['exchange']['type'] != 'fanout' && empty($options['connection']['channel']['routing_key'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>routing_key（路由键）无效');
        if (!isset($options['connection']['channel']['queue'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>queue（队列）配置获取失败');
        if (!isset($options['connection']['channel']['queue']['name'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>queue=>name（队列名称）配置获取失败');
        if (!isset($options['connection']['channel']['queue']['passive'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>queue=>passive（是否被动）配置获取失败');
        if (!isset($options['connection']['channel']['queue']['durable'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>queue=>durable（是否持久化）配置获取失败');
        if (!isset($options['connection']['channel']['queue']['exclusive'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>queue=>exclusive（是否排他）配置获取失败');
        if (!isset($options['connection']['channel']['queue']['auto_delete'])) throw new InvalidArgumentException('rabbitmq=>connection=>channel=>queue=>auto_delete（是否自动删除）配置获取失败');

        $hash = md5(json_encode($options));
        if (!isset(self::$instance[$hash])) {
            self::$instance[$hash] = new self($options);
        }
        return self::$instance[$hash];
    }

    public function send($data, $properties = [])
    {
        $msg = new AMQPMessage($data, $properties);
        /**
         * 发送消息
         * @var AMQPMessage $msg AMQPMessage对象
         * @var string $this ->options['connection']['channel']['exchange']['name'] 交换机名称
         * @var string $this ->options['connection']['channel']['routing_key'] 路由键 如果交换机类型
         * fanout： 该值会被忽略，因为该类型的交换机会把所有它知道的队列发消息，无差别区别
         * direct  只有精确匹配该路由键的队列，才会发送消息到该队列
         * topic   只有正则匹配到的路由键的队列，才会发送到该队列
         */
        $this->channel->basic_publish($msg, $this->options['connection']['channel']['exchange']['name'], $this->options['connection']['channel']['routing_key']);
    }

    /**
     * @param Closure $closure
     * @param int $prefetch_count
     */
    public function receive(Closure $closure,int $prefetch_count=1)
    {
        /**
         * 声明消费者队列
         */
        list($queue, ,) = $this->channel->queue_declare(
            $this->options['connection']['channel']['queue']['name'],
            $this->options['connection']['channel']['queue']['passive'],
            $this->options['connection']['channel']['queue']['durable'],
            $this->options['connection']['channel']['queue']['exclusive'],
            $this->options['connection']['channel']['queue']['auto_delete']
        );
        /**
         * 绑定交换机
         * @var string $this ->options['connection']['channel']['exchange']['name'] 交换机名称
         * @var string $this ->options['connection']['channel']['routing_key'] 默认为空，表示对该交换机所有消息感兴趣，如果值不为空，则该队列只对该类型的消息感兴趣（除了fanout交换机以外）
         */
        $this->channel->queue_bind($queue, $this->options['connection']['channel']['exchange']['name'], $this->options['connection']['channel']['routing_key']);
        /**
         * 消费消息
         * @var int $prefetch_count 设置prefetch_count=1。这样是告诉RabbitMQ，再同一时刻，不要发送超过1条消息给一个工作者（worker），直到它已经处理了上一条消息并且作出了响应。这样，RabbitMQ就会把消息分发给下一个空闲的工作者（worker），轮询、负载均衡配置
         */
        $this->channel->basic_qos(null, $prefetch_count, null);

        $this->channel->basic_consume($this->options['connection']['channel']['queue']['name'], '', false, false, false, false, function ($message) use ($closure) {
            if ($closure($message->body)) $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        });
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->AMQPStreamConnection->close();
    }
}