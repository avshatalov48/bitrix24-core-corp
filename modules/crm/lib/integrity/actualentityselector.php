<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\ArgumentException;

use Bitrix\Crm\Exclusion;
use Bitrix\Crm\Communication;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Entity\Identificator;

/**
 * Class ActualEntitySelector
 * @package Bitrix\Crm\Integrity
 */
class ActualEntitySelector
{
	const SEARCH_PARAM_PHONE = 'FM.PHONE';
	const SEARCH_PARAM_EMAIL = 'FM.EMAIL';
	const SEARCH_PARAM_PERSON = 'PERSON';
	const SEARCH_PARAM_ORGANIZATION = 'ORGANIZATION';

	/** @var ActualRanking $ranking Actual ranking instance. */
	protected $ranking;

	/** @var boolean $isFullSearchEnabled Is full search enabled. */
	protected $isFullSearchEnabled = false;

	/** @var boolean $isExclusionCheckingEnabled Is exclusion checking enabled. */
	protected $isExclusionCheckingEnabled = true;

	/** @var DuplicateCriterion[] $duplicateCriteria Duplicate criteria. */
	protected $duplicateCriteria;

	/** @var Duplicate[] $duplicates Duplicate list.*/
	protected $duplicates;

	/** @var bool $hasExclusions True if has exclusions.*/
	protected $hasExclusions = false;

	/** @var bool $isAutoUsingFinishedLeadEnabled True if using finished lead enabled.*/
	protected $isAutoUsingFinishedLeadEnabled = false;

	/** @var array $entities Initial entities structure. */
	protected $initialEntities = array();

	/** @var array $entities Actual entities. */
	protected $entities = array(
		array(
			'CODE' => 'companyId',
			'IS_PRIMARY' => true,
			'PRIMARY_SORT' => 40,
			'TYPE_ID' => \CCrmOwnerType::Company,
			'ID' => null,
		),
		array(
			'CODE' => 'companyDealId',
			'TYPE_ID' => \CCrmOwnerType::Deal,
			'ID' => null,
		),
		array(
			'CODE' => 'companyOrders',
			'TYPE_ID' => \CCrmOwnerType::Order,
			'ID' => null,
		),
		array(
			'CODE' => 'companyReturnCustomerLeadId',
			'TYPE_ID' => \CCrmOwnerType::Lead,
			'ID' => null,
		),
		array(
			'CODE' => 'contactId',
			'IS_PRIMARY' => true,
			'PRIMARY_SORT' => 10,
			'TYPE_ID' => \CCrmOwnerType::Contact,
			'ID' => null,
		),
		array(
			'CODE' => 'contactCompanyId',
			'TYPE_ID' => \CCrmOwnerType::Company,
			'ID' => null,
		),
		array(
			'CODE' => 'contactDealId',
			'TYPE_ID' => \CCrmOwnerType::Deal,
			'ID' => null,
		),
		array(
			'CODE' => 'contactOrders',
			'TYPE_ID' => \CCrmOwnerType::Order,
			'ID' => null,
		),
		array(
			'CODE' => 'contactReturnCustomerLeadId',
			'TYPE_ID' => \CCrmOwnerType::Lead,
			'ID' => null,
		),
		array(
			'CODE' => 'dealId',
			'IS_PRIMARY' => true,
			'TYPE_ID' => \CCrmOwnerType::Deal,
			'ID' => null,
		),
		array(
			'CODE' => 'orders',
			'IS_PRIMARY' => true,
			'TYPE_ID' => \CCrmOwnerType::Order,
			'ID' => null,
		),
		array(
			'CODE' => 'leadId',
			'IS_PRIMARY' => true,
			'PRIMARY_SORT' => 30,
			'TYPE_ID' => \CCrmOwnerType::Lead,
			'ID' => null,
		),
		array(
			'CODE' => 'returnCustomerLeadId',
			'IS_PRIMARY' => true,
			'PRIMARY_SORT' => 20,
			'TYPE_ID' => \CCrmOwnerType::Lead,
			'ID' => null,
		),
	);

