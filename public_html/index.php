<?php

require("globals.php");

function getData()
{
    global $category;
    global $bearerToken;
    global $backendServer;
    global $id;
    global $showInactive;
    global $firstOfMaxDateMonth;
    
    if ($id === null) {
        return [];
    }
    $curl_handle = curl_init();
    $url = $backendServer . '/rankings?id=' . $id;
    if ($category === 'juniorsingle' || $category === 'juniordouble') {
        $url .= "&min_birth_date=" . (date("Y") * 1 - 18) . "-01-01";
    }
    if ($showInactive) {
        $url .= "&include_inactive=1";
    }
    if (!!$firstOfMaxDateMonth) {
        $url .= "&max_date=" . $firstOfMaxDateMonth;
    }
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
    return $data;
}

$data = getData();
usort($data, function($r1, $r2) {
    if ($r1["points"] != $r2["points"]) {
        return $r2["points"] <=> $r1["points"];
    }
    if ($r1["points"] === 0 && $r1["subClassData"]["provisoryranking"] != $r2["subClassData"]["provisoryranking"]) {
        return $r2["subClassData"]["provisoryranking"] <=> $r1["subClassData"]["provisoryranking"];
    }
    return 0;
});
$firstIndexWithZeroPoints = count($data);
for ($i = 0; $i < count($data); $i++) {
    if ($data[$i]["points"] === 0) {
        $firstIndexWithZeroPoints = $i;
        break;
    }
}
$unrated = array_splice($data, $firstIndexWithZeroPoints);

function href($row, $value) {
    return '<a href="playerInfo.php?' . build_query() . '&playerId=' . $row['playerId'] . '">' . $value . "</a>";
}

function name($row) {
    $name = strtoupper($row["lastName"]) . " " . $row["firstName"];
    if (!$row["active"]) {
        $name .= " (inaktiv)";
    }
    return $name;
}
?>

<html>

<head>
  <link rel="stylesheet" type="text/css" media="screen" href="tfboe.css">
  <link rel="stylesheet" type="text/css" media="screen" href="2007.css">
  <link rel="stylesheet" type="text/css" media="screen" href="base.css">
  <link rel="stylesheet" type="text/css" media="screen" href="basemod.css">
  <link rel="stylesheet" type="text/css" media="screen" href="switch.css">
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
                <a href="index.php?<?=build_query("womenclassic")?>">Damen Classic Doppel</a>
              </div>
            </div>
            <form class="dateform" action="index.php" method="GET">
              <?php foreach ($_GET as $key => $value) { ?>
              <input type="hidden" name="<?= $key?>" value="<?= $value?>">
              <?php }?>
              <?php if ($showInactive) {?>
              <input type="hidden" name="showInactive" value="1">
              <?php }?>
              <input type="date" name="maxDate" value="<?= $maxDate?>" onchange='this.form.submit()'>
              </input>
            </form>
            <br>
            <div class="staticSwitch">
              <p class="sidebar">Nur aktive Spieler</p>
              <a class="<?= (!$showInactive ? "on" : "off")?>" href="index.php?<?= build_query(true, false) . (!$showInactive ? "&showInactive=1" : "") ?>">
                <p><?= (!$showInactive ? "AN" : "AUS")?></p><span></span>
              </a>
            </div>
            <br>
            <br>
            <table class="ranking">
              <thead>
                <tr>
                  <th></th>
                  <th>Name</th>
                  <?php if ($has_stati) { ?>
                  <th>Status</th>
                  <?php } ?>
                  <th>Elo</th>
                </tr>
              </thead>
              <?php
                $rank = null;
                $i = 0;
                $last = null; 
                for ($i = 0; $i < count($data); $i++) {
                    $row = $data[$i];
                    if ($last === null || $row["points"] < $last["points"]) {
                        $rank = $i + 1;
                    }
                    $last = $row;
              ?>
              <tr class="row<?=($i + 1) % 2 + 1?>">
                <td><b><?=$rank?></b></td>
                <td><?=href($row, name($row))?></td>
                <?php if ($has_stati) { ?>
                <td><?=get_status($row["points"])?></td>
                <?php } ?>
                <td><b><?=round($row["points"])?></b></td>
              </tr>
              <?php } ?>
            </table>
            <br>
            <h1>Noch nicht eingestufte Spieler<br><sub><?=$name?></sub></h1>
            <br>
            <table class="ranking">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Einstufungswert</th>
                  <th>Spiele</th>
                </tr>
              </thead>
              <?php
                for ($i = 0; $i < count($unrated); $i++) {
                    $row = $unrated[$i];
              ?>
              <tr class="row<?=($i + 1) % 2 + 1?>">
                <td><?=href($row, name($row))?></td>
                <td><b><?=max(1200, round($row["subClassData"]["provisoryranking"]))?></b></td>
                <td><b><?=$row["subClassData"]["playedgames"]?></b></td>
              </tr>
              <?php } ?>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
