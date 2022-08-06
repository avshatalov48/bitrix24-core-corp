<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	"js" => [
		"/bitrix/js/voximplant/common/common.js",
	],
	"css" => [
		"/bitrix/js/voximplant/common/common.css",
		"/bitrix/js/voximplant/common/telephony.css"
	],
	"rel" => [
		"main.polyfill.promise",
		"ui.design-tokens",
		"ui.fonts.opensans",
	]
);