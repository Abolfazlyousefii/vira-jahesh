<?php

// Start PHP from the repository root so its sandbox can write the whole workspace.
// Laravel's development router expects public/ as its request working directory.
chdir(dirname(__DIR__).'/public');

return require dirname(__DIR__).'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