	/**
	 * Create instance of class.
	 * $fields = array(
	 *  'NAME' => 'Mike',
	 *  'SECOND_NAME' => 'Julio',
	 *  'LAST_NAME' => 'Johnson',
	 *  'COMPANY_TITLE' => 'Example company name',
	 *  'FM' => array(
	 *    'EMAIL' => array(array('VALUE' => 'name@example.com')),
	 *    'PHONE' => array(array('VALUE' => '+98765432100')),
	 *  )
	 * ).
	 *
	 * @param array $fields Entity fields.
	 * @param array $searchParameters Search parameters for searching duplicates.
	 * @return static
	 * @throws ArgumentException
	 */
	public static function create(array $fields, array $searchParameters)
	{
		$criteria = static::createDuplicateCriteria($fields, $searchParameters);
		$instance = new static($criteria);
		return $instance;
	}

	/**
	 * Create duplicate criteria from entity fields.
	 *
	 * @param array $fields Entity fields.
	 * @param array $searchParameters List of search parameters.
	 * @return DuplicateCriterion[]
	 * @throws ArgumentException
	 */
	public static function createDuplicateCriteria(array $fields, array $searchParameters)
	{
		$criteria = array();

		$searchParameters = array_unique($searchParameters);
		foreach ($searchParameters as $searchParameter)
		{
			switch ($searchParameter)
			{
				case self::SEARCH_PARAM_PERSON:
					if (!isset($fields['LAST_NAME']) || !$fields['LAST_NAME'])
					{
						continue;
					}

					$criteria[] = new DuplicatePersonCriterion(
						$fields['LAST_NAME'],
						isset($fields['NAME']) ? $fields['NAME'] : '',
						isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : ''
					);
					break;

				case self::SEARCH_PARAM_ORGANIZATION:
					if (!isset($fields['COMPANY_TITLE']) || !$fields['COMPANY_TITLE'])
					{
						continue;
					}

					$criteria[] = new DuplicateOrganizationCriterion($fields['COMPANY_TITLE']);
					break;

				case self::SEARCH_PARAM_EMAIL:
					if (!isset($fields['FM']))
					{
						continue;
					}

					$values = DuplicateCommunicationCriterion::extractMultifieldsValues(
						$fields['FM'],
						\CCrmFieldMulti::EMAIL
					);

					foreach ($values as $value)
					{
						$criteria[] = new DuplicateCommunicationCriterion(Communication\Type::EMAIL_NAME, $value);
					}
					break;

				case self::SEARCH_PARAM_PHONE:
					if (!isset($fields['FM']))
					{
						continue;
					}

					$values = DuplicateCommunicationCriterion::extractMultifieldsValues(
						$fields['FM'],
						\CCrmFieldMulti::PHONE
					);

					foreach ($values as $value)
					{
						$criteria[] = new DuplicateCommunicationCriterion(Communication\Type::PHONE_NAME, $value);
					}
					break;

				default:
					throw new ArgumentException('Unsupported search parameter "' . $searchParameter .'"');
			}
		}

		return $criteria;
	}

	/**
	 * Constructor.
	 *
	 * @param DuplicateCriterion[] $criteria List of duplicate criteria for searching duplicates.
	 */
	public function __construct(array $criteria = array())
	{
		$this->isAutoUsingFinishedLeadEnabled = LeadSettings::getCurrent()->isAutoUsingFinishedLeadEnabled()
			&& LeadSettings::getCurrent()->isEnabled();
		$this->initialEntities = $this->entities;
		$this->setCriteria($criteria);
		$this->search();
	}

	/**
	 * Clear.
	 * Remove criteria, founded duplicates, entity data.
	 *
	 * @return $this
	 */
	public function clear()
	{
		$this->setCriteria();
		$this->duplicates = array();
		$this->entities = $this->initialEntities;
		return $this;
	}

