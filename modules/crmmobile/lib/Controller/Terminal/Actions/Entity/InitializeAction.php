<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\Entity;

use Bitrix\Crm\Item;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\ProductGrid\ProductGridCreatePaymentQuery;
use Bitrix\CrmMobile\Integration\Sale\Payment\EntityEditorFieldsProvider;
use Bitrix\Main\Engine\CurrentUser;

class InitializeAction extends Action
{
	final public function run(Item $entity): array
	{
		return [
			'steps' => [
				'responsible' => [
					'fields' => [
						(new EntityEditorFieldsProvider())->getResponsibleField(),
					],
				],
				'product' => [
					'grid' => (new ProductGridCreatePaymentQuery($entity))->execute(),
				],
				'finish' => [
					'currentUserId' => CurrentUser::get()->getId(),
				],
			],
		];
	}
}
