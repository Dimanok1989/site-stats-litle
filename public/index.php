<?php

require __DIR__ . "/../vendor/autoload.php";

if (file_exists(__DIR__ . "/config.php"))
    require __DIR__ . "/config.php";

try {
    $check = (new \Kolgaev\SiteStatsLitle\IP)->check();

    if (getenv("KOLGAEV_STATS_DEBUG")) {
        echo json_encode($check);
    }

    if (!empty($check['block'])) {
        if ($check['block'] == true) {
            http_response_code(500);
            exit;
        }
    }
} catch (\Exception $e) {
    if (getenv("KOLGAEV_STATS_DEBUG")) {
        echo json_encode([
            'error' => $e->getMessage(),
        ]);
    }
}
