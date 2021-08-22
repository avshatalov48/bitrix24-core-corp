<?php

namespace Bitrix\Crm\Ads\Pixel\ConversionEventTriggers;

use CCrmOwnerType;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Seo\Conversion\Facebook;
use Bitrix\Crm\Ads\Pixel\EventBuilders\AbstractFacebookBuilder;
use Bitrix\Crm\Ads\Pixel\EventBuilders\CrmConversionEventBuilderInterface;

/**
 * Class DealTrigger
 * @package Bitrix\Crm\Ads\Pixel\ConversionEventTriggers
 */
final class DealTrigger extends BaseTrigger
{
	protected const DEAL_CODE = 'deal';

	protected const FACEBOOK_TYPE = 'facebook';

	/** @var array $deal */
	protected $deal;

	/**
	 * DealTrigger constructor.
	 *
	 * @param $dealId
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function __construct($dealId)
	{
		parent::__construct();
		$this->deal = DealTable::getRow([
				'select' => [
					'COMPANY_ID',
					'CONTACT_ID',
					'STAGE_ID',
					'CATEGORY_ID',
					'OPPORTUNITY',
					'CURRENCY_ID',
					'SOURCE_ID'
				],
				'filter' => ['=ID' => $dealId],
			]);
	}

	/**
	 * @inheritDoc
	 */
	protected function getCode(): string
	{
		return static::FACEBOOK_TYPE . '.' . static::DEAL_CODE;
	}

	/**
	 * @inheritDoc
	 */
	protected function getType(): string
	{
		return static::FACEBOOK_TYPE;
	}

	/**
	 * @inheritDoc
	 */
	protected function getConversionEventBuilder(): CrmConversionEventBuilderInterface
	{
		return new class($this->deal) extends AbstractFacebookBuilder implements CrmConversionEventBuilderInterface {
			/**@var array|null $deal */
			protected $deal;

			/**
			 * FacebookCrmDealEventBuilder constructor.
			 *
			 * @param array|null $deal
			 */
			public function __construct(?array $deal)
			{
				$this->deal = $deal;
			}

			protected function resolveEventName(): string
			{
				['CATEGORY_ID' => $categoryId, 'STAGE_ID' => $stage] = $this->deal;
				$categoryId = $categoryId ?? 0;
				$stageName = Factory::createTarget(CCrmOwnerType::Deal)->getStatusInfos($categoryId)[$stage]['NAME'];
				if ($categoryId > 0)
				{
					return $this->getEntityName() . '. ' . DealCategory::getName($categoryId) . '. ' . $stageName;
				}

				return $this->getEntityName() . '. ' . $stageName;
			}

			protected function getEntityName(): string
			{
				if (array_key_exists($key = CCrmOwnerType::Deal, $typeDescriptions = CCrmOwnerType::GetAllDescriptions()))
				{
					return $typeDescriptions[$key];
				}

				return '';
			}

			/**
			 * @param $entity
			 *
			 * @return array|null
			 */
			public function getEventParams($entity): ?array
			{
				return [
					'event_name' => $this->resolveEventName(),
					'action_source' => Facebook\Event::ACTION_SOURCE_SYSTEM_GENERATED,
					'user_data' => $entity,
					'custom_data' => [
						'value' => (float)($this->deal['OPPORTUNITY'] ?? 0),
						'currency' => $this->deal['CURRENCY_ID'],
					],
				];
			}

			/**
			 * @return array
			 */
			public function getUserData(): array
			{
				return $this->getDealUserData($this->deal);
			}
		};
	}

	/**
	 * @inheritDoc
	 */
	protected function checkConfiguration(): bool
	{
		if (($configuration = $this->getConfiguration()) && !empty($deal = $this->deal))
		{
			if ($configuration->has('items') && is_array($categories = $configuration->get('items')))
			{
				return array_key_exists('CATEGORY_ID',$deal) && in_array($deal['CATEGORY_ID'],$categories);
			}
		}

		return false;
	}

	/**
	 * @param array $deal
	 */
	public static function onDealChangeStage(array $deal)
	{
		try
		{
			if (array_key_exists('ID',$deal) && array_key_exists('STAGE_ID',$deal))
			{
				(new static($deal['ID']))->execute();
			}
		}
		catch (\Throwable $throwable)
		{
		}
	}
}