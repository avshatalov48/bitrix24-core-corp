<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CBPCrmCreateAdsActivityVk');

class CBPCrmCreateAdsActivityFb extends CBPCrmCreateAdsActivityVk
{
	protected static function getAdsType()
	{
		return 'facebook';
	}
}