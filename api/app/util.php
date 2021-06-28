<?php

namespace App\Functions;

use App\Config;
use DateTime;
use DateTimeZone;

function error($message, $code = 400)
{
  http_response_code($code);
  die(json_encode(['error' => ['code' => $code, 'message' => $message]]));
}

function response($payload, $code = 200)
{
  http_response_code($code);
  if ($code == 204) {
    exit();
  }
  exit(json_encode($payload));
}

function get_valid_date(\PDO $pdo)
{
  $config = $pdo->query('SELECT * FROM config LIMIT 1;')->fetchAll()[0];
  $timezone = new DateTimeZone('+0700');
  $now = new DateTime('now', $timezone);
  $drawdate = new DateTime('now', $timezone);
  $drawdate->setTime($config['drawHour'], $config['drawMinute']);
  if ($now < $drawdate) {
    $datelimit = new DateTime('yesterday', $timezone);
  } else {
    $datelimit = $now;
  }
  return $datelimit;
}

function check_headers()
{
  $headers = getallheaders();
  $headers = array_change_key_case($headers, CASE_UPPER);
  if (
    !$headers ||
    empty($headers['X-TOGEL-API-KEY']) ||
    !in_array($headers['X-TOGEL-API-KEY'], Config::API_KEYS)
  ) {
    error("Not authorized", 401);
  }
  return true;
}

function set_cache($time)
{
  header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', $time));
}

function parse_date(string $input)
{
  try {
    $date = new DateTime($input);
  } catch (\Exception $e) {
    error('Invalid Date');
  }
  return $date;
}
