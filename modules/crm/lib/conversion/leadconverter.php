<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Merger\CompanyMerger;
use Bitrix\Crm\Merger\ContactMerger;
use Bitrix\Crm\Requisite\AddressRequisiteConverter;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Requisite\RequisiteConvertException;
use Bitrix\Crm\Settings\ConversionSettings;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class LeadConverter extends EntityConverter
{
	/** @var array */
	private static $maps = array();
	/** @var LeadConversionMapper|null */
	private $mapper = null;
	/** @var int */
	private $conversionTypeID = LeadConversionType::UNDEFINED;
	/** @var bool */
	private $isReturnCustomer = false;
	/** @var bool */
	private $enableActivityCompletion = true;

	public function __construct(EntityConversionConfig $config = null)
	{
		if($config === null)
		{
			$config = new LeadConversionConfig();
		}
		parent::__construct($config);

		$this->setSourceFactory(Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Lead));
	}
	/**
	 * Check if completion of lead activities is enabled.
	 * Completion of activities is performed then lead goes into final status. It is enabled by default.
	 * @return bool
	 */
	public function isActivityCompletionEnabled()
	{
		return $this->enableActivityCompletion;
	}
	/**
	 * Enable/disable completion of lead activities.
	 * Completion of activities is performed then lead goes into final status.
	 * @param bool $enable Flag of enabling completion of lead activities.
	 */
	public function enableActivityCompletion($enable)
	{
		$this->enableActivityCompletion = $enable;
	}

	protected function isUseNewApi(EntityConversionConfigItem $configItem): bool
	{
		// lead doesn't support factories yet
		return false;
	}

	/**
	 * Initialize converter.
	 * @return void
	 * @throws EntityConversionException If entity is not exist.
	 * @throws EntityConversionException If read or update permissions are denied.
	 */
	public function initialize()
	{
		parent::initialize();

		if(!self::checkReadPermission(\CCrmOwnerType::LeadName, $this->entityID))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Lead,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::READ_DENIED
			);
		}

		if(!self::checkUpdatePermission(\CCrmOwnerType::LeadName, $this->entityID))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Lead,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::UPDATE_DENIED
			);
		}

		$resolvedFields = LeadConversionType::resolveByEntityID($this->entityID);

		if($resolvedFields === LeadConversionType::UNDEFINED)
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Lead,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::NOT_FOUND
			);
		}
		$this->conversionTypeID = $resolvedFields;
		$fields = \Bitrix\Crm\Conversion\LeadConversionType::loadLeadDataById((int)$this->entityID);

		$this->isReturnCustomer = isset($fields['IS_RETURN_CUSTOMER']) &&$fields['IS_RETURN_CUSTOMER'] == 'Y';

		$this->determineStartingPhase();
	}
	/**
	 * Get converter entity type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}
	/**
	 * Check if current phase is final.
	 * @return bool
	 */
	public function isFinished()
	{
		return LeadConversionPhase::isFinal($this->currentPhase);
	}
	/**
	 * Get conversion mapper
	 * @return LeadConversionMapper|null
	 */
	public function getMapper()
	{
		if($this->mapper === null)
		{
			$this->mapper = new LeadConversionMapper($this->entityID);
		}

		return $this->mapper;
	}
	/**
	 * Get conversion map for for specified entity type.
	 * Try to load saved map. If map is not found then default map will be created.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionMap
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function getMap($entityTypeID)
	{
		return self::prepareMap($entityTypeID);
	}
	/**
	 * Map entity fields to specified type.
	 * @param int $entityTypeID Entity type ID.
	 * @param array|null $options Mapping options.
	 * @return array
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public function mapEntityFields($entityTypeID, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}
		$options['INIT_DATA'] = $this->config->getEntityInitData($entityTypeID);

		$fields = $this->getMapper()->map($this->getMap($entityTypeID), $options);

		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			$companyID = self::getDestinationEntityID(\CCrmOwnerType::CompanyName, $this->resultData);
			if($companyID > 0)
			{
				$fields['COMPANY_ID'] = $companyID;
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			if($this->isReturnCustomer)
			{
				$contactIDs = Crm\Binding\LeadContactTable::getLeadContactIDs($this->entityID);
				if(!empty($contactIDs))
				{
					$fields['CONTACT_IDS'] = $contactIDs;
					$fields['CONTACT_ID'] = $contactIDs[0];
				}

				$companyID = $this->mapper->getSourceFieldValue('COMPANY_ID');
				if($companyID > 0)
				{
					$fields['COMPANY_ID'] = $companyID;
				}
			}
			else
			{
				$contactID = self::getDestinationEntityID(\CCrmOwnerType::ContactName, $this->resultData);
				if($contactID > 0)
				{
					$fields['CONTACT_ID'] = $contactID;
				}

				$companyID = self::getDestinationEntityID(\CCrmOwnerType::CompanyName, $this->resultData);
				if($companyID > 0)
				{
					$fields['COMPANY_ID'] = $companyID;
				}
			}
		}
		return $fields;
	}
	/**
	 * Try to move converter to next phase
	 * @return bool
	 */
	public function moveToNextPhase()
	{
		switch($this->currentPhase)
		{
			case static::PHASE_NEW_API:
			case LeadConversionPhase::INTERMEDIATE:
				$this->currentPhase = LeadConversionPhase::COMPANY_CREATION;
				return true;
				break;
			case LeadConversionPhase::COMPANY_CREATION:
				$this->currentPhase = LeadConversionPhase::CONTACT_CREATION;
				return true;
				break;
			case LeadConversionPhase::CONTACT_CREATION:
				$this->currentPhase = LeadConversionPhase::DEAL_CREATION;
				return true;
				break;
			case LeadConversionPhase::DEAL_CREATION:
				$this->currentPhase = LeadConversionPhase::FINALIZATION;
				return true;
				break;
			//case LeadConversionPhase::FINALIZATION:
			default:
				return false;
		}
	}
	/**
	 * Try to execute current conversion phase.
	 * @return bool
	 * @throws EntityConversionException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectException
	 */
	public function executePhase()
	{
		if (parent::executePhase())
		{
			return true;
		}

		if($this->currentPhase === LeadConversionPhase::COMPANY_CREATION
			|| $this->currentPhase === LeadConversionPhase::CONTACT_CREATION
			|| $this->currentPhase === LeadConversionPhase::DEAL_CREATION)
		{
			if($this->currentPhase === LeadConversionPhase::COMPANY_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Company;
			}
			elseif($this->currentPhase === LeadConversionPhase::CONTACT_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Contact;
			}
			else//if($this->currentPhase === LeadConversionPhase::DEAL_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Deal;
			}

			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			$config = $this->config->getItem($entityTypeID);
			if(!$config || !$config->isActive())
			{
				return false;
			}

			if(!LeadConversionScheme::isTargetTypeSupported($entityTypeID, array('TYPE_ID' => $this->conversionTypeID)))
			{
				return false;
			}

			$entityID = self::getDestinationEntityID($entityTypeName, $this->contextData);

			//Only one company and one contact may be created
			if($entityTypeID === \CCrmOwnerType::Company || $entityTypeID === \CCrmOwnerType::Contact)
			{
				$boundEntityID = $this->getChildEntityID($entityTypeID);
				if($boundEntityID > 0)
				{
					//Entity is already bound
					self::setDestinationEntityID(
						$entityTypeName,
						$boundEntityID,
						$this->resultData,
						array(
							'isNew' => self::isNewDestinationEntity(
								$entityTypeName,
								$boundEntityID,
								$this->contextData
							)
						)
					);
					return true;
				}
			}

			/** @var LeadConversionMapper $mapper */
			$mapper = $this->getMapper();
			/** @var EntityConversionMap $map */
			$map = self::prepareMap($entityTypeID);

			if($entityID > 0)
			{
				$isNewEntity = self::isNewDestinationEntity($entityTypeName, $entityID, $this->contextData);

				if($entityTypeID === \CCrmOwnerType::Company)
				{
					$entity = new \CCrmCompany(false);

					if(isset($this->contextData['ENABLE_MERGE'])
						&& $this->contextData['ENABLE_MERGE'] === true)
					{
						if(!$isNewEntity && !self::checkUpdatePermission($entityTypeName, $entityID))
						{
							throw new EntityConversionException(
								\CCrmOwnerType::Lead,
								$entityTypeID,
								EntityConversionException::TARG_DST,
								EntityConversionException::UPDATE_DENIED
							);
						}

						$fields = self::getEntityFields(\CCrmOwnerType::Company, $entityID);
						if(!is_array($fields))
						{
							throw new EntityConversionException(
								\CCrmOwnerType::Lead,
								\CCrmOwnerType::Company,
								EntityConversionException::TARG_DST,
								EntityConversionException::NOT_FOUND
							);
						}

						$mappedFields = $mapper->map($map, array('DISABLE_USER_FIELD_INIT' => true));
						if(!empty($mappedFields))
						{
							$merger = new CompanyMerger($this->getUserID(), true);
							$merger->mergeFields(
								$mappedFields,
								$fields,
								false,
								array('ENABLE_UPLOAD' => true, 'ENABLE_UPLOAD_CHECK' => false)
							);
							$fields['LEAD_ID'] = $this->entityID;
							if($entity->Update($entityID, $fields, true, true, $this->getUpdateOptions()))
							{
								//region BizProcess
								$errors = [];
								\CCrmBizProcHelper::AutoStartWorkflows(
									\CCrmOwnerType::Company,
									$entityID,
									\CCrmBizProcEventType::Edit,
									$errors
								);
								//endregion
							}
						}
					}
					elseif(!\CCrmCompany::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Lead,
							\CCrmOwnerType::Company,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}
				}
				elseif($entityTypeID === \CCrmOwnerType::Contact)
				{
					$entity = new \CCrmContact(false);

					if(isset($this->contextData['ENABLE_MERGE'])
						&& $this->contextData['ENABLE_MERGE'] === true)
					{
						if(!$isNewEntity && !self::checkUpdatePermission($entityTypeName, $entityID))
						{
							throw new EntityConversionException(
								\CCrmOwnerType::Lead,
								$entityTypeID,
								EntityConversionException::TARG_DST,
								EntityConversionException::UPDATE_DENIED
							);
						}

						$fields = self::getEntityFields(\CCrmOwnerType::Contact, $entityID);
						if(!is_array($fields))
						{
							throw new EntityConversionException(
								\CCrmOwnerType::Lead,
								\CCrmOwnerType::Contact,
								EntityConversionException::TARG_DST,
								EntityConversionException::NOT_FOUND
							);
						}

						$mappedFields = $mapper->map($map, array('DISABLE_USER_FIELD_INIT' => true));
						if(!empty($mappedFields))
						{
							$merger = new ContactMerger($this->getUserID(), true);
							$merger->mergeFields(
								$mappedFields,
								$fields,
								false,
								array('ENABLE_UPLOAD' => true, 'ENABLE_UPLOAD_CHECK' => false)
							);
							$fields['LEAD_ID'] = $this->entityID;
							if($entity->Update($entityID, $fields, true, true, $this->getUpdateOptions()))
							{
								//region BizProcess
								$errors = [];
								\CCrmBizProcHelper::AutoStartWorkflows(
									\CCrmOwnerType::Contact,
									$entityID,
									\CCrmBizProcEventType::Edit,
									$errors
								);
								//endregion
							}
						}
					}
					elseif(!\CCrmContact::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Lead,
							\CCrmOwnerType::Contact,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}
				}
				else//if($entityTypeID === \CCrmOwnerType::Deal)
				{
					if(!\CCrmDeal::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Lead,
							\CCrmOwnerType::Deal,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}

					$entity = new \CCrmDeal(false);
				}

				$fields = self::getEntityFields($entityTypeID, $entityID);
				if(!is_array($fields))
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						$entityTypeID,
						EntityConversionException::TARG_DST,
						EntityConversionException::NOT_FOUND
					);
				}

				if(!isset($fields['LEAD_ID']) || $fields['LEAD_ID'] <= 0)
				{
					$fields = array('LEAD_ID' => $this->entityID);
					$entity->Update($entityID, $fields, false, false, $this->getUpdateOptions());
				}

				self::setDestinationEntityID(
					$entityTypeName,
					$entityID,
					$this->resultData,
					array(
						'isNew' => self::isNewDestinationEntity(
							$entityTypeName,
							$entityID,
							$this->contextData
						)
					)
				);
				return true;
			}

			if(!self::checkCreatePermission($entityTypeName, $config))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::CREATE_DENIED
				);
			}

			if(UserFieldSynchronizer::needForSynchronization(\CCrmOwnerType::Lead, $entityTypeID))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::NOT_SYNCHRONIZED
				);
			}

			if(!ConversionSettings::getCurrent()->isAutocreationEnabled())
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::AUTOCREATION_DISABLED
				);
			}

			if($this->isBizProcCheckEnabled()
				&& \CCrmBizProcHelper::HasParameterizedAutoWorkflows($entityTypeID, \CCrmBizProcEventType::Create))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::HAS_WORKFLOWS
				);
			}

			$fields = $mapper->map($map, array('INIT_DATA' => $config->getInitData()));
			if(empty($fields))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::EMPTY_FIELDS
				);
			}

			if (isset($this->contextData['RESPONSIBLE_ID']))
			{
				$fields['ASSIGNED_BY_ID'] = $this->contextData['RESPONSIBLE_ID'];
			}

			if($entityTypeID === \CCrmOwnerType::Company)
			{
				$entity = new \CCrmCompany(false);
				$entityID = $entity->Add($fields, true, $this->getAddOptions());
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Company,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->getLastError()
					);
				}

				//region BizProcess
				$errors = [];
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Company,
					$entityID,
					\CCrmBizProcEventType::Create,
					$errors
				);
				//endregion

				self::setDestinationEntityID(
					\CCrmOwnerType::CompanyName,
					$entityID,
					$this->resultData,
					array('isNew' => true)
				);
			}
			elseif($entityTypeID === \CCrmOwnerType::Contact)
			{
				$companyID = self::getDestinationEntityID(\CCrmOwnerType::CompanyName, $this->resultData);
				if($companyID > 0)
				{
					$fields['COMPANY_ID'] = $companyID;
				}

				$entity = new \CCrmContact(false);
				if(!$entity->CheckFields($fields, false, $this->getAddOptions()))
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						$entityTypeID,
						EntityConversionException::TARG_DST,
						EntityConversionException::INVALID_FIELDS,
						$entity->getLastError()
					);
				}

				$entityID = $entity->Add($fields, true, $this->getAddOptions());
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Contact,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->getLastError()
					);
				}

				//region BizProcess
				$errors = [];
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Contact,
					$entityID,
					\CCrmBizProcEventType::Create,
					$errors
				);
				//endregion

				self::setDestinationEntityID(
					\CCrmOwnerType::ContactName,
					$entityID,
					$this->resultData,
					array('isNew' => true)
				);
			}
			else//if($entityTypeID === \CCrmOwnerType::Deal)
			{
				if ($this->isReturnCustomer)
				{
					$contactIDs = Crm\Binding\LeadContactTable::getLeadContactIDs($this->entityID);
					if(!empty($contactIDs))
					{
						$fields['CONTACT_IDS'] = $contactIDs;
					}

					$companyID = $this->mapper->getSourceFieldValue('COMPANY_ID');
					if($companyID > 0)
					{
						$fields['COMPANY_ID'] = $companyID;
					}
				}
				else
				{
					$contactID = self::getDestinationEntityID(\CCrmOwnerType::ContactName, $this->resultData);
					if($contactID > 0)
					{
						$fields['CONTACT_ID'] = $contactID;
					}

					$companyID = self::getDestinationEntityID(\CCrmOwnerType::CompanyName, $this->resultData);
					if($companyID > 0)
					{
						$fields['COMPANY_ID'] = $companyID;
					}
				}

				$entity = new \CCrmDeal(false);
				$entityID = $entity->Add($fields, true, $this->getAddOptions());
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Deal,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->getLastError()
					);
				}

				if(isset($fields['PRODUCT_ROWS'])
					&& is_array($fields['PRODUCT_ROWS'])
					&& !empty($fields['PRODUCT_ROWS']))
				{
					$saveProductRowsResult = \CCrmDeal::SaveProductRows($entityID, $fields['PRODUCT_ROWS'], false, false, false);

					if(!$saveProductRowsResult)
					{
						/** @var \CApplicationException $ex */
						$ex = $GLOBALS['APPLICATION']->GetException();

						$this->resultData['ERROR'] = [
							'MESSAGE' => $ex ? $ex->GetString() : Main\Localization\Loc::getMessage('CRM_LEAD_CONVERTER_PRODUCT_ROWS_SAVING_ERROR'),
						];
					}
				}

				// requisite link
				$requisiteEntityList = array();
				$mcRequisiteEntityList = array();
				$requisite = new EntityRequisite();
				if (isset($fields['COMPANY_ID']) && $fields['COMPANY_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $fields['COMPANY_ID']);
				if (isset($fields['CONTACT_ID']) && $fields['CONTACT_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => $fields['CONTACT_ID']);
				if (isset($fields['MYCOMPANY_ID']) && $fields['MYCOMPANY_ID'] > 0)
					$mcRequisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $fields['MYCOMPANY_ID']);
				$requisiteIdLinked = 0;
				$bankDetailIdLinked = 0;
				$mcRequisiteIdLinked = 0;
				$mcBankDetailIdLinked = 0;
				$requisiteInfoLinked = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);
				if (is_array($requisiteInfoLinked))
				{
					if (isset($requisiteInfoLinked['REQUISITE_ID']))
						$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
					if (isset($requisiteInfoLinked['BANK_DETAIL_ID']))
						$bankDetailIdLinked = (int)$requisiteInfoLinked['BANK_DETAIL_ID'];
				}
				$mcRequisiteInfoLinked = $requisite->getDefaultMyCompanyRequisiteInfoLinked($mcRequisiteEntityList);
				if (is_array($mcRequisiteInfoLinked))
				{
					if (isset($mcRequisiteInfoLinked['MC_REQUISITE_ID']))
						$mcRequisiteIdLinked = (int)$mcRequisiteInfoLinked['MC_REQUISITE_ID'];
					if (isset($mcRequisiteInfoLinked['MC_BANK_DETAIL_ID']))
						$mcBankDetailIdLinked = (int)$mcRequisiteInfoLinked['MC_BANK_DETAIL_ID'];
				}
				unset($requisite, $requisiteEntityList, $mcRequisiteEntityList, $requisiteInfoLinked, $mcRequisiteInfoLinked);
				if ($requisiteIdLinked > 0 || $mcRequisiteIdLinked > 0)
				{
					EntityLink::register(
						\CCrmOwnerType::Deal, $entityID,
						$requisiteIdLinked, $bankDetailIdLinked,
						$mcRequisiteIdLinked, $mcBankDetailIdLinked
					);
				}
				unset($requisiteIdLinked, $bankDetailIdLinked, $mcRequisiteIdLinked, $mcBankDetailIdLinked);

				//region BizProcess
				$errors = [];
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Deal,
					$entityID,
					\CCrmBizProcEventType::Create,
					$errors
				);
				//endregion

				//Region automation
				$starter = new Crm\Automation\Starter(\CCrmOwnerType::Deal, $entityID);
				$starter->runOnAdd();
				//end region

				self::setDestinationEntityID(
					\CCrmOwnerType::DealName,
					$entityID,
					$this->resultData,
					array('isNew' => true)
				);
			}
			return true;
		}
		elseif($this->currentPhase === LeadConversionPhase::FINALIZATION)
		{
			$result = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STATUS_ID')
			);

			$presentFields = is_object($result) ? $result->Fetch() : null;
			if(is_array($presentFields))
			{
				$fields = array();
				$entityUpdateOptions = [
					'REGISTER_SONET_EVENT' => true,
					// required fields were checked on a CORRECT action before, if needed.
					// now any error in update will break the scenario
					'DISABLE_USER_FIELD_CHECK' => true,
				];

				$statusID = isset($presentFields['STATUS_ID']) ? $presentFields['STATUS_ID'] : '';
				if($statusID !== 'CONVERTED')
				{
					$fields['STATUS_ID'] = 'CONVERTED';
					$entityUpdateOptions['ENABLE_ACTIVITY_COMPLETION'] = $this->isActivityCompletionEnabled();
				}

				$contactID = self::getDestinationEntityID(\CCrmOwnerType::ContactName, $this->resultData);
				if($contactID > 0)
				{
					$fields['CONTACT_ID'] = $contactID;
					// relation was registered on contact add. avoid duplication
					$entityUpdateOptions['EXCLUDE_FROM_RELATION_REGISTRATION'][] =
						new Crm\ItemIdentifier(\CCrmOwnerType::Contact, $contactID);
				}

				$companyID = self::getDestinationEntityID(\CCrmOwnerType::CompanyName, $this->resultData);
				if($companyID > 0)
				{
					$fields['COMPANY_ID'] = $companyID;
					// relation was registered on company add. avoid duplication
					$entityUpdateOptions['EXCLUDE_FROM_RELATION_REGISTRATION'][] =
						new Crm\ItemIdentifier(\CCrmOwnerType::Company, $companyID);
				}

				if(!empty($fields))
				{
					$entity = new \CCrmLead(false);
					if (isset($this->contextData['USER_ID']))
					{
						$entityUpdateOptions['CURRENT_USER'] = $this->contextData['USER_ID'];
					}
					$this->log(
						'UpdateLead',
						[
							'fields' => $fields,
							'options' => $entityUpdateOptions,
						],
						\Psr\Log\LogLevel::INFO
					);

					if($entity->Update($this->entityID, $fields, true, true, $entityUpdateOptions))
					{
						//region Requisites
						if($companyID > 0 || $contactID > 0)
						{
							$dbResult = \CCrmLead::GetListEx(
								array(),
								array('=ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
								false,
								false,
								array('ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY')
							);

							$addressFields = is_object($dbResult) ? $dbResult->Fetch() : null;
							if(is_array($addressFields))
							{
								$requisite = new EntityRequisite();
								try
								{
									//region Process Company requisite
									if($companyID > 0)
									{
										$companyPresetID = EntityRequisite::getDefaultPresetId(\CCrmOwnerType::Company);
										if($companyPresetID > 0)
										{
											$requisiteCount = $requisite->getCountByFilter(
												array(
													'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
													'ENTITY_ID' => $companyID
												)
											);

											if($requisiteCount === 0)
											{
												$converter = new AddressRequisiteConverter(
													\CCrmOwnerType::Company,
													$companyPresetID,
													false
												);
												$converter->processEntity($companyID);
											}
										}
									}
									//endregion
									//region Process Contact requisite
									if($contactID > 0)
									{
										$contactPresetID = EntityRequisite::getDefaultPresetId(\CCrmOwnerType::Contact);
										if($contactPresetID > 0)
										{
											$requisiteCount = $requisite->getCountByFilter(
												array(
													'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
													'ENTITY_ID' => $contactID
												)
											);

											if($requisiteCount === 0)
											{
												$converter = new AddressRequisiteConverter(
													\CCrmOwnerType::Contact,
													$contactPresetID,
													false
												);
												$converter->processEntity($contactID);
											}
										}
									}
									//endregion
								}
								catch(\Bitrix\Crm\Requisite\AddressRequisiteConvertException $ex)
								{
									$this->log(
										'AddressRequisiteConvertException',
										[
											'entityTypeId' => $ex->getEntityTypeID(),
											'presetId' => $ex->getPresetID(),
											'localizedMessage' => $ex->getLocalizedMessage(),
											'message' => $ex->getMessage(),
											'code' => $ex->getCode(),
										],
										\Psr\Log\LogLevel::ERROR
									);
								}
								catch(RequisiteConvertException $ex)
								{
									$this->log(
										'RequisiteConvertException',
										[
											'message' => $ex->getMessage(),
											'code' => $ex->getCode(),
										],
										\Psr\Log\LogLevel::ERROR
									);
								}
							}
						}
						//endregion

						if (!$this->shouldSkipBizProcAutoStart())
						{
							//region BizProcess
							$errors = [];
							\CCrmBizProcHelper::AutoStartWorkflows(
								\CCrmOwnerType::Lead,
								$this->entityID,
								\CCrmBizProcEventType::Edit,
								$errors
							);
							//endregion
							if (!empty($errors))
							{
								$this->log(
									'AutoStartWorkflowsError',
									[
										'errors' => $errors
									],
									\Psr\Log\LogLevel::ERROR
								);
							}
						}

						//region Automation
						$starter = new Crm\Automation\Starter(\CCrmOwnerType::Lead, $this->entityID);
						$starter->runOnUpdate($fields, $presentFields);
						//end region
					}
					else
					{
						$this->log(
							'UpdateLeadError',
							[
								'error' => $entity->getLastError(),
								'fields' => $fields,
								'options' => $entityUpdateOptions,
							],
							\Psr\Log\LogLevel::ERROR
						);
					}
				}

				//region Call finalization phase common action from parent
				$this->onFinalizationPhase();
				//endregion
			}

			return true;
		}

		return false;
	}

	/**
	 * Remove all lead's bindings from specified child entity.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	protected function unbindChildEntity($entityTypeID, $entityID)
	{
		$this->detachEntity($entityTypeID, $entityID);

		$lead = new \CCrmLead(false);
		$entityFields = array('LEAD_ID' => false);
		// relation is registered on company/contact update. avoid duplication
		$leadOptions = [
			'EXCLUDE_FROM_RELATION_REGISTRATION' => [new Crm\ItemIdentifier($entityTypeID, $entityID)],
		];
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			(new \CCrmContact(false))->Update($entityID, $entityFields);
			$leadFields = array('CONTACT_ID' => false);
			$lead->Update($this->entityID, $leadFields, true, true, $leadOptions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			(new \CCrmCompany(false))->Update($entityID, $entityFields);
			$leadFields = array('COMPANY_ID' => false);
			$lead->Update($this->entityID, $leadFields, true, true, $leadOptions);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			(new \CCrmDeal(false))->Update($entityID, $entityFields);
		}
	}
	/**
	 * Remove all lead's bindings from all child entities (bound to current Lead).
	 * Following child entity types will be processed: Contact, Company and Deal.
	 * @return void
	 */
	public function unbindChildEntities()
	{
		$entityMap = array(
			\CCrmOwnerType::Contact => $this->getChildEntityIDs(\CCrmOwnerType::Contact, 1),
			\CCrmOwnerType::Company => $this->getChildEntityIDs(\CCrmOwnerType::Company, 1),
			\CCrmOwnerType::Deal => $this->getChildEntityIDs(\CCrmOwnerType::Deal)
		);

		foreach($entityMap as $entityTypeID => $entityIDs)
		{
			foreach($entityIDs as $entityID)
			{
				$this->unbindChildEntity($entityTypeID, $entityID);
			}
		}
	}
	/**
	 * Get entity fields.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityID Entity ID.
	 * @return array|null
	 */
	protected static function getEntityFields($entityTypeID, $entityID)
	{
		$dbResult = null;
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			$dbResult = \CCrmContact::GetListEx(
				array(),
				array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('*', 'UF_*')
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$dbResult = \CCrmCompany::GetListEx(
				array(),
				array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('*', 'UF_*')
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('*', 'UF_*')
			);
		}

		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		return is_array($fields) ? $fields : null;
	}
	/**
	 * Get child entity IDs created by Lead (associated with this converter).
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $limit Maximum count entity IDs in returning result.
	 * @return array
	 */
	protected function getChildEntityIDs($entityTypeID, $limit = 0)
	{
		$navigationParams = false;
		if($limit > 0)
		{
			$navigationParams = array('nTopCount' => 1);
		}

		$dbResult = null;
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			$dbResult = \CCrmContact::GetListEx(
				array(),
				array('=LEAD_ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				$navigationParams,
				array('ID')
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$dbResult = \CCrmCompany::GetListEx(
				array(),
				array('=LEAD_ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				$navigationParams,
				array('ID')
			);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=LEAD_ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				$navigationParams,
				array('ID')
			);
		}

		if(!$dbResult)
		{
			return array();
		}

		$results = array();
		while($fields = $dbResult->Fetch())
		{
			$results[] = (int)$fields['ID'];
		}
		return $results;
	}
	/**
	 * Get child entity ID created by Lead (associated with this converter).
	 * @param int $entityTypeID Entity Type ID.
	 * @return int
	 */
	protected function getChildEntityID($entityTypeID)
	{
		$result = $this->getChildEntityIDs($entityTypeID, 1);
		return !empty($result) ? $result[0] : 0;
	}
	/**
	 * Preparation of conversion map for specified entity type.
	 * Try to load saved map. If map is not found then default map will be created.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionMap
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	protected static function prepareMap($entityTypeID)
	{
		if(isset(self::$maps[$entityTypeID]))
		{
			return self::$maps[$entityTypeID];
		}

		$map = EntityConversionMap::load(\CCrmOwnerType::Lead, $entityTypeID);
		if($map === null)
		{
			$map = LeadConversionMapper::createMap($entityTypeID);
			$map->save();
		}
		elseif($map->isOutOfDate())
		{
			LeadConversionMapper::updateMap($map);
			$map->save();
		}

		return (self::$maps[$entityTypeID] = $map);
	}
	/**
	 * Get Supported Destination Types
	 * @return array
	 */
	public function getSupportedDestinationTypeIDs(): array
	{
		return array(\CCrmOwnerType::Contact, \CCrmOwnerType::Company, \CCrmOwnerType::Deal);
	}
	/**
	 * Delete specified entity.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityID Entity ID.
	 */
	protected function removeEntity($entityTypeID, $entityID)
	{
		$entity = null;
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			$entity = new \CCrmContact(false);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			$entity = new \CCrmCompany(false);
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			$entity = new \CCrmDeal(false);
		}

		if($entity)
		{
			$entity->Delete($entityID);
		}
	}

	protected function doExternalize(array &$params)
	{
		$params['isReturnCustomer'] = $this->isReturnCustomer ? 'Y' : 'N';
	}

	protected function doInternalize(array $params)
	{
		$this->isReturnCustomer = isset($params['isReturnCustomer']) && $params['isReturnCustomer'] === 'Y';
	}

	protected function getUpdateOptions(): array
	{
		$options = parent::getUpdateOptions();

		if(isset($this->contextData['MODE']))
		{
			$options['MODE'] = $this->contextData['MODE'];
		}
		if(isset($this->contextData['USER_ID']))
		{
			$options['USER_ID'] = $this->contextData['USER_ID'];
		}

		//Disable required field check to ensure updated data will be saved.
		$options['DISABLE_USER_FIELD_CHECK'] = true;

		return $options;
	}

	protected function getLogContext(): array
	{
		return array_merge(
			parent::getLogContext(),
			[
				'conversionTypeId' => $this->conversionTypeID,
				'isReturnCustomer' => $this->isReturnCustomer,
			]
		);
	}
}
