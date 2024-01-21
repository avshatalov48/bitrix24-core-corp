<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\FactoryTabable;

final class CrmCustomSectionFactory implements FactoryTabable
{
	private const MINIMAL_API_VERSION = 45;

	private Context $context;

	private ?array $customSections = [];

	public function setContext(Context $context): void
	{
		$this->context = $context;
	}

	public function __construct()
	{
		if ($this->isModulesIncludedAndCustomSectionsAvailable())
		{
			$this->customSections = IntranetManager::getCustomSections() ?? [];
		}
	}

	private function isModulesIncludedAndCustomSectionsAvailable(): bool
	{
		return (
			Loader::includeModule('crm')
			&& Loader::includeModule('crmmobile')
			&& IntranetManager::isCustomSectionsAvailable()
			&& \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager()->checkExternalDynamicAvailability()
		);
	}

	public function getTabsList(): array
	{
		$result = [];

		foreach ($this->customSections as $item)
		{
			$pages = $item->getPages();
			if (empty($pages))
			{
				continue;
			}

			$result[CrmCustomSection::getCode($item->getId())] = new CrmCustomSection($item);
		}

		return $result;
	}
}
