<?php

namespace Bitrix\Sign\Item\MyDocumentsGrid;


use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<Member>
 */
class MemberCollection extends Collection
{
	public function getItemClassName(): string
	{
		return Member::class;
	}
}