	/**
	 * Set criteria.
	 *
	 * @param DuplicateCriterion[] $criteria List of duplicate criteria for searching duplicates.
	 * @return $this
	 */
	public function setCriteria(array $criteria = array())
	{
		$this->duplicateCriteria = $criteria;
		return $this;
	}

	/**
	 * Get criteria.
	 *
	 * @return DuplicateCriterion[]
	 */
	public function getCriteria()
	{
		return $this->duplicateCriteria;
	}

	/**
	 * Append email criterion.
	 *
	 * @param string $communicationType Type defined at Communication\Type class.
	 * @param string $value Value.
	 * @return $this
	 */
	public function appendCommunicationCriterion($communicationType, $value)
	{
		$this->duplicateCriteria[] = new DuplicateCommunicationCriterion(
			$communicationType, $value
		);
		return $this;
	}

	/**
	 * Append email criterion.
	 *
	 * @param string $email Email.
	 * @return $this
	 */
	public function appendEmailCriterion($email)
	{
		return $this->appendCommunicationCriterion(Communication\Type::EMAIL_NAME, $email);
	}

	/**
	 * Append phone number criterion.
	 *
	 * @param string $phone Phone number.
	 * @return $this
	 */
	public function appendPhoneCriterion($phone)
	{
		return $this->appendCommunicationCriterion(Communication\Type::PHONE_NAME, $phone);
	}

	/**
	 * Append person criterion.
	 *
	 * @param string $lastName Last name.
	 * @param string $name Name.
	 * @param string $secondName Second name.
	 * @return $this
	 */
	public function appendPersonCriterion($lastName, $name = '', $secondName = '')
	{
		$this->duplicateCriteria[] = new DuplicatePersonCriterion($lastName, $name, $secondName);
		return $this;
	}

	/**
	 * Return true if entities are found.
	 *
	 * @return bool
	 */
	public function hasEntities()
	{
		return count($this->getEntities()) > 0;
	}

	/**
	 * Get actual entity id by code.
	 *
	 * @param string $code Code.
	 * @return int|int[]|null
	 * @throws ArgumentException
	 */
	public function get($code)
	{
		foreach ($this->entities as $entity)
		{
			if ($entity['CODE'] === $code)
			{
				return $entity['ID'];
			}
		}

		throw new ArgumentException("Code '{$code}' not existed.");
	}

	/**
	 * Get actual entity id by code.
	 *
	 * @param string $code Code.
	 * @param int|int[]|null $value Entity id.
	 * @throws ArgumentException
	 */
	public function set($code, $value)
	{
		$value = (int) $value;
		foreach ($this->entities as $index => $entity)
		{
			if ($entity['CODE'] === $code)
			{
				$entity['ID'] = $value ?: null;
				$this->entities[$index] = $entity;
				return;
			}
		}

		throw new ArgumentException("Code '{$code}' not existed.");
	}

	/**
	 * Get actual entities with non-primary entities.
	 *
	 * @return array
	 */
	public function getAll()
	{
		$list = array();
		foreach($this->entities as $entity)
		{
			if (!$entity['ID'])
			{
				continue;
			}

			$list[] = $entity;
		}

		return $list;
	}

	/**
	 * Get actual entity list.
	 *
	 * @return array
	 */
	public function getEntities()
	{
		$list = array();
		$num = 0;
		foreach($this->entities as $entity)
		{
			if (!isset($entity['IS_PRIMARY']) || !$entity['IS_PRIMARY'])
			{
				continue;
			}

			if (!$entity['ID'])
			{
				continue;
			}

			$sort = isset($entity['PRIMARY_SORT']) ? $entity['PRIMARY_SORT'] : 100;
			$sort += ++$num/100;
			$list[(string) $sort] = array(
				'ENTITY_TYPE_ID' => $entity['TYPE_ID'],
				'ENTITY_ID' => $entity['ID'],
			);
		}

		ksort($list, SORT_NUMERIC);
		$list = array_values($list);
		return $list;
	}

