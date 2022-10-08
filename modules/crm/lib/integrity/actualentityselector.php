<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\ArgumentException;

use Bitrix\Crm;
use Bitrix\Crm\Exclusion;
use Bitrix\Crm\Communication;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Entity\Identificator;
use Bitrix\Crm\Binding\OrderContactCompanyTable;
use Bitrix\Main\SystemException;

/**
 * Class ActualEntitySelector
 * @package Bitrix\Crm\Integrity
 * @method int[] getCompanyOrders() Get company orders.
 * @method void setCompanyOrders(array $list) Set company orders.
 * @method int[] getCompanyDeals() Get company deals.
 * @method void setCompanyDeals(array $list) Set company deals.
 * @method int[] getCompanyDynamics() Get company dynamics.
 * @method void setCompanyDynamics(array $list) Set company dynamics.
 * @method int|null getCompanyDynamicId() Get company dynamic ID.
 * @method void setCompanyDynamicId($id) Set company dynamic ID.
 * @method int|null getCompanyDealId() Get company deal ID.
 * @method void setCompanyDealId($id) Set company deal ID.
 * @method int[] getCompanyReturnCustomerLeads() Get company return customer leads.
 * @method void setCompanyReturnCustomerLeads(array $list) Set company return customer leads.
 * @method int|null getCompanyReturnCustomerLeadId() Get company return customer lead ID.
 * @method void setCompanyReturnCustomerLeadId($id) Set company return customer lead ID.
 *
 * @method int|null getContactCompanyId() Get contact company ID.
 * @method void setContactCompanyId($id) Set contact company ID.
 * @method int[] getContactOrders() Get contact orders.
 * @method void setContactOrders(array $list) Set contact orders.
 * @method int[] getContactDynamics() Get contact dynamics.
 * @method void setContactDynamics(array $list) Set contact dynamics.
 * @method int|null getContactDynamicId() Get contact dynamic ID.
 * @method void setContactDynamicId($id) Set contact dynamic ID.
 * @method int[] getContactDeals() Get contact deals.
 * @method void setContactDeals(array $list) Set contact deals.
 * @method int|null getContactDealId() Get contact deal ID.
 * @method void setContactDealId($id) Set contact deal ID.
 * @method int[] getContactReturnCustomerLeads() Get contact return customer leads.
 * @method void setContactReturnCustomerLeads(array $list) Set contact return customer leads.
 * @method int|null getContactReturnCustomerLeadId() Get contact return customer lead ID.
 * @method void setContactReturnCustomerLeadId($id) Set contact return customer lead ID.
 *
 * @method int[] getContacts() Get contacts.
 * @method void setContacts(array $list) Set contacts.
 * @method int|null getContactId() Get contact ID.
 * @method void setContactId($id) Set contact ID.
 *
 * @method int[] getCompanies() Get companies.
 * @method void setCompanies(array $list) Set companies.
 * @method int|null getCompanyId() Get company ID.
 * @method void setCompanyId($id) Set company ID.
 *
 * @method int[] getLeads() Get leads.
 * @method void setLeads(array $list) Set leads.
 * @method int|null getLeadId() Get lead ID.
 * @method void setLeadId($id) Set lead ID.
 *
 * @method int[] getOrders() Get orders.
 * @method void setOrders(array $list) Set orders.
 *
 * @method int[] getDeals() Get deals.
 * @method void setDeals(array $list) Set deals.
 * @method int|null getDealId() Get deal ID.
 * @method void setDealId($id) Set deal ID.
 *
 * @method int[] getDynamics() Get dynamics.
 * @method void setDynamics(array $list) Set dynamics.
 * @method int|null getDynamicId() Get dynamic ID.
 * @method void setDynamicId($id) Set dynamic ID.
 *
 * @method int[] getReturnCustomerLeads() Get return customer leads.
 * @method void setReturnCustomerLeads(array $list) Set return customer leads.
 * @method int|null getReturnCustomerLeadId() Get return customer lead ID.
 * @method void setReturnCustomerLeadId($id) Set return customer lead ID.
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
			'CODE' => 'companies',
			'IS_PRIMARY' => true,
			'PRIMARY_SORT' => 40,
			'TYPE_ID' => \CCrmOwnerType::Company,
			'ID' => [],
		),
		array(
			'CODE' => 'companyOrders',
			'TYPE_ID' => \CCrmOwnerType::Order,
			'ID' => [],
		),
		array(
			'CODE' => 'companyDynamics',
			'TYPE_ID' => \CCrmOwnerType::Undefined,
			'ID' => [],
		),
		array(
			'CODE' => 'companyDeals',
			'TYPE_ID' => \CCrmOwnerType::Deal,
			'ID' => [],
		),
		array(
			'CODE' => 'companyReturnCustomerLeads',
			'TYPE_ID' => \CCrmOwnerType::Lead,
			'ID' => [],
		),
		array(
			'CODE' => 'contacts',
			'IS_PRIMARY' => true,
			'PRIMARY_SORT' => 10,
			'TYPE_ID' => \CCrmOwnerType::Contact,
			'ID' => [],
		),
		array(
			'CODE' => 'contactCompanies',
			'TYPE_ID' => \CCrmOwnerType::Company,
			'ID' => [],
		),
		array(
			'CODE' => 'contactOrders',
			'TYPE_ID' => \CCrmOwnerType::Order,
			'ID' => [],
		),
		array(
			'CODE' => 'contactDynamics',
			'TYPE_ID' => \CCrmOwnerType::Undefined,
			'ID' => [],
		),
		array(
			'CODE' => 'contactDeals',
			'TYPE_ID' => \CCrmOwnerType::Deal,
			'ID' => [],
		),
		array(
			'CODE' => 'contactReturnCustomerLeads',
			'TYPE_ID' => \CCrmOwnerType::Lead,
			'ID' => [],
		),
		array(
			'CODE' => 'dynamics',
			'IS_PRIMARY' => true,
			'TYPE_ID' => \CCrmOwnerType::Undefined,
			'ID' => [],
		),
		array(
			'CODE' => 'deals',
			'IS_PRIMARY' => true,
			'TYPE_ID' => \CCrmOwnerType::Deal,
			'ID' => [],
		),
		array(
			'CODE' => 'orders',
			'IS_PRIMARY' => true,
			'TYPE_ID' => \CCrmOwnerType::Order,
			'ID' => [],
		),
		array(
			'CODE' => 'leads',
			'IS_PRIMARY' => true,
			'PRIMARY_SORT' => 30,
			'TYPE_ID' => \CCrmOwnerType::Lead,
			'ID' => [],
		),
		array(
			'CODE' => 'returnCustomerLeads',
			'IS_PRIMARY' => true,
			'TYPE_ID' => \CCrmOwnerType::Lead,
			'ID' => [],
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
						break;
					}

					$criterion = new DuplicatePersonCriterion(
						$fields['LAST_NAME'],
						isset($fields['NAME']) ? $fields['NAME'] : '',
						isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : ''
					);
					$criterion->setStrictComparison(true);
					$criteria[] = $criterion;
					break;

				case self::SEARCH_PARAM_ORGANIZATION:
					if (!isset($fields['COMPANY_TITLE']) || !$fields['COMPANY_TITLE'])
					{
						break;
					}

					$criterion = new DuplicateOrganizationCriterion($fields['COMPANY_TITLE']);
					$criterion->setStrictComparison(true);
					$criteria[] = $criterion;
					break;

				case self::SEARCH_PARAM_EMAIL:
					if (!isset($fields['FM']))
					{
						break;
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
						break;
					}

					$values = DuplicateCommunicationCriterion::extractMultifieldsValues(
						$fields['FM'],
						\CCrmFieldMulti::PHONE
					);

					foreach ($values as $value)
					{
						$criteria[] = new DuplicateCommunicationCriterion(Communication\Type::PHONE_NAME, (string) $value);
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
		$this->fillDynamicEntityDictionary();
		$this->useFinishedLead(null);
		$this->initialEntities = $this->entities;
		$this->setCriteria($criteria);
		$this->search();
	}

	/**
	 * Magic method __call.
	 *
	 * @param string $name Method name.
	 * @param array $arguments Arguments.
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		$operation = mb_substr($name, 0, 3);
		if (!in_array($operation, ['get', 'set'], true))
		{
			throw new SystemException("Unknown method name `$name`");
		}

		$action = lcfirst(mb_substr($name, 3));
		if ($action)
		{
			return call_user_func_array([$this, $operation], array_merge([$action], $arguments));
		}

		throw new SystemException("Unknown method name `$name`");
	}

	/**
	 * Set dynamic type ID.
	 *
	 * @param int $entityTypeId Entity type ID.
	 * @return $this
	 */
	public function setDynamicTypeId($entityTypeId)
	{
		$entityTypeId = (int)$entityTypeId;
		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			foreach($this->entities as $index => $entity)
			{
				if (!in_array($entity['CODE'], ['dynamics', 'companyDynamics', 'contactDynamics']))
				{
					continue;
				}

				if ($entity['TYPE_ID'] === $entityTypeId)
				{
					continue;
				}

				$this->entities[$index]['TYPE_ID'] = $entityTypeId;
				$this->entities[$index]['ID'] = [];
			}
		}

		return $this;
	}


	/**
	 * Get dynamic type ID.
	 *
	 * @return int
	 */
	public function getDynamicTypeId()
	{
		return $this->getRawByCode('dynamics')['TYPE_ID'] ?? \CCrmOwnerType::Undefined;
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
		if (!trim($lastName) || !trim($name))
		{
			return $this;
		}

		$criterion = new DuplicatePersonCriterion($lastName, $name, $secondName);
		$criterion->setStrictComparison(true);
		$this->duplicateCriteria[] = $criterion;
		return $this;
	}

	/**
	 * Append person criterion.
	 *
	 * @param string $title Organization title.
	 * @return $this
	 */
	public function appendOrganizationCriterion($title)
	{
		$criterion = new DuplicateOrganizationCriterion($title);
		$criterion->setStrictComparison(true);
		$this->duplicateCriteria[] = $criterion;
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
	 * @internal
	 */
	public function get($code)
	{
		// prepare codes
		$expectSingle = false;
		$codes = [$code];
		if (mb_substr($code, -3) === 'yId')
		{
			$codes[] = mb_substr($code, 0, -3).'ies';
			$expectSingle = true;
		}
		else if (mb_substr($code, -2) === 'Id')
		{
			$codes[] = mb_substr($code, 0, -2).'s';
			$expectSingle = true;
		}

		// get value
		foreach ($this->entities as $entity)
		{
			if (in_array($entity['CODE'], $codes, true))
			{
				return ($expectSingle
					? (current(array_values($entity['ID'])) ?: null)
					: $entity['ID']
				);
			}
		}

		throw new ArgumentException("Code '{$code}' not existed.");
	}

	/**
	 * Get actual entity id by code.
	 *
	 * @param string $code Code.
	 * @param int|int[]|null $value Entity id.
	 * @param array $options $options.
	 * @throws ArgumentException
	 * @internal
	 */
	public function set($code, $value, array $options = [])
	{
		// prepare $valueMulti and $valueSingle
		if (is_array($value))
		{
			$value = array_map(
				function ($item)
				{
					return (int) $item;
				},
				$value
			);
			$value = array_filter(
				$value,
				function ($item)
				{
					return $item > 0;
				}
			);
		}
		else
		{
			$valueSingle = (int) $value;
			$valueSingle = $valueSingle > 0 ? $valueSingle : null;
			$value = $valueSingle ? [$valueSingle] : [];
		}

		// prepare $codes
		$codes = [$code];
		if (mb_substr($code, -3) === 'yId')
		{
			$codes[] = mb_substr($code, 0, -3).'ies';
		}
		elseif (mb_substr($code, -2) === 'Id')
		{
			$codes[] = mb_substr($code, 0, -2).'s';
		}

		// set value
		foreach ($this->entities as $index => $entity)
		{
			if (in_array($entity['CODE'], $codes, true))
			{
				$entity['ID'] = $value;
				if (isset($options['skipRanking']))
				{
					$entity['SKIP_RANKING'] = (bool) $options['skipRanking'];
				}
				if (isset($options['skipDuplicates']))
				{
					$entity['SKIP_DUPLICATES'] = (bool) $options['skipDuplicates'];
				}

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
	 * @internal
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

			foreach ($entity['ID'] as $entityId)
			{
				$sort = isset($entity['PRIMARY_SORT']) ? $entity['PRIMARY_SORT'] : 100;
				$sort += ++$num/100;
				$list[(string) $sort] = array(
					'ENTITY_TYPE_ID' => $entity['TYPE_ID'],
					'ENTITY_ID' => $entityId,
				);
			}
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
			$list[$entity->getTypeId()][] = $entity->getId();
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
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;
		$isEntityTypeDynamics = \CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId);

		foreach($this->entities as $entity)
		{
			if (empty($entity['IS_PRIMARY']))
			{
				continue;
			}

			if ($entity['TYPE_ID'] !== $entityTypeId)
			{
				if (!$isEntityTypeDynamics || $entity['CODE'] !== 'dynamics')
				{
					continue;
				}
			}

			// check with lead type
			if ($entityTypeId === \CCrmOwnerType::Lead)
			{
				$isCurrentLeadRC = $entity['CODE'] == 'returnCustomerLeads';
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
					if ($leadRow['CONTACT_ID'] && !$this->getContactId())
					{
						$this->setEntity(\CCrmOwnerType::Contact, $leadRow['CONTACT_ID'], true);
					}
					if ($leadRow['COMPANY_ID'] && !$this->getCompanyId())
					{
						$this->setEntity(\CCrmOwnerType::Company, $leadRow['COMPANY_ID'], true);
					}
				}

				$skipRanking = true;
			}
			elseif ($entityTypeId === \CCrmOwnerType::Deal)
			{
				$dealRows = DealTable::getList([
					'select' => ['ID', 'CONTACT_ID', 'COMPANY_ID'],
					'filter' => ['=ID' => $entityId],
					'limit' => 3
				]);
				foreach ($dealRows as $dealRow)
				{
					if ($dealRow['CONTACT_ID'] && !$this->getContactId())
					{
						$this->setEntity(\CCrmOwnerType::Contact, $dealRow['CONTACT_ID'], true);
					}
					if ($dealRow['COMPANY_ID'] && !$this->getCompanyId())
					{
						$this->setEntity(\CCrmOwnerType::Company, $dealRow['COMPANY_ID'], true);
					}
				}

				$skipRanking = true;
			}
			elseif ($entityTypeId === \CCrmOwnerType::Order)
			{
				$orderRows = OrderContactCompanyTable::getList([
					'select' => ['ENTITY_TYPE_ID', 'ENTITY_ID'],
					'filter' => [
						'=ORDER_ID' => $entityId,
						'=IS_PRIMARY' => 'Y',
						'=ENTITY_TYPE_ID' => [\CCrmOwnerType::Contact, \CCrmOwnerType::Company],
					],
					'limit' => 3
				]);
				foreach ($orderRows as $orderRow)
				{
					if ($orderRow['ENTITY_TYPE_ID'] == \CCrmOwnerType::Contact && !$this->getContactId())
					{
						$this->setEntity(\CCrmOwnerType::Contact, $orderRow['ENTITY_ID'], true);
					}
					if ($orderRow['ENTITY_TYPE_ID'] == \CCrmOwnerType::Company && !$this->getCompanyId())
					{
						$this->setEntity(\CCrmOwnerType::Company, $orderRow['ENTITY_ID'], true);
					}
				}

				$skipRanking = true;
			}
			elseif ($isEntityTypeDynamics)
			{
				$dynamicFactory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
				$dynamicItem = $dynamicFactory->getItem($entityId);
				if (!$dynamicItem)
				{
					continue;
				}

				$this->setDynamicTypeId($entityTypeId);
				if ($dynamicItem->getCompanyId())
				{
					$this->setEntity(\CCrmOwnerType::Company, $dynamicItem->getCompanyId(), true);
				}

				$count = 3;
				foreach ($dynamicItem->getContacts() as $contact)
				{
					$this->setEntity(\CCrmOwnerType::Contact, $contact->getId(), true);
					if (!--$count)
					{
						break;
					}
				}

				$skipRanking = true;
			}

			$this->set($entity['CODE'], $entityId,
				[
					'skipRanking' => $skipRanking,
					'skipDuplicates' => true,
				]
			);
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
			$list = array_merge($list, $entity['ID']);
		}

		// unique list
		return array_unique($list);
	}

	protected function rank($entityTypeId)
	{
		// actual ranking
		return $this->getRanking()
			->setDynamicTypeId($this->getDynamicTypeId())
			->rank(
				$entityTypeId,
				$this->getRankableList($entityTypeId),
				$this->canRank($entityTypeId)
			)
		;
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
	 * Use finished lead.
	 *
	 * @param bool|null $mode Mode.
	 * @return $this
	 */
	public function useFinishedLead($mode = null)
	{
		if (is_bool($mode))
		{
			$this->isAutoUsingFinishedLeadEnabled = $mode;
		}
		else
		{
			$leadSettings = LeadSettings::getCurrent();
			$this->isAutoUsingFinishedLeadEnabled = $leadSettings->isAutoUsingFinishedLeadEnabled() && $leadSettings->isEnabled();
		}

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

		if ($this->getCompanyDeals())
		{
			$this->setDeals($this->getCompanyDeals());
		}
		else if ($this->getContactDeals())
		{
			$this->setDeals($this->getContactDeals());
		}

		if (!empty($this->getCompanyOrders()))
		{
			$this->setOrders($this->getCompanyOrders());
		}
		else if (!empty($this->getContactOrders()))
		{
			$this->setOrders($this->getContactOrders());
		}

		if (!empty($this->getCompanyDynamics()))
		{
			$this->setDynamics($this->getCompanyDynamics());
		}
		else if (!empty($this->getContactDynamics()))
		{
			$this->setDynamics($this->getContactDynamics());
		}

		if ($this->getCompanyReturnCustomerLeads())
		{
			$this->setReturnCustomerLeads($this->getCompanyReturnCustomerLeads());
		}
		else if ($this->getContactReturnCustomerLeads())
		{
			$this->setReturnCustomerLeads($this->getContactReturnCustomerLeads());
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

		$this->setCompanyId($ranking->getEntityId());
		$this->setCompanyOrders($ranking->getOrders());

		if ($this->getDynamicTypeId())
		{
			$this->setCompanyDynamics($ranking->getDynamics());
		}

		if (!$this->getDeals() && !$this->getReturnCustomerLeadId())
		{
			// set only if ::setEntity with deals or rc-leads didn't use
			$this->setCompanyDeals($ranking->getDeals());
			$this->setCompanyReturnCustomerLeads($ranking->getLeads());
		}
	}

	protected function searchContact()
	{
		$ranking = $this->rank(\CCrmOwnerType::Contact);
		if (!$ranking->getEntityId())
		{
			return;
		}

		$contactId = $ranking->getEntityId();
		$this->setContactId($contactId);
		$this->setContactOrders($ranking->getOrders());

		if ($this->getDynamicTypeId())
		{
			$this->setContactDynamics($ranking->getDynamics());
		}

		if (!$this->getDeals() && !$this->getReturnCustomerLeadId())
		{
			// set only if ::setEntity with deals or rc-leads didn't use
			$this->setContactDeals($ranking->getDeals());
			$this->setContactReturnCustomerLeads($ranking->getLeads());
		}

		if ($contactId)
		{
			$contactDb = ContactTable::getList(array(
				'select' => ['COMPANY_ID'],
				'filter' => ['=ID' => $contactId]
			));
			if (($contact = $contactDb->fetch()) && $contact['COMPANY_ID'])
			{
				$this->setContactCompanyId((int) $contact['COMPANY_ID']);
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
			$this->setLeadId($leadId);
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

		$sortedCriteria = [];
		$hasCommCriterion = false;
		foreach ($this->duplicateCriteria as $index => $criterion)
		{
			$sort = 10000;
			if ($criterion instanceof DuplicateCommunicationCriterion)
			{
				$sort = 1000;
				$hasCommCriterion = true;
			}
			else if ($criterion instanceof DuplicatePersonCriterion)
			{
				$sort = 100000;
			}
			$sortedCriteria[$sort + $index] = $criterion;
		}
		ksort($sortedCriteria);

		$found = false;
		foreach ($sortedCriteria as $criterion)
		{
			if (($hasCommCriterion || $found) && $criterion instanceof DuplicatePersonCriterion)
			{
				if (!$criterion->getSecondName() || !$criterion->getName())
				{
					continue;
				}
			}

			$criterion->sortDescendingByEntityTypeId();
			$duplicate = $criterion->find(\CCrmOwnerType::Undefined, 250);
			if($duplicate !== null)
			{
				if ($duplicate->getEntityIDs())
				{
					$found = true;
				}

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

	protected function fillDynamicEntityDictionary()
	{

	}
}