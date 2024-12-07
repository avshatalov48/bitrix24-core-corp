<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Conversion\ConversionManager;
use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\Mode;
use Bitrix\Main\Engine\ActionFilter;

class Conversion extends Base
{
	public function configureActions(): array
	{
		return [
			'getConversionMenuItems' => [
				'+prefilters' => [
					new ActionFilter\Csrf(),
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
					new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
					new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
					new ActionFilter\CloseSession(),
					new CheckReadPermission(),
				],
			],
		];
	}

	public function getConfigCrmModeAction()
	{
		$modes = [
			Mode::CLASSIC => 'CLASSIC',
			Mode::SIMPLE => 'SIMPLE',
		];

		$existActiveLeads = false;

		$dbResult = \CCrmLead::GetListEx(
			[],
			[
				"STATUS_SEMANTIC_ID" => \Bitrix\Crm\PhaseSemantics::PROCESS,
				'CHECK_PERMISSIONS' => 'N'
			],
			false,
			["nTopCount" => 1],
			["ID"]
		);
		if ($dbResult->Fetch())
		{
			$existActiveLeads = true;
		}

		return [
			'currentCrmMode' => $modes[Mode::getCurrent()],
			'existActiveLeads' => $existActiveLeads,
		];
	}

	public function getConversionMenuItemsAction(Item $entity): array
	{
		$conversionConfig = ConversionManager::getConfig($entity->getEntityTypeId());
		$conversionConfig->deleteItemByEntityTypeId(\CCrmOwnerType::SmartDocument);
		$conversionData = $conversionConfig->getScheme()->toJson(true);

		//remove from conversion to old invoices and documents
		$conversionData['items'] = array_values(
			array_filter($conversionData['items'],
				fn($item) => $item['name'] !== \CCrmOwnerType::InvoiceName
					&& $item['name'] !== \CCrmOwnerType::SmartDocumentName
			)
		);

		if ($entity->getEntityTypeId() === \CCrmOwnerType::Lead)
		{
			$conversionData['isReturnCustomer'] = $entity->getIsReturnCustomer();
			if ($entity->getStageId() === 'CONVERTED' || $entity->getIsReturnCustomer())
			{
				$items = [];
				foreach ($conversionData['items'] as $item)
				{
					if (\CCrmOwnerType::DealName === $item['name'])
					{
						$items[] = $item;
					}
				}

				$conversionData['items'] = $items;
			}
		}

		if (\CCrmOwnerType::Lead === $entity->getEntityTypeId())
		{
			$userPermissions = Container::getInstance()->getUserPermissions();
			$conversionData['permissions'] = [
				\CCrmOwnerType::Contact => [
					'read' => $userPermissions->checkReadPermissions(\CCrmOwnerType::Contact, 0, 0),
					'write' => $userPermissions->checkUpdatePermissions(\CCrmOwnerType::Contact, 0, 0),
				],
				\CCrmOwnerType::Company => [
					'read' => $userPermissions->checkReadPermissions(\CCrmOwnerType::Company, 0, 0),
					'write' => $userPermissions->checkUpdatePermissions(\CCrmOwnerType::Company, 0, 0),
				],
			];
		}

		return $conversionData;
	}
}
