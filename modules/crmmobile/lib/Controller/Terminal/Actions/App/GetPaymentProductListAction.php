<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\App;

use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\ProductGrid\ProductGridDocumentQuery;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Crm\Order\Permissions;

class GetPaymentProductListAction extends Action
{
	final public function run(int $id, CurrentUser $currentUser): array
	{
		$hasReadPermission = Permissions\Payment::checkReadPermission($id);
		if (!$hasReadPermission)
		{
			return [];
		}

		return [
			'grid' => (new ProductGridDocumentQuery($id))->execute(),
		];
	}
}
