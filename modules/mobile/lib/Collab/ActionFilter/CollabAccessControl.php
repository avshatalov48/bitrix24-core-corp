<?php

namespace Bitrix\Mobile\Collab\ActionFilter;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Socialnetwork\Collab\CollabFeature;

class CollabAccessControl extends Engine\ActionFilter\Base
{
	/**
	 * @throws LoaderException
	 */
	public function onBeforeAction(Event $event): EventResult|null
	{
		if (!CollabFeature::isOn())
		{
			return $this->addAccessDeniedError();
		}

		$actionParams = $event->getParameters();
		if (
			isset($actionParams['action'])
			&& $actionParams['action']->getName() === 'isCollabToolEnabled'
		)
		{
			return null;
		}

		if (!static::isCollabToolEnabled())
		{
			return $this->addAccessDeniedError();
		}

		return null;
	}

	/**
	 * @return bool
	 * @throws LoaderException
	 */
	public static function isCollabToolEnabled(): bool
	{
		if (Loader::includeModule('intranet'))
		{
			return ToolsManager::getInstance()->checkAvailabilityByToolId('collab');
		}

		return true;
	}

	private function addAccessDeniedError(): EventResult
	{
		$this->addError(new Error(
			Main\Localization\Loc::getMessage('COLLAB_ACCESS_CONTROL_ACCESS_DENIED')
		));

		return new EventResult(EventResult::ERROR, null, null, $this);
	}
}