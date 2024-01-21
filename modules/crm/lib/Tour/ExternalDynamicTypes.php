<?php

namespace Bitrix\Crm\Tour;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

class ExternalDynamicTypes extends Base
{
	protected const OPTION_NAME = 'aha-moment-external-dynamic-types-moved';
	private const EXTERNAL_DYNAMIC_TYPES_EXISTS_OPTION = 'isset-external-dynamic-types';

	/**
	 * @inheritDoc
	 */
	protected function canShow(): bool
	{
		return
			!$this->isUserSeenTour()
			&& $this->isExternalDynamicTypesExists()
		;
	}

	public function getSteps(): array
	{
		return [
			[
				'id' => 'crm-external-dynamic-types-step',
				'target' => '#pagetitle',
				'title' => Loc::getMessage('CRM_TOUR_EXTERNAL_DYNAMIC_TYPES_TITLE'),
				'text' => Loc::getMessage('CRM_TOUR_EXTERNAL_DYNAMIC_TYPES_TEXT'),
				'article' => 18913880,
			],
		];
	}

	public function getOptions(): array
	{
		return [
			'steps' => [
				'popup' => [
					'width' => 380,
				],
			],
		];
	}

	private function isExternalDynamicTypesExists(): bool
	{
		$existsOption = Option::get('crm', self::EXTERNAL_DYNAMIC_TYPES_EXISTS_OPTION, null);
		if (!is_null($existsOption))
		{
			return (bool)$existsOption;
		}

		$customSections = IntranetManager::getCustomSections() ?? [];
		foreach ($customSections as $customSection)
		{
			foreach ($customSection->getPages() as $page)
			{
				$pageSettings = $page->getSettings();
				$entityTypeID = IntranetManager::getEntityTypeIdByPageSettings($pageSettings);
				if ($entityTypeID > 0)
				{
					$this->setExternalDynamicTypesExists(true);

					return true;
				}
			}
		}

		$this->setExternalDynamicTypesExists(false);

		return false;
	}

	private function setExternalDynamicTypesExists(bool $isExists): void
	{
		Option::set('crm', self::EXTERNAL_DYNAMIC_TYPES_EXISTS_OPTION, $isExists);
	}
}
