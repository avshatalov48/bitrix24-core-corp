<?php

namespace Bitrix\Sign\Connector\Crm;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Integration\Crm\MyCompanyCollection;

final class MyCompany extends Company
{
	/**
	 * @param list<int> $inIds
	 */
	public static function listItems(?int $itemsAmount = null, array $inIds = []): MyCompanyCollection
	{
		$result = new Item\Integration\Crm\MyCompanyCollection();
		if (!Loader::includeModule('crm'))
		{
			return $result;
		}

		$crmContainer = Container::getInstance();
		$limitFilter = !$itemsAmount ? ['limit' => $itemsAmount] : [];
		$filter = ['IS_MY_COMPANY' => 'Y'];
		if (!empty($inIds))
		{
			$filter['@ID'] = $inIds;
		}

		$items = $crmContainer->getFactory(\CCrmOwnerType::Company)?->getItems(
			[
				'select' => ['ID'],
				'filter' => $filter,
			] + $limitFilter
		);

		$items ??= [];
		foreach ($items as $item)
		{
			$id = $item->getId();

			$myCompany = MyCompany::getById($id);
			if ($myCompany !== null)
			{
				$result->add(MyCompany::getById($id));
			}
		}

		return $result;
	}

	public static function getById(int $id): ?Item\Integration\Crm\MyCompany
	{
		if (!Loader::includeModule('crm'))
		{
			return null;
		}
		if (!\CCrmCompany::isMyCompany($id))
		{
			return null;
		}

		$crmContainer = Container::getInstance();
		$item = $crmContainer->getFactory(\CCrmOwnerType::Company)?->getItem($id);
		if ($item === null)
		{
			return null;
		}

		$id = $item->getId();
		$connector = new MyCompany($id);

		return new Item\Integration\Crm\MyCompany(
			name: $connector->getName(),
			id: $id,
		);
	}

	public function getTaxId(?Item\Connector\FetchRequisiteModifier $fetchRequisiteModifier = null): ?string
	{
		$rqInn = $this->fetchRequisite($fetchRequisiteModifier)->getFirstByName('COMPANY_RQ_INN')?->value;

		return is_string($rqInn)  && $rqInn !== ''? $rqInn : null;
	}

	public function getCrmEntityTypeId(): int
	{
		if (!Loader::includeModule('crm'))
		{
			return 0;
		}

		return \CCrmOwnerType::Company;
	}
}