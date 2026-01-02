<?php

use Bitrix\Intranet;
use Bitrix\Intranet\Internal\Integration\Timeman\WorkTime;
use Bitrix\Main\Web\Uri;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetAvatarWidget extends \CBitrixComponent
{
	public function executeComponent()
	{
		$user = new Intranet\User();

		$this->arResult['skeleton'] = (new Intranet\User\Widget())->getSkeletonConfiguration();
		$this->arResult['avatar'] = Uri::urnEncode((new Intranet\User\Widget\Content\Main($user))->getUserPhotoSrc());
		$this->arResult['userRole'] = $user->getUserRole()->value;
		$this->arResult['userId'] = $user->getId();
		$this->arResult['signDocumentsCounter'] = \Bitrix\Intranet\User\Widget\Content\Tool\MyDocuments::getCount();
		$this->arResult['signDocumentsPullEventName'] = \Bitrix\Intranet\User\Widget\Content\Tool\MyDocuments::getCounterEventName();
		$this->arResult['workTimeData'] = $this->getWorkTimeData($user->getId());

		$this->includeComponentTemplate();
	}

	private function getWorkTimeData(int $currentUserId): array
	{
		$cache = new \CPHPCache;

		$cacheTtl = 3500;
		$cacheId = 'timeman-work-time-data-v2-' . $currentUserId;
		$cacheDir = '/timeman/work-time-data/' . $currentUserId;

		if ($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			return $cache->getVars();
		}

		$cache->startDataCache();

		$workTimeAvailable = WorkTime::canUse();

		$workTimeState = '';
		$workTimeAction = '';
		$workTimeClass = '';

		if ($workTimeAvailable)
		{
			$timeManUser = \CTimeManUser::instance();
			$workTimeState = $timeManUser->state();

			if ($workTimeState === 'CLOSED')
			{
				$workTimeAction = $timeManUser->openAction();
				$workTimeAction = ($workTimeAction === false) ? '' : $workTimeAction;

				if ($workTimeAction === 'OPEN')
				{
					$workTimeClass = '--worktime-not-started';
				}
				else
				{
					$workTimeClass = '--worktime-finished';
				}
			}
			elseif ($workTimeState === 'EXPIRED')
			{
				$workTimeClass = '--worktime-not-finished';
			}
			elseif ($workTimeState === 'PAUSED')
			{
				$workTimeClass = '--worktime-paused';
			}
		}

		$workTimeData = [
			'workTimeAvailable' => $workTimeAvailable,
			'workTimeState' => $workTimeState,
			'workTimeAction' => $workTimeAction,
			'workTimeClass' => $workTimeClass,
		];

		$cache->endDataCache($workTimeData);

		return $workTimeData;
	}
}
