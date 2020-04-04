<?php
namespace Bitrix\Crm\Controller\Action\Order;

use Bitrix\Main;
use Bitrix\Crm;

class SearchBuyerAction extends Main\Search\SearchAction
{
	/**
	 * BX.ajax.runAction("crm.api.orderbuyer.search", { data: { searchQuery: "John Smith", options: {} } });
	 *
	 * @param string $searchQuery
	 * @param array|null $options
	 * @param Main\UI\PageNavigation|null $pageNavigation
	 *
	 * @return array|Main\Search\ResultItem[]
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function provideData($searchQuery, array $options = null, Main\UI\PageNavigation $pageNavigation = null)
	{
		if (!is_array($options))
		{
			$options = [];
		}

		$filter = Main\UserUtils::getAdminSearchFilter([
			'FIND' => $searchQuery
		]);

		$filter['=ACTIVE'] = 'Y';
		$filter['=GROUP.GROUP_ID'] = Crm\Order\BuyerGroup::getSystemGroupId();
		$userData = Main\UserTable::getList(array(
			'filter' => $filter,
			'select' => ["ID", "LOGIN", "ACTIVE", "EMAIL", "NAME", "LAST_NAME", "SECOND_NAME"],
			'runtime' => [
				new Main\Entity\ReferenceField(
					'GROUP',
					'\Bitrix\Main\UserGroupTable',
					['=ref.USER_ID' => 'this.ID'],
					['join_type' => 'LEFT']
				)
			],
			'data_doubling' => false
		));

		$result = [];
		$nameFormat = \CSite::getNameFormat(false);
		while ($user = $userData->fetch())
		{
			$title = \CUser::FormatName(
				$nameFormat,
				array(
					'LOGIN' => $user['LOGIN'],
					'NAME' => $user['NAME'],
					'LAST_NAME' => $user['LAST_NAME'],
					'SECOND_NAME' => $user['SECOND_NAME']
				),
				true,
				false
			);
			$resultItem = new Main\Search\ResultItem($title, '', $user['ID']);
			$resultItem->setSubTitle($user['LOGIN']);
			$resultItem->setAttribute('email', [
					[
						'type' => 'EMAIL',
						'value' => $user['EMAIL']
					]
				]
			);
			$result[] = $resultItem;
		}

		if (empty($result) && is_array($options['emptyItem']) && !empty($options['emptyItem']))
		{
			$emptyItem = new Main\Search\ResultItem('', '');
			if (!empty($options['emptyItem']['subtitle']))
			{
				$emptyItem->setSubTitle($options['emptyItem']['subtitle']);
			}

			$result[] = $emptyItem;
		}

		return $result;
	}
}
