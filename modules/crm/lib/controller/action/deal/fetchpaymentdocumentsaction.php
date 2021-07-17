<?php

namespace Bitrix\Crm\Controller\Action\Deal;

use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Crm;

/**
 * Class FetchPaymentDocumentsAction
 * @package Bitrix\Crm\Controller\Action\Deal
 * @example BX.ajax.runAction("crm.api.deal.fetchPaymentDocuments", { data: { dealId: 123 } });
 */
class FetchPaymentDocumentsAction extends Main\Engine\Action
{
	public const ERROR_CODE_ACCESS_DENIED = 'ACCESS_DENIED';

	/**
	 * Entry point.
	 * @param int $dealId
	 * @return ?array
	 */
	public function run(int $dealId)
	{
		if (!\CCrmDeal::CheckReadPermission($dealId))
		{
			$this->addError(new Error('Access denied', static::ERROR_CODE_ACCESS_DENIED));
			return null;
		}

		/** @var Crm\Deal\PaymentDocumentsRepository */
		$repository = ServiceLocator::getInstance()->get('crm.deal.paymentDocumentsRepository');

		$result = $repository->getDocumentsForDeal($dealId);

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
