<?php

namespace Bitrix\HumanResources\Compatibility\Converter;

use Bitrix\HumanResources\Compatibility\Adapter\StructureBackwardAdapter;
use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Enum\LoggerEntityType;
use Bitrix\HumanResources\Exception\CompanyStructureNotFoundException;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\ElementNotFoundException;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Model\NodeBackwardAccessCodeTable;
use Bitrix\HumanResources\Repository\NodeRepository;
use Bitrix\HumanResources\Repository\RoleRepository;
use Bitrix\HumanResources\Repository\StructureRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Repository\NodeMemberRepository;
use Bitrix\HumanResources\Service\NodeService;
use Bitrix\HumanResources\Service\StructureWalkerService;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\HumanResources\Contract\Util\Logger;
use Bitrix\HumanResources\Config;
use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;

class StructureBackwardConverter
{
	private ?NodeService $nodeService;
	private ?NodeRepository $nodeRepository;
	private ?StructureRepository $structureRepository;
	private ?NodeMemberRepository $nodeMemberRepository;
	private ?StructureWalkerService $structureWalkerService;
	private ?RoleRepository $roleRepository;
	private Logger $logger;

	/** @var array<int>  */
	private array $newNodeMap = [];

	private array $nodesToComplete = [];

	private static ?int $structureId = null;
	private static array $roles;

	private const MODULE_NAME = 'humanresources';
	private const DEPUTY_HEAD_ROLE_NAME = 'DEPUTY_HEAD';

	public function __construct(
		?NodeService $nodeService = null,
		?StructureRepository $structureRepository = null,
		?NodeMemberRepository $nodeMemberRepository = null,
		?RoleRepository $roleRepository = null,
		?NodeRepository $nodeRepository = null,
		?StructureWalkerService $structureWalkerService = null,
		?Logger $logger = null,
	)
	{
		$this->nodeService = $nodeService ?? Container::getNodeService();
		$this->structureRepository = $structureRepository ?? Container::getStructureRepository();
		$this->nodeMemberRepository = $nodeMemberRepository ?? Container::getNodeMemberRepository();
		$this->roleRepository = $roleRepository ?? Container::getRoleRepository();
		$this->nodeRepository = $nodeRepository ?? Container::getNodeRepository();
		$this->structureWalkerService = $structureWalkerService ?? Container::getStructureWalkerService();
		$this->logger = $logger ?? Container::getStructureLogger();
	}

