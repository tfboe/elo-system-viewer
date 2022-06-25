<?php
require("secrets.php");

// first is the official token for the official server and second is local development token
$backendServer = 'https://tfboe-elo.tischfussball.wien/';
//$backendServer = 'php-server:8000';

$names = [
    "opensingle" => "Offenes Einzel",
    "opendouble" => "Offenes Doppel",
    "womensingle" => "Damen Einzel",
    "womendouble" => "Damen Doppel",
    "juniorsingle" => "Junioren Einzel",
    "juniordouble" => "Junioren Doppel",
    "seniorsingle" => "Senioren Einzel",
    "seniordouble" => "Senioren Doppel",
    "classic" => "Classic Doppel",
    "mixed" => "Mixed",
];

$doubles = [
    "opendouble",
    "womendouble",
    "juniordouble",
    "seniordouble",
    "classic",
    "mixed",
];

$ids = [
    "opensingle" => "d08eda56-a7ee-11eb-8243-0242ac140002",
    "opendouble" => "dc264cb5-a7ee-11eb-8243-0242ac140002",
    "womensingle" => "e6456a1e-a7ee-11eb-8243-0242ac140002",
    "womendouble" => "e8fc01a0-a7ee-11eb-8243-0242ac140002",
    "juniorsingle" => "edba2a8b-a7ee-11eb-8243-0242ac140002",
    "juniordouble" => "f06e92ce-a7ee-11eb-8243-0242ac140002",
    "seniorsingle" => "f4f95737-a7ee-11eb-8243-0242ac140002",
    "seniordouble" => "f7972e41-a7ee-11eb-8243-0242ac140002",
    "classic" => "fbb021e4-a7ee-11eb-8243-0242ac140002",
    "mixed" => "fe9448f4-a7ee-11eb-8243-0242ac140002",
];

$stati = [
    "Rookie" => 1400,
    "Semi-Pro" => 1600,
    "Pro" => 1800,
    "Pro-Master" => null,
];

$categories_with_stati = [
    "opensingle" => true,
    "opendouble" => true,
    "womensingle" => true,
    "womendouble" => true,
];

function get_status($points) {
    global $stati;
    $points = round($points);
    foreach ($stati as $name => $max) {
        if ($max === null || $points < $max) {
            return $name;
        }
    }
    return "Pro-Master";
}

$has_stati = false;

if (isset($_GET["category"])) {
    $category = strtolower($_GET["category"]);
    $name = $names[$category];
    $isDouble = in_array($category, $doubles);
    $id = null;
    if (array_key_exists($category, $ids)) {
        $id = $ids[$category];
    }
    $has_stati = array_key_exists($category, $categories_with_stati) && $categories_with_stati[$category] === true;
} else if (isset($_GET["id"]) && isset($_GET["isDouble"]) && isset($_GET["name"])) { 
    $id = $_GET["id"];
    $name = $_GET["name"];
    $isDouble = $_GET["isDouble"];
    $category = null;
}

$showInactive = $_GET["showInactive"];
$maxDate = $_GET["maxDate"];
if (!!$maxDate) {
    $maxDateObj = date_create($maxDate);
    $firstOfMaxDateMonth = date_format($maxDateObj, "Y-m") . "-01";
}

function build_query($category = true, $withShowInactive = true, $withMaxDate = true, $withPlayerId = false, $withItsfLicenseNumber = false) {
    $params = $_GET;
    if ($withShowInactive === false) {
        unset($params["showInactive"]);
    }
    if ($withMaxDate === false) {
        unset($params["maxDate"]);
    }
    if ($withPlayerId === false) {
        unset($params["playerId"]);
    }
    if ($withItsfLicenseNumber === false) {
        unset($params["itsfLicenseNumber"]);
    }
    if ($category === false) {
        unset($params["category"]);
    } else if ($category !== true) {
        $params["category"] = $category;
    }
    
    return http_build_query($params);
}
