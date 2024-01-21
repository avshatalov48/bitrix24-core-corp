<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Integration\Intranet\ToolsManager;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Request;

class DisablingTools extends Controller
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
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getEntitySliderCodeIfDisabled' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getCrmSliderCodeIfDisabled' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getSlidersCodesForDisabledStaticEntityIdsAction(): array
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
			$result[$staticEntity] = (
				$this->toolsManager->checkEntityTypeAvailability($staticEntity)
				? null
				: $this->toolsManager->getSliderCodeByEntityTypeId($staticEntity)
			);
		}

		return $result;
	}

	public function getEntitySliderCodeIfDisabledAction(int $entityTypeId): array
	{
	    return [
			$entityTypeId => [
				'code' => (
					$this->toolsManager->checkEntityTypeAvailability($entityTypeId)
					? null
					: $this->toolsManager->getSliderCodeByEntityTypeId($entityTypeId)
				),
				'isExternal' => $this->toolsManager->isEntityTypeIdExternal($entityTypeId),
			],
		];
	}

	public function getCrmSliderCodeIfDisabledAction(): array
	{
		$result = [
			'crm' => $this->toolsManager->checkCrmAvailability()
				? null
				: $this->toolsManager::CRM_SLIDER_CODE,
		];

		return $result;
	}
}