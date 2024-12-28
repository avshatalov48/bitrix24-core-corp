<?php

namespace Bitrix\Crm\Controller\Settings;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Operation\Autostart\FillFieldsSettings;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class AI extends Base
{
	protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();

		$filters[] = new Scope(Scope::AJAX);
		$filters[] = new ContentType([ContentType::JSON]); // its pain to work with empty arrays, nulls and booleans otherwise

		$filters[] = new class extends \Bitrix\Main\Engine\ActionFilter\Base {
			public function onBeforeAction(Event $event): ?EventResult
			{
				if (!AIManager::isAiCallProcessingEnabled())
				{
					$this->addError(ErrorCode::getAccessDeniedError());

					return new EventResult(EventResult::ERROR, null, 'crm', $this);
				}

				return null;
			}
		};

		return $filters;
	}

	public function getAutostartSettingsAction(int $entityTypeId, ?int $categoryId = null): ?array
	{
		if (!FillFieldsSettings::checkReadPermissions($entityTypeId, $categoryId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		return [
			'settings' => FillFieldsSettings::get($entityTypeId, $categoryId),
		];
	}

	public function saveAutostartSettingsAction(array $settings, int $entityTypeId, ?int $categoryId = null): ?array
	{
		if (!FillFieldsSettings::checkSavePermissions($entityTypeId, $categoryId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$settingsObject = FillFieldsSettings::fromJson($settings);
		if (!$settingsObject)
		{
			$this->addError(new Error('settings has invalid structure', ErrorCode::INVALID_ARG_VALUE));

			return null;
		}

		$result = FillFieldsSettings::save($settingsObject, $entityTypeId, $categoryId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $this->getAutostartSettingsAction($entityTypeId, $categoryId);
	}
}
