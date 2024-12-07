<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Integration\Zoom\Conference;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDescription;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\FileList;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Model\File;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

Container::getInstance()->getLocalization()->loadMessages();

final class Zoom extends Activity
{
	private ?array $conferenceInfo = null;
	private array $audioRecords = [];
	private array $videoRecords = [];
	private array $downloadUrlList = [];

	protected function getActivityTypeId(): string
	{
		return 'Zoom';
	}

	public function getIconCode(): ?string
	{
		return Common\Icon::CAMERA;
	}

	public function getTitle(): string
	{
		return Loc::getMessage(
			$this->isScheduled()
				? 'CRM_TIMELINE_ITEM_ZOOM_TITLE_START'
				: 'CRM_TIMELINE_ITEM_ZOOM_TITLE_END',
		);
	}

	public function getLogo(): ?Logo
	{
		return Common\Logo::getInstance(Common\Logo::ZOOM)
			->createLogo()
			?->setAdditionalIconCode('cloud')
		;
	}

	public function getContentBlocks(): array
	{
		$result['description'] = $this->buildDescriptionBlock();

		$filesBlock = $this->buildFilesBlock();
		if (isset($filesBlock))
		{
			$result['fileList'] = $filesBlock;
		}

		if (!empty($this->videoRecords[0]['PASSWORD']))
		{
			$result['zoomPasswordCopy'] = (new ContentBlock\Link())
				->setValue(Loc::getMessage('CRM_TIMELINE_ITEM_ZOOM_ACTION_COPY_PASSWORD'))
				->setColor(ContentBlock\Text::COLOR_BASE_90)
				->setDecoration(ContentBlock\Text::DECORATION_DASHED)
				->setIcon('copy')
				->setAction(
					(new JsEvent('Activity:Zoom:CopyPassword'))
						->addActionParamString('password', $this->videoRecords[0]['PASSWORD'])
				)
			;
		}

		return $result;
	}

	public function getButtons(): array
	{
		$buttons = parent::getButtons();

		if ($this->isScheduled())
		{
			$buttons['startZoomButton'] = (new Button(Loc::getMessage('CRM_TIMELINE_ITEM_ZOOM_BUTTON_START'), Button::TYPE_PRIMARY))
				->setAction(
					(new Redirect(new Uri($this->conferenceInfo['CONF_URL'] ?? '')))
						->addActionParamString('target', '_blank')
				)
			;
		}
		else
		{
			$buttons['scheduleButton'] = $this->getScheduleButton('Activity:Zoom:Schedule');
			if (!empty($this->downloadUrlList))
			{
				$buttons['downloadRecordingsButton'] = (new Button(
					Loc::getMessage('CRM_COMMON_ACTION_DOWNLOAD_FILES'),
					Button::TYPE_SECONDARY)
				)->setAction(
					(new JsEvent('Activity:Zoom:DownloadAllRecords'))
						->addActionParamArray('urlList', $this->downloadUrlList)
				);
			}
		}

		return $buttons;
	}

