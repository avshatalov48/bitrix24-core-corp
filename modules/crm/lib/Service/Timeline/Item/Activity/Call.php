<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Activity\StatisticsMark;
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\VoxImplantManager;
use Bitrix\Crm\Format\Duration;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Action\ShowMenu;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Audio;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ClientMark;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Footer\IconButton;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout\Menu;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\PhoneNumber;
use Bitrix\Main\Type\DateTime;
use CCrmActivityDirection;
use CCrmDateTimeHelper;
use CCrmFieldMulti;
use CCrmOwnerType;

class Call extends Activity
{
	private const BLOCK_DELIMITER = '&bull;';

	final protected function getActivityTypeId(): string
	{
		return 'Call';
	}

	public function getIconCode(): ?string
	{
		$direction = (int)$this->getAssociatedEntityModel()->get('DIRECTION');
		switch ($direction)
		{
			case CCrmActivityDirection::Incoming:
				if ($this->isMissedCall())
				{
					return 'call-incoming-missed';
				}

				return $this->isScheduled() ? 'call-incoming' : 'call-completed';
			case CCrmActivityDirection::Outgoing:
				return 'call-outcoming';
		}

		return 'call';
	}

	public function getTitle(): string
	{
		$direction = (int)$this->getAssociatedEntityModel()->get('DIRECTION');
		switch ($direction)
		{
			case CCrmActivityDirection::Incoming:
				if ($this->isMissedCall())
				{
					return Loc::getMessage(
						$this->isScheduled()
							? 'CRM_TIMELINE_TITLE_CALL_MISSED'
							: 'CRM_TIMELINE_TITLE_CALL_INCOMING_DONE'
					);
				}

				// set call end time to correct title in header
				if ($this->isScheduled())
				{
					$userTime = (string)$this->getAssociatedEntityModel()->get('END_TIME');
					if (!empty($userTime) && !CCrmDateTimeHelper::IsMaxDatabaseDate($userTime))
					{
						$this->getModel()->setDate(DateTime::createFromUserTime($userTime));
					}
				}

				$scheduledCode = $this->isPlanned()
					? 'CRM_TIMELINE_TITLE_CALL_INCOMING_PLAN'
					: 'CRM_TIMELINE_TITLE_CALL_INCOMING';

				return Loc::getMessage(
					$this->isScheduled()
						? $scheduledCode
						: 'CRM_TIMELINE_TITLE_CALL_INCOMING_DONE'
				);
			case CCrmActivityDirection::Outgoing:
				return Loc::getMessage(
					$this->isPlanned()
						? 'CRM_TIMELINE_TITLE_CALL_OUTGOING_PLAN'
						: 'CRM_TIMELINE_TITLE_CALL_OUTGOING'
				);
		}

		return Loc::getMessage('CRM_TIMELINE_CALL_TITLE_UNKNOWN');
	}

	public function getLogo(): ?Logo
	{
		return new Logo($this->isMissedCall() ? 'call' : 'call'); // TODO: use other icons
	}

