<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

final class LaunchError extends Base
{
	public function getType(): string
	{
		return 'LaunchError';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TITLE');
	}

	public function getTags(): ?array
	{
		$settings = $this->getModel()->getSettings();
		if (empty($settings))
		{
			return null;
		}

		$statusTag = new Tag(Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TAG'), Tag::TYPE_FAILURE);
		$errorText = empty($settings['ERRORS']) ? '' : implode(PHP_EOL, $settings['ERRORS']);
		if (!empty($errorText))
		{
			$statusTag->setHint($errorText);
		}

		return [
			'error' => $statusTag,
		];
	}
}
