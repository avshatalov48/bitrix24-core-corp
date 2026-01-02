<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

class AttachmentCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return Attachment::class;
	}
}