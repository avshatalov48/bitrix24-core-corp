<?php

declare(strict_types=1);

namespace Bitrix\Im\V2\Chat\Member\Provider;

use Bitrix\Im\Model\RelationTable;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Chat\Member\MemberCollection;
use Bitrix\Im\V2\Chat\Member\MemberCursor;
use Bitrix\Im\V2\Common\ContextCustomer;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Query\Query;

class MemberProvider
{
	use ContextCustomer;

	public const ROLE_PRIORITY_MAP = [
		Chat::ROLE_OWNER => 1,
		Chat::ROLE_MANAGER => 2,
		Chat::ROLE_MEMBER => 3,
	];

	protected Chat $chat;

	public function __construct(int $chatId)
	{
		$this->chat = Chat::getInstance($chatId);
	}

	public function get(int $limit = 50, ?MemberCursor $cursor = null): MemberCollection
	{
		$query = RelationTable::query()
			->setSelect(['ID', 'USER_ID', 'ROLE'])
			->where('CHAT_ID', (int)$this->chat->getId())
			->setOrder(['ROLE_PRIORITY', 'ID'])
			->setLimit($limit)
		;

		$this->prepareQuery($query, $cursor);
		$rawResult = $query->fetchAll();

		return MemberCollection::initByRawResult($rawResult);
	}

	public function getAllUserIds(): array
	{
		return $this->chat->getRelations()->getUserIds();
	}

	protected function prepareQuery(Query $query, ?MemberCursor $cursor): void
	{
		$this->defineRoles($query);
		if ($cursor)
		{
			$this->applyCursor($query, $cursor);
		}
	}

	protected function defineRoles(Query $query): void
	{
		$chatAuthorId = (int)$this->chat->getAuthorId();
		$ownerRole = Chat::ROLE_OWNER;
		$managerRole = Chat::ROLE_MANAGER;
		$memberRole = Chat::ROLE_MEMBER;
		$rolePriorityMap = self::ROLE_PRIORITY_MAP;

		$roleField = new ExpressionField(
			'ROLE',
			"CASE
				WHEN %s = {$chatAuthorId} THEN '{$ownerRole}'
				WHEN %s = 'Y' THEN '{$managerRole}'
				ELSE '{$memberRole}'
			END",
			['USER_ID', 'MANAGER']
		);
		$query->registerRuntimeField('ROLE', $roleField);

		$rolePriorityField = new ExpressionField(
			'ROLE_PRIORITY',
			"CASE
				WHEN %s = '{$ownerRole}' THEN {$rolePriorityMap[$ownerRole]}
				WHEN %s = '{$managerRole}' THEN {$rolePriorityMap[$managerRole]}
				ELSE {$rolePriorityMap[$memberRole]}
			END",
			['ROLE', 'ROLE']
		);
		$rolePriorityField->configureValueType(IntegerField::class);
		$query->registerRuntimeField($rolePriorityField);
	}

	protected function applyCursor(Query $query, MemberCursor $cursor): void
	{
		$rolePriority = self::ROLE_PRIORITY_MAP[$cursor->role];
		$filter = Query::filter()
			->logic('or')
			->where('ROLE_PRIORITY', '>', $rolePriority)
			->where(
				Query::filter()
					->where('ROLE_PRIORITY', $rolePriority)
					->where('ID', '>', $cursor->relationId)
			)
		;
		$query->where($filter);
	}
}
