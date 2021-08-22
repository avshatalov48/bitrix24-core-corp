<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\CallDuration;

use Bitrix\Report\VisualConstructor\IReportMultipleData;
use CVoxImplantMain;

/**
 * Class CallDuration
 * @package Bitrix\Voximplant\Integration\Report\Handler\CallDuration
 */
class CallDurationGrid extends CallDuration implements IReportMultipleData
{
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		if (!$calculatedData['report'])
		{
			return [];
		}

		$this->preloadUserInfo(array_column($calculatedData['report'], 'PORTAL_USER_ID'));

		$result = [];
		foreach ($calculatedData['report'] as $row)
		{
			$user = $this->getUserInfo($row['PORTAL_USER_ID']);

			$result[] = [
				'value' => [
					'USER_NAME' => $user['name'],
					'USER_ICON' => $user['icon'],
					'INCOMING_DURATION' => $row['INCOMING_DURATION'],
					'INCOMING_DURATION_FORMATTED' => $this->formatDuration($row['INCOMING_DURATION']),
					'OUTGOING_DURATION' => $row['OUTGOING_DURATION'],
					'OUTGOING_DURATION_FORMATTED' => $this->formatDuration($row['OUTGOING_DURATION']),
					'INCOMING_DYNAMICS' => $this->formatPeriodCompare($row['INCOMING_DURATION_COMPARE']),
					'OUTGOING_DYNAMICS' => $this->formatPeriodCompare($row['OUTGOING_DURATION_COMPARE']),
				],
				'url' => [
					'INCOMING_DURATION' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => [
							CVoxImplantMain::CALL_INCOMING,
							CVoxImplantMain::CALL_INCOMING_REDIRECT,
						],
						'STATUS' => self::CALL_STATUS_SUCCESS,
					]),
					'OUTGOING_DURATION' => $this->createUrl(self::TELEPHONY_DETAIL_URI, [
						'PORTAL_USER_ID' => $row['PORTAL_USER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
					]),
				]
			];
		}

		return $result;
	}

	public function getMultipleDemoData()
	{

	}
}