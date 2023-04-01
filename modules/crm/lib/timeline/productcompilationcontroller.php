<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\Service\Timeline\Item\DealProductList\SkuConverter;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\ItemIdentifier;

Loc::loadMessages(__FILE__);

class ProductCompilationController extends Controller
{
	public function onCompilationViewed(int $dealId, array $params): void
	{
		if ($dealId <= 0)
		{
			throw new Main\ArgumentException('Deal ID must be greater than zero.');
		}

		if (
			$this->isCompilationProductListSent($dealId, $params)
			&& !$this->isCompilationAlreadyViewed($dealId, $params)
		)
		{
			$this->addToTimeline($dealId, $params, ProductCompilationType::COMPILATION_VIEWED);
		}
	}

	protected function isCompilationProductListSent($dealId, $params): bool
	{
		return $this->isCompilationMessageSent(ProductCompilationType::PRODUCT_LIST, $dealId, $params);
	}

	protected function isCompilationAlreadyViewed(int $dealId, array $params): bool
	{
		return $this->isCompilationMessageSent(ProductCompilationType::COMPILATION_VIEWED, $dealId, $params);
	}

	protected function isCompilationMessageSent(int $compilationMessageType, int $dealId, array $params): bool
	{
		$timelineTableResult = Entity\TimelineTable::getList([
			'order' => ['ID' => 'ASC'],
			'filter' => [
				'TYPE_ID' => TimelineType::PRODUCT_COMPILATION,
				'TYPE_CATEGORY_ID' => $compilationMessageType,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ASSOCIATED_ENTITY_ID' => $dealId,
			]
		]);

		while ($item = $timelineTableResult->fetch())
		{
			if (
				isset($item['SETTINGS']['COMPILATION_ID'])
				&& (int)$item['SETTINGS']['COMPILATION_ID'] === (int)$params['SETTINGS']['COMPILATION_ID']
			)
			{
				return true;
			}
		}

		return false;
	}

	public function onOrderCheckout(int $dealId, array $params): void
	{
		if ($dealId <= 0)
		{
			throw new Main\ArgumentException('Deal ID must be greater than zero.');
		}

		$this->addToTimeline($dealId, $params, ProductCompilationType::NEW_DEAL_CREATED);
	}

	public function onCompilationSent(int $dealId, array $params): void
	{
		if ($dealId <= 0)
		{
			throw new Main\ArgumentException('Deal ID must be greater than zero.');
		}

		if (isset($params['SETTINGS']['SENT_PRODUCTS']) && is_array($params['SETTINGS']['SENT_PRODUCTS']))
		{
			$params['SETTINGS']['SENT_PRODUCTS'] = array_map(
				static function (BaseSku $sku)
				{
					return SkuConverter::convertToProductModel($sku)->toArray();
				},
				$params['SETTINGS']['SENT_PRODUCTS']
			);
		}

		$this->addToTimeline($dealId, $params, ProductCompilationType::PRODUCT_LIST);
		$this->addToTimeline($dealId, [], ProductCompilationType::COMPILATION_NOT_VIEWED);

		$isOrderNoticeNeeded = !empty(OrderEntityTable::getOrderIdsByOwner($dealId, \CCrmOwnerType::Deal));
		if ($isOrderNoticeNeeded)
		{
			$this->addToTimeline($dealId, $params, ProductCompilationType::ORDER_EXISTS);
		}
	}

	protected function addToTimeline(int $dealId, array $params, int $typeCategoryId): void
	{
		$settings = $params['SETTINGS'] ?? [];

		$bindings = [
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID' => $dealId
			]
		];

		$authorId = \CCrmDeal::GetByID($dealId, false)['ASSIGNED_BY'];
		if ((int)$authorId <= 0)
		{
			$authorId = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$entityId = ProductCompilationEntry::create([
			'ENTITY_ID' => $dealId,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
			'TYPE_CATEGORY_ID' => $typeCategoryId,
			'CREATED' => Main\Type\DateTime::createFromTimestamp(time()),
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'BINDINGS' => $bindings,
		]);

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$entityId
			);
		}
	}
}
