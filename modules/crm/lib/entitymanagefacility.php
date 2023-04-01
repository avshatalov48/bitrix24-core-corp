<?php

namespace Bitrix\Crm;

use Bitrix\Main\ArgumentException;
use Bitrix\Crm\Activity\BindingSelector;
use Bitrix\Crm\Integrity\ActualEntitySelector;
use Bitrix\Crm\Merger;
use Bitrix\Crm\Automation;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\Entity\Identificator;
use Bitrix\Crm\Tracking;

/**
 * Class EntityManageFacility
 * @package Bitrix\Crm
 */
class EntityManageFacility
{
	const UPDATE_MODE_NONE = 0;
	const UPDATE_MODE_MERGE = 1; // merge all: contact & company
	const UPDATE_MODE_REPLACE = 2; // replace all: contact & company

	const REGISTER_MODE_DEFAULT = 0; // register is add or update
	const REGISTER_MODE_ONLY_ADD = 1; // register is only add if it can
	const REGISTER_MODE_ONLY_UPDATE = 2; // register is only update
	const REGISTER_MODE_ALWAYS_ADD = 3; // register is always add

	const DIRECTION_INCOMING = 1;
	const DIRECTION_OUTGOING = 2;

	/** @var ActualEntitySelector|null  */
	protected $selector;

	/** @var Tracking\Trace $trace Trace instance. */
	protected $trace;
	/** @var int|null $traceId Trace ID. */
	protected $traceId;

	/** @var array  */
	protected $errors = array();
	/** @var null|array  */
	protected $bindings = null;

	/** @var int  */
	protected $direction = self::DIRECTION_INCOMING;

	/** @var null|int  */
	protected $registeredId = null;
	/** @var null|int  */
	protected $registeredTypeId = null;
	/** @var Identificator\ComplexCollection  */
	protected $registeredEntities;

	/** @var int  */
	protected $updateClientMode = self::UPDATE_MODE_MERGE;
	/** @var int  */
	protected $registerMode = self::REGISTER_MODE_DEFAULT;
	/** @var bool  */
	protected $isAutomationRun = true;
	/** @var bool  */
	protected $isAutoGenRcEnabled = true;
	/** @var bool  */
	protected $isLeadEnabled = true;
	/** @var bool  */
	protected $isBpRun = false;


	/**
	 * EntityManageFacility constructor.
	 *
	 * @param ActualEntitySelector|null $selector Selector.
	 */
	public function __construct(ActualEntitySelector $selector = null)
	{
		if (!$selector)
		{
			$selector = new ActualEntitySelector();
		}

		$this->selector = $selector;

		$this->setUpdateClientMode(self::UPDATE_MODE_MERGE);
		$this->isAutoGenRcEnabled = LeadSettings::getCurrent()->isAutoGenRcEnabled();
		$this->isLeadEnabled = LeadSettings::getCurrent()->isEnabled();
		$this->registeredEntities = new Identificator\ComplexCollection();
	}

	/**
	 * Set trace.
	 *
	 * @param Tracking\Trace $trace Trace instance.
	 * @return $this
	 */
	public function setTrace(Tracking\Trace $trace)
	{
		$this->trace = $trace;
		return $this;
	}

	/**
	 * Set trace ID.
	 *
	 * @param int|null $traceId Trace ID.
	 * @return $this
	 */
	public function setTraceId($traceId)
	{
		$this->traceId = $traceId;
		return $this;
	}

	/**
	 * Get trace instance.
	 *
	 * @return Tracking\Trace|null
	 */
	public function getTrace()
	{
		return $this->trace;
	}

	protected function traceEntity($entityTypeId, $entityId)
	{
		if ($this->direction !== self::DIRECTION_INCOMING)
		{
			return $this;
		}

		if (!$entityTypeId || !$entityId)
		{
			return $this;
		}

		if (!$this->trace && !$this->traceId)
		{
			return $this;
		}

		if (!$this->traceId)
		{
			$this->traceId = $this->trace->save();
		}

		if (!$this->traceId)
		{
			return $this;
		}

		Tracking\Trace::appendEntity($this->traceId, $entityTypeId, $entityId);
		return $this;
	}

	/**
	 * Set direction.
	 *
	 * @param int $direction Direction.
	 * @return $this
	 */
	public function setDirection($direction)
	{
		$this->direction = $direction;
		return $this;
	}

