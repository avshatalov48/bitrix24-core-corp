<?php

namespace Bitrix\Crm\Service\Timeline\Item\Factory;

use Bitrix\Crm\Activity\Provider\CalendarSharing;
use Bitrix\Crm\Activity\Provider\ConfigurableRestApp;
use Bitrix\Crm\Activity\Provider\Delivery;
use Bitrix\Crm\Activity\Provider\Document;
use Bitrix\Crm\Activity\Provider\Notification;
use Bitrix\Crm\Activity\Provider\Payment;
use Bitrix\Crm\Activity\Provider\SignB2eDocument;
use Bitrix\Crm\Activity\Provider\SignDocument;
use Bitrix\Crm\Activity\Provider\Sms;
use Bitrix\Crm\Activity\Provider\StoreDocument;
use Bitrix\Crm\Activity\Provider\Tasks;
use Bitrix\Crm\Activity\Provider\ToDo;
use Bitrix\Crm\Activity\Provider\Visit;
use Bitrix\Crm\Activity\ProviderId;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Item\Activity\StoreDocument\NotEnoughGoodsInStock;
use Bitrix\Crm\Service\Timeline\Item\Model;
use CCrmActivityType;

class ConfigurableActivity
{
	/**
	 * Create timeline item for Activity based on Item\Configurable implementation
	 *
	 * @param int $typeId
	 * @param string $providerId
	 * @param Context $context
	 * @param Model $model
	 *
	 * @return Item|null
	 */
	public static function create(int $typeId, string $providerId, Context $context, Model $model): ?Item
	{
		$providerTypeId = $model->getAssociatedEntityModel()?->get('PROVIDER_TYPE_ID');

		if ($typeId === CCrmActivityType::Email)
		{
			return new Item\Activity\Email($context, $model);
		}

		if ($typeId === CCrmActivityType::Call)
		{
			return new Item\Activity\Call($context, $model);
		}

		if ($typeId === CCrmActivityType::Provider)
		{
			if ($providerId === ProviderId::IMOPENLINES_SESSION)
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

			if ($providerId === SignB2eDocument::getId())
			{
				if (SignB2eDocument::isActive())
				{
					return new Item\Activity\SignB2eDocument($context, $model);
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

			if ($providerId === CalendarSharing::getId())
			{
				return new Item\Activity\CalendarSharing($context, $model);
			}

			if ($providerId === ConfigurableRestApp::getId())
			{
				return new Item\Activity\ConfigurableRestApp($context, $model);
			}

			if ($providerId === Tasks\Comment::getId())
			{
				return new Item\Activity\Tasks\Comment($context, $model);
			}

			if ($providerId === Tasks\Task::getId())
			{
				return new Item\Activity\Tasks\Task($context, $model);
			}

			if ($providerId === Visit::getId())
			{
				return new Item\Activity\Visit($context, $model);
			}
		}

		return null;
	}
}
