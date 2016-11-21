<?php

function getCallerLine(): string {
    $backtrace = debug_backtrace();
    return $backtrace[1]['file'] . ':' . $backtrace[1]['line'];
}