	/**
	 * @return bool
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function convert(): bool
	{
		$departmentTree = $this->getDepartmentTreeFromOldStructure();
		$this->disableEvents();
		try
		{
			if (!Config\Storage::instance()->isCompanyStructureConverted(false))
			{
				$this->installFixtures();
			}

			if (empty($departmentTree['DEPARTMENTS']))
			{
				return false;
			}

			foreach ($departmentTree['DEPARTMENTS'] as $department)
			{
				$this->processNode($department);
			}

			while (!empty($this->nodesToComplete))
			{
				$node = array_shift($this->nodesToComplete);
				$this->processNode($node);
			}

			$this->compareNodesToDeleteUnavailable($departmentTree['DEPARTMENTS']);

			$structure = $this->structureRepository->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);
			$rebuildResult = $this->structureWalkerService->rebuildStructure($structure->id);
			if (!$rebuildResult->isSuccess())
			{
				$this->logStructureConvertationFailure(
					reason: $rebuildResult->getErrors()[0]?->getMessage(),
				);

				return false;
			}

			$this->clearOldStructureCache();
		}
		catch (ElementNotFoundException)
		{
			$this->logStructureConvertationFailure();

			return false;
		}
		Config\Storage::instance()->setCompanyStructureConverted(true);

		return true;
	}

	private function installFixtures(): void
	{
		try
		{
			\Bitrix\HumanResources\Service\Container::getStructureRepository()->create(
				new \Bitrix\HumanResources\Item\Structure(
					name: \Bitrix\HumanResources\Type\StructureType::COMPANY->value,
					type: \Bitrix\HumanResources\Type\StructureType::COMPANY,
					xmlId: \Bitrix\HumanResources\Item\Structure::DEFAULT_STRUCTURE_XML_ID,
				)
			);

			$roleRepository = \Bitrix\HumanResources\Service\Container::getRoleRepository();
			$roleRepository->create(
				new \Bitrix\HumanResources\Item\Role(
					name: 'HEAD',
					xmlId: \Bitrix\HumanResources\Item\NodeMember::DEFAULT_ROLE_XML_ID['HEAD'],
					entityType: \Bitrix\HumanResources\Type\RoleEntityType::MEMBER,
					childAffectionType: \Bitrix\HumanResources\Type\RoleChildAffectionType::AFFECTING,
					priority: 100,
				),
			);
			$roleRepository->create(
				new \Bitrix\HumanResources\Item\Role(
					name: 'EMPLOYEE',
					xmlId: \Bitrix\HumanResources\Item\NodeMember::DEFAULT_ROLE_XML_ID['EMPLOYEE'],
					entityType: \Bitrix\HumanResources\Type\RoleEntityType::MEMBER,
					childAffectionType: \Bitrix\HumanResources\Type\RoleChildAffectionType::NO_AFFECTION,
					priority: 0,
				),
			);
			self::addDeputyHeadRole();
		}
		catch (\Bitrix\Main\DB\SqlQueryException | CreationFailedException)
		{}
	}

	/**
	 * @param array $oldDepartment
	 *
	 * @return void
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\HumanResources\Exception\ElementNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function processNode(array $oldDepartment): void
	{
		if (empty($oldDepartment))
		{
			return;
		}

		$parentId = $this->getParentId($oldDepartment);
		if ($parentId === null)
		{
			return;
		}

		$node = new Node(
			name: $oldDepartment['NAME'],
			type: NodeEntityType::DEPARTMENT,
			structureId: $this->getCompanyStructureId(),
			parentId: $parentId,
		);

		$foundedNode = $this->nodeRepository->getByAccessCode(
			DepartmentBackwardAccessCode::makeById((int)$oldDepartment['ID']),
		);

		if ($foundedNode)
		{
			if ($foundedNode->parentId !== $node->parentId)
			{
				$foundedNode->parentId = $node->parentId;
				$this->nodeRepository->update($foundedNode);
			}
			$this->newNodeMap[$oldDepartment['ID']] = $foundedNode->id;

			return;
		}

		$newNode = $this->nodeService->insertNode($node, false);

		if (!isset($this->newNodeMap[$oldDepartment['ID']]))
		{
			$this->newNodeMap[$oldDepartment['ID']] = $newNode->id;
		}

		$this->createBackwardAccessCode($newNode, $oldDepartment['ID']);
	}

	private function getDepartmentTreeFromOldStructure(): array
	{
		$this->clearOldStructureCache();
		$intranet = new \ReflectionClass(\CIntranetUtils::class);
		$refProp = $intranet->getProperty('SECTIONS_SETTINGS_CACHE');
		$refProp->setValue(null, []);
		$refProp = $intranet->getProperty('SECTIONS_SETTINGS_WITHOUT_EMPLOYEE_CACHE');
		$refProp->setValue(null, []);

		$info = [];
		$departments = \CIntranetUtils::GetStructureWithoutEmployees(false);
		foreach ($departments['DATA'] as $department)
		{
			$info['DEPARTMENTS'][$department['ID']] = $department;
		}

		return $info;
	}

	/**
	 * @return int|null
	 * @throws \Bitrix\HumanResources\Exception\ElementNotFoundException
	 */
	public function getCompanyStructureId(): ?int
	{
		if (self::$structureId)
		{
			return self::$structureId;
		}

		$structure = $this->structureRepository->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);
		if ($structure)
		{
			self::$structureId = $structure->id;
		}
		else
		{
			throw new ElementNotFoundException('Structure not found');
		}

