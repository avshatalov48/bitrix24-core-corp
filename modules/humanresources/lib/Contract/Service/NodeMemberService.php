<?php

namespace Bitrix\HumanResources\Contract\Service;

use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Type;
use Bitrix\HumanResources\Type\MemberSubordinateRelationType;

interface NodeMemberService
{
	public const MEMBER_TO_MEMBER_SUBORDINATE_CACHE_KEY = 'node_member/member_from/%d/member_to/%d';

	public function getMemberInformation(int $memberId): Item\NodeMember;
	public function moveMember(Item\NodeMember $nodeMember, Item\Node $node): Item\NodeMember;

	/**
	 * Calculates relation between members with id $memberId and member with id $targetMemberId
	 * Simplified: Who is member for targetMember
	 *
	 * @param int $memberId
	 * @param int $targetMemberId
	 *
	 * @return MemberSubordinateRelationType
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getMemberSubordination(int $memberId, int $targetMemberId): Type\MemberSubordinateRelationType;
	public function getAllEmployees(
		int $nodeId,
		bool $withAllChildNodes = false,
		bool $onlyActive = true
	): Item\Collection\NodeMemberCollection;

	public function getPagedEmployees(
		int $nodeId,
		bool $withAllChildNodes = false,
		int $offset = 0,
		int $limit = 500,
		bool $onlyActive = true,
	):  Item\Collection\NodeMemberCollection;

	public function getDefaultHeadRoleEmployees(int $nodeId): Item\Collection\NodeMemberCollection;

	/**
	 * @param Item\NodeMember $nodeMember
	 *
	 * @return Item\NodeMember|null
	 */
	public function removeUserMemberFromDepartment(Item\NodeMember $nodeMember): ?Item\NodeMember;

	/**
	 * @param Item\Node $node
	 * @param array $departmentUserIds
	 *
	 * @return Item\Collection\NodeMemberCollection
	 */
	public function saveUsersToDepartment(Item\Node $node, array $departmentUserIds = []): Item\Collection\NodeMemberCollection;

	public function removeUserMembersFromDepartmentByCollection(
		Item\Collection\NodeMemberCollection $nodeMemberCollection,
	): Item\Collection\NodeMemberCollection;

	/**
	 * @param Item\Node $node
	 * @param array $departmentUserIds
	 *
	 * @return array
	 * @throws \Bitrix\HumanResources\Exception\CreationFailedException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\DuplicateEntryException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function moveUsersToDepartment(Item\Node $node, array $departmentUserIds = []): Item\Collection\NodeMemberCollection;
}