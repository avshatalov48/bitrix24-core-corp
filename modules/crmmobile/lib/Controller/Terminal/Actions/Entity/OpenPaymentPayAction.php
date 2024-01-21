<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\Entity;

use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Mixin\ProvidesPsCreationActionProviders;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Mixin\ProvidesPullConfig;
use Bitrix\CrmMobile\Integration\Sale\Payment\GetPaymentQuery;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Crm\Order\Permissions;
use Bitrix\Sale\Repository\PaymentRepository;

class OpenPaymentPayAction extends Action
{
	use ProvidesPsCreationActionProviders;
	use ProvidesPullConfig;

	final public function run(int $id, CurrentUser $currentUser): ?array
	{
		if (!Permissions\Payment::checkReadPermission($id))
		{
			return null;
		}

		return array_merge(
			[
				'payment' => (new GetPaymentQuery(PaymentRepository::getInstance()->getById($id)))->execute(),
			],
			self::getPsCreationActionProviders(),
			self::getPullConfig(),
		);
	}
}
