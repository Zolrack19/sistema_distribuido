<?php
// $psScript = __DIR__ . "../script.ps1";
// $command = "powershell -NoProfile -ExecutionPolicy Bypass -File \"$psScript\"";

// $output = shell_exec($command);

// header('Content-Type: application/json');
// echo $output ?: json_encode(["error" => "No se pudo obtener la información"]);

echo json_encode([
  "CPU" => [
    "Name" => "AMD RYZEN",
    "NumberOfCores" => random_int(1, 10),
    "LoadPercentage" => random_int(5, 100)
  ],
  "RAM" => [
    "TotalGB" => random_int(60, 120),
    "FreeGB" => random_int(5, 59)
  ],
  "Disks" => [
    [
      "DeviceID" => "C:",
      "TotalGB" => random_int(200, 500),
      "FreeGB" => random_int(100, 199) 
    ],
    [
      "DeviceID" => "D:",
      "TotalGB" => random_int(500, 600),
      "FreeGB" => random_int(200, 500)
    ]
  ]
]);