<?php
include_once "../App/Gameplay.php";
include_once "../App/Player.php";

use App\{Gameplay, Player};

session_start();

if (isset($_SESSION['player_id'])) {
    $output = "";
    $gameplay = new Gameplay($_SESSION['player_id']);
    $me = new Player($_SESSION['player_id']);
    $opponent = new Player($me->opponentId);

    if ($me->status == 'WAITING' && $opponent->status == 'WAITING') {
        $gameplay->setPlayersStatus('CONFIRMING');
    } elseif ($me->status == 'READY' && $opponent->status == 'READY') {
//        TODO: loswanie ruchu (statusów)
        $me->setStatus('PLAYER_MOVE');
        $opponent->setStatus('OPPONENT_MOVE');
    } elseif ($me->status == 'REVENGE' && $opponent->status == 'REVENGE') {
        $gameplay->setPlayersStatus('CONFIRMING');
        $gameplay->resetPlayersBallsLocation();
    }

    if ($me->status == 'WAITING') {
        $output .=
            '<div class="game-id-div"><h1>Twoja gra: <span>' . $gameplay->getUniqueGameId() . '</span></h1></div>
            <h2>Oczekiwanie na przeciwnika</h2>
            <div class="loader"></div>';
    } elseif ($me->status == 'CONFIRMING') {
        $output .=
            "<h2>$opponent->nickname czeka na Ciebie!</h2>
             <h2>Gotowy?</h2>";
        $output .= include '../templates/confirmBtn.php';
        $output .= include '../templates/backBtn.php';
    } elseif ($me->status == 'READY') {
        $output .=
            '<h2>Oczekiwanie na potwierdzenie przez przeciwnika</h2>
            <div class="loader"></div>';
    } elseif ($me->status == 'PLAYER_MOVE' || $me->status == 'OPPONENT_MOVE') {
        $output .= $gameplay->displayOutput();
    } elseif ($me->status == 'REVENGE') {
        $output .=
            '<h2>Oczekiwanie na potwierdzenie rewanżu</h2>
            <div class="loader"></div>';
        $output .= include '../templates/backBtn.php';
    } elseif ($opponent->status == 'DISCONNECTED') {
        $output .=
            '<h2>Przeciwnik rozłączył się</h2>'
            . include '../templates/backBtn.php';
    } elseif ($me->status == 'WIN' || $me->status == 'LOSE') {
        $output .= match ($me->status) {
            'WIN' => '<h2>WYGRAłEś</h2>',
            'LOSE' => '<h2>PRZEGRAłEś</h2>',
        };
        if ($opponent->status == 'REVENGE') {
            $output .=
                '<div class="game-info-div">Przeciwnik chce rewanżu!</div>';
        }
        $output .= include '../templates/backBtn.php';
        $output .= include '../templates/revengeBtn.php';
    }

    echo $output;
}

//TODO: Dodaj i wyświetlaj licznik wygranych gier