<?php


namespace Bitrix\Crm\Controller;


use Bitrix\Crm\Order\ContactCompanyCollection;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Sale\Registry;

class OrderContactCompany extends \Bitrix\Sale\Controller\Controller
{
	public function getFieldsAction()
	{
		$entity = new \Bitrix\Crm\Order\Rest\Entity\OrderContactCompany();
		return ['CLIENT'=>$entity->prepareFieldInfos(
			$entity->getFields()
		)];
	}

	public function listAction(PageNavigation $pageNavigation, array $select = [], array $filter = [], array $order = []): Page
	{
		$select = empty($select)? ['*']:$select;
		$order = empty($order)? ['ID'=>'ASC']:$order;

		$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
		/** @var ContactCompanyCollection $contactCompanyCollection */
		$contactCompanyCollection = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);

		$tradeBindings = $contactCompanyCollection::getList(
			[
				'select'=>$select,
				'filter'=>$filter,
				'order'=>$order,
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit()
			]
		)->fetchAll();

		return new Page('CLIENTS', $tradeBindings, function() use ($filter)
		{
			$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
			/** @var ContactCompanyCollection $contactCompanyCollection */
			$contactCompanyCollection = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);

			return count(
				$contactCompanyCollection::getList(['filter'=>$filter])->fetchAll()
			);
		});
	}

	static public function prepareFields($fields)
	{
		$data = [
			\CCrmOwnerType::Company=>[],
			\CCrmOwnerType::Contact=>[]
		];

		$contactIsPrinary = false;

		if(isset($fields['CLIENTS']))
		{
			foreach ($fields['CLIENTS'] as $client)
			{
				//TODO: необходимо вынести в проверку полей. ѕол€ в rest описаны как об€зательные
				if(intval($client['ENTITY_TYPE_ID'])<=0 || intval($client['ENTITY_ID'])<=0)
					continue;

				$client['IS_PRIMARY'] = (isset($client['IS_PRIMARY']) && $client['IS_PRIMARY'] == 'Y') ? 'Y':'N';

				// компани€ может быть только одна
				if(count($data[\CCrmOwnerType::Company])==0)
				{
					if($client['ENTITY_TYPE_ID'] == \CCrmOwnerType::Company)
					{
						// компани€ должна быть isPrinary т.к. в этого требует матчер
						$client['IS_PRIMARY'] = 'Y';
						$data[\CCrmOwnerType::Company][] = $client;
					}
				}

				if($client['ENTITY_TYPE_ID'] == \CCrmOwnerType::Contact)
				{
					if(!$contactIsPrinary)
						$contactIsPrinary = $client['IS_PRIMARY'] == 'Y';

					$data[\CCrmOwnerType::Contact][] = $client;
				}
			}

			if(count($data[\CCrmOwnerType::Contact])>0 && !$contactIsPrinary)
			{
				//если ни один из переданных контактов не €вл€етс€ isPrinary, проставл€ем признак дл€ первого
				$data[\CCrmOwnerType::Contact][0]['IS_PRIMARY'] = 'Y';
			}
			return ['CLIENTS'=>array_merge($data[\CCrmOwnerType::Company], $data[\CCrmOwnerType::Contact])];
		}
		return [];
	}
}