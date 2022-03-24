<?php

require("globals.php");

function player($arr) {
    if ($arr == null) {
        return "";
    }
    return $arr["firstName"] . " " . $arr["lastName"];
}

function team($arr) {
    $result = "";
    foreach($arr as $player) {
        if ($result != "") {
            $result .= " und ";
        }
        $result .= player($player);
    }
    return $result;
}

function rank($rank) {
    return $rank . ". Rang";
}

function name($tournament, $competition) {
    if ($tournament == $competition) {
        return $tournament;
    } else {
        return $tournament . " - " . $competition;
    }
}

function getData()
{
    global $bearerToken;
    global $backendServer;
    global $id;
    
    if ($id === null) {
        return [];
    }
    $curl_handle = curl_init();
    $url = $backendServer . '/tournamentProfile/' . $id . '/' . $_GET["playerId"];
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    // return the body instead of printing it
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, ["Authorization: bearer " . $bearerToken]);    
    $data = curl_exec($curl_handle);
    $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    curl_close($curl_handle);
    if ($data === false || $httpcode != 200) {
        return [];
    }
    $data = json_decode($data, true);
    if ($data === null) {
        return [];
    }

    $additionalTournaments = [];
    //split tournaments by competition
    foreach ($data["tournaments"] as &$values) {
        $gamesByComp = [];
        foreach ($values["games"] as $game) {
            if (!array_key_exists($game["competitionIdentifier"], $gamesByComp)) {
                $gamesByComp[$game["competitionIdentifier"]] = [];
            }
            $gamesByComp[$game["competitionIdentifier"]][] = $game;
        }
        $name = $values["info"]["name"];
        $values["info"]["name"] = name($name, $values["games"][0]["competitionName"]);
        if (count($gamesByComp) > 1) {
            $first = true;
            foreach ($gamesByComp as $games) {
                if ($first) {
                    $values["games"] = $games;
                    $values["info"]["name"] = name($name, $games[0]["competitionName"]);
                    $first = false;
                } else {
                    $tournament = ["info" => $values["info"], "games" => $games];
                    $tournament["info"]["name"] = name($name, $games[0]["competitionName"]);
                    $additionalTournaments[] = $tournament;
                }
            }
        }
    }

    $data["tournaments"] = array_merge($data["tournaments"], $additionalTournaments);

    foreach ($data["tournaments"] as &$values) {
        $values["partner"] = null;
        usort($values["games"], function($g1, $g2) {
            $competition = $g2["competitionIdentifier"] <=> $g1["competitionIdentifier"];
            if ($competition != 0) {
                return $competition;
            }
            
            return $g2["start"] <=> $g1["start"];
        });
        $values["eloChange"] = 0;
        $values["newElo"] = 0;
        if (count($values["games"]) > 0) {
            $latestStart = 0;
            $values["partner"] = player($values["games"][0]["partner"]);
            
            $ranksPerCompetition = [];
            foreach($values["games"] as $game) {
                if (player($game["partner"]) !== $values["partner"]) {
                    $values["partner"] = "verschiedene";
                }
                if (!array_key_exists($game["competitionIdentifier"], $ranksPerCompetition)) {
                    $ranksPerCompetition[$game["competitionIdentifier"]] = [-1, -1];
                }
                if ($game["start"] > $ranksPerCompetition[$game["competitionIdentifier"]][1]) {
                    $ranksPerCompetition[$game["competitionIdentifier"]] = [$game["competitionRank"], $game["start"]];
                }
                $values["eloChange"] += $game["elo"];
                if ($game["start"] > $latestStart) {
                    $values["newElo"] = $game["newElo"];
                    $latestStart = $game["start"];
                }
            }
            $values["competitionRank"] = rank(array_values($ranksPerCompetition)[0][0]);
            foreach ($ranksPerCompetition as $rank) {
                if (rank($rank[0]) != $values["competitionRank"]) {
                    $values["competitionRank"] = "verschiedene";
                }
            }
        }
    }
    usort($data["tournaments"], function ($t1, $t2) {
        return $t2["games"][0]["start"] <=> $t1["games"][0]["start"];
    });
    return $data;
}

function change($value, $precision = 1) {
    if ($value >= 1000) {
        $precision = 0;
    }
    if ($value === 0) {
        return withClass('neutral', 0);
    } else if ($value > 0) {
        return withClass('positive', "+" . number_format($value, $precision, ',', ''));
    } else {
        return withClass('negative', number_format($value, $precision, ',', ''));
    }
}

function withClass($class, $value) {
    return '<p class="' . $class . '">' . $value . '</p>';
}

function result($result) {
    $split = explode(":", $result);
    $class = 'neutral';
    if (count($split) !== 2) {
        return withClass($class, $result);
    }
    $own = intval($split[0]);
    $other = intval($split[1]);
    if ($own < $other) {
        $class = 'negative';
    } else if ($own > $other) {
        $class = 'positive';
    }
    return withClass($class, $result);
}