	/**
	 * Get entity by type id.
	 *
	 * @param integer $entityTypeId Entity type ID.
	 * @param boolean $isPrimary Filter by primary mark.
	 * @return array|null
	 */
	protected function getEntityByTypeId($entityTypeId, $isPrimary = true)
	{

		foreach($this->entities as $entity)
		{
			if ($isPrimary && (!isset($entity['IS_PRIMARY']) || !$entity['IS_PRIMARY']))
			{
				continue;
			}

			if ($entity['TYPE_ID'] == $entityTypeId)
			{
				return $entity;
			}
		}

		return null;
	}

	/**
	 * Set actual entity instead of using duplicate search.
	 *
	 * @param Identificator\ComplexCollection $entities Entities.
	 * @param bool $skipRanking Skip ranking.
	 * @return $this
	 */
	public function setEntities(Identificator\ComplexCollection $entities, $skipRanking = false)
	{
		$list = [];
		foreach ($entities as $entity)
		{
			switch ($entity->getTypeId() )
			{
				case \CCrmOwnerType::Order:
					if (!isset($list[\CCrmOwnerType::Order]))
					{
						$list[\CCrmOwnerType::Order] = [];
					}
					$list[\CCrmOwnerType::Order][] = $entity->getId();
					break;

				default:
					$this->setEntity($entity->getTypeId(), $entity->getId(), $skipRanking);
					break;
			}
		}

		foreach ($list as $typeId => $ids)
		{
			$this->setEntity($typeId, $ids, $skipRanking);
		}

		return $this;
	}

	/**
	 * Set actual entity instead of using duplicate search.
	 *
	 * @param integer $entityTypeId Entity type ID.
	 * @param integer|integer[] $entityId Entity ID.
	 * @param bool $skipRanking Skip ranking.
	 * @return $this
	 */
	public function setEntity($entityTypeId, $entityId, $skipRanking = false)
	{
		if (is_array($entityId))
		{
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Order:
					$entityId = is_array($entityId) ? $entityId : [$entityId];
					$entityId = array_filter($entityId, 'is_numeric');
					$entityId = array_map('intval', $entityId);
					$entityId = empty($entityId) ? null : $entityId;
					break;

				default:
					$entityId = current($entityId);
					$entityId = $entityId ? (int) $entityId : null;
					break;
			}
		}

		foreach($this->entities as $index => $entity)
		{
			if (!isset($entity['IS_PRIMARY']) || !$entity['IS_PRIMARY'])
			{
				continue;
			}

			if ($entity['TYPE_ID'] != $entityTypeId)
			{
				continue;
			}

			// check with lead type
			if ($entityTypeId == \CCrmOwnerType::Lead)
			{
				$isCurrentLeadRC = $entity['CODE'] == 'returnCustomerLeadId';
				$leadRow = LeadTable::getRow([
					'select' => ['ID', 'IS_RETURN_CUSTOMER', 'CONTACT_ID', 'COMPANY_ID'],
					'filter' => ['=ID' => $entityId]
				]);
				$isLeadRC = $leadRow['IS_RETURN_CUSTOMER'] === 'Y';
				if ($isCurrentLeadRC != $isLeadRC)
				{
					continue;
				}

				if ($isLeadRC)
				{
					if ($leadRow['CONTACT_ID'])
					{
						$this->setEntity(\CCrmOwnerType::Contact, $leadRow['CONTACT_ID'], true);
					}
					if ($leadRow['COMPANY_ID'])
					{
						$this->setEntity(\CCrmOwnerType::Company, $leadRow['COMPANY_ID'], true);
					}
				}

				$skipRanking = true;
			}
			elseif ($entityTypeId == \CCrmOwnerType::Deal)
			{
				$dealRow = DealTable::getRow([
					'select' => ['ID', 'CONTACT_ID', 'COMPANY_ID'],
					'filter' => ['=ID' => $entityId]
				]);
				if ($dealRow)
				{
					if ($dealRow['CONTACT_ID'])
					{
						$this->setEntity(\CCrmOwnerType::Contact, $dealRow['CONTACT_ID'], true);
					}
					if ($dealRow['COMPANY_ID'])
					{
						$this->setEntity(\CCrmOwnerType::Company, $dealRow['COMPANY_ID'], true);
					}
				}
			}

			$entity['ID'] = $entityId;
			$entity['SKIP_DUPLICATES'] = true;
			$entity['SKIP_RANKING'] = $skipRanking;
			$this->entities[$index] = $entity;
		}

