<?php

namespace Bitrix\Sign\Item\MyDocumentsGrid;

use Bitrix\Sign\Contract;

use Bitrix\Sign\Type\MyDocumentsGrid\Action;

class Row implements Contract\Item
{
	public function __construct(
		public int $id,
		public Document $document,
		public MemberCollection $members,
		public Member $myMemberInProcess,
		public ?File $file,
		public ?Action $action,
	) {}
}