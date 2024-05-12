<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

final class Restoration extends LogMessage
{
	public function getType(): string
	{
		return 'Restoration';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_RESTORATION_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::RESTORATION;
	}

	public function getContentBlocks(): ?array
	{
		$entityTypeId = $this->getModel()->getAssociatedEntityTypeId();
		if (!CCrmOwnerType::IsDefined($entityTypeId))
		{
			return null;
		}

		$entityDescription = ContentBlockFactory::createTitle(CCrmOwnerType::GetDescription($entityTypeId));
		$entityTitle = $this->getAssociatedEntityModel()?->get('TITLE') ?? Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND');
		$entityUrl = $this->getAssociatedEntityModel()?->get('SHOW_URL');

		$result['content'] = (new LineOfTextBlocks())
			->addContentBlock('title', $entityDescription)
			->addContentBlock(
				'value',
				ContentBlockFactory::createTextOrLink(
					$entityTitle,
					$entityUrl ? new Redirect(new Uri($entityUrl)) : null
				)
			)
		;

		return $result;
	}
}
