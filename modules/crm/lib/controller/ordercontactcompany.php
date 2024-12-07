<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Order\ContactCompanyCollection;

use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sale\Registry;

class OrderContactCompany extends \Bitrix\Sale\Controller\Controller
{
	public function getFieldsAction(): array
	{
		$entity = new \Bitrix\Crm\Order\Rest\Entity\OrderContactCompany();

		return [
			'CLIENT' => $entity->prepareFieldInfos($entity->getFields()),
		];
	}

	public function listAction(
		PageNavigation $pageNavigation,
		array $select = [],
		array $filter = [],
		array $order = []
	): Page
	{
		$select = empty($select) ? ['*'] : $select;
		$order = empty($order) ? ['ID'=>'ASC'] : $order;

		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);

		/** @var ContactCompanyCollection $contactCompanyCollection */
		$contactCompanyCollection = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);

		$tradeBindings = $contactCompanyCollection::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order,
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
		])->fetchAll();

		return new Page('CLIENTS', $tradeBindings, static function() use ($filter)
		{
			$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);

			/** @var ContactCompanyCollection $contactCompanyCollection */
			$contactCompanyCollection = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);

			return count(
				$contactCompanyCollection::getList(['filter'=>$filter])->fetchAll()
			);
		});
	}

	public static function prepareFields($fields): array
	{
		$data = [
			\CCrmOwnerType::Company=>[],
			\CCrmOwnerType::Contact=>[],
		];

		$contactIsPrimary = false;

		if (isset($fields['CLIENTS']))
		{
			foreach ($fields['CLIENTS'] as $client)
			{
				//TODO: must be included in the field check. Fields in rest are described as required
				if (
					(int)$client['ENTITY_TYPE_ID'] <= 0
					|| (int)$client['ENTITY_ID'] <= 0
				)
				{
					continue;
				}

				$client['IS_PRIMARY'] = isset($client['IS_PRIMARY']) && $client['IS_PRIMARY'] == 'Y' ? 'Y' : 'N';

				// there can only be one company
				if (empty($data[\CCrmOwnerType::Company]))
				{
					if ((int)$client['ENTITY_TYPE_ID'] === \CCrmOwnerType::Company)
					{
						// the company must be isPrimary because the matcher requires this
						$client['IS_PRIMARY'] = 'Y';
						$data[\CCrmOwnerType::Company][] = $client;
					}
				}

				if ((int)$client['ENTITY_TYPE_ID'] === \CCrmOwnerType::Contact)
				{
					if (!$contactIsPrimary)
					{
						$contactIsPrimary = $client['IS_PRIMARY'] === 'Y';
					}

					$data[\CCrmOwnerType::Contact][] = $client;
				}
			}

			if (
				!empty($data[\CCrmOwnerType::Contact])
				&& !$contactIsPrimary
			)
			{
				// if none of the transferred contacts is isPrimary, set the flag for the first
				$data[\CCrmOwnerType::Contact][0]['IS_PRIMARY'] = 'Y';
			}

			return [
				'CLIENTS' => array_merge(
					$data[\CCrmOwnerType::Company],
					$data[\CCrmOwnerType::Contact]
				),
			];
		}

		return [];
	}
}
