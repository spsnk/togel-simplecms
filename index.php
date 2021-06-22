<?php

require 'vendor/autoload.php';

use App\SQLiteConnection;
use App\Entry;
use function App\Functions\error;
use function App\Functions\response;

header("Access-Control-Allow-Origin: *");
header("Accept: application/json");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,PATCH,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Origin, Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));

$method = $_SERVER["REQUEST_METHOD"];

if ($method == 'OPTIONS') {
  response(array(
    "message" => "ok"
  ));
}

$pdo = (new SQLiteConnection())->connect();

$request = explode('/', $_REQUEST["url"]);
$result = "";
switch ($request[0]) {
  case 'results':
    $entry = new Entry($pdo);
    switch ($method) {
      case 'GET':
        if (!empty($request[1])) {
          if ($request[1] == "pages") {
            if (empty($request[2]) || !is_numeric($request[2])) {
              error("Invalid page");
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
        response("post requested");
        break;
      default:
        error("Method not allowed", 405);
    }
    break;
  case 'latest':
    $entry = new Entry($pdo);
    if ($method == "GET") {
      $result = $entry->get_latests_result();
      response($result);
      break;
    } else {
      error("Method not allowed", 405);
    }
    break;
  case '7370736e6b40676974687562':
    if ($method == "POST") {
      $json = file_get_contents('php://input');
      $data = json_decode($json);
      switch ($data->action) {
        case 'setup':
          SQLiteConnection::setup();
          response("Setup succesfull");
          break;
        case 'drop':
          SQLiteConnection::drop();
          response("Drop succesfull");
          break;
        case 'seed':
          SQLiteConnection::seed();
          response("Seed succesfull");
          break;
        case 'test':
          $result = $pdo->query("DROP TABLE entry;");
          $result = $pdo->query("DROP TABLE config;");
          $result = $pdo->query("VACUUM;");
          response($result);
          break;
        default:
          error("Bad Request");
      }
    }
    break;
  default:
    error("Bad Request");
}
error("Resource not found", 404);
