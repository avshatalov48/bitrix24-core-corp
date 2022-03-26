<?php

namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Crm;

/**
 * Class FetchPaymentDocumentsAction
 * @package Bitrix\Crm\Controller\Action\Entity
 * @example BX.ajax.runAction("crm.api.entity.fetchPaymentDocuments", { data: { ownerTypeId: 2, ownerId: 123 } });
 */
class FetchPaymentDocumentsAction extends Main\Engine\Action
{
	private const ERROR_CODE_ACCESS_DENIED = 'ACCESS_DENIED';

	/**
	 * Entry point.
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 * @return ?array
	 */
	public function run(int $ownerTypeId, int $ownerId)
	{
		if (!Crm\Security\EntityAuthorization::checkReadPermission($ownerTypeId, $ownerId))
		{
			$this->addError(new Error('Access denied', static::ERROR_CODE_ACCESS_DENIED));
			return null;
		}

		/** @var Crm\Entity\PaymentDocumentsRepository */
		$repository = ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');

		$result = $repository->getDocumentsForEntity($ownerTypeId, $ownerId);

		if ($result->isSuccess())
		{
			return $result->getData();
		}
		else
		{
			$this->addErrors($result->getErrors());
			return null;
		}
	}
}