	public function getContentBlocks(): array
	{
		$result = [];

		/** @var AssociatedEntityModel $model */
		$model = $this->getAssociatedEntityModel();

		$clientBlock = $this->getClientContentBlock(self::BLOCK_WITH_FORMATTED_VALUE);
		if (isset($clientBlock))
		{
			$result['client'] = $clientBlock;
		}

		$responsibleUser = $this->getUserData($model->get('RESPONSIBLE_ID'));
		if (isset($responsibleUser))
		{
			$result['responsibleUser'] = (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					(new Text())
						->setValue(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_BLOCK_TITLE_RESPONSIBLE_USER')))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
				)
				->addContentBlock(
					'data',
					ContentBlockFactory::createTextOrLink(
						$responsibleUser['FORMATTED_NAME'],
						new Redirect($responsibleUser['SHOW_URL'])
					)->setFontWeight(Text::FONT_WEIGHT_BOLD)
				)
			;
		}

		$subject = $model->get('SUBJECT');
		if (isset($subject) && $this->isPlanned())
		{
			$result['subject'] = (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					(new Text())
						->setValue(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_BLOCK_TITLE_THEME')))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
				)
				->addContentBlock(
					'data',
					(new Text())->setValue((string)$subject)
				)
			;
		}


		// TODO: not implemented yet
		/*if ($this->isMissedCall())
		{
			$result['callQueue'] = (new ContentBlockWithTitle())
				->setTitle(sprintf('%s:', Loc::getMessage('CRM_TIMELINE_BLOCK_TITLE_QUEUE')))
				->setContentBlock((new Text())->setValue('not implemented yet')) // TODO: fix after improving in voximplant module
			;
		}*/

		$recordUrl = $this->getCallRecordUrl();
		if (!empty($recordUrl))
		{
			$result['audio'] = (new Audio())
				->setId($model->get('ID'))
				->setSource($recordUrl)
			;
		}

		$callInfo = $this->getCallInformation();
		if (!empty($callInfo))
		{
			$callInfoBlock = new LineOfTextBlocks();

			$formattedValue = PhoneNumber\Parser::getInstance()->parse($callInfo['PORTAL_NUMBER'])->format();
			if (!empty($formattedValue))
			{
				$callInfoBlock
					->addContentBlock(
					'info1',
					(new Text())
						->setValue(Loc::getMessage('CRM_TIMELINE_BLOCK_CALL_ADDITIONAL_INFO_1'))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
					)
					->addContentBlock(
						'number',
						(new Text())
							->setValue($formattedValue)
							->setColor(Text::COLOR_BASE_70)
							->setFontSize(Text::FONT_SIZE_SM)
							->setFontWeight(Text::FONT_WEIGHT_MEDIUM)
					)
				;
			}

			if ((int)$callInfo['DURATION'] > 0)
			{
				$callInfoBlock
					->addContentBlock(
					'delimiter',
					(new Text())
						->setValue(html_entity_decode(self::BLOCK_DELIMITER))
						->setColor(Text::COLOR_BASE_70)
						->setFontSize(Text::FONT_SIZE_SM)
						->setFontWeight(Text::FONT_WEIGHT_MEDIUM)
					)
					->addContentBlock(
						'info2',
						(new Text())
							->setValue(Loc::getMessage('CRM_TIMELINE_BLOCK_CALL_ADDITIONAL_INFO_2'))
							->setColor(Text::COLOR_BASE_70)
							->setFontSize(Text::FONT_SIZE_SM)
					)
					->addContentBlock(
						'duration',
						(new Text())
							->setValue(Duration::format((int)$callInfo['DURATION']))
							->setColor(Text::COLOR_BASE_70)
							->setFontSize(Text::FONT_SIZE_SM)
					)
				;
			}

			$result['callInformation'] = $callInfoBlock;
		}

		$clientMark = $this->mapClientMark((int)$model->get('RESULT_MARK'));
		if (isset($clientMark))
		{
			$result['mark'] = (new ClientMark())
				->setMark($clientMark)
				->setText(
					Loc::getMessage(
						'CRM_TIMELINE_BLOCK_CLIENT_MARK_TEXT',
						['#MARK#' => (int)$callInfo['CALL_VOTE']]
					)
				);
		}

		$description = $this->getDescription((string)$model->get('DESCRIPTION'));
		if (!empty($description))
		{
			$result['description'] = (new Text())->setValue($description)
				->setFontSize(Text::COLOR_BASE_90)
			;
		}

		return $result;
	}

	public function getButtons(): array
	{
		$communication = $this->getAssociatedEntityModel()->get('COMMUNICATION') ?? [];

		$scheduleButton = (new Button(Loc::getMessage('CRM_TIMELINE_BUTTON_CALL_SCHEDULE'), Button::TYPE_SECONDARY))
			->setAction((new JsEvent('Call:Schedule'))
				->addActionParamInt('activityId', $this->getActivityId()))
		;
		$doneButton = (new Button(Loc::getMessage('CRM_TIMELINE_BUTTON_CALL_COMPLETE'), Button::TYPE_PRIMARY))
			->setAction($this->getCompleteAction())
		;

		$direction = (int)$this->getAssociatedEntityModel()->get('DIRECTION');
		switch ($direction)
		{
			case CCrmActivityDirection::Incoming:
				if ($this->isMissedCall())
				{
					if (!empty($communication))
					{
						$buttons['callButton'] = $this->getCallButton(
							$communication,
							$this->isScheduled() ? Button::TYPE_PRIMARY : Button::TYPE_SECONDARY
						);
					}

					if ($this->isScheduled())
					{
						$buttons['scheduleButton'] = $scheduleButton;
					}

					return $buttons ?? [];
				}

				if ($this->isScheduled())
				{
					if ($this->isPlanned())
					{
						return [
							'doneButton' => $doneButton,
						];
					}
					else
					{
						return [
							'doneButton' => $doneButton,
							'scheduleButton' => $scheduleButton,
						];
					}
				}

				return empty($communication)
					? []
					: ['callButton' => $this->getCallButton($communication, Button::TYPE_SECONDARY)];
			case CCrmActivityDirection::Outgoing:
				return empty($communication)
					? []
					: ['callButton' => $this->getCallButton($communication, Button::TYPE_SECONDARY)];
		}

		return [];
	}

	public function getAdditionalIconButton(): ?IconButton
	{
		$callInfo = $this->getCallInformation();
		if (isset($callInfo['HAS_TRANSCRIPT']) && $callInfo['HAS_TRANSCRIPT'])
		{
			return (new IconButton('list', Loc::getMessage('CRM_TIMELINE_BUTTON_TIP_TRANSCRIPT')))
				->setAction((new JsEvent('Call:OpenTranscript'))
					->addActionParamString('callId', $callInfo['CALL_ID']))
			;
		}

		return null;
	}

	public function getTags(): ?array
	{
		$tags = [];
		$callInfo = $this->getCallInformation();
		if ($this->isMissedCall())
		{
			$tags['missedCall'] = new Tag(
				Loc::getMessage('CRM_TIMELINE_TAG_CALL_MISSED'),
				Tag::TYPE_FAILURE
			);
		}

		if (!empty($callInfo) && $callInfo['HAS_TRANSCRIPT'] && $callInfo['TRANSCRIPT_PENDING'])
		{
			$tags['transcriptPending'] = new Tag(
				Loc::getMessage('CRM_TIMELINE_TAG_TRANSCRIPT_PENDING'),
				Tag::TYPE_WARNING
			);
		}

		return $tags;
	}

	protected function getDeleteConfirmationText(): string
	{
		$direction = (int)$this->getAssociatedEntityModel()->get('DIRECTION');
		$title = $this->getAssociatedEntityModel()->get('SUBJECT') ?? '';
		switch ($direction)
		{
			case CCrmActivityDirection::Incoming:
				return Loc::getMessage('CRM_TIMELINE_INCOMING_CALL_DELETION_CONFIRM', ['#TITLE#' => $title]);
			case CCrmActivityDirection::Outgoing:
				return Loc::getMessage('CRM_TIMELINE_OUTGOING_CALL_DELETION_CONFIRM', ['#TITLE#' => $title]);
		}

		return parent::getDeleteConfirmationText();
	}

	private function isMissedCall(): bool
	{
		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');

		return isset($settings['MISSED_CALL']) && $settings['MISSED_CALL'];
	}

	private function getCallInformation(): array
	{
		$result = $this->getAssociatedEntityModel()->get('CALL_INFO') ?? [];
		if (!empty($result))
		{
			return $result;
		}

		$originId = $this->getAssociatedEntityModel()->get('ORIGIN_ID');
		if ($this->isVoxImplant($originId))
		{
			return VoxImplantManager::getCallInfo(mb_substr($originId, 3)) ?? [];
		}

		return [];
	}

	private function getCallRecordUrl(): string
	{
		$originId = $this->getAssociatedEntityModel()->get('ORIGIN_ID');
		if (!$this->isVoxImplant($originId))
		{
			return '';
		}

		if (!empty($this->getAssociatedEntityModel()->get('MEDIA_FILE_INFO')['URL']))
		{
			return (string)$this->getAssociatedEntityModel()->get('MEDIA_FILE_INFO')['URL'];
		}

		$storageElementIds = $this->getAssociatedEntityModel()->get('STORAGE_ELEMENT_IDS');
		$storageTypeId = $this->getAssociatedEntityModel()->get('STORAGE_TYPE_ID');
		if (empty($storageElementIds) || empty($storageTypeId))
		{
			return '';
		}

		$elementIds = unserialize($storageElementIds, ['allowed_classes' => false]);
		if (isset($elementIds[0]))
		{
			$info = StorageManager::getFileInfo(
				$elementIds[0],
				$storageTypeId,
				false,
				['OWNER_TYPE_ID' => CCrmOwnerType::Activity, 'OWNER_ID' => $this->getActivityId()]
			);

			if (
				is_array($info)
				&& in_array(GetFileExtension(mb_strtolower($info['NAME'])), ['wav', 'mp3', 'mp4'])
			)
			{
				return (string)$info['VIEW_URL'];
			}
		}

		return '';
	}

	private function mapClientMark(int $callVote): ?string
	{
		switch ($callVote)
		{
			case StatisticsMark::Negative:
				return ClientMark::NEGATIVE;
			case StatisticsMark::Neutral:
				return ClientMark::NEUTRAL;
			case StatisticsMark::Positive:
				return ClientMark::POSITIVE;
			default:
				return null;
		}
	}

	private function getCallButton(array $communication, string $type): Button
	{
		$button = new Button(Loc::getMessage('CRM_TIMELINE_BUTTON_CALL'), $type);
		$makeCallAction = function (string $phone) use ($communication) {
			return (new JsEvent('Call:MakeCall'))
				->addActionParamInt('activityId', $this->getActivityId())
				->addActionParamInt('entityTypeId', (int)$communication['ENTITY_TYPE_ID'])
				->addActionParamInt('entityId', (int)$communication['ENTITY_ID'])
				->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
				->addActionParamInt('ownerId', $this->getContext()->getEntityId())
				->addActionParamString('phone', $phone)
			;
		};
		$phoneList = $this->getPhoneList($communication['ENTITY_TYPE_ID'], $communication['ENTITY_ID']);
		if (count($phoneList) > 1)
		{
			$phoneMenu = new Menu();
			foreach ($phoneList as $item)
			{
				$title = empty($item['COMPLEX_NAME'])
					? sprintf('%s', $item['VALUE_FORMATTED'])
					: sprintf('%s: %s', $item['COMPLEX_NAME'], $item['VALUE_FORMATTED']);

				$phoneMenu->addItem(
					sprintf('phone_menu_%d_%d', $this->getActivityId(), $item['ID']),
					(new Menu\MenuItem($title))->setAction($makeCallAction((string)$item['VALUE']))
				);
			}

			$button->setAction(new ShowMenu($phoneMenu));
		}
		else
		{
			$button->setAction($makeCallAction((string)$communication['VALUE']));
		}

		return $button;
	}

	private function getPhoneList(int $entityTypeId, int $entityId): array
	{
		$result = [];
		$dbResult = CCrmFieldMulti::GetList(['ID' => 'asc'], [
			'ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeId),
			'ELEMENT_ID' => $entityId,
			'TYPE_ID' => 'PHONE'
		]);
		while ($fields = $dbResult->Fetch())
		{
			$value = $fields['VALUE'] ?? '';
			if (empty($value))
			{
				continue;
			}

			$result[] = [
				'ID' => $fields['ID'],
				'VALUE' => $value,
				'VALUE_TYPE' => $fields['VALUE_TYPE'],
				'VALUE_FORMATTED' => PhoneNumber\Parser::getInstance()->parse($value)->format(),
				'COMPLEX_ID' => $fields['COMPLEX_ID'],
				'COMPLEX_NAME' => CCrmFieldMulti::GetEntityNameByComplex($fields['COMPLEX_ID'], false)
			];
		}

		return $result;
	}

	private function getDescription(string $input): string
	{
		if (empty($input))
		{
			return '';
		}

		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');
		if (isset($settings['IS_DESCRIPTION_ONLY']) && $settings['IS_DESCRIPTION_ONLY']) // new description format
		{
			return trim($input);
		}

		$parts = explode("\n", $input);
		if (mb_strpos($parts[0], Loc::getMessage('CRM_TIMELINE_BLOCK_DESCRIPTION_EXCLUDE_1')) === 0)
		{
			return '';
		}

		if (mb_strpos($parts[0], Loc::getMessage('CRM_TIMELINE_BLOCK_DESCRIPTION_EXCLUDE_2')) === 0)
		{
			return '';
		}

		return $input;
	}

	private function isVoxImplant(?string $originId): bool
	{
		return isset($originId) && mb_strpos($originId, 'VI_') !== false;
	}
}
