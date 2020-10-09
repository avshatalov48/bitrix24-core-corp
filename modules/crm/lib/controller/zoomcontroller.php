<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Integration\Zoom\Conference;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Service\MicroService\BaseReceiver;
use Bitrix\SocialServices\Integration\Zoom\Recording;

class ZoomController extends BaseReceiver
{
	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		Loader::includeModule('crm');

		return parent::processBeforeAction($action);
	}

	public function registerEndMeetingAction(int $conferenceId)
	{
		$updateResult = Conference::updateEndStatus($conferenceId);
		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());
		}

		return null;
	}

	public function registerStopRecordingsAction(int $conferenceId, array $recordingsData)
	{
		if (!Loader::includeModule('socialservices'))
		{
			return $this->addError(new Error('Socialservices module is not installed'));
		}
		$updateResult = Recording::onRecordingStopped($conferenceId, $recordingsData);
		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());
		}
		return null;
	}

	public function getMeetingRecordingsAction(int $conferenceId, array $recordingsData, string $downloadToken)
	{
		if (!Loader::includeModule('socialservices'))
		{
			return $this->addError(new Error('Socialservices module is not installed'));
		}
		$updateResult = \Bitrix\SocialServices\Integration\Zoom\Conference::saveRecordings($conferenceId, $recordingsData, $downloadToken);
		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());
		}

		return null;
	}
}