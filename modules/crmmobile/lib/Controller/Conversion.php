<?php

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Item;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use \Bitrix\Crm\Settings\Mode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Crm\Conversion\ConversionManager;
use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;

class Conversion extends Controller
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

		$dbRes = \CCrmLead::GetListEx(['DATE_CREATE' => 'desc'],
			["STATUS_SEMANTIC_ID" => \Bitrix\Crm\PhaseSemantics::PROCESS],
			false, ["nPageSize" => 1], ["ID"]);
		$dbRes->NavStart(1, false);
		if ($dbRes->GetNext())
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
				fn ($item) => $item['name'] !== \CCrmOwnerType::InvoiceName
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
