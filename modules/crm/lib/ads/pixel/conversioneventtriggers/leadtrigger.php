<?php

namespace Bitrix\Crm\Ads\Pixel\ConversionEventTriggers;

use Bitrix\Crm\Ads\Pixel\EventBuilders\AbstractFacebookBuilder;
use Bitrix\Crm\Ads\Pixel\EventBuilders\CrmConversionEventBuilderInterface;
use Bitrix\Crm\Automation\Factory;
use Bitrix\Seo\Conversion\Facebook;
use CCrmOwnerType;
use Throwable;

/**
 * Class LeadTrigger
 * @package Bitrix\Crm\Ads\Pixel\ConversionEventTriggers
 */
final class LeadTrigger extends BaseTrigger
{
	protected const LEAD_CODE = 'lead';

	protected const FACEBOOK_TYPE = 'facebook';

	/**@var int|string|null $leadId */
	protected $leadId;

	/**
	 * LeadTrigger constructor.
	 *
	 * @param $leadId
	 */
	public function __construct(int $leadId)
	{
		$this->leadId = $leadId;
		parent::__construct();
	}

	/**
	 * @param array $lead
	 */
	public static function onLeadStageChange(array $lead)
	{
		try
		{
			!isset($lead['ID'], $lead['STATUS_ID']) ? : (new static(intval($lead['ID'])))->execute();
		}
		catch (Throwable $throwable)
		{

		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getCode(): string
	{
		return static::FACEBOOK_TYPE.'.'.static::LEAD_CODE;
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
		return new class($this->leadId) extends AbstractFacebookBuilder implements CrmConversionEventBuilderInterface {

			/**@var  array|null */
			protected $lead;

			/**
			 * FacebookCrmLeadEventBuilder constructor.
			 *
			 * @param int $dealId
			 */
			public function __construct(int $dealId)
			{
				$this->lead = $this->getLead($dealId);
			}

			protected function resolveEventName(): string
			{
				$entityName = $this->getEntityName();
				$stageName = Factory
					::createTarget(CCrmOwnerType::Lead)
					->getStatusInfos()[$this->lead['STATUS_ID']]['NAME'];

				return $entityName.'. '.$stageName;
			}

			protected function getEntityName(): string
			{
				if (array_key_exists($key = CCrmOwnerType::Lead,
					$typeDescriptions = CCrmOwnerType::GetAllDescriptions()))
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
						'value' => (float)($this->lead['OPPORTUNITY'] ?? 0),
						'currency' => $this->lead['CURRENCY_ID'],
					],
				];
			}

			/**
			 * @return array
			 */
			public function getUserData(): array
			{
				return $this->getLeadUserData($this->lead);
			}
		};
	}

	protected function checkConfiguration(): bool
	{
		if ($configuration = $this->getConfiguration())
		{
			return $configuration->has('enable') && filter_var($configuration->get('enable'), FILTER_VALIDATE_BOOLEAN);
		}

		return false;
	}
}