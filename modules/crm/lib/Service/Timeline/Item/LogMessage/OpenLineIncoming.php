<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Integration\OpenLineManager;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use CCrmActivity;

class OpenLineIncoming extends LogMessage
{
	public function getType(): string
	{
		return 'OpenLineIncoming';
	}

	public function getIconCode(): ?string
	{
		return Icon::OPENLINE_INCOMING_MESSAGE;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_OL_INCOMING_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$userCode = $this->getAssociatedEntityModel()->get('PROVIDER_PARAMS')['USER_CODE'] ?? null;
		$connectorType = OpenLineManager::getLineConnectorType($userCode);

		$sourceList = [];
		$providerId = $this->getAssociatedEntityModel()->get('PROVIDER_ID');
		if ($providerId && $provider = CCrmActivity::GetProviderById($providerId))
		{
			$sourceList = $provider::getResultSources();
		}

		$channelName = empty($sourceList)
			? (string)$this->getAssociatedEntityModel()->get('TITLE')
			: $sourceList[$connectorType] ?? $sourceList['livechat'];

		return [
			'content' =>
				(new LineOfTextBlocks())
					->addContentBlock(
						'title',
						ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_LOG_OL_INCOMING_CHANNEL'))
					)
					->addContentBlock('data', (new Text())->setValue($channelName)->setColor(Text::COLOR_BASE_90))
		];
	}
}
