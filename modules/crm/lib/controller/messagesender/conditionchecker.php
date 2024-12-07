<?php


namespace Bitrix\Crm\Controller\MessageSender;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Notifications\FeatureStatus;
use Bitrix\Notifications\Limit;
use Bitrix\Notifications\Settings;

class ConditionChecker extends Base
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new Scope(Scope::NOT_REST);

		return $filters;
	}

	public function getVirtualWhatsAppConfigAction(int $entityTypeId): array
	{
		if (!Container::getInstance()->getUserPermissions()->canReadType($entityTypeId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return [];
		}

		if (!$this->checkNotificationsModule())
		{
			return [];
		}

		$infoHelperCode = null;
		$virtualWhatsAppStatus = Settings::getScenarioAvailability(Settings::SCENARIO_VIRTUAL_WHATSAPP);
		if ($virtualWhatsAppStatus !== FeatureStatus::UNAVAILABLE)
		{
			$infoHelperCode = $virtualWhatsAppStatus === FeatureStatus::LIMITED
				? Limit::getInfoHelperCodeForScenario(Settings::SCENARIO_VIRTUAL_WHATSAPP)
				: null;
		}

		return [
			'infoHelperCode' => $infoHelperCode,
		];
	}

	private function checkNotificationsModule(): bool
	{
		if (Loader::includeModule('notifications'))
		{
			return true;
		}

		$this->addError(
			new Error(Loc::getMessage('CRM_CONTROLLER_MESSAGESENDER_CONDITIONCHECKER_NEED_MODULES'))
		);

		return false;
	}

	public function getSmsSendersAction(int $entityTypeId): array
	{
		if (!Container::getInstance()->getUserPermissions()->canReadType($entityTypeId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return [];
		}

		return SmsManager::getSenderSelectList();
	}
}