	/**
	 * Get entity selector.
	 *
	 * @return ActualEntitySelector
	 */
	public function getSelector()
	{
		return $this->selector;
	}

	/**
	 * Get bindings collection.
	 *
	 * @return Identificator\ComplexCollection
	 */
	public function getBindingCollection()
	{
		$collection = new Identificator\ComplexCollection();
		foreach ($this->getActivityBindings() as $binding)
		{
			$collection->addIdentificator($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);
		}

		return $collection;
	}

	/**
	 * Get bindings.
	 *
	 * @return array
	 */
	public function getActivityBindings()
	{
		if (!is_array($this->bindings))
		{
			$this->bindings = BindingSelector::findBindings($this->selector);
		}

		$needResort = false;
		$bindings = $this->bindings;
		if ($this->registeredId)
		{
			$bindings[] = array(
				'OWNER_TYPE_ID' => $this->registeredTypeId,
				'OWNER_ID' => $this->registeredId
			);

			$needResort = true;
		}
		foreach ($this->registeredEntities->toArray() as $entity)
		{
			$bindings[] = array(
				'OWNER_TYPE_ID' => $entity->getTypeId(),
				'OWNER_ID' => $entity->getId()
			);

			$needResort = true;
		}

		if ($needResort)
		{
			$bindings = BindingSelector::sortBindings($bindings);
		}

		return $bindings;
	}

	/**
	 * Returns registered entity id.
	 * @return int|null
	 */
	public function getRegisteredId()
	{
		return $this->registeredId;
	}

	/**
	 * Return registered entity type id.
	 *
	 * @return int|null
	 */
	public function getRegisteredTypeId()
	{
		return $this->registeredTypeId;
	}

	/**
	 * Get registered entities.
	 *
	 * @return Identificator\ComplexCollection
	 */
	public function getRegisteredEntities()
	{
		return $this->registeredEntities;
	}

	/**
	 * Returns id of the selected entity or id of the created entity or null (in this priority).
	 *
	 * @return int|null
	 */
	public function getPrimaryId()
	{
		return $this->getSelector()->getPrimaryId() ?: $this->registeredId;
	}

	/**
	 * Returns id of the type of the selected entity or id of the type of the created entity or null (in this priority).
	 *
	 * @return null
	 */
	public function getPrimaryTypeId()
	{
		return $this->getSelector()->getPrimaryTypeId() ?: $this->registeredTypeId;
	}

	/**
	 * Returns id of the person, responsible of the primary entity (if entity is found or registered).
	 *
	 * @return int|null
	 */
	public function getPrimaryAssignedById()
	{
		$id = $this->getPrimaryId();
		if (!$id)
		{
			return null;
		}

		return \CCrmOwnerType::getResponsibleID($this->getPrimaryTypeId(), $id, false);
	}

	/**
	 * Add entity if it need. Update client fields if it need.
	 * 1. Type of added entity may be different from the passed parameter.
	 * It depends on the settings: direction, lead free mode.
	 * 2. Entity may not be created.
	 * It depends on: RC leads mode, finding actual entities.
	 *
	 * @param int $entityTypeId Entity type Id.
	 * @param array $fields Fields for entity adding.
	 * @param bool $updateSearch Enable updating search of entity after adding.
	 * @param array $options Options of entity adding.
	 * @return bool
	 * @throws ArgumentException When try to use unsupported entity type id.
	 * @throws ArgumentException When Fields for entity type not found.
	 */
	public function registerTouch($entityTypeId, array &$fields, $updateSearch = true, $options = array())
	{
		// if 'lead free mode' and not outgoing event, then create lead only
		$preferredEntityTypeId = $entityTypeId;
		$probableEntityTypeId = \CCrmOwnerType::Lead;
		if (!$this->isLeadEnabled && $this->direction !== self::DIRECTION_OUTGOING)
		{
			if (in_array($entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]))
			{
				$entityTypeId = $probableEntityTypeId;
			}
		}

		// get fields from fields by type ID.
		$fieldKeys = array_keys($fields);
		if (array_filter($fieldKeys, 'is_integer') === $fieldKeys)
		{
			if (empty($fields[$preferredEntityTypeId]))
			{
				throw new ArgumentException("Empty fields for preferred Entity Type Id: {$preferredEntityTypeId}");
			}
			if (empty($fields[$probableEntityTypeId]))
			{
				throw new ArgumentException("Empty fields for probable Entity Type Id: {$probableEntityTypeId}");
			}
			$fields = $fields[$entityTypeId];
		}

