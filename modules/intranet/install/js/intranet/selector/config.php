<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'css' => array(
		'/bitrix/js/intranet/selector/intranet.selector.css',
		'/bitrix/js/intranet/selector/callback.css'
	),
	'js' => array(
		'/bitrix/js/intranet/selector/intranet.selector.js'
	),
	'rel' => array('ui.selector')
);