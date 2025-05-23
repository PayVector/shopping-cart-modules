<?php

/*
 * Product: PayVector Payment Gateway
 * Version: 1.0.0
 * Release Date: 2014.02.03
 *
 * Copyright (C) 2014 PayVector <support@payvector.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// The domain ONLY for the hosted payment form - e.g. if the hosted payment form URL is
// https://mms.paymentprocessor.net/Pages/PublicPages/PaymentForm.aspx, then the domain
// will be "paymentprocessor.net"
$PaymentProcessorDomain = "payvector.net";

// This is the port that the gateway communicates on -->
// e.g. for "https://gwX.paymentprocessor.net:4430/", this should be 4430
// e.g. for "https://gwX.thepaymentgateway.net/", this should be 443
$PaymentProcessorPort = 443;

if ($PaymentProcessorPort == 443)
{
	$PaymentProcessorFullDomain = $PaymentProcessorDomain."/";
}
else
{
	$PaymentProcessorFullDomain = $PaymentProcessorDomain.":".$PaymentProcessorPort."/";
}