		return self::$structureId;
	}

	/**
	 * @throws \Bitrix\HumanResources\Exception\ElementNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function detectRole(int $head, int $employee): int
	{
		$roleXmlId = $head === $employee ? NodeMember::DEFAULT_ROLE_XML_ID['HEAD']
				: NodeMember::DEFAULT_ROLE_XML_ID['EMPLOYEE'];

		if (isset(self::$roles[$roleXmlId]))
		{
			return self::$roles[$roleXmlId];
		}
		$role = $this->roleRepository->findByXmlId($roleXmlId);

		if (!$role)
		{
			throw new ElementNotFoundException('Role not found');
		}
		self::$roles[$roleXmlId] = $role->id;

		return $role->id;
	}

	/**
	 * @param \Bitrix\HumanResources\Item\Node $newNode
	 * @param int $oldNodeId
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createBackwardAccessCode(Node $newNode, int $oldNodeId): void
	{
		if ($newNode->accessCode || !$newNode->id)
		{
			return;
		}

		$existed = NodeBackwardAccessCodeTable::query()
			->addSelect('ACCESS_CODE')
			->where('NODE_ID', $newNode->id)
			->setLimit(1)
			->exec()
			->fetch();

		if ($existed)
		{
			$newNode->accessCode = $existed['ACCESS_CODE'];
			return;
		}
		$newNode->accessCode = DepartmentBackwardAccessCode::makeById($oldNodeId);

		$existed = NodeBackwardAccessCodeTable::query()
			->addSelect('ACCESS_CODE')
			->where('ACCESS_CODE', $newNode->accessCode)
			->setLimit(1)
			->exec()
			->fetchObject()
		;

		if ($existed?->getAccessCode())
		{
			$newNode->accessCode = $existed['ACCESS_CODE'];
			$existed
				->setNodeId($newNode->id)
				->save()
			;

			return;
		}

		$nodeBackwardCode = NodeBackwardAccessCodeTable::getEntity()->createObject();
		$nodeBackwardCode
			->setNodeId($newNode->id)
			->setAccessCode($newNode->accessCode)
			->save()
		;
	}

	/**
	 * @return void
	 */
	public function clearOldStructureCache(): void
	{
		$ibDept = \COption::GetOptionInt('intranet', 'iblock_structure', false);
		if ($ibDept <= 0)
			return;

		$cacheDir = '/intranet/structure';

		$obCache = new \CPHPCache();
		$obCache->CleanDir($cacheDir);

		$cacheDir = '/intranet/structure/branches';
		$obCache->CleanDir($cacheDir);
	}

	/**
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function compareNodesToDeleteUnavailable(array $oldStruct): void
	{
		$newStruct = StructureBackwardAdapter::getStructureWithoutEmployee();

		foreach ($newStruct['DATA'] as $struct)
		{
			if (!isset($oldStruct[$struct['ID']]))
			{
				$this->logger->write([
					'entityType' => LoggerEntityType::STRUCTURE->name,
					'entityId' => (int)$struct['ID'],
					'message' => 'compareNodesToDeleteUnavailable: not found old structure with ID',
					'userId' => CurrentUser::get()->getId(),
				]);

				return;
			}
		}
	}

	private function getParentId(array $oldDepartment): ?int
	{
		$oldStructParentId = (int) $oldDepartment['IBLOCK_SECTION_ID'] ?? null;
		if ($oldStructParentId === 0)
		{
			return $oldStructParentId;
		}

		$parentId = $this->newNodeMap[$oldStructParentId] ?? null;

		if ($parentId !== null && !isset($this->newNodeMap[$oldStructParentId]))
		{
			$found = null;
			try
			{
				$found = $this->nodeRepository->getByAccessCode(
					DepartmentBackwardAccessCode::makeById($oldStructParentId),
				);
			}
			catch (\Exception)
			{
			}
			if (!$found)
			{
				$this->nodesToComplete[$oldDepartment['ID']] = $oldDepartment;
				return null;
			}

			return $found->id;
		}

		return $parentId;
	}

	private function disableEvents()
	{
		Container::getEventSenderService()->removeEventHandlers(
			self::MODULE_NAME,
			\Bitrix\HumanResources\Enum\EventName::NODE_ADDED->name,
		);
		Container::getEventSenderService()->removeEventHandlers(
			self::MODULE_NAME,
			\Bitrix\HumanResources\Enum\EventName::NODE_UPDATED->name,
		);
		Container::getEventSenderService()->removeEventHandlers(
			self::MODULE_NAME,
			\Bitrix\HumanResources\Enum\EventName::MEMBER_ADDED->name,
		);
		Container::getEventSenderService()->removeEventHandlers(
			self::MODULE_NAME,
			\Bitrix\HumanResources\Enum\EventName::MEMBER_DELETED->name,
		);
	}

	private function logStructureConvertationFailure(?string $reason = ""): void
	{
		$message = 'Failed to convert Structure';
		if (!empty($reason))
		{
			$message .= ": ${reason}";
		}

		$this->logger->write([
			'message' => $message,
			'userId' => CurrentUser::get()->getId(),
		]);
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return bool
	 * @throws \Bitrix\HumanResources\Exception\CompanyStructureNotFoundException
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\HumanResources\Exception\ElementNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function moveEmployeesToDepartments(int $limit = 20, int $offset = 0): bool
	{
		$this->disableEvents();

		$structureId = $this->getCompanyStructureId();

		if (!$structureId)
		{
			throw new CompanyStructureNotFoundException();
		}

		$nodes = Container::getNodeRepository()->getAllPagedByStructureId($structureId, $limit, $offset);

		if ($nodes->empty())
		{
			Config\Storage::instance()->setEmployeeTransferred(true);

			return false;
		}

		foreach ($nodes as $node)
		{
			$oldDepartmentId = DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode);

			if (!$oldDepartmentId)
			{
				continue;
			}

			$oldDepartment = $this->departmentGet($oldDepartmentId);

			if (!$oldDepartment || !isset($oldDepartment['ID']))
			{
				continue;
			}

			$employees = \CIntranetUtils::getDepartmentEmployees(
				arDepartments: $oldDepartment['ID'],
				onlyActive: 'N',
				arSelect: ['ID', 'ACTIVE'],
			);

			$ufHead = $oldDepartment['UF_HEAD'] ?? 0;

			while ($employee = $employees->Fetch())
			{
				$employeeId = (int)($employee['ID'] ?? 0);
				if (!$employeeId)
				{
					continue;
				}

				try
				{
					$nodeMember = new NodeMember(
						entityType: MemberEntityType::USER,
						entityId: $employeeId,
						nodeId: $node->id,
						active: $employee['ACTIVE'] === 'Y',
						role: $this->detectRole((int)$ufHead, $employeeId),
					);

					$existed = $this->nodeMemberRepository->findByEntityTypeAndEntityIdAndNodeId(
						$nodeMember->entityType,
						$nodeMember->entityId,
						$nodeMember->nodeId,
					);

					if ($existed)
					{
						continue;
					}

 					$this->nodeMemberRepository->create($nodeMember);
				}
				catch (DuplicateEntryException | SqlQueryException)
				{}
			}
		}

		return true;
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return bool
	 * @throws \Bitrix\HumanResources\Exception\CompanyStructureNotFoundException
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\HumanResources\Exception\ElementNotFoundException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function updateInactiveEmployees(int $limit = 20, int $offset = 0): bool
	{
		$this->disableEvents();

		$structureId = $this->getCompanyStructureId();

		if (!$structureId)
		{
			throw new CompanyStructureNotFoundException();
		}

		$nodes = Container::getNodeRepository()->getAllPagedByStructureId($structureId, $limit, $offset);

		if ($nodes->empty())
		{
			return false;
		}
		$employeesToUpdate = [];

		foreach ($nodes as $node)
		{
			$employees = Container::getNodeMemberService()->getAllEmployees(
				nodeId: $node->id,
				onlyActive: false,
			)->filter(static fn ($nodeMember) => $nodeMember->active === false );


			foreach ($employees as $employee)
			{
				$user = \CUser::GetByID($employee->entityId)->Fetch();

				if ($user['ACTIVE'] === 'Y')
				{
					$employeesToUpdate[$employee->entityId] = $employee->entityId;
				}
			}
		}

		if (!empty($employeesToUpdate))
		{
			Container::getNodeMemberRepository()->setActiveByEntityTypeAndEntityIds(
				MemberEntityType::USER,
				$employeesToUpdate,
				true,
			);
		}

		return true;
	}

	private function departmentGet(int $departmentId): array | false
	{
		if (Loader::includeModule('iblock') && $departmentId > 0)
		{
			$dbRes = \CIBlockSection::GetList(
				[],
				[
					'ID' => $departmentId,
					'IBLOCK_ID' => self::getDeptIblock(),
				],
				false,
				[
					'ID',
					'UF_HEAD',
				],
			);
			return $dbRes->Fetch();
		}

		return false;
	}

	protected static function getDeptIblock()
	{
		return \COption::GetOptionInt('intranet', 'iblock_structure', 0);
	}

	/**
	 * @return string
	 */
	public static function startDefaultConverting(): string
	{
		$converterScript =
			'Bitrix\HumanResources\Compatibility\Converter\StructureBackwardConverter::startDefaultConverting();';
		try
		{
			if (
				!Container::getStructureBackwardConverter()
					->convert()
			)
			{
				return $converterScript;
			}
		}
		catch (\Throwable $e)
		{
			AddMessage2Log($e->getMessage(), self::MODULE_NAME);

			return $converterScript;
		}

		return 'Bitrix\HumanResources\Compatibility\Converter\StructureBackwardConverter::moveEmployees();';
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return string
	 */
	public static function moveEmployees(int $limit = 20, int $offset = 0): string
	{
		try
		{
			if (
				Container::getStructureBackwardConverter()
					->moveEmployeesToDepartments($limit, $offset)
			)
			{
				$offset += $limit;
				\CAgent::AddAgent(
					name: "Bitrix\\HumanResources\\Compatibility\\Converter\\StructureBackwardConverter::moveEmployees($limit, $offset);",
					module: self::MODULE_NAME,
					interval: 60,
					existError: false,
				);

				return '';
			}
		}
		catch (\Throwable $e)
		{
			Container::getStructureLogger()->write([
				'entityType' => LoggerEntityType::STRUCTURE->name,
				'entityId' => 0,
				'message' => $e->getMessage(),
			]);

			return "Bitrix\\HumanResources\\Compatibility\\Converter\\StructureBackwardConverter::moveEmployees($limit, $offset);";
		}

		return '';
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return string
	 */
	public static function checkInactiveEmployees(int $limit = 20, int $offset = 0): string
	{
		try
		{
			if (
				Container::getStructureBackwardConverter()
					->updateInactiveEmployees($limit, $offset)
			)
			{
				$offset += $limit;
				\CAgent::AddAgent(
					name: "Bitrix\\HumanResources\\Compatibility\\Converter\\StructureBackwardConverter::checkInactiveEmployees($limit, $offset);",
					module: self::MODULE_NAME,
					interval: 60,
					existError: false,
				);

				return '';
			}
		}
		catch (\Throwable $e)
		{
			Container::getStructureLogger()->write([
				'entityType' => LoggerEntityType::STRUCTURE->name,
				'entityId' => 0,
				'message' => $e->getMessage(),
			]);

			return "Bitrix\\HumanResources\\Compatibility\\Converter\\StructureBackwardConverter::checkInactiveEmployees($limit, $offset);";
		}

		return '';
	}

	public static function installDeputyHeadRole(): string
	{
		try
		{
			self::addDeputyHeadRole();
		}
		catch (\Bitrix\Main\DB\SqlQueryException | CreationFailedException)
		{
			return 'Bitrix\HumanResources\Compatibility\Converter\StructureBackwardConverter::installDeputyHeadRole();';
		}

		return '';
	}

	private static function addDeputyHeadRole(): void
	{
		$roleRepository = Container::getRoleRepository();
		$role = $roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['DEPUTY_HEAD']);
		if (!$role)
		{
			$roleRepository->create(
				new \Bitrix\HumanResources\Item\Role(
					name: self::DEPUTY_HEAD_ROLE_NAME,
					xmlId: NodeMember::DEFAULT_ROLE_XML_ID[self::DEPUTY_HEAD_ROLE_NAME],
					entityType: \Bitrix\HumanResources\Type\RoleEntityType::MEMBER,
					childAffectionType: \Bitrix\HumanResources\Type\RoleChildAffectionType::AFFECTING,
					priority: 90,
				),
			);
		}
	}

	public function reSyncStructureTree(): void
	{
		$this->disableEvents();

		$structure = Container::getStructureRepository()->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);

		if (!$structure)
		{
			return;
		}

		$oldStructure = \CIntranetUtils::GetStructureWithoutEmployees(false);

		foreach ($oldStructure['DATA'] as $department)
		{
			$parent =
				$this->checkParent(
					$department['IBLOCK_SECTION_ID'],
					$oldStructure,
					$structure
				);

			$node = Container::getNodeRepository()->getByAccessCode(
				DepartmentBackwardAccessCode::makeById((int)$department['ID']),
			);

			if (!$node)
			{
				$node = Container::getNodeService()->insertNode(
					new Node(
						name: $department['NAME'],
						type: NodeEntityType::DEPARTMENT,
						structureId: $structure->id,
						parentId: $parent?->id,
					),
				);
				$this->createBackwardAccessCode($node, (int)$department['ID']);

				continue;
			}

			$node->parentId = $parent?->id;
			$node->name = $department['NAME'];

			try
			{
				Container::getNodeRepository()->update($node);
			}
			catch (\Exception $e)
			{
				Container::getStructureLogger()->write([
					'entityType' => LoggerEntityType::STRUCTURE->name,
					'entityId' => $structure->id,
					'message' => 'Failed to rebuild structure',
					'userId' => CurrentUser::get()->getId(),
				]);
			}
		}

		$rebuildResult = Container::getStructureWalkerService()->rebuildStructure($structure->id);
		if (!$rebuildResult->isSuccess())
		{
			Container::getStructureLogger()->write([
				'entityType' => LoggerEntityType::STRUCTURE->name,
				'entityId' => $structure->id,
				'message' => 'Failed to rebuild structure',
				'userId' => CurrentUser::get()->getId(),
			]);
		}
	}

	/**
	 * @param $oldDepartmentId
	 * @param $oldStructure
	 * @param Structure|null $structure
	 *
	 * @return Node|null
	 * @throws CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function checkParent($oldDepartmentId, $oldStructure, ?Structure $structure): ?Node
	{
		$parent = Container::getNodeRepository()->getByAccessCode(
			DepartmentBackwardAccessCode::makeById((int)$oldDepartmentId),
		);

		if (!$parent && (int)$oldDepartmentId > 0)
		{
			$ownParentId = $oldStructure['DATA'][$oldDepartmentId]['IBLOCK_SECTION_ID'];
			$ownParent = $this->checkParent(
				$ownParentId,
				$oldStructure,
				$structure,
			);

			$currentDepartment = $oldStructure['DATA'][$oldDepartmentId];
			$parent = Container::getNodeService()->insertNode(
				new Node(
					name: $currentDepartment['NAME'],
					type: NodeEntityType::DEPARTMENT,
					structureId: $structure->id,
					parentId: $ownParent?->id,
				),
			);

			$this->createBackwardAccessCode($parent, (int)$oldDepartmentId);
		}

		return $parent;
	}
}
