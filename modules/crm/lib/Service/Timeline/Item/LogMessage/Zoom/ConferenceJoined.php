<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Zoom;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDescription;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;

final class ConferenceJoined extends LogMessage
{
	public function getType(): string
	{
		return 'ConferenceJoined';
	}

	public function getIconCode(): ?string
	{
		return Icon::CAMERA;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_CONFERENCE_JOINED_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$data = $this->getAssociatedEntityModel()?->get('ZOOM_INFO') ?? [];
		if (empty($data['CONF_URL']))
		{
			return [
				'description' => (new Text())->setValue((string)$this->getAssociatedEntityModel()?->get('SUBJECT'))
			];
		}

		return [
			'description' => (new EditableDescription())
				->setText((string)$data['CONF_URL'])
				->setEditable(false)
				->setCopied(true)
		];
	}
}
