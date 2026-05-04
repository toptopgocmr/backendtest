<?php

return [
    "paths" => ["api/*", "sanctum/csrf-cookie"],
    "allowed_methods" => ["*"],
    "allowed_origins" => [
        "https://tholadimmo.up.railway.app",
        "http://localhost:51244",
        "http://localhost:3000",
        "http://localhost:8080",
    ],
    "allowed_origins_patterns" => [],
    "allowed_headers" => ["*"],
    "exposed_headers" => [],
    "max_age" => 0,
    "supports_credentials" => true,
];
