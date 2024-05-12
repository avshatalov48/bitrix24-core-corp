<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Integration\Intranet\ToolsManager;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\CrmMobile\Controller\Base;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Request;

class DisablingTools extends Base
{
	private ToolsManager $toolsManager;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
	}

	public function configureActions(): array
	{
		return [
			'getSlidersCodesForDisabledStaticEntityIds' => [
				'+prefilters' => $this->getPrefilters(),
			],
			'getEntitySliderCodeIfDisabled' => [
				'+prefilters' => $this->getPrefilters(),
			],
			'getCrmSliderCodeIfDisabled' => [
				'+prefilters' => $this->getPrefilters(),
			],
		];
	}

	private function getPrefilters() : array
	{
	    return [
			new CloseSession(),
		];
	}

	public function getSlidersCodesForDisabledStaticEntitiesAction(): array
	{
		$staticEntities = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Invoice,
			\CCrmOwnerType::SmartInvoice,
			\CCrmOwnerType::Quote,
		];

		$result = [];

		foreach ($staticEntities as $staticEntity)
		{
			$result[$staticEntity] = $this->getSliderCodeIfDisabled($staticEntity);
		}

		$result['crm'] = $this->toolsManager->checkCrmAvailability()
			? null : $this->toolsManager::CRM_SLIDER_CODE;

		return $result;
	}

	public function getEntitySliderCodeIfDisabledAction(int $entityTypeId): array
	{
	    return [
			$entityTypeId => [
				'code' => $this->getSliderCodeIfDisabled($entityTypeId),
				'isExternal' => $this->toolsManager->isEntityTypeIdExternal($entityTypeId),
			],
		];
	}

	private function getSliderCodeIfDisabled(int $entityTypeId): ?string
	{
	    return $this->toolsManager->checkEntityTypeAvailability($entityTypeId)
			? null
			: $this->toolsManager->getSliderCodeByEntityTypeId($entityTypeId);
	}
}