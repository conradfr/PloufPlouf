<?php

use PloufPlouf\IndexControllerProvider,
    PloufPlouf\BlacklistControllerProvider;


// mounting controller
$app->mount('/', new IndexControllerProvider());
$app->mount('/blacklist', new BlacklistControllerProvider());