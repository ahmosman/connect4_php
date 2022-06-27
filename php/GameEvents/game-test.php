<?php

session_start();
require __DIR__ . '/../../vendor/autoload.php';
use App\Player;

if (isset($_POST['test_player_id']) && !empty($_POST['test_player_id'])) {
    $player = new Player($_POST['test_player_id']);
    $_SESSION['player_id'] = $player->playerId;
    echo 'success';
} else {
    echo 'Nie podano id gracza';
}