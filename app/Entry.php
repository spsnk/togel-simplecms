<?php

namespace App;

use DateTime;
use DateTimeZone;
use Exception;

use function App\Functions\error;
use function App\Functions\get_valid_date;

class Entry
{
  private \PDO $db;

  public function __construct($db)
  {
    $this->db = $db;
  }


  public function get_result(String $result_id)
  {
    try {
      $date = new DateTime($result_id);
    } catch (Exception $e) {
      $result = null;
      error("Invalid Date");
    }
    $query = "SELECT * FROM entry WHERE day = ? LIMIT 1;";
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute(array($date->format('Y-m-d')));
      $result = $stmt->fetchAll();
      if (empty($result)) {
        error("Not found", 404);
      } else {
        $result = $result[0];
      }
    } catch (\PDOException $e) {
      error("Database error", 500);
    }
    return $result;
  }

  public function get_results(int $page = 1)
  {
    $datelimit = get_valid_date($this->db);
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    $query = "SELECT * FROM entry WHERE day <= ? ORDER BY day DESC LIMIT ? OFFSET ?";
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute(array($datelimit->format('Y-m-d'), $per_page, $offset));
      $result = $stmt->fetchAll();
    } catch (\PDOException $e) {
      $result = null;
    }
    return $result;
  }

  public function get_latests_result()
  {
    $datelimit = get_valid_date($this->db);
    $query = "SELECT * FROM entry WHERE day <= ? ORDER BY day DESC LIMIT 1";
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute(array($datelimit->format('Y-m-d')));
      $result = $stmt->fetchAll()[0];
    } catch (\PDOException $e) {
      $result = null;
    }
    return $result;
  }

  public function add_entry(array $input)
  {
    $statement = "INSERT INTO entry
      (number, timestamp)
      VALUES (:number, :timestamp);";
    try {
      $stmt = $this->db->prepare($statement);
      $stmt->execute(
        array(
          'number' => $input['number'],
          'timestamp' => $input['timestamp']
        )
      );
      return $stmt->rowCount();
    } catch (\PDOException $e) {
      exit(json_encode(array(
        "message" => $e->getMessage(),
        "error" => $e
      )));
    }
  }

  public function update_entry($id, array $input)
  {
    $statement = "UPDATE entry
      SET 
        number = :number,
        timestamp =:timestamp
      WHERE id = :id;";
    try {
      $stmt = $this->db->prepare($statement);
      $stmt->execute(
        array(
          'id' => (int) $id,
          'number' => $input['number'],
          'timestamp' => $input['timestamp']
        )
      );
      return $stmt->rowCount();
    } catch (\PDOException $e) {
      exit(json_encode(array(
        "message" => $e->getMessage(),
        "error" => $e
      )));
    }
  }
}
