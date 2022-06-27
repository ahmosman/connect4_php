<?php

namespace App;

use Exception;
use mysqli;

class Game
{
    protected Player $player;
    protected Player $opponent;
    protected string $boardSize;
    private int $gameId;
    private int $uniqueGameId;
    private mysqli $conn;


    public function __construct(int $playerId = null)
    {
        $config = new Config();
        $this->conn = $config->getConn();
        if (!is_null($playerId)) {
            $this->player = new Player($playerId);
            $this->opponent = new Player($this->player->opponentId);
            $this->gameId = $this->player->gameId;
            try {
                $stmt = $this->conn->prepare(
                    "SELECT * FROM games
                WHERE game_id = ?"
                );
                $stmt->bind_param("i", $this->gameId);
                $stmt->execute();
                $gameAssoc = $stmt->get_result()->fetch_assoc();
                $this->boardSize = $gameAssoc['board_size'] ?? '8x8';
                $this->uniqueGameId = $gameAssoc['unique_game_id'];
            } catch (Exception $e) {
                error_log($e->getMessage());
                exit('Wystąpił błąd');
            }
        }
    }

    public static function create(Player $player, Player $opponent): self
    {
        $game = new self();

        //generowanie unikalnego id gry
        $uniqueGameId = mt_rand(100000, 999999);
        while (true) {
            try {
                $stmt = $game->conn->prepare(
                    "SELECT * FROM games WHERE unique_game_id = ?"
                );
                $stmt->bind_param("s", $uniqueGameId);
                $stmt->execute();
                $check_unique_query = $stmt->get_result();
                if ($check_unique_query->num_rows > 0) {
                    $uniqueGameId = mt_rand(100000, 999999);
                } else {
                    break;
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
                exit('Wystąpił błąd');
            }
        }
        $game->uniqueGameId = $uniqueGameId;

        $gameId = $game->conn->query("SHOW TABLE STATUS LIKE 'games'")
                      ->fetch_assoc()['Auto_increment'];

        //tworzenie nowego rekordu w tabeli games z unikalnym id
        try {
            $stmt = $game->conn->prepare(
                "INSERT INTO games (unique_game_id) VALUES (?)"
            );
            $stmt->bind_param("s", $game->uniqueGameId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log($e->getMessage());
            exit('Wystąpił błąd');
        }

        $player->setGameId($gameId);
        $opponent->setGameId($gameId);
        $game->player = $player;
        $game->opponent = $opponent;
        return $game;
    }

    public static function join(string $uniqueGameId): array
    {
        $result = [
            'response' => 'Brak gry o podanym ID',
            'playerToJoinId' => null
        ];
        if ($uniqueGameId > 0) {
            $game = new self();
            try {
                $stmt = $game->conn->prepare(
                    "SELECT * from games where unique_game_id = ?"
                );
                $stmt->bind_param("s", $uniqueGameId);
                $stmt->execute();
                $gameExistsQuery = $stmt->get_result();
                $stmt->close();
                if ($gameExistsQuery->num_rows > 0) {
                    $stmt = $game->conn->prepare(
                        "SELECT player_id, status from games g join players p on g.game_id = p.game_id where unique_game_id = ?"
                    );
                    $stmt->bind_param("s", $uniqueGameId);
                    $stmt->execute();
                    $searchPlayerQuery = $stmt->get_result();
                    while ($player = $searchPlayerQuery->fetch_assoc()) {
                        if ($player['status'] == 'NONE') {
                            $result['response'] = 'success';
                            $result['playerToJoinId'] = $player['player_id'];
                            return $result;
                        }
                        $result['response'] = 'Ktoś inny już dołączył do gry';
                    }
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                exit('Wystąpił błąd');
            }
        }
        return $result;
    }

    public function getUniqueGameId(): string
    {
        return $this->uniqueGameId;
    }

    public function setPlayersStatus(string $status): void
    {
        $this->player->setStatus($status);
        $this->opponent->setStatus($status);
    }

    public function resetPlayersBallsLocation()
    {
        $playerId = $this->player->playerId;
        $opponentId = $this->player->opponentId;
        try {
            $stmt = $this->conn->prepare(
                "UPDATE players SET balls_location = '[]' where player_id in (?, ?)"
            );
            $stmt->bind_param("ii", $playerId, $opponentId);
            $stmt->execute();
            $this->player->ballsLocation = [];
            $this->opponent->ballsLocation = [];
        } catch (Exception $e) {
            error_log($e->getMessage());
            exit('Wystąpił błąd');
        }
    }

}