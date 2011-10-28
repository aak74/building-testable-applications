<?php

namespace IBL;

class GameMapper 
{
    protected $conn;
    protected $map = array();

    public function __construct($conn)
    {
        $this->conn = $conn; 

        // Load our class mapper from the XML config file
        foreach (simplexml_load_file(LIB_ROOT . 'ibl/maps/game.xml') as $field) {
            $this->map[(string)$field->name] = $field; 
        }
    }

    public function createGameFromRow($row)
    {
        $game = new Game($this);

        foreach ($this->map as $field) {
            $setProp = (string)$field->mutator;
            $value = $row[(string)$field->name];

            if ($setProp && $value) {
                call_user_func(array($game, $setProp), $value); 
            } 
        } 

        return $game;
    }

    public function findById($id)
    {
        try {
            $sql = "SELECT * FROM games WHERE id = ?";
            $sth = $this->conn->prepare($sql);
            $sth->execute(array((int)$id));
            $row = $sth->fetch();

            if ($row) {
                return $this->createGameFromRow($row);
            }
        } catch (\PDOException $e) {
            echo "DB Error: " . $e->getMessage(); 
        }

        return false;
    }

    public function findByWeek($week)
    {
        try {
            $sql = "SELECT * FROM games WHERE week = ?";
            $sth = $this->conn->prepare($sql);
            $sth->execute(array((int)$week));
            $rows = $sth->fetch();
            $games = array();

            if ($rows) {
                foreach ($rows as $row) {
                    $games[] = $this->createGameFromRow($row); 
                }
            } 

            return $games;
        } catch (\PDOException $e) {
            echo 'DB_Error: ' . $e->getMessage(); 
        }
    }

    public function save(\IBL\Game $game)
    {
        try {
            // Of course, Postgres has to do things a little differently
            // and we cannot use lastInsertId() so you alter the INSERT
            // statement to return the insert ID 
            $sql = "INSERT INTO games (week, home_score, away_score, home_team_id, away_team_id) 
                VALUES(?, ?, ?, ?, ?) RETURNING id";
            $sth = $this->conn->prepare($sql);
            $response = $sth->execute(array($game->getWeek(), $game->getHomeScore(), $game->getAwayScore(), $game->getHomeTeamId(), $game->getAwayTeamId()));
            $result = $sth->fetch(\PDO::FETCH_ASSOC);
             
            if ($result['id']) {
                $game->setId($result['id']);
            } else {
                throw new \Exception('Unable to create new Game record'); 
            }
        } catch(\PDOException $e) {
            echo "A database problem occurred: " . $e->getMessage(); 
        }
    }
}
