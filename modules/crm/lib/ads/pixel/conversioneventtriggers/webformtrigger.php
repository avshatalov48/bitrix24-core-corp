<?php

namespace Bitrix\Crm\Ads\Pixel\ConversionEventTriggers;

use Bitrix\Crm\Ads\Pixel\EventBuilders\AbstractFacebookBuilder;
use Bitrix\Crm\Ads\Pixel\EventBuilders\CrmConversionEventBuilderInterface;
use Bitrix\Crm\WebForm\Form;
use Bitrix\Crm\WebForm\ResultEntity;
use Bitrix\Seo\Conversion\Facebook;

/**
 * Class WebFormTrigger
 * @package Bitrix\Crm\Ads\Pixel\ConversionEventTriggers
 */
final class WebFormTrigger extends BaseTrigger
{
	protected const WEB_FORM_CODE = 'webform';

	protected const FACEBOOK_TYPE = 'facebook';

	/** @var ResultEntity $resultEntity */
	protected $resultEntity;

	/**
	 * WebFormTrigger constructor.
	 *
	 * @param ResultEntity $resultEntity
	 */
	public function __construct(ResultEntity $resultEntity)
	{
		$this->resultEntity = $resultEntity;
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	protected function checkConfiguration(): bool
	{
		if (($configuration = $this->getConfiguration()) && $this->resultEntity)
		{
			if ($configuration->has('items') && is_array($forms = $configuration->get('items')))
			{
				return ($formId = $this->resultEntity->getFormId()) && in_array($formId,$forms);
			}
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	protected function getCode(): string
	{
		return static::FACEBOOK_TYPE . '.' . static::WEB_FORM_CODE;
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
		return new class($this->resultEntity) extends AbstractFacebookBuilder implements CrmConversionEventBuilderInterface {

			/** @var ResultEntity $resultEntity*/
			private $resultEntity;

			/**@var Form $form */
			private $form;

			/**
			 * FacebookCrmWebFormEventBuilder constructor.
			 *
			 * @param ResultEntity $resultEntity
			 */
			public function __construct(ResultEntity $resultEntity)
			{
				($this->form = new Form())->loadOnlyForm($resultEntity->getFormId());
				$this->resultEntity = $resultEntity;
			}

			/**
			 * @return array
			 */
			public function getUserData() : array
			{
				$userData = [];
				if (!empty($this->form->get()))
				{
					if (empty($userData) && $id = $this->resultEntity->getEntityIdByTypeName(\CCrmOwnerType::ContactName))
					{
						if (!empty($data = $this->getContactUserData($id)))
						{
							$userData[] = $data;
						}
					}

					if (empty($userData) && $id = $this->resultEntity->getEntityIdByTypeName(\CCrmOwnerType::CompanyName))
					{
						if (!empty($data = $this->getCompanyUserData($id)))
						{
							$userData[] = $data;
						}
					}

					if (empty($userData) && $id = $this->resultEntity->getEntityIdByTypeName(\CCrmOwnerType::LeadName))
					{
						if ($lead = $this->getLead($id))
						{
							$userData = array_merge_recursive($userData,$this->getLeadUserData($lead));
						}
					}

					if (empty($userData) && $id = $this->resultEntity->getEntityIdByTypeName(\CCrmOwnerType::DealName))
					{
						if ($deal = $this->getDeal($id))
						{
							$userData = array_merge_recursive($userData,$this->getDealUserData($deal));
						}
					}
				}

				return $userData;
			}

			/**
			 * @param $entity
			 *
			 * @return array|null
			 */
			public function getEventParams($entity): ?array
			{
				return [
					'event_name' => Facebook\Event::EVENT_COMPLETE_REGISTRATION,
					'action_source' => Facebook\Event::ACTION_SOURCE_SYSTEM_GENERATED,
					'user_data' => $entity,
					'event_source_url' => $this->form->getLandingUrl(),
					'custom_data' => [
						'content_name' => $this->form->get()['NAME'],
					],
				];
			}
		};
	}

	/**
	 * @param ResultEntity $entity
	 */
	public static function onFormFill(ResultEntity $entity)
	{
		try
		{
			(new static($entity))->execute();
		}
		catch (\Throwable $throwable)
		{
		}
	}
}