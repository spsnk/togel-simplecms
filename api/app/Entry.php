<?php

namespace App;

use DateTime;
use DateTimeZone;
use Exception;

use function App\Functions\error;
use function App\Functions\get_valid_date;
use function App\Functions\parse_date;

class Entry
{
  private \PDO $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function get_result(string $result_id)
  {
    $date = parse_date($result_id);
    $query = 'SELECT * FROM entry WHERE day = ? LIMIT 1;';
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute([$date->format('Y-m-d')]);
      $result = $stmt->fetchAll();
      if (empty($result)) {
        error('Not found', 404);
      } else {
        $result = $result[0];
        $result["starter"] = explode(",", $result["starter"]);
        $result["consolation"] = explode(",", $result["consolation"]);
      }
    } catch (\PDOException $e) {
      error('Database error', 500);
    }
    return array("data" => $result);
  }

  public function get_results(int $page = 1)
  {
    $datelimit = get_valid_date($this->db);
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    $query =
      'SELECT * FROM entry WHERE day <= ? ORDER BY day DESC LIMIT ? OFFSET ?';
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute([$datelimit->format('Y-m-d'), $per_page, $offset]);
      $result = $stmt->fetchAll();
      $total = $this->db->query("SELECT COUNT(day) as total FROM entry;")->fetch()["total"];
      for ($i = 0; $i < count($result); $i++) {
        $result[$i]["starter"] = explode(",", $result[$i]["starter"]);
        $result[$i]["consolation"] = explode(",", $result[$i]["consolation"]);
      }
    } catch (\PDOException $e) {
      $result = null;
      $total = 0;
    }
    return array("data" => $result, "recordsTotal" => intval($total), "pageSize" => $per_page);
  }


  public function get_upcoming_results(int $page = 1)
  {
    $datelimit = get_valid_date($this->db);
    $per_page = 5;
    $offset = ($page - 1) * $per_page;
    $query =
      'SELECT * FROM entry WHERE day > ? ORDER BY day ASC LIMIT ? OFFSET ?';
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute([$datelimit->format('Y-m-d'), $per_page, $offset]);
      $result = $stmt->fetchAll();
      for ($i = 0; $i < count($result); $i++) {
        $result[$i]["starter"] = explode(",", $result[$i]["starter"]);
        $result[$i]["consolation"] = explode(",", $result[$i]["consolation"]);
      }
    } catch (\PDOException $e) {
      $result = null;
    }
    return array("data" => $result);
  }

  public function get_latests_result()
  {
    $datelimit = get_valid_date($this->db);
    $query = 'SELECT * FROM entry WHERE day <= ? ORDER BY day DESC LIMIT 1';
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute([$datelimit->format('Y-m-d')]);
      $result = $stmt->fetchAll();
      if (!empty($result)) {
        $result = $result[0];
        $result["starter"] = explode(",", $result["starter"]);
        $result["consolation"] = explode(",", $result["consolation"]);
      }
    } catch (\PDOException $e) {
      $result = null;
    }
    return array("data" => $result);
  }


  public function get_yesterday_result()
  {
    $datelimit = get_valid_date($this->db);
    $datelimit->modify("-1 day");
    $query = 'SELECT * FROM entry WHERE day == ? ORDER BY day DESC LIMIT 1';
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute([$datelimit->format('Y-m-d')]);
      $result = $stmt->fetchAll();
      if (!empty($result)) {
        $result = $result[0];
        $result["starter"] = explode(",", $result["starter"]);
        $result["consolation"] = explode(",", $result["consolation"]);
      }
    } catch (\PDOException $e) {
      $result = null;
    }
    return array("data" => $result);
  }

  public function add_entry(array $input)
  {
    $fields = ['day', 'first', 'second', 'third', 'starter', 'consolation'];
    foreach ($fields as $field) {
      if (!array_key_exists($field, $input)) {
        error('Missing field [' . $field . ']');
      }
    }
    $input['day'] = parse_date($input['day'])->format('Y-m-d');
    $input["starter"] = implode(",", $input["starter"]);
    $input["consolation"] = implode(",", $input["consolation"]);
    $statement =
      'INSERT INTO entry VALUES (:day, :first, :second, :third, :starter, :consolation)';
    try {
      $stmt = $this->db->prepare($statement);
      $stmt->execute($input);
      return array("data" => $input);
    } catch (\PDOException $e) {
      if ($e->errorInfo[0] == 23000) {
        error('Conflict', 409);
      }
      error($e->getMessage(), 500);
    }
  }

  public function delete_entry(string $input)
  {
    if (empty($input)) {
      error('Bad Request');
    }
    $parsed_date = parse_date($input);
    $statement = 'DELETE FROM entry WHERE day = ?';
    try {
      $stmt = $this->db->prepare($statement);
      return array("data" => $stmt->execute([$parsed_date->format('Y-m-d')]));
    } catch (\PDOException $e) {
      error($e->getMessage(), 500);
    }
  }

  public function update_entry(string $id, array $input)
  {
    if (empty($id)) {
      error('Bad Request');
    }
    $fields = ['first', 'second', 'third', 'starter', 'consolation'];
    foreach ($fields as $field) {
      if (!array_key_exists($field, $input)) {
        error('Missing field [' . $field . ']');
      }
    }
    $input['day'] = parse_date($id)->format('Y-m-d');
    $input["starter"] = implode(",", $input["starter"]);
    $input["consolation"] = implode(",", $input["consolation"]);
    $statement = "UPDATE entry
      SET 
        first =:first,
        second = :second,
        third = :third,
        starter = :starter,
        consolation = :consolation
      WHERE day = :day;";
    try {
      $stmt = $this->db->prepare($statement);
      $stmt->execute($input);
      if ($stmt->rowCount() < 1) {
        error('No item affected');
      }
      return array("data" => $stmt->rowCount());
    } catch (\PDOException $e) {
      error($e->getMessage(), 500);
    }
  }
}
