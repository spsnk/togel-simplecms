<?php

namespace App;

use DateTime;
use Exception;

use function App\Functions\error;

/**
 * SQLite connnection
 */
class SQLiteConnection
{
  /**
   * PDO instance
   * @var type 
   */
  private $pdo;
  protected static $connection_string = "sqlite:" . Config::PATH_TO_SQLITE_FILE;
  /**
   * return in instance of the PDO object that connects to the SQLite database
   * @return \PDO
   */

  public function connect()
  {
    try {
      if ($this->pdo == null) {
        $this->pdo = new \PDO(SQLiteConnection::$connection_string);
        $this->pdo->setAttribute(
          \PDO::ATTR_ERRMODE,
          \PDO::ERRMODE_EXCEPTION
        );
        $this->pdo->setAttribute(
          \PDO::ATTR_DEFAULT_FETCH_MODE,
          \PDO::FETCH_ASSOC
        );
      }
    } catch (\PDOException $e) {
      die("Cannot connect to database");
    }
    if ($this->pdo == null) {
      die("Cannot connect to database");
    }
    return $this->pdo;
  }

  public static function setup()
  {
    try {
      $pdo = new \PDO(SQLiteConnection::$connection_string);
      $query = "create table if not exists entry(
        day date primary key,
        first varchar(4),
        second varchar(4),
        third varchar(4),
        starter varchar,
        consolation varchar
      );";
      $pdo->query($query);
      $config_query = "create table if not exists config(
        drawHour integer,
        drawMinute integer,
        results_per_page integer
      );";
      $pdo->query($config_query);
      $pdo->query("INSERT INTO config VALUES (6,6,10)");
    } catch (Exception $e) {
      die($e);
    }
  }

  public static function drop()
  {
    try {
      $pdo = new \PDO(SQLiteConnection::$connection_string);
      $pdo->query("drop table entry;");
      $pdo->query("drop table config;");
    } catch (Exception $e) {
      die($e);
    }
  }

  public static function seed()
  {
    try {
      $pdo = new \PDO(SQLiteConnection::$connection_string);
      $now = new DateTime('now');
      $origin = (new DateTime('now'))->modify('-2 years');
      $stmt = $pdo->prepare("INSERT INTO entry VALUES (:date, :first, :second, :third, :starter, :consolation)");
      for ($i = $origin; $i < $now; $i->modify("+1 day")) {
        try {
          $stmt->execute(
            array(
              "date" => $i->format("Y-m-d"),
              "first" => SQLiteConnection::get_rand_result(),
              "second" => SQLiteConnection::get_rand_result(),
              "third" => SQLiteConnection::get_rand_result(),
              "starter" => SQLiteConnection::get_n_results(10),
              "consolation" => SQLiteConnection::get_n_results(10)
            )
          );
        } catch (\PDOException $e) {
          \error_log($e);
        }
      }
    } catch (Exception $e) {
      error($e, 500);
    }
  }

  protected static function get_n_results($n)
  {
    $thing = [];
    for ($j = 0; $j < $n; $j++) {
      $thing[] = SQLiteConnection::get_rand_result();
    }
    return \implode(",", $thing);
  }

  protected static function get_rand_result()
  {
    $digits = 4;
    return str_pad(rand(0, pow(10, $digits) - 1), $digits, '0', \STR_PAD_LEFT);
  }
}
