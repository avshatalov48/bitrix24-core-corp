<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsCode;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

class Conversion extends LogMessage
{
	protected const SWITCHABLE_ENTITY_TYPE_IDS = [
		CCrmOwnerType::Invoice,
		CCrmOwnerType::SmartInvoice,
		CCrmOwnerType::Quote,
	];

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
			case CCrmOwnerType::Lead:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_LEAD';
				break;
			case CCrmOwnerType::Deal:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_DEAL';
				break;
			case CCrmOwnerType::Contact:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_CONTACT';
				break;
			case CCrmOwnerType::Company:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_COMPANY';
				break;
			case CCrmOwnerType::Quote:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_QUOTE_MSGVER_1';
				break;
			case CCrmOwnerType::Invoice:
			case CCrmOwnerType::SmartInvoice:
				$locMessage = 'CRM_TIMELINE_CONVERSION_TITLE_INVOICE';
				break;
		}

		return Loc::getMessage($locMessage);
	}

	final public function getContentBlocks(): ?array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$result = [];

		$entities = $this->getHistoryItemModel()?->get('ENTITIES');
		$entityTypeLocks = $this->getEntityTypeLocks($entities);

		foreach($entities as $entityData)
		{
			$entityTypeId = (int)($entityData['ENTITY_TYPE_ID'] ?? 0);
			$entityId = (int)($entityData['ENTITY_ID'] ?? 0);

			if(CCrmOwnerType::IsDefined($entityTypeId))
			{
				CCrmOwnerType::TryGetEntityInfo($entityTypeId, $entityId, $entityInfo, false);

				$entityExists = ($entityInfo['SHOW_URL'] ?? '') !== '';
				$entityTitle = $entityExists ? (string)$entityInfo['TITLE'] : Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND');

				$key = CCrmOwnerType::ResolveName($entityTypeId). '_' . $entityId;
				$title = ContentBlockFactory::createTitle(CCrmOwnerType::GetDescription($entityTypeId));

				$result[$key . '_web'] = (new LineOfTextBlocks())
					->addContentBlock('title', $title)
					->addContentBlock(
						'value',
						ContentBlockFactory::createTextOrLink(
							$entityTitle,
							$this->getAction($entityTypeId, $entityInfo, $entityTypeLocks)
						)
					)
					->setScopeWeb()
				;

				$result[$key . '_mobile'] = (new LineOfTextBlocks())
					->addContentBlock('title', $title)
					->addContentBlock(
						'value',
						ContentBlockFactory::createTextOrLink(
							$entityTitle,
							$this->getActionFromEntityInfo($entityInfo)
						)
					)
					->setScopeMobile()
				;
			}
		}

		return $result;
	}

	private function getAction(int $entityTypeId, array $entityInfo, array $entityTypeLocks): ?Action
	{
		if (isset($entityTypeLocks[$entityTypeId]))
		{
			return new JsCode($entityTypeLocks[$entityTypeId]);
		}

		return $this->getActionFromEntityInfo($entityInfo);
	}

	private function getActionFromEntityInfo(array $entityInfo): ?Redirect
	{
		return (
			empty($entityInfo['SHOW_URL'])
				? null
				: new Redirect(new Uri($entityInfo['SHOW_URL']))
		);
	}

	private function getEntityTypeLocks(?array $entities): array
	{
		$entityTypeIds = array_unique(array_column($entities, 'ENTITY_TYPE_ID'));

		$toolsManager = Container::getInstance()->getIntranetToolsManager();
		$entityTypeLocks = [];
		$availabilityManager = AvailabilityManager::getInstance();

		foreach (self::SWITCHABLE_ENTITY_TYPE_IDS as $entityTypeId)
		{
			if (!in_array($entityTypeId, $entityTypeIds, true))
			{
				continue;
			}

			if ($toolsManager->checkEntityTypeAvailability($entityTypeId))
			{
				continue;
			}

			$entityTypeLocks[$entityTypeId] = $availabilityManager->getEntityTypeAvailabilityLock($entityTypeId);
		}

		return $entityTypeLocks;
	}
}
