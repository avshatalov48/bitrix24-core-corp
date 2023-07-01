<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/../Ecommerce.php');

class OrderCheckCreationError extends LogMessage
{
	public function getType(): string
	{
		return 'OrderCheckCreationError';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_ENTITY_NAME');
	}

	public function getIconCode(): ?string
	{
		return Layout\Common\Icon::CHECK;
	}

	public function getTags(): ?array
	{
		return [
			'status' => new Layout\Header\Tag(
				Loc::getMessage('CRM_TIMELINE_ECOMMERCE_NOT_PRINTED'),
				Layout\Header\Tag::TYPE_FAILURE
			)
		];
	}

	public function getContentBlocks(): ?array
	{
		return [
			'content' => (new Text())->setValue(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CHECK_NOT_CREATED')),
		];
	}
}
