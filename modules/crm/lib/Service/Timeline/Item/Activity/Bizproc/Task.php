<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc;

use Bitrix\Crm\Activity\TaskPingSettingsProvider;
use Bitrix\Crm\Model\ActivityPingOffsetsTable;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxAction;
use Bitrix\Crm\Service\Timeline\Layout\Body;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ItemSelector;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Timeline\Bizproc\Data\Workflow;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\Service\Timeline\Item\Bizproc\TaskBlockData;

class Task extends Base
{
	use WorkflowFacesBlockTrait;

	public const TASK_STATUS_RUNNING = 'running';
	public const TASK_STATUS_DELEGATED = 'delegated';
	public const TASK_STATUS_DELETED = 'deleted';
	public const TASK_STATUS_DONE = 'done';
	public const TASK_NAME_ROW_LIMIT = 2;

	protected function getActivityTypeId(): string
	{
		return 'BizprocTask';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_TITLE');
	}

	public function getLogo(): ?Body\Logo
	{
		return Common\Logo::getInstance(Common\Logo::BIZPROC_TASK)
			->createLogo()
			?->setInCircle(false)
			?->setAdditionalIconCode('clock')
		;
	}

	public function getTags(): ?array
	{
		return [
			'status' => $this->getStatusTag(),
		];
	}

	private function getStatusTag(): Tag
	{
		return match ($this->getStatus())
		{
			self::TASK_STATUS_RUNNING => new Tag(
				Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_TAG_RUNNING') ?? '',
				Tag::TYPE_PRIMARY
			),
			self::TASK_STATUS_DELEGATED => new Tag(
				Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_TAG_DELEGATED') ?? '',
				Tag::TYPE_SECONDARY
			),
			self::TASK_STATUS_DELETED => new Tag(
				Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_TAG_DELETED') ?? '',
				Tag::TYPE_SECONDARY
			),
			default => new Tag(
				Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_TAG_DONE') ?? '',
				Tag::TYPE_SECONDARY
			),
		};
	}

	public function getContentBlocks(): ?array
	{
		$content = [];

		$overdueDate = $this->buildOverdueDateBlock();
		if ($overdueDate)
		{
			$content['overdueDate'] = $overdueDate;
		}

		$settings = $this->getSettings();

		$process = $this->buildProcessNameBlock(
			(string)($settings['WORKFLOW_TEMPLATE_NAME'] ?? ''),
			(string)($this->getWorkflowId()),
		);
		if ($process)
		{
			$content['process'] = $process;
		}

		$taskData = new TaskBlockData(
			(int)($this->getTaskId()),
			(string)($settings['TASK_NAME'] ?? ''),
			(int)($this->getAuthorId()),
			self::TASK_NAME_ROW_LIMIT,
		);
		$task = $this->buildTaskBlock($taskData);
		if ($task)
		{
			$content['task'] = $task;
		}

		$faces = $this->buildAvatarsBlock();
		if ($faces)
		{
			$content['faces'] = $faces;
		}

		$mobilePingSelectorBlock = $this->buildMobilePingListBlock();
		if (isset($mobilePingSelectorBlock))
		{
			$content['mobilePingSelector'] = $mobilePingSelectorBlock->setScopeMobile();
		}

		return $content;
	}

	private function buildOverdueDateBlock(): ?Body\ContentBlock\Activity\DeadlineAndPingSelector
	{
		$settings = $this->getSettings();

		$overdueDate = $settings['OVERDUE_DATE'] ?? null;
		if (!$overdueDate || !DateTime::isCorrect($overdueDate))
		{
			return null;
		}
		$deadline = new DateTime($overdueDate);

		$isScheduled = $this->getModel()->isScheduled();

		$block =
			(new Body\ContentBlock\Activity\DeadlineAndPingSelector())
				->setBackgroundToken(
					$isScheduled
						? Body\ContentBlock\Activity\DeadlineAndPingSelector::BACKGROUND_ORANGE
						: Body\ContentBlock\Activity\DeadlineAndPingSelector::BACKGROUND_GREY
				)
				->setDeadlineBlock($this->buildDeadlineBlock($deadline))
				->setDeadlineBlockTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_DEADLINE') ?? '')
		;

		if ($isScheduled)
		{
			$block->setPingSelectorBlock($this->buildPingSelector($deadline));
		}

		return $block;
	}

	private function buildDeadlineBlock(DateTime $deadline): Body\ContentBlock\EditableDate
	{
		return
			(new Body\ContentBlock\EditableDate())
				->setDate($deadline)
				->setWithTime()
				->setStyle(Body\ContentBlock\EditableDate::STYLE_PILL)
				->setBackgroundColor(Body\ContentBlock\EditableDate::BACKGROUND_COLOR_NONE)
				->setReadonly(true)
		;
	}

