<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Mixin\FileListPreparer;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\CalendarLogo;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Audio;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

final class Visit extends Activity
{
	use FileListPreparer;

	protected function getActivityTypeId(): string
	{
		return 'Visit';
	}

	public function getIconCode(): ?string
	{
		return Common\Icon::VISIT;
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_ITEM_VISIT_TITLE');
	}
	public function getLogo(): ?Logo
	{
		$recordUrl = $this->getRecordUrl();
		if ($recordUrl)
		{
			$changePlayerStateAction = (new JsEvent('Activity:Visit:ChangePlayerState'))
				->addActionParamInt('recordId', $this->getAssociatedEntityModel()?->get('ID'))
				->addActionParamString('recordUri', $recordUrl)
			;

			return Common\Logo::getInstance(Common\Logo::CALL_PLAY_RECORD)
				->createLogo()
				?->setAction($changePlayerStateAction)
			;
		}

		// no record - show default logo with calendar icon
		$deadline = $this->getDeadline();
		if (!$deadline)
		{
			return null;
		}

		return (new CalendarLogo($deadline));
	}

	public function getContentBlocks(): array
	{
		$recordUrl = $this->getRecordUrl();
		if (!$recordUrl)
		{
			return [
				'emptyState' => (new Text())
					->setValue(Loc::getMessage('CRM_TIMELINE_ITEM_VISIT_EMPTY_STATE'))
					->setColor(Text::COLOR_BASE_50)
					->setFontWeight(Text::FONT_WEIGHT_MEDIUM)
			];
		}

		return [
			'audio' => (new Audio())
				->setId($this->getAssociatedEntityModel()?->get('ID'))
				->setSource($recordUrl)
		];
	}

	public function getButtons(): array
	{
		$buttons = parent::getButtons();
		$buttons['scheduleButton'] = $this->getScheduleButton('Activity:Visit:Schedule');

		return $buttons;
	}

	public function getTags(): ?array
	{
		$tags = [];
		if (!$this->getRecordUrl())
		{
			$tags['errorRecord'] = new Tag(
				Loc::getMessage('CRM_TIMELINE_ITEM_VISIT_TAG_ERROR_RECORD'),
				Tag::TYPE_FAILURE
			);
		}

		return $tags;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();
		unset($items['view']);

		return $items;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function getRecordUrl(): ?string
	{
		$recordUrls = array_unique(array_column($this->fetchAudioRecordList(), 'VIEW_URL'));

		return $recordUrls ? (string)$recordUrls[0] : null;
	}
}
