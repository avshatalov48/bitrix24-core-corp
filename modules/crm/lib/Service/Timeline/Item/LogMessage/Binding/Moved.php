<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Binding;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Timeline\LogMessageType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

final class Moved extends LogMessage
{
	public function getType(): string
	{
		return $this->getModel()->getAssociatedEntityTypeId() === CCrmOwnerType::Activity
			? 'Activity:Moved'
			: 'Moved'
		;
	}

	public function getTitle(): ?string
	{
		return match ($this->getModel()->getTypeCategoryId())
		{
			LogMessageType::CALL_MOVED => Loc::getMessage('CRM_TIMELINE_MOVED_CALL_TITLE'),
			LogMessageType::OPEN_LINE_MOVED => Loc::getMessage('CRM_TIMELINE_MOVED_OPENLINE_TITLE'),
			LogMessageType::EMAIL_INCOMING_MOVED => Loc::getMessage('CRM_TIMELINE_MOVED_EMAIL_INCOMING_TITLE'),
			default => Loc::getMessage('CRM_TIMELINE_MOVED_UNKNOWN_TITLE'),
		};
	}

	public function getContentBlocks(): ?array
	{
		$result = [];
		$moveFrom = $this->getModel()->getSettings()['FROM'] ?? null;
		$moveTo = $this->getModel()->getSettings()['TO'] ?? null;
		if ($moveFrom)
		{
			$moveBlock = $this->buildMoveBlock($moveFrom, Loc::getMessage('CRM_TIMELINE_MOVED_TO'));
		}
		elseif ($moveTo)
		{
			$moveBlock = $this->buildMoveBlock($moveTo, Loc::getMessage('CRM_TIMELINE_MOVED_FROM'));
		}

		if (isset($moveBlock))
		{
			$result['moveInfo'] = $moveBlock;
		}

		return $result;
	}

	private function buildMoveBlock(array $input, string $title): ?LineOfTextBlocks
	{
		$entityTypeId = $input['ENTITY_TYPE_ID'] ?? 0;
		$entityId = $input['ENTITY_ID'] ?? 0;
		if (!CCrmOwnerType::TryGetEntityInfo($entityTypeId, $entityId, $item, false))
		{
			return null;
		}

		if (!is_array($item))
		{
			return null;
		}

		$url = isset($item['SHOW_URL']) ? new Uri($item['SHOW_URL']) : null;

		return (new LineOfTextBlocks())
			->addContentBlock(
				'title',
				ContentBlockFactory::createTitle($title)
			)
			->addContentBlock(
				'data',
				ContentBlockFactory::createTextOrLink(
					(string)$item['TITLE'],
					$url ? new Redirect($url) : null
				)->setIsBold(true)
			)
		;
	}
}
