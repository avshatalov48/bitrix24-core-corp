<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

class Conversion extends LogMessage
{
	public function getType(): string
	{
		return 'Conversion';
	}

	public function getIconCode(): ?string
	{
		return Icon::CONVERSION;
	}

	public function getTitle(): ?string
	{
		$sourceEntityTypeId = $this->getModel()->getAssociatedEntityTypeId();
		$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE';
		switch ($sourceEntityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_LEAD';
				break;
			case \CCrmOwnerType::Deal:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_DEAL';
				break;
			case \CCrmOwnerType::Contact:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_CONTACT';
				break;
			case \CCrmOwnerType::Company:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_COMPANY';
				break;
			case \CCrmOwnerType::Quote:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_QUOTE';
				break;
			case \CCrmOwnerType::Invoice:
			case \CCrmOwnerType::SmartInvoice:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_INVOICE';
				break;
		}

		return Loc::getMessage($locMessage);
	}

	final public function getContentBlocks(): ?array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$result = [];

		$entities = $this->getHistoryItemModel()->get('ENTITIES');

		foreach($entities as $entityData)
		{
			$entityTypeId = (int)($entityData['ENTITY_TYPE_ID'] ?? 0);
			$entityId = (int)($entityData['ENTITY_ID'] ?? 0);

			if(\CCrmOwnerType::IsDefined($entityTypeId))
			{
				\CCrmOwnerType::TryGetEntityInfo($entityTypeId, $entityId, $entityInfo, false);

				$entityExists = ($entityInfo['SHOW_URL'] ?? '') !== '';
				$entityTitle = $entityExists ? (string)$entityInfo['TITLE'] : Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND');

				$result[\CCrmOwnerType::ResolveName($entityTypeId). '_' . $entityId] = (new LineOfTextBlocks())
					->addContentBlock(
						'title',
						ContentBlockFactory::createTitle(\CCrmOwnerType::GetDescription($entityTypeId))
					)
					->addContentBlock(
						'value',
						ContentBlockFactory::createTextOrLink(
							$entityTitle,
							empty($entityInfo['SHOW_URL'])
								? null
								: new Redirect(new Uri($entityInfo['SHOW_URL']))
						)
					);
			}
		}

		return $result;
	}
}
