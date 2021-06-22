<?php

namespace App\Functions;

use DateTime;
use DateTimeZone;

function error($message, $code = 400)
{
  http_response_code($code);
  die(json_encode(array("error" => array("code" => $code, "message" => $message))));
}

function response($payload, $code = 200)
{
  http_response_code($code);
  exit(json_encode(array("data" => $payload)));
}

function get_valid_date(\PDO $pdo)
{
  $config = $pdo->query("SELECT * FROM config LIMIT 1;")->fetchAll()[0];
  $timezone = new DateTimeZone('+0700');
  $now = new DateTime("now", $timezone);
  $drawdate = new DateTime("now", $timezone);
  $drawdate->setTime($config["drawHour"], $config["drawMinute"]);
  if ($now < $drawdate) {
    $datelimit = new DateTime('yesterday', $timezone);
  } else {
    $datelimit = $now;
  }
  return $datelimit;
}