	private function buildPingSelector(): Body\ContentBlock\PingSelector
	{
		$valuesList = $this->getPingValueList();
		$offsets = $this->getPingOffsets();
		$identifier = $this->getContext()->getIdentifier();

		return
			(new Body\ContentBlock\PingSelector())
				->setValue(array_column($offsets, 'offset'))
				->setValuesList(array_values($valuesList))
				->setIcon('bell')
				->setDeadline(new DateTime($this->getAssociatedEntityModel()->get('DEADLINE')))
				->setAction(
					(new RunAjaxAction('crm.activity.ping.updateOffsets'))
						->addActionParamInt('ownerTypeId', $identifier->getEntityTypeId())
						->addActionParamInt('ownerId', $identifier->getEntityId())
						->addActionParamInt('id', $this->getActivityId())
						->addActionParamString('providerId', \Bitrix\Crm\Activity\Provider\Bizproc\Task::getId())
				)
		;
	}

	private function getPingOffsets(): array
	{
		$offsets = $this->getAssociatedEntityModel()?->get('PING_OFFSETS') ?? [];
		if (!is_array($offsets))
		{
			$offsets = (array)$offsets;
		}

		if (empty($offsets))
		{
			$activityId = $this->getModel()->getAssociatedEntityId();
			$offsets = (
				$activityId > 0
					? ActivityPingOffsetsTable::getOffsetsByActivityId($activityId)
					: \Bitrix\Crm\Activity\Provider\Bizproc\Task::getDefaultPingOffsets()
			);
		}

		return TaskPingSettingsProvider::getValuesByOffsets($offsets);
	}

	private function getPingValueList(): array
	{
		$valuesList = [];
		foreach (TaskPingSettingsProvider::getDefaultOffsetList() as $item)
		{
			$id = (int)$item['offset'];
			$valuesList[$id] = ['id' => $id, 'title' => $item['title']];
		}

		$offsets = $this->getPingOffsets();
		foreach ($offsets as $item)
		{
			$offset = (int)$item['offset'];
			$valuesList[$offset] = ['id' => $offset, 'title' => (string)$item['title']];
		}
		ksort($valuesList, SORT_NUMERIC);

		return $valuesList;
	}

	private function buildMobilePingListBlock(): ?ContentBlock
	{
		$settings = $this->getSettings();

		$overdueDate = $settings['OVERDUE_DATE'] ?? null;
		if (!$overdueDate || !DateTime::isCorrect($overdueDate))
		{
			return null;
		}

		$emptyStateText = Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_PING_OFFSETS_EMPTY_STATE');
		$selectorTitle = Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_PING_OFFSETS_SELECTOR_TITLE');

		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
			return $this->buildChangeablePingSelectorMobileBlock($emptyStateText, $selectorTitle);
		}

