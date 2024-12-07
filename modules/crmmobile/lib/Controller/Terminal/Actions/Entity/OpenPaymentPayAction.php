<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\Entity;

use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Mixin\ProvidesPsCreationActionProviders;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Mixin\ProvidesPullConfig;
use Bitrix\CrmMobile\Integration\Sale\Payment\GetPaymentQuery;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Crm\Order\Permissions;
use Bitrix\Main\Loader;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\SalesCenter\Integration\LandingManager;

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

		if (Loader::includeModule('salescenter'))
		{
			$connectedSiteId = LandingManager::getInstance()->getConnectedSiteId();
			$isPhoneConfirmed = LandingManager::getInstance()->isPhoneConfirmed();
		}

		return array_merge(
			[
				'payment' => (new GetPaymentQuery(PaymentRepository::getInstance()->getById($id)))->execute(),
			],
			[
				'isPhoneConfirmed' => $isPhoneConfirmed ?? true,
				'connectedSiteId' => $connectedSiteId ?? 0,
			],
			self::getPsCreationActionProviders(),
			self::getPullConfig(),
		);
	}
}
