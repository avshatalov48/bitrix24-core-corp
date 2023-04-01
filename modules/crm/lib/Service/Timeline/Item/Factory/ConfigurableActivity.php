<?php

namespace Bitrix\Crm\Service\Timeline\Item\Factory;

use Bitrix\Crm\Activity\Provider\Delivery;
use Bitrix\Crm\Activity\Provider\Document;
use Bitrix\Crm\Activity\Provider\Payment;
use Bitrix\Crm\Activity\Provider\SignDocument;
use Bitrix\Crm\Activity\Provider\Sms;
use Bitrix\Crm\Activity\Provider\StoreDocument;
use Bitrix\Crm\Activity\Provider\ToDo;
use Bitrix\Crm\Activity\Provider\Notification;
use Bitrix\Crm\Activity\ProviderId;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Service\Timeline\Item\Activity\StoreDocument\NotEnoughGoodsInStock;

class ConfigurableActivity
{
	/**
	 * Create timeline item for Activity based on Item\Configurable implementation
	 *
	 * @param int $typeId
	 * @param string $providerId
	 * @param Context $context
	 * @param Model $model
	 * @return Item
	 */
	public static function create(int $typeId, string $providerId, Context $context, Model $model): ?Item
	{
		$providerTypeId = $model->getAssociatedEntityModel()->get('PROVIDER_TYPE_ID');

		// Configurable items for calls and openlines are temporary disabled
		if ($typeId === \CCrmActivityType::Call && Crm::isUniversalActivityScenarioEnabled())
		{
			return new Item\Activity\Call($context, $model);
		}

		if ($typeId === \CCrmActivityType::Provider)
		{
			if ($providerId === ProviderId::IMOPENLINES_SESSION && Crm::isUniversalActivityScenarioEnabled())
			{
				return new Item\Activity\OpenLine($context, $model);
			}

			if ($providerId === Document::getId())
			{
				if (Document::isActive())
				{
					return new Item\Activity\Document($context, $model);
				}

				return new Item\NotAvailable($context, $model);
			}

			if ($providerId === SignDocument::getId())
			{
				if (SignDocument::isActive())
				{
					return new Item\Activity\SignDocument($context, $model);
				}

				return new Item\NotAvailable($context, $model);
			}

			if ($providerId === ToDo::getId())
			{
				return new Item\Activity\ToDo($context, $model);
			}

			if ($providerId === StoreDocument::getId())
			{
				$map = [
					StoreDocument::PROVIDER_TYPE_ID_PRODUCT => NotEnoughGoodsInStock\Product::class,
					StoreDocument::PROVIDER_TYPE_ID_SERVICE => NotEnoughGoodsInStock\Service::class,
				];

				$className = $map[$providerTypeId] ?? null;
				if ($className)
				{
					return new $className($context, $model);
				}
			}

			if ($providerId === Payment::getId())
			{
				return new Item\Activity\Payment($context, $model);
			}

			if ($providerId === Delivery::getId())
			{
				return new Item\Activity\Delivery($context, $model);
			}

			if ($providerId === Sms::getId())
			{
				return new Item\Activity\Sms\Sms($context, $model);
			}

			if ($providerId === Notification::getId())
			{
				return new Item\Activity\Sms\Notification($context, $model);
			}
		}

		return null;
	}
}
