<?php

namespace Bitrix\HumanResources\Install\Agent;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Compatibility\Utils\OldStructureUtils;
use Bitrix\HumanResources\Enum\LoggerEntityType;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Item\Structure;
use Bitrix\HumanResources\Model\NodeMemberRole;
use Bitrix\HumanResources\Model\NodeMemberRoleTable;
use Bitrix\HumanResources\Model\NodeMemberTable;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Config;
use Bitrix\Main\Engine\CurrentUser;

class SyncDepartmentHeads
{
	private const LIMIT = 200;

	public static function run(int $lastDepartmentId = 0): string
	{
		if (!Config\Storage::instance()->isCompanyStructureConverted())
		{
			return self::finish();
		}

		$structure = Container::getStructureRepository()
			->getByXmlId(Structure::DEFAULT_STRUCTURE_XML_ID)
		;
		$headRoleId = Container::getRoleRepository()
			->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD'])?->id
		;
		$employeeRoleId = Container::getRoleRepository()
			->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['EMPLOYEE'])?->id
		;

		if (!$headRoleId || !$structure || !$employeeRoleId)
		{
			return self::finish();
		}

		$nodeRepository = Container::getNodeRepository();
		$nodeCollection = $nodeRepository->getAllPagedByStructureId($structure->id, self::LIMIT, $lastDepartmentId);
		if ($nodeCollection->empty())
		{
			return self::finish();
		}

		$linkedToOldStructureNodeCollection = $nodeCollection->filter(static fn(Item\Node $node) => !empty($node->accessCode));

		$oldDepartmentIds = array_values(array_map(
			static fn(Item\Node $node) => DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode),
			$linkedToOldStructureNodeCollection->getItemMap(),
		));

		try
		{
			$oldDepartments = OldStructureUtils::getListByIds($oldDepartmentIds);
		}
		catch (UpdateFailedException $exception)
		{
			$message = $exception->getMessage();
			Container::getStructureLogger()->write([
				'message' => "SyncDepartmentHeads::run($lastDepartmentId): $message",
			]);

			return self::restart($lastDepartmentId);
		}

		$oldDepartmentsWithHeads = array_filter(
			$oldDepartments,
			static fn($department) => ($department['UF_HEAD'] ?? null) !== null,
		);

		$oldDepartmentsHeadIds = array_values(array_map(
			static fn($department) => (int)$department['UF_HEAD'],
			$oldDepartmentsWithHeads,
		));

		if (empty($oldDepartmentsHeadIds))
		{
			return self::next($lastDepartmentId);
		}

		$affectedNodeCollection = $linkedToOldStructureNodeCollection->filter(
			static fn(Item\Node $node) => isset($oldDepartmentsWithHeads[DepartmentBackwardAccessCode::extractIdFromCode($node->accessCode)]),
		);

		$nodeIdToHeadIdMap = [];
		foreach ($affectedNodeCollection as $affectedNode)
		{
			$oldDepartmentWithHeads = $oldDepartmentsWithHeads[DepartmentBackwardAccessCode::extractIdFromCode($affectedNode->accessCode)] ?? null;
			if (empty($oldDepartmentWithHeads))
			{
				continue;
			}

			$nodeIdToHeadIdMap[$affectedNode->id] = $oldDepartmentWithHeads['UF_HEAD'] ?? 0;
		}

		$nodeMemberCollection = Container::getNodeMemberRepository()
			->findAllByEntityIdsAndEntityType($oldDepartmentsHeadIds, MemberEntityType::USER)
			->filter(
				static fn(Item\NodeMember $member) => $member->entityId === (int)($nodeIdToHeadIdMap[$member->nodeId] ?? 0)
			)
		;

		if ($nodeMemberCollection->empty())
		{
			return self::next($lastDepartmentId);
		}

		$memberIdsToBeHead = [];
		foreach ($nodeMemberCollection as $nodeMember)
		{
			$memberIdsToBeHead[] = $nodeMember->id;
		}

		if (empty($memberIdsToBeHead))
		{
			return self::next($lastDepartmentId);
		}

		$result = NodeMemberRoleTable::getList([
			'select' => ['ID'],
			'filter' => [
				'MEMBER_ID' => $memberIdsToBeHead,
			],
		]);

		$nodeMemberRoleIds = [];
		while ($row = $result->fetch())
		{
			if (empty($row['ID']))
			{
				continue;
			}

			$nodeMemberRoleIds[] = (int)$row['ID'];
		}

		$result = NodeMemberRoleTable::updateMulti($nodeMemberRoleIds, ['ROLE_ID' => $headRoleId]);
		if (!$result->isSuccess())
		{
			return self::restart($lastDepartmentId);
		}

		return self::next($lastDepartmentId);
	}

	private static function restart(int $lastDepartmentId): string
	{
		return "\\Bitrix\\HumanResources\\Install\\Agent\\SyncDepartmentHeads::run($lastDepartmentId);";
	}

	private static function next(int $lastDepartmentId): string
	{
		$nextId = $lastDepartmentId + self::LIMIT;
		return "\\Bitrix\\HumanResources\\Install\\Agent\\SyncDepartmentHeads::run($nextId);";
	}

	private static function finish(): string
	{
		\Bitrix\HumanResources\Compatibility\Adapter\StructureBackwardAdapter::clearCache();

		return '';
	}
}