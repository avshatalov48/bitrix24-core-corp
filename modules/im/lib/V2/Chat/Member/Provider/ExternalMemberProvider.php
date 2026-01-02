<?php

declare(strict_types=1);

namespace Bitrix\Im\V2\Chat\Member\Provider;

use Bitrix\Im\V2\Chat\Member\MemberCursor;
use Bitrix\Main\ORM\Query\Query;

class ExternalMemberProvider extends MemberProvider
{
	protected function prepareQuery(Query $query, ?MemberCursor $cursor): void
	{
		parent::prepareQuery($query, $cursor);
		$externalMemberIds = $this->getExternalMemberIds();
		if ($externalMemberIds === null)
		{
			return;
		}

		$query->whereIn('USER_ID', $externalMemberIds);
	}

	public function getAllUserIds(): array
	{
		return $this->getExternalMemberIds() ?? parent::getAllUserIds();
	}

	protected function getExternalMemberIds(): ?array
	{
		return []; // todo: implement
	}
}
