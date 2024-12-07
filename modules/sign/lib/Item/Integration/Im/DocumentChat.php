<?php

namespace Bitrix\Sign\Item\Integration\Im;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\Integration\Im\DocumentChatType;

class DocumentChat implements Contract\Item
{
	public function __construct(
		public int              $documentId,
		public int              $chatId,
		public DocumentChatType $type,
		public ?int             $id = null,
	)
	{}
}