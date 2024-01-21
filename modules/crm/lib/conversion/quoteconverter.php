<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Settings\ConversionSettings;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main;

class QuoteConverter extends EntityConverter
{
	/** @var array */
	private static $maps = array();
	/** @var QuoteConversionMapper|null  */
	private $mapper = null;

	public function __construct(EntityConversionConfig $config = null)
	{
		if($config === null)
		{
			$config = new QuoteConversionConfig();
		}
		parent::__construct($config);

		$this->setSourceFactory(Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Quote));
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

		$this->determineStartingPhase();

		if(!\CCrmQuote::Exists($this->entityID))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Quote,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::NOT_FOUND
			);
		}

		/** @var \CCrmPerms $permissions */
		$permissions = $this->getUserPermissions();
		if(!\CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::QuoteName, $this->entityID, $permissions))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Quote,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::READ_DENIED
			);
		}

		if(!\CCrmAuthorizationHelper::CheckUpdatePermission(\CCrmOwnerType::QuoteName, $this->entityID, $permissions))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Quote,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::UPDATE_DENIED
			);
		}
	}
	/**
	 * Get converter entity type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Quote;
	}
	/**
	 * Check if current phase is final.
	 * @return bool
	 */
	public function isFinished()
	{
		if ($this->currentPhase === static::PHASE_NEW_API)
		{
			return parent::isFinished();
		}

		return QuoteConversionPhase::isFinal($this->currentPhase);
	}
	/**
	 * Get conversion mapper
	 * @return QuoteConversionMapper|null
	 */
	public function getMapper()
	{
		if($this->mapper === null)
		{
			$this->mapper = new QuoteConversionMapper($this->entityID);
		}

		return $this->mapper;
	}
	/**
	 * Get conversion map for for specified entity type.
	 * Try to load saved map. If map is not found then default map will be created.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionMap
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
	 */
	public function mapEntityFields($entityTypeID, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}
		$options['INIT_DATA'] = $this->config->getEntityInitData($entityTypeID);

		return $this->getMapper()->map($this->getMap($entityTypeID), $options);
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
			case QuoteConversionPhase::INTERMEDIATE:
				$this->currentPhase = QuoteConversionPhase::DEAL_CREATION;
				return true;
				break;
			case QuoteConversionPhase::DEAL_CREATION:
				$this->currentPhase = QuoteConversionPhase::INVOICE_CREATION;
				return true;
				break;
			case QuoteConversionPhase::INVOICE_CREATION:
				$this->currentPhase = QuoteConversionPhase::FINALIZATION;
				return true;
				break;
			//case QuoteConversionPhase::FINALIZATION:
			default:
				return false;
		}
	}
	/**
	 * Try to execute current conversion phase.
	 * @return bool
	 * @throws EntityConversionException If mapper return empty fields.
	 * @throws EntityConversionException If target entity is not found.
	 * @throws EntityConversionException If target entity fields are invalid.
	 * @throws EntityConversionException If target entity has bizproc workflows.
	 * @throws EntityConversionException If target entity creation is failed.
	 * @throws EntityConversionException If target entity update permission is denied.
	 */
	public function executePhase()
	{
		if (parent::executePhase())
		{
			return true;
		}

		if($this->currentPhase === QuoteConversionPhase::DEAL_CREATION
			|| $this->currentPhase === QuoteConversionPhase::INVOICE_CREATION)
		{
			Main\Localization\Loc::loadMessages(__FILE__);

			if($this->currentPhase === QuoteConversionPhase::DEAL_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Deal;
			}
			else//if($this->currentPhase === QuoteConversionPhase::INVOICE_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Invoice;
			}

			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			$config = $this->config->getItem($entityTypeID);

			if(!$config || !$config->isActive())
			{
				return false;
			}

			/** @var \CCrmPerms $permissions */
			$permissions = $this->getUserPermissions();
			$entityID = isset($this->contextData[$entityTypeName]) ? $this->contextData[$entityTypeName] : 0;

			if($entityID > 0)
			{
				if($entityTypeID === \CCrmOwnerType::Deal)
				{
					if(!\CCrmDeal::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Quote,
							\CCrmOwnerType::Deal,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}

					$entity = new \CCrmDeal(false);
					$fields = array('QUOTE_ID' => $this->entityID);
					$entity->Update(
						$entityID,
						$fields,
						false,
						false,
						$this->getUpdateOptions()
					);
					$this->resultData[$entityTypeName] = $entityID;
				}
				else//if($entityTypeID === \CCrmOwnerType::Invoice)
				{
					if(!\CCrmInvoice::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Quote,
							\CCrmOwnerType::Invoice,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}

					$entity = new \CCrmInvoice(false);
					$fields = array('UF_QUOTE_ID' => $this->entityID);
					try
					{
						$entity->Update($entityID, $fields, $this->getUpdateOptions());
					}
					catch(Main\DB\SqlQueryException $e)
					{
					}
					$this->resultData[$entityTypeName] = $entityID;
				}
				return true;
			}

			if(!\CCrmAuthorizationHelper::CheckCreatePermission($entityTypeName , $permissions))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Quote,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::CREATE_DENIED
				);
			}

			if(UserFieldSynchronizer::needForSynchronization(\CCrmOwnerType::Quote, $entityTypeID))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Quote,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::NOT_SYNCHRONIZED
				);
			}

			if(!ConversionSettings::getCurrent()->isAutocreationEnabled())
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Quote,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::AUTOCREATION_DISABLED
				);
			}

			if($entityTypeID === \CCrmOwnerType::Deal
				&& $this->isBizProcCheckEnabled()
				&& \CCrmBizProcHelper::HasParameterizedAutoWorkflows($entityTypeID, \CCrmBizProcEventType::Create))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Quote,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::HAS_WORKFLOWS
				);
			}

			/** @var QuoteConversionMapper $mapper */
			$mapper = $this->getMapper();

			//We can't create deal from quote that created from deal
			if($entityTypeID === \CCrmOwnerType::Deal
				&& $mapper->getSourceFieldValue('DEAL_ID', 0) > 0)
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Quote,
					$entityTypeID,
					EntityConversionException::TARG_SRC,
					EntityConversionException::INVALID_OPERATION,
					GetMessage('CRM_QUOTE_CONVERTER_DEAL_PROHIBITED_MSGVER_1')
				);
			}

			$map = self::prepareMap($entityTypeID);
			$fields = $mapper->map($map, array('INIT_DATA' => $config->getInitData()));
			if(empty($fields))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Quote,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::EMPTY_FIELDS
				);
			}

			if($entityTypeID === \CCrmOwnerType::Deal)
			{
				$entity = new \CCrmDeal(false);
				if(!$entity->CheckFields($fields, false, $this->getAddOptions()))
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Quote,
						\CCrmOwnerType::Deal,
						EntityConversionException::TARG_DST,
						EntityConversionException::INVALID_FIELDS,
						$entity->LAST_ERROR
					);
				}

				$entityID = $entity->Add($fields, true, $this->getAddOptions());
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Quote,
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
				if (isset($fields['QUOTE_ID']) && $fields['QUOTE_ID'] > 0)
				{
					$mcRequisiteEntityList[] = $requisiteEntityList[] =
						array('ENTITY_TYPE_ID' => \CCrmOwnerType::Quote, 'ENTITY_ID' => $fields['QUOTE_ID']);
				}
				if (isset($fields['COMPANY_ID']) && $fields['COMPANY_ID'] > 0)
				{
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $fields['COMPANY_ID']);
				}
				if (isset($fields['CONTACT_BINDINGS']) && is_array($fields['CONTACT_BINDINGS']))
				{
					$primaryContactID = EntityBinding::getPrimaryEntityID(
						\CCrmOwnerType::Contact,
						$fields['CONTACT_BINDINGS']
					);

					if($primaryContactID > 0)
					{
						$requisiteEntityList[] = array(
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
							'ENTITY_ID' => $primaryContactID
						);
					}
				}
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

				$starter = new Crm\Automation\Starter(\CCrmOwnerType::Deal, $entityID);
				$starter->runOnAdd();
				//endregion

				$this->resultData[\CCrmOwnerType::DealName] = $entityID;
			}
			else//if($entityTypeID === \CCrmOwnerType::Invoice)
			{
				// requisite link 1 of 2
				$requisiteEntityList = array();
				$mcRequisiteEntityList = array();
				$requisite = new EntityRequisite();
				if (isset($fields['UF_QUOTE_ID']) && $fields['UF_QUOTE_ID'] > 0)
				{
					$mcRequisiteEntityList[] = $requisiteEntityList[] =
						array('ENTITY_TYPE_ID' => \CCrmOwnerType::Quote, 'ENTITY_ID' => $fields['UF_QUOTE_ID']);
				}
				if (isset($fields['UF_DEAL_ID']) && $fields['UF_DEAL_ID'] > 0)
				{
					$mcRequisiteEntityList[] = $requisiteEntityList[] =
						array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => $fields['UF_DEAL_ID']);
				}
				if (isset($fields['UF_COMPANY_ID']) && $fields['UF_COMPANY_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $fields['UF_COMPANY_ID']);
				if (isset($fields['UF_CONTACT_ID']) && $fields['UF_CONTACT_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => $fields['UF_CONTACT_ID']);
				if (isset($fields['UF_MYCOMPANY_ID']) && $fields['UF_MYCOMPANY_ID'] > 0)
					$mcRequisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $fields['UF_MYCOMPANY_ID']);
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
				if (!isset($fields['UF_MYCOMPANY_ID']) || $fields['UF_MYCOMPANY_ID'] <= 0)
				{
					$defLink = EntityLink::getDefaultMyCompanyRequisiteLink();
					if (is_array($defLink))
					{
						$fields['UF_MYCOMPANY_ID'] = isset($defLink['MYCOMPANY_ID']) ? (int)$defLink['MYCOMPANY_ID'] : 0;
						$mcRequisiteIdLinked = isset($defLink['MC_REQUISITE_ID']) ? (int)$defLink['MC_REQUISITE_ID'] : 0;
						$mcBankDetailIdLinked = isset($defLink['MC_BANK_DETAIL_ID']) ? (int)$defLink['MC_BANK_DETAIL_ID'] : 0;
					}
					unset($defLink);
				}

				$entity = new \CCrmInvoice(false);
				$isSuccessful = \CCrmStatusInvoice::isStatusSuccess($fields['STATUS_ID']);
				$isFailed = !$isSuccessful && \CCrmStatusInvoice::isStatusFailed($fields['STATUS_ID']);
				if(!$entity->CheckFields($fields, false, $isSuccessful, $isFailed, $this->getAddOptions()))
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Quote,
						\CCrmOwnerType::Invoice,
						EntityConversionException::TARG_DST,
						EntityConversionException::INVALID_FIELDS,
						$entity->LAST_ERROR
					);
				}

				$recalculated = false;
				$entityID = $entity->Add($fields, $recalculated, SITE_ID, $this->getAddOptions());
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Quote,
						\CCrmOwnerType::Invoice,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->LAST_ERROR
					);
				}

				// requisite link 2 of 2
				if ($requisiteIdLinked > 0 || $mcRequisiteIdLinked > 0)
				{
					EntityLink::register(
						\CCrmOwnerType::Invoice, $entityID,
						$requisiteIdLinked, $bankDetailIdLinked,
						$mcRequisiteIdLinked, $mcBankDetailIdLinked
					);
				}
				unset($requisiteIdLinked, $bankDetailIdLinked, $mcRequisiteIdLinked, $mcBankDetailIdLinked);

				$this->resultData[\CCrmOwnerType::InvoiceName] = $entityID;
			}

			return true;
		}
		elseif($this->currentPhase === QuoteConversionPhase::FINALIZATION)
		{
			$this->onFinalizationPhase();

			//Do not update DEAL_ID field here. This field is used, then quote is created from deal.
			return true;
		}

		return false;
	}
	/**
	 * Preparation of conversion map for specified entity type.
	 * Try to load saved map. If map is not found then default map will be created.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionMap
	*/
	protected static function prepareMap($entityTypeID)
	{
		if(isset(self::$maps[$entityTypeID]))
		{
			return self::$maps[$entityTypeID];
		}

		$map = EntityConversionMap::load(\CCrmOwnerType::Quote, $entityTypeID);
		if($map === null)
		{
			$map = QuoteConversionMapper::createMap($entityTypeID);
			$map->save();
		}
		elseif($map->isOutOfDate())
		{
			QuoteConversionMapper::updateMap($map);
			$map->save();
		}

		return (self::$maps[$entityTypeID] = $map);
	}

	/**
	 * Get Supported Destination Types.
	 * @return array
	 */
	public function getSupportedDestinationTypeIDs(): array
	{
		return array(\CCrmOwnerType::Deal, \CCrmOwnerType::Invoice);
	}

	/**
	 * Returns true if activities from Quote entity should be copied to destination entity
	 *
	 * @return bool
	 */
	public function isAttachingSourceActivitiesEnabled(): bool
	{
		return false;
	}
}