	public function getMenuItems(): array
	{
		$menuItems = parent::getMenuItems();
		unset($menuItems['view']);

		$info = $this->fetchConferenceInfo();
		$menuItems['copyInviteUrl'] = (new MenuItem(Loc::getMessage('CRM_COMMON_ACTION_COPY_LINK')))
			->setAction(
				(new JsEvent('Activity:Zoom:CopyInviteUrl'))
					->addActionParamString('url', (string)$info['CONF_URL'])
			)
			->setSort(100)
		;

		if (!($this->isScheduled() && $this->hasUpdatePermission()))
		{
			unset($menuItems['delete']);
		}

		return $menuItems;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function fetchConferenceInfo(): array
	{
		if (isset($this->conferenceInfo))
		{
			return $this->conferenceInfo;
		}

		if ($this->isScheduled())
		{
			$conferenceId = $this->getAssociatedEntityModel()?->get('ASSOCIATED_ENTITY_ID') ?? 0;

			$this->conferenceInfo = Conference::getConferenceData($conferenceId);
		}
		else
		{
			$this->conferenceInfo = $this->getAssociatedEntityModel()?->get('ZOOM_INFO') ?? [];

			$recordings = $this->conferenceInfo['RECORDINGS'] ?? [];
			foreach ($recordings as $row)
			{
				if (isset($row['AUDIO']))
				{
					$this->audioRecords[] = $row['AUDIO'];
				}
				if (isset($row['VIDEO']))
				{
					$this->videoRecords[] = $row['VIDEO'];
				}
			}

			$this->downloadUrlList = array_merge(
				array_column($this->audioRecords, 'DOWNLOAD_URL'),
				array_column($this->videoRecords, 'DOWNLOAD_URL'),
			);
		}

		return $this->conferenceInfo;
	}

	private function buildDescriptionBlock(): ContentBlock
	{
		$info = $this->fetchConferenceInfo();

		$descriptionBlock = (new EditableDescription())
			->setText(
				Loc::getMessage(
					'CRM_TIMELINE_ITEM_ZOOM_CREATED_CONFERENCE_MESSAGE',
					[
						'#CONFERENCE_TITLE#' => $info['TOPIC'] ?? Loc::getMessage('CRM_TIMELINE_ITEM_ZOOM_DEFAULT_TOPIC'),
						'#DATE_TIME#' => $this->getFormattedDate($info['CONF_START_TIME'] ?? null),
						'#DURATION#' => $info['DURATION'],
						'#URL#' => (string)$info['CONF_URL'],
					]
				)
			)
			->setEditable(false)
			->setCopied(true)
			->setBackgroundColor(
				$this->isScheduled()
					? EditableDescription::BG_COLOR_YELLOW
					: EditableDescription::BG_COLOR_WHITE
			)
		;

		if (!$this->isScheduled())
		{
			$descriptionBlock->setHeight(EditableDescription::HEIGHT_SHORT);
		}

		return $descriptionBlock;
	}

	private function buildFilesBlock(): ?ContentBlock
	{
		if ($this->isAllRecordsEmpty())
		{
			return null;
		}

		$startDate = $this->getFormattedDate($this->conferenceInfo['CONF_START_TIME'] ?? null, '', true);
		$allowedFileIds = array_unique(array_column($this->audioRecords, 'FILE_ID'));
		$fileList = [];

		foreach ($this->audioRecords as $index => $audioFile)
		{
			if (!in_array((string)$audioFile['FILE_ID'], $allowedFileIds, true))
			{
				continue;
			}

			$fileList[] = new File(
				$audioFile['FILE_ID'],
				(int)($audioFile['FILE_ID'] ?? 0),
				Loc::getMessage('CRM_TIMELINE_ITEM_ZOOM_AUDIO_RECORD_TITLE', [
					'#DATE_TIME#' => $startDate,
					'#PART_NUM#' => $index + 1,
				]) . '.' . mb_strtolower($audioFile['FILE_TYPE']),
				(int)$audioFile['FILE_SIZE'],
				$audioFile['DOWNLOAD_URL'],
			);
		}

		foreach ($this->videoRecords as $index => $videoFile)
		{
			$fileList[] = new File(
				$videoFile['ID'],
				(int)($videoFile['FILE_ID'] ?? 0),
				Loc::getMessage('CRM_TIMELINE_ITEM_ZOOM_VIDEO_RECORD_TITLE', [
					'#DATE_TIME#' => $startDate,
					'#PART_NUM#' => $index + 1,
				]),
				(int)$videoFile['FILE_SIZE'],
				$videoFile['PLAY_URL'],
			);
		}

		return (new ContentBlock\ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_ZOOM_RECORD_LIST_TITLE'))
			->setTitleBottomPadding(10)
			->setContentBlock((new FileList())->setFiles($fileList))
		;
	}

	private function getFormattedDate(mixed $date, string $delimiter = ', ', bool $useShort = false): string
	{
		if (!$date)
		{
			return '';
		}

		$culture = Application::getInstance()->getContext()->getCulture();

		if (is_string($date))
		{
			$startTime = DateTime::tryParse($date, 'Y-m-d H:i:s');
			if ($startTime)
			{
				$startTime = $startTime->toUserTime()->getTimestamp();
			}
			else
			{
				return '';
			}
		}
		elseif ($date instanceof DateTime)
		{
			$startTime = $date->toUserTime()->getTimestamp();
		}
		else
		{
			return '';
		}

		if ($useShort)
		{
			return FormatDate($culture?->getMediumDateFormat(), $startTime);
		}

		return sprintf(
			'%s%s%s',
			FormatDate($culture?->getMediumDateFormat(), $startTime),
			$delimiter,
			FormatDate($culture?->getShortTimeFormat(), $startTime),
		);
	}

	private function isAllRecordsEmpty(): bool
	{
		return
			empty($this->audioRecords)
			&& empty($this->videoRecords)
		;
	}
}
