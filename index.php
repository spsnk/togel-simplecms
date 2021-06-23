<?php

require 'vendor/autoload.php';

use App\SQLiteConnection;
use App\Entry;

use function App\Functions\check_headers;
use function App\Functions\error;
use function App\Functions\response;
use function App\Functions\set_cache;

header('Access-Control-Allow-Origin: *');
header('Accept: application/json');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: OPTIONS,GET,PATCH,POST,PUT,DELETE');
header('Access-Control-Max-Age: 3600');
header(
  'Access-Control-Allow-Headers: Origin, Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With',
);

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
  response([
    'message' => 'ok',
  ]);
}

$pdo = (new SQLiteConnection())->connect();

$request = explode('/', $_REQUEST['url']);
$result = '';
switch ($request[0]) {
  case 'results':
    set_cache(60 * 60);
    $entry = new Entry($pdo);
    switch ($method) {
      case 'GET':
        if (!empty($request[1])) {
          if ($request[1] == 'pages') {
            if (empty($request[2]) || !is_numeric($request[2])) {
              error('Invalid page');
            }
            $result = $entry->get_results($request[2]);
          } else {
            $result = $entry->get_result($request[1]);
          }
        } else {
          $result = $entry->get_results();
        }
        response($result);
        break;
      case 'POST':
        check_headers();
        $input = json_decode(file_get_contents('php://input'), true);
        $response = $entry->add_entry($input);
        response($response, 201);
        break;
      case 'PUT':
        if (!empty($request[1])) {
          $input = json_decode(file_get_contents('php://input'), true);
          $result = $entry->update_entry($request[1], $input);
          response($result, 204);
        }
        error('Bad Request');
        break;
      case 'DELETE':
        check_headers();
        if (!empty($request[1])) {
          $result = $entry->delete_entry($request[1]);
        } else {
          error('Invalid date');
        }
        response('', 204);
        break;
      default:
        error('Method not allowed', 405);
    }
    break;
  case 'livedraw':
    if ($method == 'GET') {
      set_cache(60 * 60 * 23);
      $now = new DateTime("now", new DateTimeZone("+0700"));
      $time = new DateTime("now", new DateTimeZone("+0700"));
      try {
        $config = $pdo->query('SELECT * FROM config LIMIT 1;')->fetchAll()[0];
        $time->setTime($config['drawHour'], $config['drawMinute']);
      } catch (Exception $e) {
        $time->setTime(0, 0);
      }
      if ($now < $time) {
        $time->modify("+1 day");
      }
      response([
        'next' => $time->format(DateTime::ISO8601),
      ]);
    } else {
      error('Method not allowed', 405);
    }
    break;
  case 'latest':
    set_cache(0);
    $entry = new Entry($pdo);
    if ($method == 'GET') {
      $result = $entry->get_latests_result();
      response($result);
      break;
    } else {
      error('Method not allowed', 405);
    }
    break;
  case '7370736e6b40676974687562':
    check_headers();
    if ($method == 'POST') {
      $json = file_get_contents('php://input');
      $data = json_decode($json);
      switch ($data->action) {
        case 'setup':
          SQLiteConnection::setup();
          response('Setup succesfull');
          break;
        case 'drop':
          SQLiteConnection::drop();
          response('Drop succesfull');
          break;
        case 'seed':
          SQLiteConnection::seed();
          response('Seed succesfull');
          break;
        case 'test':
          $result = 'Testing';
          response($result);
          break;
        default:
          error('Bad Request');
      }
    }
    break;
  default:
    error('Bad Request');
}
error('Resource not found', 404);