		// executing
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				// preparing fields if entity type ID was changed
				switch ($preferredEntityTypeId)
				{
					case \CCrmOwnerType::Contact:
						if (empty($fields['TITLE']) && !empty($fields['NAME']))
						{
							$fields['TITLE'] = $fields['NAME'];
						}
						break;

					case \CCrmOwnerType::Company:
						if (empty($fields['COMPANY_TITLE']) && !empty($fields['TITLE']))
						{
							$fields['COMPANY_TITLE'] = $fields['TITLE'];
						}
						break;
				}
				$this->registerLead($fields, $updateSearch, $options);
				break;

			case \CCrmOwnerType::Deal:
				$this->registerDeal($fields, $updateSearch, $options);
				break;

			case \CCrmOwnerType::Contact:
				$this->registerContact($fields, $updateSearch, $options);
				break;

			case \CCrmOwnerType::Company:
				$this->registerCompany($fields, $updateSearch, $options);
				break;

			default:
				throw new ArgumentException("Unsupported Entity Type Id: {$entityTypeId}");
		}

		return !empty($this->registeredId);
	}

	/**
	 * Add lead if it need. Update client fields if it need.
	 *
	 * @param array $fields Fields.
	 * @param bool $updateSearch is update search needed.
	 * @param array $options Options.
	 * @return bool
	 */
	public function registerLead(array &$fields, $updateSearch = true, $options = array())
	{
		$this->registeredId = null;
		$this->registeredTypeId = \CCrmOwnerType::Lead;
		$this->isBpRun = false;

		if ($this->canAddLead())
		{
			$this->registeredId = $this->addLead($fields, $updateSearch, $options);
			$complex = $this->convertLead($this->registeredId);
			if ($complex)
			{
				$this->registeredTypeId = $complex->getTypeId();
				$this->registeredId = $complex->getId();
			}
			else
			{
				$this->registeredEntities->addIdentificator(
					$this->registeredTypeId,
					$this->registeredId,
					true
				);
			}
		}
		elseif ($this->canUpdate())
		{
			$this->updateClientFields($fields, true, $updateSearch, $options);
		}

		if ($this->isAutomationRun)
		{
			$this->runAutomation();
		}

		return !empty($this->registeredId);
	}

	/**
	 * Add company if it need. Update client fields if it need.
	 *
	 * @param array $fields Fields.
	 * @param bool $updateSearch is update search needed.
	 * @param array $options Options.
	 * @return bool
	 */
	public function registerCompany(array &$fields, $updateSearch = true, $options = array())
	{
		$this->registeredId = null;
		$this->registeredTypeId = \CCrmOwnerType::Company;
		$this->isBpRun = false;

		if ($this->canAddCompany())
		{
			if (!isset($fields['TITLE']) || !$fields['TITLE'])
			{
				$fields['TITLE'] = (isset($fields['COMPANY_TITLE']) && $fields['COMPANY_TITLE']) ? $fields['COMPANY_TITLE'] : '';
			}

			// use entity configuration setting when create
			if (!isset($fields['OPENED']) || empty($fields['OPENED']))
			{
				$fields['OPENED'] = Settings\CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			}

			$company = new \CCrmCompany(false);
			$this->registeredId = $company->add($fields, $updateSearch, $options);
			$this->registeredEntities->addIdentificator(
				$this->registeredTypeId,
				$this->registeredId,
				true
			);
			if (!$this->registeredId)
			{
				$this->errors[] = $company->LAST_ERROR;
			}
			else
			{
				$this->traceEntity($this->registeredTypeId, $this->registeredId);
			}
		}
		elseif ($this->canUpdate())
		{
			$this->updateClientFields($fields, true, $updateSearch, $options);
		}

		if ($this->isAutomationRun)
		{
			$this->runAutomation();
		}

		return !empty($this->registeredId);
	}

	/**
	 * Add contact if it need. Update client fields if it need.
	 *
	 * @param array $fields Fields.
	 * @param bool $updateSearch is update search needed.
	 * @param array $options Options.
	 * @return bool
	 */
	public function registerContact(array &$fields, $updateSearch = true, $options = array())
	{
		$this->registeredId = null;
		$this->registeredTypeId = \CCrmOwnerType::Contact;
		$this->isBpRun = false;

		if ($this->canAddContact())
		{
			// use entity configuration setting when create
			if (!isset($fields['OPENED']) || empty($fields['OPENED']))
			{
				$fields['OPENED'] = Settings\ContactSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
			}

			$contact = new \CCrmContact(false);
			$this->registeredId = $contact->add($fields, $updateSearch, $options);
			$this->registeredEntities->addIdentificator(
				$this->registeredTypeId,
				$this->registeredId,
				true
			);
			if (!$this->registeredId)
			{
				$this->errors[] = $contact->LAST_ERROR;
			}
			else
			{
				$this->traceEntity($this->registeredTypeId, $this->registeredId);
			}
		}
		elseif ($this->canUpdate())
		{
			$this->updateClientFields($fields, true, $updateSearch, $options);
		}

		if ($this->isAutomationRun)
		{
			$this->runAutomation();
		}

		return !empty($this->registeredId);
	}

	/**
	 * Add deal if it need.
	 *
	 * @param array $fields Fields.
	 * @param bool $updateSearch is update search needed.
	 * @param array $options Options.
	 * @return int|null
	 */
	public function registerDeal(array &$fields, $updateSearch = true, $options = array())
	{
		$this->registeredId = null;
		$this->registeredTypeId = \CCrmOwnerType::Deal;
		$this->isBpRun = false;

		if ($this->canAddDeal())
		{
			$this->registeredId = $this->addDeal($fields, $updateSearch, $options);
			$this->registeredEntities->addIdentificator(
				$this->registeredTypeId,
				$this->registeredId,
				true
			);
		}

		if ($this->isAutomationRun)
		{
			$this->runAutomation();
		}

		return $this->registeredId;
	}

	/**
	 * Return true if can add.
	 *
	 * @return bool
	 */
	protected function canAdd()
	{
		$allowModes = [
			self::REGISTER_MODE_DEFAULT,
			self::REGISTER_MODE_ONLY_ADD,
			self::REGISTER_MODE_ALWAYS_ADD
		];
		if (!in_array($this->registerMode, $allowModes))
		{
			return false;
		}

		return !$this->getSelector()->hasExclusions();
	}

	/**
	 * Return true if can update.
	 *
	 * @return bool
	 */
	protected function canUpdate()
	{
		if (!in_array($this->registerMode, [self::REGISTER_MODE_DEFAULT, self::REGISTER_MODE_ONLY_UPDATE]))
		{
			return false;
		}

		return !$this->getSelector()->hasExclusions();
	}

	/**
	 * Return true if can add lead.
	 * If register mode is "add only" then should create lead too.
	 *
	 * @return bool
	 */
	public function canAddLead()
	{
		// return false if can't add
		if (!$this->canAdd())
		{
			return false;
		}

		//  return true if register mode is "add always"
		if ($this->registerMode === self::REGISTER_MODE_ALWAYS_ADD)
		{
			return true;
		}

		// return true if can create plain lead
		if ($this->selector->canCreateLead())
		{
			return true;
		}

		return $this->canAddReturnCustomerLead();
	}

	protected function canAddReturnCustomerLead()
	{
		// return false if can't add
		if (!$this->canAdd())
		{
			return false;
		}

		//  return false if rc lead gen disabled and leads enabled
		if (!$this->isAutoGenRcEnabled && $this->isLeadEnabled)
		{
			return false;
		}

		// return false if it has entities like company or contact
		if (!$this->selector->hasEntities())
		{
			return false;
		}

		//  return true if register mode is "add always"
		if ($this->registerMode === self::REGISTER_MODE_ALWAYS_ADD)
		{
			return true;
		}

		return $this->selector->canCreateReturnCustomerLead();
	}



	/**
	 * Return true if can add lead.
	 * If register mode is "add only" then should create lead too.
	 *
	 * @return bool
	 */
	public function canAddDeal()
	{
		// return false if can't add
		if (!$this->canAdd())
		{
			return false;
		}

		if (!$this->selector->hasEntities())
		{
			return false;
		}

		//  return true if register mode is "add always"
		if ($this->registerMode === self::REGISTER_MODE_ALWAYS_ADD)
		{
			return true;
		}

		// return true if can create plain lead
		return $this->selector->canCreateDeal();
	}

	/**
	 * Return true if can add company.
	 *
	 * @return bool
	 */
	public function canAddCompany()
	{
		return $this->canAdd() && $this->selector->canCreatePrimaryEntity();
	}

	/**
	 * Return true if can add contact.
	 *
	 * @return bool
	 */
	public function canAddContact()
	{
		return $this->canAdd() && $this->selector->canCreatePrimaryEntity();
	}

	/**
	 * Return true if can add entity.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @return bool
	 * @throws ArgumentException
	 */
	public function canAddEntity($entityTypeId)
	{
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				return $this->canAddLead();
			case \CCrmOwnerType::Contact:
				if ($this->isLeadEnabled || $this->direction === self::DIRECTION_OUTGOING)
				{
					return $this->canAddContact();
				}
				else
				{
					return $this->canAddLead();
				}
			case \CCrmOwnerType::Company:
				return $this->canAddCompany();
			case \CCrmOwnerType::Deal:
				return $this->canAddDeal();
			default:
				throw new ArgumentException("Unsupported Entity Type Id: {$entityTypeId}");
		}
	}

	/**
	 * Add lead. It can create regular lead or return customer lead.
	 * And if RC-lead created, update client fields.
	 *
	 * @param array $fields Fields.
	 * @param bool $updateSearch is update search needed.
	 * @param array $options Options.
	 * @return int|null
	 */
	public function addLead(array &$fields, $updateSearch = true, $options = array())
	{
		$this->clearErrors();

		if (!$this->canAddLead())
		{
			return null;
		}

		$isRCLeadAdded = false;
		if ($this->canAddReturnCustomerLead())
		{
			if ($this->selector->getCompanyId())
			{
				$fields['COMPANY_ID'] = $this->selector->getCompanyId();
				if (!$this->selector->getContactId())
				{
					$userPermissions = \CCrmPerms::getUserPermissions($this->getUserId($fields));
					$fields['CONTACT_IDS'] = array_filter(
						Binding\ContactCompanyTable::getCompanyContactIDs($fields['COMPANY_ID']),
						function ($contactId) use ($userPermissions)
						{
							return \CCrmContact::CheckReadPermission($contactId, $userPermissions);
						}
					);
				}
			}
			if ($this->selector->getContactId())
			{
				$fields['CONTACT_ID'] = $this->selector->getContactId();
			}

			if ($this->selector->getCompanyId() || $this->selector->getContactId())
			{
				$fields['IS_RETURN_CUSTOMER'] = 'Y';
				$isRCLeadAdded = true;
			}
		}
		else
		{
			$fields['IS_RETURN_CUSTOMER'] = 'N';
		}

		$updateClientFields = $fields;
		if (!isset($options['DISABLE_USER_FIELD_CHECK']))
		{
			$options['DISABLE_USER_FIELD_CHECK'] = true;
		}

		// use entity configuration setting when create
		if (!isset($fields['OPENED']) || empty($fields['OPENED']))
		{
			$fields['OPENED'] = Settings\LeadSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}

		$lead = new \CCrmLead(false);
		$leadId = $lead->add($fields, $updateSearch, $options);
		if (!$leadId)
		{
			$this->errors[] = $lead->LAST_ERROR;
			return $leadId;
		}

		if ($isRCLeadAdded)
		{
			$this->updateClientFields($fields, true, $updateSearch, $options);
		}

		$this->traceEntity(\CCrmOwnerType::Lead, $leadId);

		return $leadId;
	}

	/**
	 * Add deal.
	 *
	 * @param array $fields Fields.
	 * @param bool $updateSearch is update search needed.
	 * @param array $options Options.
	 * @return int|null
	 */
	public function addDeal(array &$fields, $updateSearch = true, $options = array())
	{
		$this->clearErrors();

		if (!$this->canAddDeal())
		{
			return null;
		}

		if ($this->selector->getCompanyId())
		{
			$fields['COMPANY_ID'] = $this->selector->getCompanyId();
			if (!$this->selector->getContactId())
			{
				$userPermissions = \CCrmPerms::getUserPermissions($this->getUserId($fields));
				$fields['CONTACT_IDS'] = array_filter(
					Binding\ContactCompanyTable::getCompanyContactIDs($fields['COMPANY_ID']),
					function ($contactId) use ($userPermissions)
					{
						return \CCrmContact::CheckReadPermission($contactId, $userPermissions);
					}
				);
			}
		}
		if ($this->selector->getContactId())
		{
			$fields['CONTACT_ID'] = $this->selector->getContactId();
		}

		if (!isset($options['DISABLE_USER_FIELD_CHECK']))
		{
			$options['DISABLE_USER_FIELD_CHECK'] = true;
		}

		// use entity configuration setting when create
		if (!isset($fields['OPENED']) || empty($fields['OPENED']))
		{
			$fields['OPENED'] = Settings\DealSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N';
		}

		$deal = new \CCrmDeal(false);
		$dealId = $deal->add($fields, $updateSearch, $options);
		if (!$dealId)
		{
			$this->errors[] = $deal->LAST_ERROR;
			return null;
		}

		$this->traceEntity(\CCrmOwnerType::Deal, $dealId);
		return $dealId;
	}

	/**
	 * Run automation. Setting `disableAutomationRun` is ignored.
	 *
	 * @return $this
	 */
	public function runAutomation()
	{
		// run on add
		if ($this->canAdd() && $this->registeredId && $this->registeredTypeId)
		{
			// run business process
			if (!$this->isBpRun)
			{
				$bpErrors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					$this->registeredTypeId,
					$this->registeredId,
					\CCrmBizProcEventType::Create,
					$bpErrors
				);

				// mark as BP run
				$this->isBpRun = true;
			}

			// run automation
			if (!$this->isEntityTypeConvertible($this->registeredTypeId))
			{
				$starter = new Automation\Starter($this->registeredTypeId, $this->registeredId);
				$starter->runOnAdd();
			}
		}
		elseif ($this->canUpdate() && $this->getPrimaryId() && $this->getPrimaryTypeId())
		{
			// run business process
			$bpErrors = array();
			\CCrmBizProcHelper::AutoStartWorkflows(
				$this->getPrimaryTypeId(),
				$this->getPrimaryId(),
				\CCrmBizProcEventType::Edit,
				$bpErrors
			);

			// TODO: call Automation\Starter::runOnUpdate on entity update.
		}

		return $this;
	}

	protected function clearErrors()
	{
		return $this->errors = array();
	}

	/**
	 * Return true if there is no error.
	 *
	 * @return bool
	 */
	public function hasErrors()
	{
		return count($this->errors) > 0;
	}
	/**
	 * Get error messages.
	 *
	 * @return array
	 */
	public function getErrorMessages()
	{
		return $this->errors;
	}

	/**
	 * Get update client fields mode.
	 *
	 * @return int
	 */
	public function getUpdateClientMode()
	{
		return $this->updateClientMode;
	}

	/**
	 * Set update client fields mode.
	 *
	 * @param int $mode Mode.
	 * @return $this
	 * @throws ArgumentException
	 */
	public function setUpdateClientMode($mode)
	{
		if (!in_array($mode, array(self::UPDATE_MODE_NONE, self::UPDATE_MODE_MERGE, self::UPDATE_MODE_REPLACE)))
		{
			throw new ArgumentException("Update client mode {$mode} not implemented.");
		}

		$this->updateClientMode = $mode;
		return $this;
	}

	/**
	 * Get register mode.
	 *
	 * @return int
	 */
	public function getRegisterMode()
	{
		return $this->registerMode;
	}

	/**
	 * Set register mode.
	 *
	 * @param int $mode Mode.
	 * @return $this
	 * @throws ArgumentException
	 */
	public function setRegisterMode($mode)
	{
		$allowModes = [
			self::REGISTER_MODE_DEFAULT,
			self::REGISTER_MODE_ONLY_ADD,
			self::REGISTER_MODE_ONLY_UPDATE,
			self::REGISTER_MODE_ALWAYS_ADD
		];
		if (!in_array($mode, $allowModes))
		{
			throw new ArgumentException("Register mode {$mode} not implemented.");
		}

		$this->registerMode = $mode;
		return $this;
	}

	/**
	 * Disable automation run.
	 *
	 * @return $this
	 */
	public function disableAutomationRun()
	{
		$this->isAutomationRun = false;
		return $this;
	}

	/**
	 * Returns whether automation is enabled.
	 *
	 * @return bool
	 */
	public function isAutomationRun()
	{
		return $this->isAutomationRun;
	}

	/**
	 * Enable auto generation return customer lead.
	 *
	 * @return $this
	 */
	public function enableAutoGenRc()
	{
		$this->isAutoGenRcEnabled = true;
		return $this;
	}

	/**
	 * Update contact and company by lead fields.
	 *
	 * @param array $fields Lead fields.
	 * @param bool $compare
	 * @param bool $updateSearch
	 * @param array $options
	 * @return void
	 * @throws ArgumentException
	 */
	public function updateClientFields(array $fields, $compare = true, $updateSearch = true, array $options = [])
	{
		$mergeItems = array();
		if ($this->selector->getCompanyId())
		{
			$mergeItemFields = array();
			if (isset($fields['COMPANY_TITLE']) && $fields['COMPANY_TITLE'])
			{
				$mergeItemFields['TITLE'] = $fields['COMPANY_TITLE'];
			}

			if (!$this->selector->getContactId())
			{
				if (isset($fields['FM']))
				{
					$mergeItemFields['FM'] = $fields['FM'];
				}
			}

			if (!empty($mergeItemFields))
			{
				$mergeItems[] = array(
					'typeId' => \CCrmOwnerType::Company,
					'id' => $this->selector->getCompanyId(),
					'fields' => $mergeItemFields
				);
			}
		}

		if ($this->selector->getContactId())
		{
			$mergeItemFields = $fields;
			$customerFields = \CCrmLead::getCustomerFields();
			foreach ($mergeItemFields as $fieldName => $fieldValue)
			{
				if (in_array($fieldName, $customerFields))
				{
					continue;
				}

				unset($mergeItemFields[$fieldName]);
			}
			unset($mergeItemFields['COMPANY_TITLE']);

			if (!empty($mergeItemFields))
			{
				$mergeItems[] = array(
					'typeId' => \CCrmOwnerType::Contact,
					'id' => $this->selector->getContactId(),
					'fields' => $mergeItemFields
				);
			}
		}

		if ($this->selector->getLeadId())
		{
			if (!$this->selector->getContactId() && !$this->selector->getCompanyId())
			{
				$mergeItems[] = array(
					'typeId' => \CCrmOwnerType::Lead,
					'id' => $this->selector->getLeadId(),
					'fields' => $fields
				);
			}
		}
		elseif ($this->selector->getReturnCustomerLeadId())
		{
			$mergeItemFields = $fields;
			$customerFields = \CCrmLead::getCustomerFields();
			foreach ($mergeItemFields as $fieldName => $fieldValue)
			{
				if (!in_array($fieldName, $customerFields))
				{
					continue;
				}

				unset($mergeItemFields[$fieldName]);
			}

			$mergeItems[] = array(
				'typeId' => \CCrmOwnerType::Lead,
				'id' => $this->selector->getReturnCustomerLeadId(),
				'fields' => $mergeItemFields
			);
		}

		switch ($this->updateClientMode)
		{
			case self::UPDATE_MODE_REPLACE:

				foreach ($mergeItems as $mergeItem)
				{
					if ($mergeItem['typeId'] == \CCrmOwnerType::Company)
					{
						$entityObject = new \CCrmCompany(false);
					}
					elseif ($mergeItem['typeId'] == \CCrmOwnerType::Contact)
					{
						$entityObject = new \CCrmContact(false);
					}
					elseif ($mergeItem['typeId'] == \CCrmOwnerType::Lead)
					{
						$entityObject = new \CCrmLead(false);
					}
					else
					{
						continue;
					}

					$entityId = $mergeItem['id'];
					$mergeFields = $mergeItem['fields'];
					if (isset($mergeFields['FM']) && empty($mergeFields['FM']))
					{
						unset($mergeFields['FM']);
					}

					if (!empty($mergeFields))
					{
						$entityObject->update($entityId, $mergeFields);
					}
				}

				break;

			case self::UPDATE_MODE_MERGE:

				foreach ($mergeItems as $mergeItem)
				{
					if ($mergeItem['typeId'] == \CCrmOwnerType::Company)
					{
						$entityObject = new \CCrmCompany(false);
						$merger = new Merger\CompanyMerger(0, false);
					}
					elseif ($mergeItem['typeId'] == \CCrmOwnerType::Contact)
					{
						$entityObject = new \CCrmContact(false);
						$merger = new Merger\ContactMerger(0, false);
					}
					elseif ($mergeItem['typeId'] == \CCrmOwnerType::Lead)
					{
						$entityObject = new \CCrmLead(false);
						$merger = new Merger\LeadMerger(0, false);
					}
					else
					{
						continue;
					}

					$entityTypeId = $mergeItem['typeId'];
					$entityId = $mergeItem['id'];
					$mergeFields = $mergeItem['fields'];

					$entityMultiFields = [];
					$multiFields = \CCrmFieldMulti::getEntityFields(
						\CCrmOwnerType::resolveName($entityTypeId),
						$entityId,
						null
					);
					foreach($multiFields as $multiField)
					{
						if (!isset($entityMultiFields[$multiField['TYPE_ID']]))
						{
							$entityMultiFields[$multiField['TYPE_ID']] = [];
						}

						$entityMultiFields[$multiField['TYPE_ID']][$multiField['ID']] = [
							'VALUE' => $multiField['VALUE'],
							'VALUE_TYPE' => $multiField['VALUE_TYPE'],
						];
					}

					$entityFieldsDb = $entityObject->getListEx(
						array(),
						array(
							'=ID' => $entityId,
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('*', 'UF_*')
					);
					$entityFields = $entityFieldsDb->fetch();
					if ($entityFields)
					{
						foreach ($entityFields as $key => $value)
						{
							if ($value === null)
							{
								unset($entityFields[$key]);
							}
						}

						$entityFields['FM'] = $entityMultiFields;
						$merger->mergeFields($mergeFields, $entityFields, false, array('ENABLE_UPLOAD' => true));
						$this->uniqueMultiFields($entityFields['FM'], $entityMultiFields);

						$entityObject->update($entityId, $entityFields, $compare, $updateSearch, $options);
					}
				}

				break;
		}

	}


	protected function getUserId(array $fields = [])
	{
		if (!empty($fields) && !empty($fields['ASSIGNED_BY_ID']))
		{
			return (int) $fields['ASSIGNED_BY_ID'];
		}

		$assignedById = $this->getPrimaryAssignedById();
		if ($assignedById)
		{
			return (int) $assignedById;
		}

		return \CCrmSecurityHelper::getCurrentUserID();
	}

	/**
	 * Unique multi fields.
	 *
	 * @param array $targetFields Target fields.
	 * @param array $originalFields Original fields.
	 * @return void
	 */
	protected function uniqueMultiFields(array &$targetFields, array $originalFields = [])
	{
		foreach ($originalFields as $typeCode => $fields)
		{
			if (!isset($targetFields[$typeCode]))
			{
				continue;
			}

			foreach ($fields as $key => $field)
			{
				if (empty($field['VALUE']))
				{
					continue;
				}

				foreach ($targetFields[$typeCode] as $targetKey => $targetField)
				{
					if (empty($targetField['VALUE']))
					{
						continue;
					}

					if ($targetField['VALUE'] !== $field['VALUE'])
					{
						continue;
					}

					unset($targetFields[$typeCode][$targetKey]);
				}
			}

			if (count($targetFields[$typeCode]) === 0)
			{
				unset($targetFields[$typeCode]);
			}
		}
	}

	/**
	 * Run lead conversion at disabled lead mode.
	 * Collect created entities.
	 * Return converted deal.
	 *
	 * @param int $leadId Lead ID.
	 * @return Identificator\Complex|null
	 */
	public function convertLead($leadId)
	{
		$entityTypeId = \CCrmOwnerType::Lead;
		if (!$leadId || !$this->isEntityTypeConvertible($entityTypeId))
		{
			return null;
		}

		$starter = new Automation\Starter($entityTypeId, $leadId);
		$result = $starter->runOnAdd()->getConversionResult();
		if (!$result)
		{
			return null;
		}

		// mark as BP run
		$this->isBpRun = true;

		// set founded entities to selector
		foreach ($result->getBoundEntities() as $entity)
		{
			$this->getSelector()->setEntity($entity->getTypeId(), $entity->getId());
		}

		// set created entities to registered entities and to selector
		foreach ($result->getCreatedEntities() as $entity)
		{
			$this->registeredEntities->setComplex($entity, true);
			$this->getSelector()->setEntity($entity->getTypeId(), $entity->getId());
		}

		return $result->getCreatedEntities()->getComplexByTypeId(\CCrmOwnerType::Deal);
	}

	/**
	 * Return true if registered entity is convertible.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @return bool
	 */
	protected function isEntityTypeConvertible($entityTypeId = \CCrmOwnerType::Undefined)
	{
		return (
			!$this->isLeadEnabled
			&&
			$entityTypeId === \CCrmOwnerType::Lead
		);
	}
}