		return $this;
	}

	protected function getRawByCode($entityCode)
	{
		foreach ($this->entities as $entity)
		{
			if ($entity['CODE'] === $entityCode)
			{
				return $entity;
			}
		}

		return null;
	}

	protected function canRank($entityTypeId)
	{
		$entity = $this->getEntityByTypeId($entityTypeId);
		if (!$entity || !$entity['ID'])
		{
			return true;
		}

		if (!isset($entity['SKIP_RANKING']) || !$entity['SKIP_RANKING'])
		{
			return true;
		}

		return false;
	}

	protected function canUseDuplicates($entityTypeId)
	{
		$entity = $this->getEntityByTypeId($entityTypeId);
		if (!$entity || !$entity['ID'])
		{
			return true;
		}

		if (!isset($entity['SKIP_DUPLICATES']) || !$entity['SKIP_DUPLICATES'])
		{
			return true;
		}

		return false;
	}

	protected function getRankableList($entityTypeId)
	{
		$list = [];
		if ($this->canUseDuplicates($entityTypeId))
		{
			// get list with default ranking
			foreach ($this->duplicates as $duplicate)
			{
				$list = array_merge($list, $duplicate->getEntityIDsByType($entityTypeId));
			}
		}

		if (empty($list))
		{
			$entity = $this->getEntityByTypeId($entityTypeId);
			if ($entity['ID'])
			{
				$list[] = $entity['ID'];
			}
		}

		// unique list
		return array_unique($list);
	}

	protected function rank($entityTypeId)
	{
		// actual ranking
		return $this->getRanking()->rank(
			$entityTypeId,
			$this->getRankableList($entityTypeId),
			$this->canRank($entityTypeId)
		);
	}

	/**
	 * Enable searching of all entity types.
	 *
	 * @return $this
	 */
	public function enableFullSearch()
	{
		$this->isFullSearchEnabled = true;
		return $this;
	}

	/**
	 * Disable exclusion checking.
	 *
	 * @return $this
	 */
	public function disableExclusionChecking()
	{
		$this->isExclusionCheckingEnabled = false;
		return $this;
	}

	/**
	 * Search actual entity list.
	 *
	 * @return $this
	 */
	public function search()
	{
		// check for exclusions
		if ($this->isExclusionCheckingEnabled && !$this->checkForExclusions())
		{
			$this->duplicates = [];
			return $this;
		}

		$this->duplicates = $this->findDuplicates();
		if (count($this->duplicates) == 0 && !$this->hasEntities())
		{
			// stop searching if no duplicates and no entities set manually
			return $this;
		}

		$this->searchCompany();
		$this->searchContact();
		if ($this->isFullSearchEnabled || (!$this->getCompanyId() && !$this->getContactId()))
		{
			$this->searchLead();
		}

		if ($this->getCompanyDealId())
		{
			$this->set('dealId', $this->getCompanyDealId());
		}
		else if ($this->getContactDealId())
		{
			$this->set('dealId', $this->getContactDealId());
		}

		if (!empty($this->getCompanyOrders()))
		{
			$this->set('orders', $this->getCompanyOrders());
		}
		else if (!empty($this->getContactOrders()))
		{
			$this->set('orders', $this->getContactOrders());
		}

		if ($this->getCompanyReturnCustomerLeadId())
		{
			$this->set('returnCustomerLeadId', $this->getCompanyReturnCustomerLeadId());
		}
		else if ($this->getContactReturnCustomerLeadId())
		{
			$this->set('returnCustomerLeadId', $this->getContactReturnCustomerLeadId());
		}

		return $this;
	}

	protected function searchCompany()
	{
		$ranking = $this->rank(\CCrmOwnerType::Company);
		if (!$ranking->getEntityId())
		{
			return;
		}

		$this->set('companyId', $ranking->getEntityId());
		$this->set('companyDealId', $ranking->getDealId());
		$this->set('companyOrders', $ranking->getOrders());
		$this->set('companyReturnCustomerLeadId', $ranking->getReturnCustomerLeadId());
	}

	protected function searchContact()
	{
		$ranking = $this->rank(\CCrmOwnerType::Contact);
		if (!$ranking->getEntityId())
		{
			return;
		}

		$contactId = $ranking->getEntityId();
		$this->set('contactId', $contactId);
		$this->set('contactDealId', $ranking->getDealId());
		$this->set('contactOrders', $ranking->getOrders());
		$this->set('contactReturnCustomerLeadId', $ranking->getReturnCustomerLeadId());

		if ($contactId)
		{
			$contactDb = ContactTable::getList(array(
				'select' => ['COMPANY_ID'],
				'filter' => ['=ID' => $contactId]
			));
			if (($contact = $contactDb->fetch()) && $contact['COMPANY_ID'])
			{
				$this->set('contactCompanyId', (int) $contact['COMPANY_ID']);
			}
		}
	}

	protected function searchLead()
	{
		$ranking = $this->rank(\CCrmOwnerType::Lead);
		$leadId = $ranking->getEntityId();
		if (!$leadId && $this->isAutoUsingFinishedLeadEnabled)
		{
			$list = $ranking->getModifiedList();
			if (!is_array($list))
			{
				$list = $this->getRankableList(\CCrmOwnerType::Lead);
			}

			$leadId = empty($list) ? null : current($list);
		}

		if ($leadId)
		{
			$this->set('leadId', $leadId);
		}
	}

	/**
	 * Return list of duplicates.
	 *
	 * @return Duplicate[]
	 */
	protected function findDuplicates()
	{
		$result = [];
		foreach ($this->duplicateCriteria as $criterion)
		{
			$criterion->sortDescendingByEntityTypeId();
			$duplicate = $criterion->find(\CCrmOwnerType::Undefined, 250);
			if($duplicate !== null)
			{
				$result[] = $duplicate;
			}
		}

		return $result;
	}

	/**
	 * Get ranking.
	 *
	 * @return ActualRanking
	 */
	public function getRanking()
	{
		if (!$this->ranking)
		{
			$this->ranking = new ActualRanking;
		}

		return $this->ranking;
	}

	/**
	 * Get duplicate list.
	 *
	 * @return Duplicate[]
	 */
	public function getDuplicates()
	{
		return $this->duplicates;
	}

	/**
	 * Get primary actual entity id.
	 *
	 * @return int|null
	 */
	public function getPrimaryId()
	{
		$entities = $this->getEntities();
		if (count($entities) == 0)
		{
			return null;
		}

		return $entities[0]['ENTITY_ID'];
	}

	/**
	 * Get primary actual entity type id.
	 *
	 * @return int|null
	 */
	public function getPrimaryTypeId()
	{
		$entities = $this->getEntities();
		if (count($entities) == 0)
		{
			return null;
		}

		return $entities[0]['ENTITY_TYPE_ID'];
	}

	/**
	 * Get id of responsible person of primary entity.
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
	 * Get actual lead id.
	 *
	 * @return int|null
	 */
	public function getLeadId()
	{
		return $this->get('leadId');
	}

	/**
	 * Get actual deal id.
	 *
	 * @return int|null
	 */
	public function getDealId()
	{
		return $this->get('dealId');
	}

	/**
	 * Get actual deal id.
	 *
	 * @return int[]
	 */
	public function getOrders()
	{
		return $this->get('orders') ?: [];
	}

	/**
	 * Get actual company id.
	 *
	 * @return int|null
	 */
	public function getCompanyId()
	{
		return $this->get('companyId');
	}

	/**
	 * Get deal id that found by actual company id.
	 *
	 * @return int|null
	 */
	public function getCompanyDealId()
	{
		return $this->get('companyDealId');
	}

	/**
	 * Get orders id that found by actual company id.
	 *
	 * @return int|null
	 */
	public function getCompanyOrders()
	{
		return $this->get('companyOrders');
	}

	/**
	 * Get lead(return customer type) id that found by actual company id.
	 *
	 * @return int|null
	 */
	public function getCompanyReturnCustomerLeadId()
	{
		return $this->get('companyReturnCustomerLeadId');
	}

	/**
	 * Get actual contact id.
	 *
	 * @return int|null
	 */
	public function getContactId()
	{
		return $this->get('contactId');
	}

	/**
	 * Get deal id that found by actual contact id.
	 *
	 * @return int|null
	 */
	public function getContactDealId()
	{
		return $this->get('contactDealId');
	}

	/**
	 * Get deal id that found by actual contact id.
	 *
	 * @return int|null
	 */
	public function getContactOrders()
	{
		return $this->get('contactOrders');
	}

	/**
	 * Get company id that found by actual contact id.
	 *
	 * @return int|null
	 */
	public function getContactCompanyId()
	{
		return $this->get('contactCompanyId');
	}

	/**
	 * Get lead(return customer type) id that found by actual contact id.
	 *
	 * @return int|null
	 */
	public function getContactReturnCustomerLeadId()
	{
		return $this->get('contactReturnCustomerLeadId');
	}

	/**
	 * Get actual lead(return customer type).
	 *
	 * @return int|null
	 */
	public function getReturnCustomerLeadId()
	{
		return $this->get('returnCustomerLeadId');
	}

	/**
	 * Return true if lead(return customer lead) can be created.
	 *
	 * @return bool
	 */
	public function canCreateLead()
	{
		return $this->canCreatePrimaryEntity();
	}

	/**
	 * Return true if primary entity can be created.
	 *
	 * @return bool
	 */
	public function canCreatePrimaryEntity()
	{
		return !$this->hasEntities();
	}

	/**
	 * Return true if deal can be created.
	 *
	 * @return bool
	 */
	public function canCreateDeal()
	{
		$entities = $this->getEntities();
		$entityCount = count($entities);

		// Actual entities not found
		if ($entityCount === 0)
		{
			return false;
		}

		// Actual lead found only
		if ($this->getLeadId() && $entityCount === 1)
		{
			return false;
		}

		// Deal found
		if ($this->getDealId())
		{
			return false;
		}

		// Lead(return customer type) found
		if ($this->getReturnCustomerLeadId())
		{
			return false;
		}

		return true;
	}

	/**
	 * Return true if lead(return customer lead) can be created.
	 *
	 * @return bool
	 */
	public function canCreateReturnCustomerLead()
	{
		$entities = $this->getEntities();
		$entityCount = count($entities);

		// Actual entities not found
		if ($entityCount === 0)
		{
			return false;
		}

		// Actual lead found only
		if ($this->getLeadId() && $entityCount === 1)
		{
			return false;
		}

		// Deal found
		if ($this->getDealId())
		{
			return false;
		}

		// Lead(return customer type) found
		if ($this->getReturnCustomerLeadId())
		{
			return false;
		}

		return true;
	}

	/**
	 * Return true if has exclusions.
	 *
	 * @return bool
	 */
	public function hasExclusions()
	{
		return $this->hasExclusions;
	}

	/**
	 * Check for exclusions.
	 *
	 * @return bool
	 */
	protected function checkForExclusions()
	{
		foreach ($this->getCriteria() as $criterion)
		{
			if (!($criterion instanceof DuplicateCommunicationCriterion))
			{
				continue;
			}

			$typeId = Communication\Type::resolveID($criterion->getCommunicationType());
			$code = $criterion->getValue();
			switch ($typeId)
			{
				case Communication\Type::PHONE:
				case Communication\Type::EMAIL:
					if (Exclusion\Store::has($typeId, $code))
					{
						$this->hasExclusions = true;
						return false;
					}
					break;
			}
		}

		return true;
	}
}