<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Merger\EntityMerger;

class LeadConversionWizard extends EntityConversionWizard
{
	public function isNewApi(): bool
	{
		//lead doesn't support factories yet
		return false;
	}

	/**
	 * @param int $entityID Entity ID.
	 * @param LeadConversionConfig|null $config Configuration parameters.
	 */
	public function __construct($entityID = 0, EntityConversionConfig $config = null)
	{
		$converter = new LeadConverter($config);
		$converter->setEntityID($entityID);

		parent::__construct($converter);
	}
	/**
	 * Check if completion of lead activities is enabled.
	 * Completion of activities is performed then lead goes into final status. It is enabled by default.
	 * @return bool
	 */
	public function isActivityCompletionEnabled()
	{
		/** @var LeadConverter $converter */
		$converter = $this->converter;
		return $converter->isActivityCompletionEnabled();
	}
	/**
	 * Enable/disable completion of lead activities.
	 * Completion of activities is performed then lead goes into final status.
	 * @param bool $enable Flag of enabling completion of lead activities.
	 */
	public function enableActivityCompletion($enable)
	{
		/** @var LeadConverter $converter */
		$converter = $this->converter;
		$converter->enableActivityCompletion($enable);
	}
	/**
	 * Execute wizard.
	 * @param array|null $contextData Conversion context data.
	 * @return bool
	 */
	public function execute(array $contextData = null)
	{
		if ($this->isNewApi())
		{
			return parent::execute($contextData);
		}

		/** @var LeadConverter $converter */
		$converter = $this->converter;

		if(is_array($contextData) && !empty($contextData))
		{
			$converter->setContextData(array_merge($converter->getContextData(), $contextData));
		}

		$result = false;
		try
		{
			$converter->initialize();
			do
			{
				$converter->executePhase();
			}
			while($converter->moveToNextPhase());

			$resultData = $converter->getResultData();
			if($this->isRedirectToShowEnabled())
			{
				if(isset($resultData[\CCrmOwnerType::DealName]))
				{
					if ($this->isMobileContext)
					{
						$this->redirectUrl = "/mobile/crm/deal/?page=view&deal_id=".$resultData[\CCrmOwnerType::DealName];
					}
					else
					{
						$this->redirectUrl = \CCrmOwnerType::GetEntityShowPath(
							\CCrmOwnerType::Deal,
							$resultData[\CCrmOwnerType::DealName],
							false,
							array('ENABLE_SLIDER' => $this->enableSlider)
						);
					}
				}
				elseif(isset($resultData[\CCrmOwnerType::ContactName]))
				{
					if ($this->isMobileContext)
					{
						$this->redirectUrl = "/mobile/crm/contact/?page=view&contact_id=".$resultData[\CCrmOwnerType::ContactName];
					}
					else
					{
						$this->redirectUrl = \CCrmOwnerType::GetEntityShowPath(
							\CCrmOwnerType::Contact,
							$resultData[\CCrmOwnerType::ContactName],
							false,
							array('ENABLE_SLIDER' => $this->enableSlider)
						);
					}
				}
				elseif(isset($resultData[\CCrmOwnerType::CompanyName]))
				{
					if ($this->isMobileContext)
					{
						$this->redirectUrl = "/mobile/crm/company/?page=view&company_id=".$resultData[\CCrmOwnerType::CompanyName];
					}
					else
					{
						$this->redirectUrl = \CCrmOwnerType::GetEntityShowPath(
							\CCrmOwnerType::Company,
							$resultData[\CCrmOwnerType::CompanyName],
							false,
							array('ENABLE_SLIDER' => $this->enableSlider)
						);
					}
				}

				$this->eventParams = array(
					'name' => 'onCrmEntityConvert',
					'args' => array(
						'entityTypeId' => \CCrmOwnerType::Lead,
						'entityTypeName' => \CCrmOwnerType::LeadName,
						'entityId' => $this->getEntityID()
					)
				);
			}
			$result = true;
		}
		catch(EntityConversionException $e)
		{
			$this->exception = $e;
			if($e->getTargetType() === EntityConversionException::TARG_DST)
			{
				if ($this->isMobileContext)
				{
					switch($e->getDestinationEntityTypeID())
					{
						case (\CCrmOwnerType::Deal):
						{
							$this->redirectUrl = "/mobile/crm/deal/?page=edit&lead_id=".$converter->getEntityID();
							break;
						}
						case (\CCrmOwnerType::Contact):
						{
							$this->redirectUrl = "/mobile/crm/contact/?page=edit&lead_id=".$converter->getEntityID();
							break;
						}
						case (\CCrmOwnerType::Company):
						{
							$this->redirectUrl = "/mobile/crm/company/?page=edit&lead_id=".$converter->getEntityID();
							break;
						}
					}
				}
				else
				{
					//Required for Deal category
					$config = $converter->getConfig();
					$options = array(
						'ENTITY_SETTINGS' => $config->getEntityInitData($e->getDestinationEntityTypeID()),
						'ENABLE_SLIDER' => $this->enableSlider
					);

					$this->redirectUrl = \CCrmUrlUtil::AddUrlParams(
						\CCrmOwnerType::GetEntityEditPath(
							$e->getDestinationEntityTypeID(),
							0,
							false,
							$options
						),
						array('lead_id' => $converter->getEntityID(), 'entityTypeId' => $e->getDestinationEntityTypeID())
					);
				}
			}
		}
		catch(\Exception $e)
		{
			$this->errorText = $e->getMessage();
		}

		$this->save();
		return $result;
	}