$data = getData();

$playerArr = $data["playerName"];
$player = player($playerArr);
$data = $data["tournaments"];

function ownTeam($partner) {
    global $isDouble;
    global $player;
    global $playerArr;

    if (!$isDouble) {
        return $player;
    } else {
        return team([$playerArr, $partner]);
    }
}

?>

<html>
<head>
<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script>

function toggle(e)
{
    e = e || window.event;
    var targ = e.target || e.srcElement || e;
    if (targ.nodeType == 3) targ = targ.parentNode; // defeat Safari bug

    $(targ).parents().next('.hide').toggle();
}

</script>
<style>

.hide {
  display: none;
}

tr.clickable {
    font-weight: bold;
}

tr.clickable:hover{
  background-color: hsl(0,0%,70%) !important;
  cursor: pointer;
}

.neutral {
    color: blue;
}

.negative {
    color: red;
}

.positive {
    color: green;
}

</style>
<link rel="stylesheet" type="text/css" media="screen" href="tfboe.css">
<link rel="stylesheet" type="text/css" media="screen" href="2007.css">
<link rel="stylesheet" type="text/css" media="screen" href="base.css">
<link rel="stylesheet" type="text/css" media="screen" href="basemod.css">
</head>

<body>
  <div id="page_margins">
    <div id="page">
      <div id="main">
        <div id="col3">
          <div id="col3_content" class="clearfix">

            <h1>Elo-Rangliste<?=!$firstOfMaxDateMonth ? "" : " vom " . date_format(date_create($firstOfMaxDateMonth), "d.m.Y")?><br><sub><?=$name?></sub></h1>
            <div class="dropdown">
              <button class="dropbtn">Spielmodus</button>
              <div class="dropdown-content">
                <a href="index.php?<?=build_query("opensingle")?>">Offenes Einzel</a>
                <a href="index.php?<?=build_query("opendouble")?>">Offenes Doppel</a>
                <a href="index.php?<?=build_query("womensingle")?>">Damen Einzel</a>
                <a href="index.php?<?=build_query("womendouble")?>">Damen Doppel</a>
                <a href="index.php?<?=build_query("mixed")?>">Mixed Doppel</a>
                <a href="index.php?<?=build_query("seniorsingle")?>">Senioren Einzel</a>
                <a href="index.php?<?=build_query("seniordouble")?>">Senioren Doppel</a>
                <a href="index.php?<?=build_query("juniorsingle")?>">Junioren Einzel</a>
                <a href="index.php?<?=build_query("juniordouble")?>">Junioren Doppel</a>
                <a href="index.php?<?=build_query("classic")?>">Offenes Classic Doppel</a>
              </div>
            </div>
            <form class="dateform" action="index.php" method="GET">
              <?php foreach ($_GET as $key => $value) { ?>
              <input type="hidden" name="<?= $key?>" value="<?= $value?>" />
              <?php }?>
              <?php if ($showInactive) {?>
              <input type="hidden" name="showInactive" value="1" />
              <?php }?>
              <input type="date" name="maxDate" value="<?= $maxDate?>" onchange='this.form.submit()' />
            </form>
            <br>
            <div class="playerinfo">Elo-Detailübersicht für Spieler <b><?=$player?></b></div>
            <table class="rankingDetail">
              <thead>
                <tr>
                  <th>Datum</th>
                  <th>Turnier</th>
                  <th><?=$isDouble ? "Partner / " : ""?>Begegnung</th>
                  <th>Ergebnis</th>
                  <th>Elo+/-</th>
                  <th>Elozahl</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($data as $i => $tournament): ?>
                <tbody>
                  <tr class="row<?=($i + 1) % 2 + 1?> clickable" onclick="toggle()">
                    <td><?=date('d.m.Y', $tournament["info"]["start"]) ?></td>
                    <td colspan="<?= $isDouble ? "1" : "2" ?>"><?=$tournament["info"]["name"]?></td>
                    <?php if ($isDouble): ?>
                    <td><?=$tournament["partner"]?></td>
                    <?php endif; ?>
                    <td><?=$tournament["competitionRank"]?></td>
                    <td><?=change($tournament["eloChange"])?></td>
                    <td><?=round($tournament["newElo"])?></td>
                  </tr>
                </tbody>
                <tbody class="hide">
                  <?php foreach($tournament["games"] as $j => $game): ?>
                  <tr>
                    <td></td>
                    <td><?=$game["phaseName"]?></td>
                    <td><?=ownTeam($game["partner"]) . " (" . round($game["teamElo"]) . ") - " . team($game["opponents"]) . " (" . round($game["opponentElo"]) . ")"?></td>
                    <td><?=result($game["result"])?></td>
                    <td><?=change($game["elo"], 2)?></td>
                    <td><?=round($game["newElo"])?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </tbody>
              <?php endforeach; ?>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>