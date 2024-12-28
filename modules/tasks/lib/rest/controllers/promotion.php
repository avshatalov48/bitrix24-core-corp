<?php

namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Tasks\Promotion\AbstractPromotion;
use Bitrix\Tasks\Promotion\PromotionFactory;
use Bitrix\Tasks\Promotion\PromotionType;

class Promotion extends Controller
{
	/**
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				AbstractPromotion::class,
				'promotion',
				function($className, $promotion): ?AbstractPromotion {
					$promotionType = PromotionType::tryFrom($promotion);

					if (!$promotionType)
					{
						$this->addError(new Error('Unknown promotion type'));

						return null;
					}

					return (new PromotionFactory())->getByPromotionType($promotionType);
				},
			),
		];
	}

	public function setViewedAction(AbstractPromotion $promotion): bool
	{
		$userId = (int)$this->getCurrentUser()?->getId();

		if ($userId <= 0)
		{
			return false;
		}

		return $promotion->setViewed($userId);
	}
}