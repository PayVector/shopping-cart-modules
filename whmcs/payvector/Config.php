<?php
$DeveloperEmailAddress = "";
$PaymentProcessorDomain = "payvector.net";
$PaymentProcessorPort   = 443;
if ($PaymentProcessorPort == 443) {
    $PaymentProcessorFullDomain = $PaymentProcessorDomain . "/";
} else {
    $PaymentProcessorFullDomain = $PaymentProcessorDomain . ":" . $PaymentProcessorPort . "/";
}