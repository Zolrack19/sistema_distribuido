<?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $carpetaGeneral = __DIR__ . "/uploads/";
    
    if (!is_dir($carpetaGeneral)) {
      mkdir($carpetaGeneral, 0777, true);
    }

    $rutaArchivo = $carpetaGeneral . basename($_FILES['archivo']['name']);
    
    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaArchivo)) {
      echo true; // bien
    } else {
      echo false; // mal
    }
  } else {
    echo false;
  }
?>