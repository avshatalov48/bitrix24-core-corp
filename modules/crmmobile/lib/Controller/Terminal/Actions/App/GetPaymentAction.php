<?php

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\App;

use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Integration\Sale\Payment\DtoItemData;
use Bitrix\CrmMobile\Integration\Sale\Payment\GetPaymentQuery;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sale\Repository\PaymentRepository;

class GetPaymentAction extends Action
{
	final public function run(int $id, CurrentUser $currentUser): ?DtoItemData
	{
		if (!Container::getInstance()->getUserPermissions()->item()->canRead(\CCrmOwnerType::OrderPayment, $id))
		{
			return null;
		}

		return (new GetPaymentQuery(
			PaymentRepository::getInstance()->getById($id)
		))->execute();
	}
}
