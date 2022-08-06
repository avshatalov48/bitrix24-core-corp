<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\ConversionSettings;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main;

class DealConverter extends EntityConverter
{
	/** @var array */
	private static $maps = array();
	/** @var DealConversionMapper|null  */
	private $mapper = null;

	public function __construct(EntityConversionConfig $config = null)
	{
		if($config === null)
		{
			$config = new DealConversionConfig();
		}
		parent::__construct($config);

		$this->setSourceFactory(Container::getInstance()->getFactory(\CCrmOwnerType::Deal));
	}
	/**
	 * Initialize converter.
	 * @return void
	 * @throws EntityConversionException If entity is not exist.
	 * @throws EntityConversionException If read or update permissions are denied.
	 */
	public function initialize()
	{
		$this->determineStartingPhase();

		if(!\CCrmDeal::Exists($this->entityID))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Deal,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::NOT_FOUND
			);
		}

		if(!self::checkReadPermission(\CCrmOwnerType::DealName, $this->entityID))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Deal,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::READ_DENIED
			);
		}

		if(!self::checkUpdatePermission(\CCrmOwnerType::DealName, $this->entityID))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Deal,
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
		return \CCrmOwnerType::Deal;
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

		return DealConversionPhase::isFinal($this->currentPhase);
	}
	/**
	 * Get conversion mapper
	 * @return DealConversionMapper|null
	 */
	public function getMapper()
	{
		if($this->mapper === null)
		{
			$this->mapper = new DealConversionMapper($this->entityID);
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
			case DealConversionPhase::INTERMEDIATE:
				$this->currentPhase = DealConversionPhase::INVOICE_CREATION;
				return true;
				break;
			case DealConversionPhase::INVOICE_CREATION:
				$this->currentPhase = DealConversionPhase::QUOTE_CREATION;
				return true;
				break;
			case DealConversionPhase::QUOTE_CREATION:
				$this->currentPhase = DealConversionPhase::FINALIZATION;
				return true;
				break;
			//case DealConversionPhase::FINALIZATION:
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

		if($this->currentPhase === DealConversionPhase::INVOICE_CREATION
			|| $this->currentPhase === DealConversionPhase::QUOTE_CREATION)
		{
			Main\Localization\Loc::loadMessages(__FILE__);

			if($this->currentPhase === DealConversionPhase::INVOICE_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Invoice;
			}
			else//if($this->currentPhase === DealConversionPhase::QUOTE_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Quote;
			}

			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			$config = $this->config->getItem($entityTypeID);


			if(!$config || !$config->isActive())
			{
				return false;
			}

			$entityID = isset($this->contextData[$entityTypeName]) ? $this->contextData[$entityTypeName] : 0;

			if($entityID > 0)
			{
				if($entityTypeID === \CCrmOwnerType::Invoice)
				{
					if(!\CCrmInvoice::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Deal,
							\CCrmOwnerType::Invoice,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}

					$entity = new \CCrmInvoice(false);
					$fields = array('UF_DEAL_ID' => $this->entityID);
					try
					{
						$entity->Update($entityID, $fields, $this->getUpdateOptions());
					}
					catch(Main\DB\SqlQueryException $e)
					{
					}
					$this->resultData[$entityTypeName] = $entityID;
				}
				else//if($entityTypeID === \CCrmOwnerType::Quote)
				{
					if(!\CCrmQuote::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Deal,
							\CCrmOwnerType::Quote,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}

					$entity = new \CCrmQuote(false);
					$fields = array('DEAL_ID' => $this->entityID);
					$entity->Update($entityID, $fields, false, false, $this->getUpdateOptions());
					$this->resultData[$entityTypeName] = $entityID;
				}

				return true;
			}

			if(!self::checkCreatePermission($entityTypeName , $config))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Deal,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::CREATE_DENIED
				);
			}

			if(UserFieldSynchronizer::needForSynchronization(\CCrmOwnerType::Deal, $entityTypeID))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Deal,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::NOT_SYNCHRONIZED
				);
			}

			if(!ConversionSettings::getCurrent()->isAutocreationEnabled())
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Deal,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::AUTOCREATION_DISABLED
				);
			}

			/** @var DealConversionMapper $mapper */
			$mapper = $this->getMapper();

			//We can't create quote from deal that created from quote
			if($entityTypeID === \CCrmOwnerType::Quote
				&& $mapper->getSourceFieldValue('QUOTE_ID', 0) > 0)
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Deal,
					$entityTypeID,
					EntityConversionException::TARG_SRC,
					EntityConversionException::INVALID_OPERATION,
					GetMessage('CRM_DEAL_CONVERTER_QUOTE_PROHIBITED')
				);
			}

			$map = self::prepareMap($entityTypeID);
			$fields = $mapper->map($map);
			if(empty($fields))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Deal,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::EMPTY_FIELDS
				);
			}

			if (isset($this->contextData['RESPONSIBLE_ID']))
			{
				$assignedKey = $entityTypeID === \CCrmOwnerType::Invoice ? 'RESPONSIBLE_ID' : 'ASSIGNED_BY_ID';
				$fields[$assignedKey] = $this->contextData['RESPONSIBLE_ID'];
			}

			if($entityTypeID === \CCrmOwnerType::Invoice)
			{
				// requisite link 1 of 2
				$requisiteEntityList = array();
				$mcRequisiteEntityList = array();
				$requisite = new EntityRequisite();
				if (isset($fields['UF_DEAL_ID']) && $fields['UF_DEAL_ID'] > 0)
				{
					$mcRequisiteEntityList[] = $requisiteEntityList[] =
						array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => $fields['UF_DEAL_ID']);
				}
				if (isset($fields['UF_QUOTE_ID']) && $fields['UF_QUOTE_ID'] > 0)
				{
					$mcRequisiteEntityList[] = $requisiteEntityList[] =
						array('ENTITY_TYPE_ID' => \CCrmOwnerType::Quote, 'ENTITY_ID' => $fields['UF_QUOTE_ID']);
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
						\CCrmOwnerType::Deal,
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
						\CCrmOwnerType::Deal,
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
			else//if($entityTypeID === \CCrmOwnerType::Quote)
			{
				// requisite link 1 of 2
				$requisiteEntityList = array();
				$mcRequisiteEntityList = array();
				$requisite = new EntityRequisite();
				if (isset($fields['DEAL_ID']) && $fields['DEAL_ID'] > 0)
				{
					$mcRequisiteEntityList[] = $requisiteEntityList[] =
						array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => $fields['DEAL_ID']);
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
				if (!isset($fields['MYCOMPANY_ID']) || $fields['MYCOMPANY_ID'] <= 0)
				{
					$defLink = EntityLink::getDefaultMyCompanyRequisiteLink();
					if (is_array($defLink))
					{
						$fields['MYCOMPANY_ID'] = isset($defLink['MYCOMPANY_ID']) ? (int)$defLink['MYCOMPANY_ID'] : 0;
						$mcRequisiteIdLinked = isset($defLink['MC_REQUISITE_ID']) ? (int)$defLink['MC_REQUISITE_ID'] : 0;
						$mcBankDetailIdLinked = isset($defLink['MC_BANK_DETAIL_ID']) ? (int)$defLink['MC_BANK_DETAIL_ID'] : 0;
					}
					unset($defLink);
				}

				$entity = new \CCrmQuote(false);
				if(!$entity->CheckFields($fields, false, $this->getAddOptions()))
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Deal,
						\CCrmOwnerType::Quote,
						EntityConversionException::TARG_DST,
						EntityConversionException::INVALID_FIELDS,
						$entity->LAST_ERROR
					);
				}

				$productRows = isset($fields['PRODUCT_ROWS']) && is_array($fields['PRODUCT_ROWS'])
					? $fields['PRODUCT_ROWS'] : array();
				if(!empty($productRows))
				{
					$currencyID = isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : '';
					$personTypeID = isset($fields['PERSON_TYPE_ID']) ? (int)$fields['PERSON_TYPE_ID'] : 0;

					if($currencyID !== '' && $personTypeID > 0)
					{
						$calculationOptions = array();
						if (\CCrmTax::isTaxMode() && isset($fields['LOCATION_ID']))
						{
							$calculationOptions['LOCATION_ID'] = $fields['LOCATION_ID'];
						}

						$result = \CCrmSaleHelper::Calculate(
							$productRows,
							$currencyID,
							$personTypeID,
							false,
							SITE_ID,
							$calculationOptions
						);
						$fields['OPPORTUNITY'] = isset($result['PRICE']) ? round(doubleval($result['PRICE']), 2) : 1.0;
						$fields['TAX_VALUE'] = isset($result['TAX_VALUE']) ? round(doubleval($result['TAX_VALUE']), 2) : 0.0;
					}
				}

				$entityID = $entity->Add($fields, true, $this->getAddOptions());
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Deal,
						\CCrmOwnerType::Quote,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->LAST_ERROR
					);
				}

				if(isset($fields['PRODUCT_ROWS'])
					&& is_array($fields['PRODUCT_ROWS'])
					&& !empty($fields['PRODUCT_ROWS']))
				{
					\CCrmQuote::SaveProductRows($entityID, $fields['PRODUCT_ROWS'], false, false, false);
				}

				// requisite link 2 of 2
				if ($requisiteIdLinked > 0 || $mcRequisiteIdLinked > 0)
				{
					EntityLink::register(
						\CCrmOwnerType::Quote, $entityID,
						$requisiteIdLinked, $bankDetailIdLinked,
						$mcRequisiteIdLinked, $mcBankDetailIdLinked
					);
				}
				unset($requisiteIdLinked, $bankDetailIdLinked, $mcRequisiteIdLinked, $mcBankDetailIdLinked);

				if(isset($fields['PRODUCT_ROWS'])
					&& is_array($fields['PRODUCT_ROWS'])
					&& !empty($fields['PRODUCT_ROWS']))
				{
					\CCrmQuote::SaveProductRows($entityID, $fields['PRODUCT_ROWS'], false, false, false);
				}

				$this->resultData[\CCrmOwnerType::QuoteName] = $entityID;
			}

			return true;
		}
		elseif($this->currentPhase === DealConversionPhase::FINALIZATION)
		{
			$this->onFinalizationPhase();
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

		$map = EntityConversionMap::load(\CCrmOwnerType::Deal, $entityTypeID);
		if($map === null)
		{
			$map = DealConversionMapper::createMap($entityTypeID);
			$map->save();
		}
		elseif($map->isOutOfDate())
		{
			DealConversionMapper::updateMap($map);
			$map->save();
		}

		return (self::$maps[$entityTypeID] = $map);
	}

	/**
	 * Get Supported Destination Types.
	 * @return array
	 */
	public function getSupportedDestinationTypeIDs()
	{
		return array(\CCrmOwnerType::Quote, \CCrmOwnerType::Invoice);
	}
}
