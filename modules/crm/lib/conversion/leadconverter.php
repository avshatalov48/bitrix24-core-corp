<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\Integration\Channel\DealChannelBinding;
use Bitrix\Crm\Merger\CompanyMerger;
use Bitrix\Crm\Merger\ContactMerger;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Requisite\AddressRequisiteConverter;
use Bitrix\Crm\Requisite\RequisiteConvertException;
use Bitrix\Crm\Settings\ConversionSettings;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;

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

	public function __construct(LeadConversionConfig $config = null)
	{
		if($config === null)
		{
			$config = new LeadConversionConfig();
		}
		parent::__construct($config);
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

	/**
	 * Initialize converter.
	 * @return void
	 * @throws EntityConversionException If entity is not exist.
	 * @throws EntityConversionException If read or update permissions are denied.
	 */
	public function initialize()
	{
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

		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'STATUS_ID', 'IS_RETURN_CUSTOMER')
		);

		$fields = $dbResult->Fetch();
		if(!$fields)
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Lead,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::NOT_FOUND
			);
		}
		$this->conversionTypeID = LeadConversionType::resolveByEntityFields($fields);
		$this->isReturnCustomer = isset($fields['IS_RETURN_CUSTOMER']) &&$fields['IS_RETURN_CUSTOMER'] == 'Y';

		if($this->currentPhase === LeadConversionPhase::INTERMEDIATE)
		{
			$this->currentPhase = LeadConversionPhase::COMPANY_CREATION;
		}
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
			if(!$config->isActive())
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
				$entityUpdateOptions = array();
				if(isset($this->contextData['MODE']))
				{
					$entityUpdateOptions['MODE'] = $this->contextData['MODE'];
				}
				if(isset($this->contextData['USER_ID']))
				{
					$entityUpdateOptions['USER_ID'] = $this->contextData['USER_ID'];
				}

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
							if($entity->Update($entityID, $fields, true, true, $entityUpdateOptions))
							{
								//region BizProcess
								$arErrors = array();
								\CCrmBizProcHelper::AutoStartWorkflows(
									\CCrmOwnerType::Company,
									$entityID,
									\CCrmBizProcEventType::Edit,
									$arErrors
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
							if($entity->Update($entityID, $fields, true, true, $entityUpdateOptions))
							{
								//region BizProcess
								$arErrors = array();
								\CCrmBizProcHelper::AutoStartWorkflows(
									\CCrmOwnerType::Contact,
									$entityID,
									\CCrmBizProcEventType::Edit,
									$arErrors
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
					$entity->Update($entityID, $fields, false, false, $entityUpdateOptions);
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

			//region Entity Creation Options
			$entityCreationOptions = array();
			if(isset($this->contextData['USER_ID']))
			{
				$entityCreationOptions['USER_ID'] = $this->contextData['USER_ID'];
			}

			if(!$this->isUserFieldCheckEnabled())
			{
				$entityCreationOptions['DISABLE_USER_FIELD_CHECK'] = true;
			}
			//endregion

			if($entityTypeID === \CCrmOwnerType::Company)
			{
				$entity = new \CCrmCompany(false);
				$entityID = $entity->Add($fields, true, $entityCreationOptions);
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Company,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->LAST_ERROR
					);
				}

				//region BizProcess
				$arErrors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Company,
					$entityID,
					\CCrmBizProcEventType::Create,
					$arErrors
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
				if(!$entity->CheckFields($fields, false, $entityCreationOptions))
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						$entityTypeID,
						EntityConversionException::TARG_DST,
						EntityConversionException::INVALID_FIELDS,
						$entity->LAST_ERROR
					);
				}

				$entityID = $entity->Add($fields, true, $entityCreationOptions);
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Contact,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->LAST_ERROR
					);
				}

				//region BizProcess
				$arErrors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Contact,
					$entityID,
					\CCrmBizProcEventType::Create,
					$arErrors
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
				$entityID = $entity->Add($fields, true, $entityCreationOptions);
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Deal,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->LAST_ERROR
					);
				}

				if(isset($fields['PRODUCT_ROWS'])
					&& is_array($fields['PRODUCT_ROWS'])
					&& !empty($fields['PRODUCT_ROWS']))
				{
					\CCrmDeal::SaveProductRows($entityID, $fields['PRODUCT_ROWS'], false, false, false);
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
				$arErrors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Deal,
					$entityID,
					\CCrmBizProcEventType::Create,
					$arErrors
				);
				//endregion

				//Region automation
				Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $entityID);
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
				$entityUpdateOptions = array('REGISTER_SONET_EVENT' => true);

				$statusID = isset($presentFields['STATUS_ID']) ? $presentFields['STATUS_ID'] : '';
				if($statusID !== 'CONVERTED')
				{
					$fields['STATUS_ID'] = 'CONVERTED';
					$entityUpdateOptions['ENABLE_ACTIVITY_COMPLETION'] = $this->isActivityCompletionEnabled();
				}

				if(!$this->isUserFieldCheckEnabled())
				{
					$entityUpdateOptions['DISABLE_USER_FIELD_CHECK'] = true;
				}

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

				if(!empty($fields))
				{
					$entity = new \CCrmLead(false);
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

							$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
							if(is_array($fields))
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
								catch(RequisiteConvertException $ex)
								{
								}
							}
						}
						//endregion

						//region BizProcess
						$arErrors = array();
						\CCrmBizProcHelper::AutoStartWorkflows(
							\CCrmOwnerType::Lead,
							$this->entityID,
							\CCrmBizProcEventType::Edit,
							$arErrors
						);
						//endregion

						//region Automation
						if ($statusID !== 'CONVERTED')
							Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $this->entityID);
						//end region
					}
				}

				//region Timeline
				Crm\Timeline\LeadController::getInstance()->onConvert(
					$this->entityID,
					array('ENTITIES' => $this->resultData)
				);
				//endregion

				//region Timeline & Channel Bindings
				$entityCreationTime = new Main\Type\DateTime();
				$entityCreationTime->add('T1S');

				foreach($this->getSupportedDestinationTypeIDs() as $entityTypeID)
				{
					$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);

					$entityID = self::getDestinationEntityID($entityTypeName, $this->resultData);
					if($entityID <= 0)
					{
						continue;
					}

					$this->attachEntity($entityTypeID, $entityID);
					if(self::isNewDestinationEntity($entityTypeName, $entityID, $this->resultData))
					{
						//HACK: We are trying to shift events of created entities
						Crm\Timeline\CreationEntry::shiftEntity($entityTypeID, $entityID, $entityCreationTime);
					}
				}
				//endregion

				//region Call finalization phase common action from parent
				$this->onFinalizationPhase();
				//endregion
			}

			return true;
		}

		return false;
	}
	/**
	 * Attach lead's activities, timeline objects and channel trackers to specified entity.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityID Entity ID.
	 * return void
	 * @throws Main\Db\SqlQueryException
	 */
	protected function attachEntity($entityTypeID, $entityID)
	{
		Crm\Timeline\Entity\TimelineBindingTable::attach(
			\CCrmOwnerType::Lead,
			$this->entityID,
			$entityTypeID,
			$entityID,
			array(
				Crm\Timeline\TimelineType::ACTIVITY,
				Crm\Timeline\TimelineType::CREATION,
				Crm\Timeline\TimelineType::MARK,
				Crm\Timeline\TimelineType::COMMENT
			)
		);

		\CCrmActivity::AttachBinding(\CCrmOwnerType::Lead, $this->entityID, $entityTypeID, $entityID);

		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			DealChannelBinding::attach(\CCrmOwnerType::Lead, $this->entityID, $entityID);
		}
	}
	/**
	 * Detach lead's activities, timeline objects and channel trackers from specified entity.
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $entityID Entity ID.
	 * return void
	 */
	protected function detachEntity($entityTypeID, $entityID)
	{
		Crm\Timeline\Entity\TimelineBindingTable::detach(
			\CCrmOwnerType::Lead,
			$this->entityID,
			$entityTypeID,
			$entityID,
			array(
				Crm\Timeline\TimelineType::ACTIVITY,
				Crm\Timeline\TimelineType::CREATION,
				Crm\Timeline\TimelineType::MARK,
				Crm\Timeline\TimelineType::COMMENT
			)
		);

		\CCrmActivity::DetachBinding(\CCrmOwnerType::Lead, $this->entityID, $entityTypeID, $entityID);

		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			DealChannelBinding::detach(\CCrmOwnerType::Lead, $this->entityID, $entityID);
		}
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
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			(new \CCrmContact(false))->Update($entityID, $entityFields);
			$leadFields = array('CONTACT_ID' => false);
			$lead->Update($this->entityID, $leadFields);
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			(new \CCrmCompany(false))->Update($entityID, $entityFields);
			$leadFields = array('COMPANY_ID' => false);
			$lead->Update($this->entityID, $leadFields);
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
	public function getSupportedDestinationTypeIDs()
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
}