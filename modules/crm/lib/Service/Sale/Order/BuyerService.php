<?php

namespace Bitrix\Crm\Service\Sale\Order;

use Bitrix\Crm\Order\Buyer;
use Bitrix\Crm\Order\BuyerGroup;
use Bitrix\Intranet\Util;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\UserTable;
use CBitrix24;
use CUser;

/**
 * Service for work with the buyer entity.
 */
class BuyerService
{
	/**
	 * Service instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self
	{
		return ServiceLocator::getInstance()->get('crm.order.buyer');
	}

	/**
	 * Attach user to shop buyers.
	 * Attaching only extranet users.
	 *
	 * @param int $userId
	 *
	 * @return Result
	 */
	public function attachUserToBuyers(int $userId): Result
	{
		$result = new Result();

		$isExtranetUser = false;
		if (Loader::includeModule('bitrix24'))
		{
			$isExtranetUser = CBitrix24::IsExtranetUser($userId);
		}
		elseif (Loader::includeModule('intranet'))
		{
			$isExtranetUser = Util::isExtranetUser($userId) || !Util::isIntranetUser($userId);
		}

		if (!$isExtranetUser)
		{
			$result->addError(
				new Error('User is not extranet')
			);
			return $result;
		}

		// set external auth id
		$userNotHasExternalAuth = UserTable::getRow([
			'select' => [
				'ID',
			],
			'filter' => [
				'=ID' => $userId,
				'=EXTERNAL_AUTH_ID' => null,
			],
		]) !== null;
		if ($userNotHasExternalAuth)
		{
			$user = new CUser();
			$user->Update($userId, [
				'EXTERNAL_AUTH_ID' => Buyer::AUTH_ID,
			]);

			if ($user->LAST_ERROR)
			{
				$result->addError(
					new Error($user->LAST_ERROR)
				);
				return $result;
			}
		}

		// add to group
		CUser::AppendUserGroup($userId, BuyerGroup::getDefaultGroups());

		return $result;
	}
}