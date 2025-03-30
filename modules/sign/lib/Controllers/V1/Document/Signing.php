<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Type\Access\AccessibleItemType;

class Signing extends \Bitrix\Sign\Engine\Controller
{
	/**
	 * @param string $uid
	 *
	 * @return array
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function startAction(string $uid): array
	{
		$result = (new Operation\SigningStart($uid))->launch();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return [];
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 */
	#[Attribute\Access\LogicOr(
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		),
		new Attribute\ActionAccess(
			permission: ActionDictionary::ACTION_B2E_DOCUMENT_EDIT,
			itemType: AccessibleItemType::DOCUMENT,
			itemIdOrUidRequestKey: 'uid',
		)
	)]
	public function stopAction(string $uid): array
	{
		$userId = $this->getCurrentUser()?->getId();
		$result = (new Operation\SigningStop($uid, $userId))->launch();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return [];
	}
}
