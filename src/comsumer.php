<?php

require dirname(__DIR__) . '/vendor/autoload.php' ;
require dirname(__DIR__) . '/src/configs.php' ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;

$exchange = 'subscribers';
$queue = 'gurucoder_subscribers';

$connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
$channel = $connection->channel();

$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
$channel->queue_bind($queue, $exchange);

function process_message(AMQPMessage $message){

  $messageBody = json_decode($message->body);
  
  $i = $messageBody->i ;
  $email = $messageBody->email ;
  $name = $messageBody->name ;
  $address = $messageBody->address ;

  $msg = $i . "\n" . $email . "\n" . $name . "\n" . $address . "\n------\n" ;

  error_log($msg, 3, 'src/consumer.log'); 

  $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

}

$consumerTag = 'local.imac.consumer' ;

$channel->basic_consume($queue, $consumerTag, false, false, false, false, 'process_message');

function shutdown($channel, $connection){
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while(count($channel->callbacks)){
  $channel->wait();
}
