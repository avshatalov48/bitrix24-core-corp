<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Model\ActivityPingOffsetsTable;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDescription;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class Ping extends LogMessage
{
	protected AssociatedEntityModel $entityModel;

	public function getType(): string
	{
		return 'Ping';
	}

	public function getIconCode(): ?string
	{
		return Icon::CLOCK;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_PING_TITLE_NEW');
	}

	public function getContentBlocks(): ?array
	{
		$this->entityModel = $this->getAssociatedEntityModel();
		$deadlineStr = $this->entityModel->get('DEADLINE');
		if (empty($deadlineStr))
		{
			return [];
		}

		$created = $this->getDate();
		$offset = 0;
		if ($created)
		{
			$deadline = DateTime::createFromUserTime($deadlineStr);
			$offset = ($deadline->getTimestamp() - $created->getTimestamp()) / 60; // minutes
		}

		$pingOffset = null;
		if ($offset === 0)
		{
			$pingOffset = 0;
		}
		else
		{
			$pingOffset = $this->getModel()->getSettings()['PING_OFFSET'] ?? null;
			if (is_null($pingOffset))
			{
				$offsetLists = ActivityPingOffsetsTable::getOffsetsByActivityId($this->getModel()->getAssociatedEntityId());
				$pingOffset = $offsetLists[1] ?? null;
			}
			else
			{
				$pingOffset = (int)($pingOffset / 60);
			}
		}

		$blocks = $this->buildContentBlocks();

		if (!is_null($pingOffset) && $pingOffset >= 0)
		{
			$startBlockTitle = $this->getOffsetText($pingOffset);
			$blocks['start'] = ContentBlockFactory::createTitle($startBlockTitle);
		}

		return $blocks;
	}

	protected function buildContentBlocks(): array
	{
		$pingText = (string)$this->entityModel->get('DESCRIPTION');
		if ($pingText === '')
		{
			$providerId = $this->entityModel->get('PROVIDER_ID');
			if ($providerId)
			{
				$provider = \CCrmActivity::GetProviderById($providerId);
				$pingText = $provider::getActivityTitle([
					'SUBJECT' => (string)$this->entityModel->get('SUBJECT'),
					'COMPLETED' => 'N',
				]);
			}
			else
			{
				$pingText = (string)$this->entityModel->get('SUBJECT');
			}
		}
		else
		{
			// Temporarily removes [p] for mobile compatibility
			$descriptionType = (int)$this->getHistoryItemModel()?->get('ASSOCIATED_ENTITY')['DESCRIPTION_TYPE'] ?? null;
			if ($this->getContext()->getType() === Context::MOBILE && $descriptionType === \CCrmContentType::BBCode)
			{
				$pingText = \Bitrix\Crm\Format\TextHelper::removeParagraphs($pingText);
			}
		}

		return [
			'subject' => (new EditableDescription())
				->setText($pingText)
				->setEditable(false)
				->setHeight(EditableDescription::HEIGHT_SHORT),
		];
	}

	private function getOffsetText(int $pingOffset): string
	{
		if ($pingOffset === 0)
		{
			return Loc::getMessage('CRM_TIMELINE_LOG_PING_ACTIVITY_STARTED_NEW');
		}

		$minutesInHour = 60;

		$daysString = null;
		$days = floor($pingOffset / ($minutesInHour * 24));
		if ($days > 0)
		{
			$daysString = Loc::getMessagePlural(
				'CRM_TIMELINE_LOG_PING_ACTIVITY_START_DAY',
				$days,
				[
					'#COUNT#' => $days,
				]
			);
		}

		$hoursString = null;
		$hours = floor(($pingOffset % ($minutesInHour * 24)) / $minutesInHour);
		if ($hours > 0)
		{
			$hoursString = Loc::getMessagePlural(
				'CRM_TIMELINE_LOG_PING_ACTIVITY_START_HOUR',
				$hours,
				[
					'#COUNT#' => $hours,
				]
			);
		}

		$minutesString = null;
		$minutes = floor($pingOffset % $minutesInHour);
		if ($minutes > 0)
		{
			$minutesString = Loc::getMessagePlural(
				'CRM_TIMELINE_LOG_PING_ACTIVITY_START_MINUTE',
				$minutes,
				[
					'#COUNT#' => $minutes,
				]
			);
		}

		$replace = [
			'#DAYS#' => $daysString,
			'#HOURS#' => $hoursString,
			'#MINUTES#' => $minutesString,
		];

		if ($days > 0)
		{
			if ($hours > 0 && $minutes > 0)
			{
				$code = 'CRM_TIMELINE_LOG_PING_ACTIVITY_START_FORMAT_DAY_HOUR_MINUTE_TITLE';
			}
			elseif ($hours > 0)
			{
				$code = 'CRM_TIMELINE_LOG_PING_ACTIVITY_START_FORMAT_DAY_HOUR_TITLE';
			}
			elseif ($minutes > 0)
			{
				$code = 'CRM_TIMELINE_LOG_PING_ACTIVITY_START_FORMAT_DAY_MINUTE_TITLE';
			}
			else
			{
				$code = 'CRM_TIMELINE_LOG_PING_ACTIVITY_START_FORMAT_DAY_TITLE';
			}

			if ($days === 1)
			{
				$code .= '_SINGLE';
			}
		}
		elseif ($hours > 0)
		{
			if ($minutes > 0)
			{
				$code = 'CRM_TIMELINE_LOG_PING_ACTIVITY_START_FORMAT_HOUR_MINUTE_TITLE';
			}
			else
			{
				$code = 'CRM_TIMELINE_LOG_PING_ACTIVITY_START_FORMAT_HOUR_TITLE';
			}

			if ($hours === 1)
			{
				$code .= '_SINGLE';
			}
		}
		else
		{
			$code = 'CRM_TIMELINE_LOG_PING_ACTIVITY_START_FORMAT_MINUTE_TITLE';

			if ($minutes === 1)
			{
				$code .= '_SINGLE';
			}
		}

		return Loc::getMessage($code, $replace);
	}
}
