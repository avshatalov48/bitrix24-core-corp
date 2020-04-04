<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CBPCrmCreateAdsActivityVk');

class CBPCrmCreateAdsActivityYa extends CBPCrmCreateAdsActivityVk
{
	protected static function getAdsType()
	{
		return 'yandex';
	}

	public function Execute()
	{
		$msg = GetMessage('CRM_CREATE_ADS_YA_IS_UNAVAILABLE');
		if (!$msg)
		{
			$msg = 'Service is unavailable now.';
		}

		$this->WriteToTrackingService($msg, 0, \CBPTrackingType::Error);
		return CBPActivityExecutionStatus::Closed;
	}
}