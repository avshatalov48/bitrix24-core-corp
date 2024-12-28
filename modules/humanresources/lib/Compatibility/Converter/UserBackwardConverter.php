<?php

namespace Bitrix\HumanResources\Compatibility\Converter;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Enum\LoggerEntityType;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Item\Node;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\EO_User;
use Bitrix\Main;
use CIntranetUtils;
use Bitrix\HumanResources\Type\MemberEntityType;

class UserBackwardConverter
{
	private readonly Contract\Repository\StructureRepository $structureRepository;
	private readonly Contract\Repository\NodeRepository $nodeRepository;
	private readonly Contract\Repository\NodeMemberRepository $nodeMemberRepository;
	private readonly Contract\Util\Logger $logger;

	public function __construct(
		?Contract\Repository\StructureRepository $structureRepository = null,
		?Contract\Repository\NodeMemberRepository $nodeMemberRepository = null,
		?Contract\Repository\NodeRepository $nodeRepository = null,
		?Contract\Util\Logger $logger = null,
	)
	{
		$this->structureRepository = $structureRepository ?? Container::getStructureRepository();
		$this->nodeRepository = $nodeMemberRepository ?? Container::getNodeRepository();
		$this->nodeMemberRepository = $nodeRepository ?? Container::getNodeMemberRepository();
		$this->logger = $logger ?? Container::getStructureLogger();
	}

	public function convert(int $userId): Main\Result
	{
		$result = new Main\Result();

		$userModel = $this->getUserModelById($userId);
		if (!$userModel)
		{
			return $result->addError(new Main\Error("User with id: $userId not found."));
		}

		$structure = $this->structureRepository->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID);
		if (!$structure)
		{
			return $result->addError(new Main\Error("Company structure not found."));
		}

		$userOldDepartmentsIds = CIntranetUtils::GetUserDepartments($userId);
		$oldDepartmentsAccessCodes = array_map(
			static fn(mixed $oldDepartmentId) => DepartmentBackwardAccessCode::makeById((int)$oldDepartmentId),
			$userOldDepartmentsIds
		);

		$allOldToNewDepartmentNodes = $this->nodeRepository->findAllByAccessCodes($oldDepartmentsAccessCodes);
		$userOldToNewDepartmentCollection = $this->getUserOldToNewDepartments(
			$userId,
			$structure->id,
			$oldDepartmentsAccessCodes
		);

		$missingOldToNewUserDepartmentNodes = $allOldToNewDepartmentNodes->filter(
			static fn(Item\Node $node) => $userOldToNewDepartmentCollection->getItemById($node->id) === null,
		);

		if ($missingOldToNewUserDepartmentNodes->empty())
		{
			return $result;
		}

		foreach ($missingOldToNewUserDepartmentNodes as $missingOldToNewUserDepartmentNode)
		{
			$result = $this->processMissingUserLinkNode(
				$missingOldToNewUserDepartmentNode,
				$userModel,
			);

			if (!$result->isSuccess())
			{
				$this->logger->write([
					'message' => "Employee convertation failure: " . implode(', ', $result->getErrorMessages()),
					'entityType' => LoggerEntityType::MEMBER_USER->name,
					'entityId' => $userId,
					'userId' => Main\Engine\CurrentUser::get()->getId(),
				]);

				return $result;
			}
		}

		return $result;
	}

	public function isConverted(int $userId): bool
	{
		$departments = CIntranetUtils::GetUserDepartments($userId);
		if (empty($departments))
		{
			return true;
		}

		array_walk(
			$departments,
			fn(&$department) => $department = DepartmentBackwardAccessCode::makeById((int)$department)
		);

		$currentLinks = $this->nodeMemberRepository->findAllByEntityIdAndEntityType(
			entityId: $userId,
			entityType: MemberEntityType::USER,
		);

		$nodes = $this->nodeRepository->findAllByAccessCodes($departments);

		$missingNodes = new Item\Collection\NodeCollection();
		foreach ($nodes as $node)
		{
			if ($currentLinks->getItemById($node->id) === null)
			{
				$missingNodes->add($node);
			}
		}

		return $missingNodes->empty();
	}

	/**
	 * @param Node $node
	 * @param EO_User $user
	 * @return Main\Result
	 */
	private function processMissingUserLinkNode(
		Item\Node $node,
		Main\EO_User $user,
	): Main\Result
	{
		$newNodeMember = new Item\NodeMember(
			entityType: MemberEntityType::USER,
			entityId: $user->getId(),
			nodeId: $node->id,
			active: $user->getActive(),
		);

		$result = new Main\Result();
		try
		{
			$this->nodeMemberRepository->create($newNodeMember);
		}
		catch (CreationFailedException $exception)
		{
			return $result->addErrors($exception->getErrors()->toArray());
		}

		return $result;
	}

	private function getUserModelById(int $userId): ?Main\EO_User
	{
		return Main\UserTable::query()
			->setSelect([
				'ID',
				'ACTIVE',
				]
			)
			->where('ID', $userId)
			->fetchObject()
		;
	}

	private function getUserOldToNewDepartments(int $userId, int $companyStructureId, array $accessCodes): Item\Collection\NodeCollection
	{
		$userNodes = $this->nodeRepository->findAllByUserId($userId);

		return $userNodes->filter(
			static fn(Item\Node $node) =>
				$node->structureId === $companyStructureId
				&& $node->type === NodeEntityType::DEPARTMENT
				&& in_array($node->accessCode, $accessCodes, true)
		);
	}
}