	public function undo()
	{
		$this->converter->undo();
	}

	/**
	 * Prepare entity fields for edit.
	 * @param int $entityTypeID Entity type ID.
	 * @param array &$fields Entity fields.
	 * @param bool|true $encode Encode fields flag.
	 * @return void
	 */
	public function prepareDataForEdit($entityTypeID, array &$fields, $encode = true)
	{
		/** @var LeadConverter $converter */
		$converter = $this->converter;
		$userFields = LeadConversionMapper::getUserFields($entityTypeID);
		$mappedFields = $converter->mapEntityFields($entityTypeID, array('ENABLE_FILES' => false));
		foreach($mappedFields as $k => $v)
		{
			if($k === 'FM' || $k === 'PRODUCT_ROWS')
			{
				$fields[$k] = $v;
				continue;
			}
			elseif(mb_strpos($k, 'UF_CRM') === 0)
			{
				$userField = isset($userFields[$k]) ? $userFields[$k] : null;
				if(is_array($userField))
				{
					// hack for UF
					if($userField['USER_TYPE_ID'] === 'file')
					{
						$GLOBALS["{$k}_old_id"] = $v;
					}
					elseif(!isset($GLOBALS[$k]))
					{
						$GLOBALS[$k] = $_REQUEST[$k] = $v;
					}
				}
			}
			elseif($encode)
			{
				$fields["~{$k}"] = $v;
				if(!is_array($v))
				{
					$fields[$k] = htmlspecialcharsbx($v);
				}
			}
			else
			{
				$fields[$k] = $v;
			}
		}
	}
	/**
	 * Prepare entity fields for save.
	 * @param int $entityTypeID Entity type ID.
	 * @param array &$fields Entity fields.
	 * @return void
	 */
	public function prepareDataForSave($entityTypeID, array &$fields)
	{
		$dstUserFields = LeadConversionMapper::getUserFields($entityTypeID);
		foreach($dstUserFields as $dstName => $dstField)
		{
			if($dstField['USER_TYPE_ID'] === 'file')
			{
				$this->prepareFileUserFieldForSave($dstName, $dstField, $fields);
			}
		}

		/** @var LeadConverter $converter */
		$converter = $this->converter;
		$mappedFields = $converter->mapEntityFields($entityTypeID, array('DISABLE_USER_FIELD_INIT' => true));
		if(!empty($mappedFields))
		{
			$merger = EntityMerger::create($entityTypeID, $converter->getUserID(), true);

			//Skip empty fields if user has left theirs empty.
			$merger->mergeFields(
				$mappedFields,
				$fields,
				true,
				[
					'ENABLE_UPLOAD' => true,
					'SKIP_MULTIPLE_USER_FIELDS' => $this->isSkipMultipleUserFields(),
				]
			);
		}
	}
	/**
	 * Load wizard related to entity from session.
	 * @param int $entityID Entity ID.
	 * @return LeadConversionWizard|null
	 */
	public static function load($entityID)
	{
		$storage = self::getSessionLocalStorage(\CCrmOwnerType::Lead);
		if (!$storage)
		{
			return null;
		}

		$externalizedWizardParams = $storage[$entityID] ?? null;
		if (!is_array($externalizedWizardParams))
		{
			return null;
		}

		$item = new LeadConversionWizard($entityID);
		$item->internalize($externalizedWizardParams);
		return $item;
	}

	/**
	 * @inheritDoc
	 */
	public static function remove($entityID)
	{
		if ($entityID > 0)
		{
			static::removeByIdentifier(new ItemIdentifier(\CCrmOwnerType::Lead, (int)$entityID));
		}
	}
}
