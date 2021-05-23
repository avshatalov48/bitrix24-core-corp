<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

// week day order
$weekStart = $arResult['TEMPLATE_DATA']['COMPANY_WORKTIME']['WEEK_START'];

$wdMap = array(
    0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6
);

if((string) $weekStart != '')
{
    $wdStrMap = array(
        'MO' => 0,
        'TU' => 1,
        'WE' => 2,
        'TH' => 3,
        'FR' => 4,
        'SA' => 5,
        'SU' => 6,
    );

    $offs = $wdStrMap[$weekStart];

    $wdMap = array();
    for($k = 0; $k < 7; $k++)
    {
        $wdMap[$k] = ($k + $offs) % 7;
    }
}

$arResult['TEMPLATE_DATA']['WEEKDAY_MAP'] = $wdMap;

// set data defaults
if(!\Bitrix\Tasks\Util\Type::isIterable($arResult['TEMPLATE_DATA']['DATA']))
{
    $arResult['TEMPLATE_DATA']['DATA'] = array();
}
$arResult['TEMPLATE_DATA']['DATA'] = CTaskTemplates::parseReplicationParams($arResult['TEMPLATE_DATA']['DATA']);

if(!array_key_exists('TIMEZONE_OFFSET', $arResult['TEMPLATE_DATA']['DATA']))
{
	$user = false; // current
	// task has a template to repeat, task creator instead of the current
	if($arResult['TEMPLATE_DATA']['TEMPLATE']['ID'])
	{
		$user =  $arResult['TEMPLATE_DATA']['TEMPLATE']['CREATED_BY'];
	}

	$arResult['TEMPLATE_DATA']['DATA']['TIMEZONE_OFFSET'] = \Bitrix\Tasks\Util\User::getTimeZoneOffset($user);
}
$arResult['TEMPLATE_DATA']['UTC_TIME_ZONE_OFFSET'] = \Bitrix\Tasks\Util::getServerTimeZoneOffset() + intval($arResult['TEMPLATE_DATA']['DATA']['TIMEZONE_OFFSET']);