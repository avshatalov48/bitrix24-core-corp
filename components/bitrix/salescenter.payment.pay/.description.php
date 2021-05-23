<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("SPP_DEFAULT_TEMPLATE_NAME"),
	"DESCRIPTION" => GetMessage("SPP_DEFAULT_TEMPLATE_DESCRIPTION"),
	"ICON" => "",
	"PATH" => array(
		"ID" => "e-store",
		"CHILD" => array(
			"ID" => "salescenter_payment_pay",
			"NAME" => GetMessage("SPP_NAME")
		)
	),
);
?>