<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\App;

use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\ProductGrid\ProductGridDocumentQuery;
use Bitrix\Main\Engine\CurrentUser;

class GetPaymentProductListAction extends Action
{
	final public function run(int $id, CurrentUser $currentUser): array
	{
		if (!Container::getInstance()->getUserPermissions()->item()->canRead(\CCrmOwnerType::OrderPayment, $id))
		{
			return [];
		}

		return [
			'grid' => (new ProductGridDocumentQuery($id))->execute(),
		];
	}
}
