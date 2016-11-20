<?php

namespace T;

require 'app/core/boot.php';

$className = require ('app/actions/test.php');

$ac = new $className();

$ac->main([]);
