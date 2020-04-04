<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CBPCrmCreateAdsActivityVk');

class CBPCrmCreateAdsActivityGa extends CBPCrmCreateAdsActivityVk
{
	protected static function getAdsType()
	{
		return 'google';
	}
}