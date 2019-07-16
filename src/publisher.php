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

$faker = Faker\Factory::create();

$iterTimes = implode(' ', array_slice($argv, 1)) ;

if( !is_numeric($iterTimes) ){
  $iterTimes = 1 ;
}
else{
  $iterTimes = intval($iterTimes);
}

$i = 0 ;

while( $i < $iterTimes ){

  $messageBody = json_encode([
    'i' => ($i+1) ,
    'name' => $faker->name ,
    'email' => $faker->email ,
    'address' => $faker->address ,
    'subscribed' => true
  ]);
  
  $message = new AMQPMessage($messageBody,[
    'content_type' => 'application/json',
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
  ]);
  
  $channel->basic_publish($message, $exchange);

  $i++ ;
}

echo "Finished publishing to queue: " . $queue . PHP_EOL ;

$channel->close();
$connection->close();