		return $this->buildReadonlyPingSelectorBlock($this->buildMobilePingBlockText($emptyStateText));
	}

	private function buildChangeablePingSelectorMobileBlock($emptyStateText = null, $selectorTitle = null): ?ContentBlock
	{
		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_PING_OFFSETS_TITLE'))
			->setContentBlock($this->buildChangeablePingItemSelectorItem($emptyStateText, $selectorTitle))
			->setInline()
			;
	}

	private function buildChangeablePingItemSelectorItem(
		$emptyStateText = null,
		$selectorTitle = null,
		bool $compactMode = false
	): ItemSelector
	{
		$valuesList = $this->getPingValueList();
		$offsets = $this->getPingOffsets();
		$identifier = $this->getContext()->getIdentifier();

		$selector = (new ItemSelector())
			->setEmptyState($emptyStateText)
			->setValue(array_column($offsets, 'offset'))
			->setValuesList(array_values($valuesList))
			->setAction(
				(new RunAjaxAction('crm.activity.ping.updateOffsets'))
					->addActionParamInt('ownerTypeId', $identifier->getEntityTypeId())
					->addActionParamInt('ownerId', $identifier->getEntityId())
					->addActionParamInt('id', $this->getActivityId())
					->addActionParamString('providerId', \Bitrix\Crm\Activity\Provider\Bizproc\Task::getId())
			)
		;

		if ($compactMode)
		{
			$selector->setCompactMode();
			$selector->setIcon('bell');
		}
		else
		{
			$selector->setSelectorTitle($selectorTitle);
		}

		return $selector;
	}

	private function buildReadonlyPingSelectorBlock($blockText = null): ?ContentBlock
	{
		if ((string)$blockText === '')
		{
			return null;
		}

		return (new LineOfTextBlocks())
			->addContentBlock(
				'remindTo',
				ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_PING_OFFSETS_TITLE') ?? '')
			)
			->addContentBlock(
				'pingSelectorReadonly',
				$this->buildReadonlyPingSelectorItem($blockText)
			);
	}

	private function buildReadonlyPingSelectorItem($blockText = null): ?Text
	{
		if ((string)$blockText === '')
		{
			return null;
		}

		return (new Text())
			->setValue($blockText)
			->setColor(Text::COLOR_BASE_70)
			->setFontSize(Text::FONT_SIZE_XS)
			->setFontWeight(Text::FONT_WEIGHT_MEDIUM)
			;
	}

	private function buildMobilePingBlockText($emptyStateText = null)
	{
		$offsets = array_column($this->getPingOffsets(), 'title');
		if (count($offsets) === 0)
		{
			return $emptyStateText;
		}

		$blockText = mb_strtolower(implode(', ', array_slice($offsets, 0, 2)));
		if (count($offsets) > 2)
		{
			$blockText = Loc::getMessage(
				'CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_PING_OFFSETS_MORE',
				[
					'#ITEMS#' => $blockText,
					'#COUNT#' => count($offsets) - 2,
				],
			);
		}

		return $blockText;
	}

	private function buildAvatarsBlock(): ?Body\ContentBlock\AvatarsStackSteps\AvatarsStackSteps
	{
		$settings = $this->getSettings();
		if (!$settings)
		{
			return null;
		}

		$faces = isset($settings['FACES']) && is_array($settings['FACES']) ? $settings['FACES'] : null;
		if ($this->isScheduled())
		{
			$workflowId = $this->getWorkflowId();
			$taskId = $this->getTaskId();
			if (!$workflowId || !$taskId)
			{
				return null;
			}

			$workflow = new Workflow($this->getWorkflowId());
			$faces = $workflow->getFaces($this->getTaskId());
		}

		return $faces ? $this->getAvatarsStackSteps($faces) : null;
	}

	public function getButtons(): ?array
	{
		$openTitle = Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_OPEN_BUTTON') ?? '';
		$openButton =
			(new Button($openTitle, Button::TYPE_SECONDARY)) // gray
				->setAction($this->getOpenTaskAction())
		;

		if (!$this->getModel()->isScheduled())
		{
			return ['open' => $openButton];
		}

		if ($this->getStatus() !== self::TASK_STATUS_RUNNING || !Loader::includeModule('bizproc'))
		{
			$openButton->setType(Button::TYPE_PRIMARY); // blue

			return ['open' => $openButton];
		}

		$settings = $this->getSettings();

		$buttons = [];
		if (isset($settings['BUTTONS']))
		{
			if (($settings['IS_INLINE'] ?? false))
			{
				foreach ($settings['BUTTONS'] as $button)
				{
					$status = \CBPTaskUserStatus::resolveStatus($button['TARGET_USER_STATUS'] ?? '');
					if ($status !== null)
					{
						$type = \CBPTaskUserStatus::isPositive($status) ? Button::TYPE_PRIMARY : Button::TYPE_SECONDARY;
						$buttons['status_' . $status] =
							(new Button($button['TEXT'], $type))
								->setAction($this->getDoTaskAction((string)$button['VALUE'], (string)$button['NAME']))
						;
					}
				}
			}
			else
			{
				$buttons['proceed'] =
					(new Button(
						Loc::getMessage('CRM_TIMELINE_ACTIVITY_BIZPROC_TASK_PROCEED_BUTTON') ?? '',
						Button::TYPE_PRIMARY
					))
						->setAction($this->getOpenTaskAction())
				;
			}
		}

		$buttons['open'] = $openButton;

		return $buttons;
	}

	public function getMenuItems(): array
	{
		$workflowId = $this->getWorkflowId();
		if (!$workflowId)
		{
			return [];
		}

		return [
			'timeline' => $this->createTimelineMenuItem($workflowId),
			'log' => $this->createLogMenuItem($workflowId)?->setScopeWeb(),
			'terminate' => $this->createTerminateMenuItem($workflowId),
		];
	}

	private function getWorkflowId(): ?string
	{
		$settings = $this->getSettings();
		if (!$settings)
		{
			return null;
		}

		$workflowId = $settings['WORKFLOW_ID'] ?? null;

		return $workflowId ? (string)$workflowId : null;
	}

	private function getTaskId(): ?int
	{
		$settings = $this->getSettings();
		if (!$settings)
		{
			return null;
		}

		$taskId = $settings['TASK_ID'] ?? null;

		return $taskId ? (int)$taskId : null;
	}

	private function getOpenTaskAction(): JsEvent
	{
		$openAction =
			(new JsEvent('Bizproc:Task:Open'))
				->addActionParamInt('taskId', (int)$this->getTaskId())
		;

		$responsibleId = (int)$this->getAuthorId();
		if ($responsibleId > 0)
		{
			$openAction->addActionParamInt('userId', $responsibleId);
		}

		return $openAction;
	}

	private function getDoTaskAction(string $value, string $name): JsEvent
	{
		return (
			(new JsEvent('Bizproc:Task:Do'))
				->addActionParamInt('taskId', (int)$this->getTaskId())
				->addActionParamString('value', $value)
				->addActionParamString('name', $name)
				->addActionParamInt('responsibleId', (int)$this->getAuthorId())
		);
	}

	private function getStatus(): string
	{
		$settings = $this->getSettings();

		return isset($settings['STATUS']) ? (string)$settings['STATUS'] : self::TASK_STATUS_DONE;
	}

	private function getSettings(): array
	{
		$settings = $this->getAssociatedEntityModel()?->get('SETTINGS');

		return is_array($settings) ? $settings : [];
	}
}
