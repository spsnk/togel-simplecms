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
      }
    } catch (\PDOException $e) {
      error('Database error', 500);
    }
    return $result;
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
    } catch (\PDOException $e) {
      $result = null;
    }
    return $result;
  }

  public function get_latests_result()
  {
    $datelimit = get_valid_date($this->db);
    $query = 'SELECT * FROM entry WHERE day <= ? ORDER BY day DESC LIMIT 1';
    try {
      $stmt = $this->db->prepare($query);
      $stmt->execute([$datelimit->format('Y-m-d')]);
      $result = $stmt->fetchAll()[0];
    } catch (\PDOException $e) {
      $result = null;
    }
    return $result;
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
    $statement =
      'INSERT INTO entry VALUES (:day, :first, :second, :third, :starter, :consolation)';
    try {
      $stmt = $this->db->prepare($statement);
      $stmt->execute($input);
      return $input;
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
      return $stmt->execute([$parsed_date->format('Y-m-d')]);
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
      return $stmt->rowCount();
    } catch (\PDOException $e) {
      error($e->getMessage(), 500);
    }
  }
}
