<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Integration\Intranet\BindingMenu\CodeBuilder;
use Bitrix\Crm\Integration\Intranet\BindingMenu\SectionCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxAction;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Note;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Converter;
use Bitrix\Crm\Service\Timeline\Layout\Header\ChangeStreamButton;
use Bitrix\Crm\Service\Timeline\Layout\MarketPanel;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Crm\Timeline\Entity\NoteTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\PhoneNumber;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

abstract class Configurable extends Item
{
	public const BLOCK_WITH_FORMATTED_VALUE = 1;
	public const BLOCK_WITH_FIXED_TITLE = 2;

	protected Layout $layout;

	public function __construct(Context $context, Model $model)
	{
		parent::__construct($context, $model);
		$this->layout = $this->buildLayout();
	}

	/**
	 * Checks item status.
	 *
	 * @return bool
	 */
	public static function isActive(): bool
	{
		return true;
	}

	/**
	 * Get model with data from timeline database table
	 *
	 * @return Model
	 */
	public function getModel(): Model
	{
		return $this->model;
	}

	/**
	 * Get model with data about associated entity
	 *
	 * @return AssociatedEntityModel|null
	 */
	protected function getAssociatedEntityModel(): ?AssociatedEntityModel
	{
		return $this->getModel()->getAssociatedEntityModel();
	}

	/**
	 * Get model with data prepared by timeline controllers
	 *
	 * @return HistoryItemModel|null
	 */
	protected function getHistoryItemModel(): ?HistoryItemModel
	{
		return $this->getModel()->getHistoryItemModel();
	}

	/**
	 * Is current timeline item associated with the same entity as the whole timeline
	 *
	 * @return bool
	 */
	protected function isItemAboutCurrentEntity(): bool
	{
		return (
			$this->getContext()->getEntityTypeId() === $this->getModel()->getAssociatedEntityTypeId()
			&& $this->getContext()->getEntityId() === $this->getModel()->getAssociatedEntityId()
		);
	}

	public function getLayout(): Layout
	{
		return $this->layout;
	}

	public function getPayload(): ?Payload
	{
		return null;
	}

	public function jsonSerialize(): array
	{
		return [
			'layout' => (new Converter($this->getLayout()))->toArray(),
			'type' => $this->getType(),
			'id' => $this->getModel()->getId(),
			'payload' => $this->getPayload(),
			'timestamp' => $this->getModel()->getDate() ? $this->getModel()->getDate()->getTimestamp() : null,
			'sort' => $this->getSort(),
			'languageId' => \Bitrix\Main\Context::getCurrent()->getLanguage(),
			'canBeReloaded' => $this->canBeReloaded(),
		];
	}

	/**
	 * String type identifier of item
	 *
	 * @return string
	 */
	abstract public function getType(): string;

	protected function buildLayout(): Layout
	{
		return (new Layout\Builder($this))->build();
	}

	public function getSort(): array
	{
		return [
			$this->getDate()
				? $this->getDate()->getTimestamp()
				: 0,
			(int)$this->getModel()->getId()
		];
	}

	/**
	 * Icon of timeline record
	 *
	 */
	public function getIcon(): ?Layout\Icon
	{
		$iconCode = $this->getIconCode();

		return $iconCode
			? (new Layout\Icon())
				->setCode($iconCode)
				->setCounterType($this->getCounterType())
				->setBackgroundColorToken($this->getBackgroundColorToken())
			: null
		;
	}

	/**
	 * Code of item icon
	 *
	 * @return string|null
	 */
	public function getIconCode(): ?string
	{
		return null;
	}

	/**
	 * If necessary to show counter near icon, method will return type of the counter
	 *
	 * @return string|null
	 */
	public function getCounterType(): ?string
	{
		return null;
	}

	/**
	 * Get code of icon background color
	 * @return string
	 */
	public function getBackgroundColorToken(): string
	{
		return Layout\Icon::BACKGROUND_PRIMARY;
	}

	/**
	 * Change stream button used to change item stream:
	 * Scheduled -> History or History <--> Fixed history
	 *
	 * @return ChangeStreamButton|null
	 */
	public function getChangeStreamButton(): ?ChangeStreamButton
	{
		return
			$this->getCompleteButton()
			?? $this->getUnpinButton()
		;
	}

	protected function getCompleteButton(): ?ChangeStreamButton
	{
		return null;
	}

	protected function getUnpinButton(): ?ChangeStreamButton
	{
		$canBeUnFixed =
			$this->isPinnable()
			&& !$this->getModel()->isScheduled()
			&& $this->getModel()->isFixed()
		;
		if (!$canBeUnFixed)
		{
			return null;
		}

		return (new ChangeStreamButton)
			->setTypeUnpin()
			->setDisableIfReadonly()
			->setAction($this->getUnpinAction())
		;
	}

	/**
	 * By default item is pinnable if it has title
	 *
	 * @return bool
	 */
	protected function isPinnable(): bool
	{
		$title = $this->getTitle();

		return is_string($title) && $title !== '';
	}

	/**
	 * Get item title
	 *
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return null;
	}

	/**
	 * Get item title onClick action
	 *
	 * @return Layout\Action|null
	 */
	public function getTitleAction(): ?Layout\Action
	{
		return null;
	}

	/**
	 * Hint icon in item header
	 *
	 * @return Layout\Header\InfoHelper|null
	 */
	public function getInfoHelper(): ?Layout\Header\InfoHelper
	{
		return null;
	}

	/**
	 * Get item header date.
	 *
	 * @return DateTime|null
	 */
	public function getDate(): ?DateTime
	{
		return $this->getModel()->getDate();
	}

	/**
	 * Placeholder for cases when date is empty
	 *
	 * @return string|null
	 */
	public function getDatePlaceholder(): ?string
	{
		return null;
	}

	/**
	 * Get header tags
	 *
	 * @return Layout\Header\Tag[]|null
	 */
	public function getTags(): ?array
	{
		return null;
	}

	/**
	 * Get item created by identifier
	 *
	 * @return int|null
	 */
	public function getAuthorId(): ?int
	{
		return $this->getModel()->getAuthorId();
	}

	/**
	 * Get item logotype
	 *
	 * @return Layout\Body\Logo|null
	 */
	public function getLogo(): ?Layout\Body\Logo
	{
		return null;
	}

	/**
	 * Get item content blocks
	 *
	 * @return Layout\Body\ContentBlock[]|null
	 */
	public function getContentBlocks(): ?array
	{
		$blocks = [];

		if ($contentText = $this->getContentText())
		{
			$blocks['content'] =  (new Text())->setValue($contentText);
		}

		return $blocks;
	}

	public function getCommonContentBlocksBlocks(): array
	{
		$blocks = [];
		if($this->needShowNotes()) {
			$blocks['note'] =  $this->buildNoteBlock();
		}
		return $blocks;
	}

	/**
	 * If item should only contain text content, get that text
	 *
	 * @return string|null
	 */
	protected function getContentText(): ?string
	{
		return null;
	}

	/**
	 * Get footer buttons
	 *
	 * @return Layout\Footer\Button[]|null
	 */
	public function getButtons(): ?array
	{
		return null;
	}

	/**
	 * Additional icon-like button in footer
	 *
	 * @return Layout\Footer\IconButton|null
	 */
	public function getAdditionalIconButton(): ?Layout\Footer\IconButton
	{
		return null;
	}

	/**
	 * Can notes block be shown
	 *
	 * @return bool
	 */
	public function needShowNotes(): bool
	{
		return false;
	}

	/**
	 * Get footer context menu items
	 *
	 * @return MenuItem[]|null
	 */
	public function getMenuItems(): ?array
	{
		$result = [];
		$this->addPinMenuItems($result);

		return $result;
	}

	protected function addPinMenuItems(array &$menuItems): void
	{
		$canBeFixed =
			$this->isPinnable()
			&& !$this->getModel()->isScheduled()
			&& !$this->getModel()->isFixed()
		;

		$canBeUnFixed =
			$this->isPinnable()
			&& !$this->getModel()->isScheduled()
			&& $this->getModel()->isFixed()
		;

		if (!$canBeFixed && !$canBeUnFixed)
		{
			return;
		}

		if ($canBeFixed)
		{
			$menuItems['pin'] = (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_FASTEN')))
				->setHideIfReadonly()
				->setSort(9900)
				->setAction(
					(new RunAjaxAction('crm.timeline.item.pin'))
						->addActionParamInt('id', $this->getModel()->getId())
						->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
						->addActionParamInt('ownerId', $this->getContext()->getEntityId())
				)
			;
		}
		if ($canBeUnFixed)
		{
			$menuItems['unpin'] = (new MenuItem(Loc::getMessage('CRM_TIMELINE_MENU_UNFASTEN')))
				->setHideIfReadonly()
				->setSort(9900)
				->setAction($this->getUnpinAction())
			;
		}
	}

	/**
	 * Is item a log message.
	 * Log messages have their own representation
	 *
	 * @return bool
	 */
	public function isLogMessage(): bool
	{
		return false;
	}

	public function getMarketPanel(): ?MarketPanel
	{
		if (!$this->hasMarketPanel())
		{
			return null;
		}

		$marketPanel = new MarketPanel($this->getMarketPanelText());
		$placementCode = $this->getMarketPanelPlacementCode();
		if ($placementCode)
		{
			$marketPanel
				->setDetailsText(Loc::getMessage('CRM_TIMELINE_MARKET_PANEL_TEXT_DETAILS'))
				->setDetailsTextAction(
					$placementCode
						? new Redirect(new Uri('/marketplace/?placement=' . $placementCode))
						: null
				)
			;
		}

		return $marketPanel;
	}

	protected function hasMarketPanel(): bool
	{
		return false;
	}

	protected function getMarketPanelText(): string
	{
		return Loc::getMessage('CRM_TIMELINE_MARKET_PANEL_TEXT');
	}

	protected function getUserData(?int $userId): array
	{
		if (!$userId)
		{
			return [];
		}

		$userData = Container::getInstance()->getUserBroker()->getById($userId);

		return $userData ?? [];
	}

	protected function buildClientBlock(int $options = 0, string $blockTitle = null): ?Layout\Body\ContentBlock
	{
		$communication = $this->getAssociatedEntityModel()->get('COMMUNICATION') ?? [];
		$title = $communication['TITLE'] ?? null;
		if (!$title)
		{
			return null;
		}

		if (($options & self::BLOCK_WITH_FORMATTED_VALUE))
		{
			$source = empty($communication['SOURCE'])
				? ''
				: PhoneNumber\Parser::getInstance()->parse($communication['SOURCE'])->format();

			$formattedValue = empty($communication['FORMATTED_VALUE'])
				? $source
				: $communication['FORMATTED_VALUE'];

			$title = sprintf('%s %s', $title, $formattedValue);
		}

		$blockTitle = $blockTitle ?? Loc::getMessage("CRM_TIMELINE_CLIENT_TITLE");

		$url = isset($communication['SHOW_URL'])
			? new Uri($communication['SHOW_URL'])
			: null;

		$textOrLink = ContentBlockFactory::createTextOrLink($title, $url ? new Redirect($url) : null);
		$textOrLink->setTitle($title);

		if ($options & self::BLOCK_WITH_FIXED_TITLE)
		{
			return (new ContentBlockWithTitle())
				->setTitle($blockTitle)
				->setContentBlock($textOrLink->setIsBold(isset($url))->setColor(Text::COLOR_BASE_90))
				->setInline()
			;
		}

		return (new LineOfTextBlocks())
			->addContentBlock(
				'title',
				ContentBlockFactory::createTitle($blockTitle)
			)
			->addContentBlock('data', $textOrLink->setIsBold(isset($url))->setColor(Text::COLOR_BASE_90))
		;
	}

	private function getMarketPanelPlacementCode(): ?string
	{
		if (Loader::includeModule('intranet'))
		{
			$placements = array_flip(\Bitrix\Intranet\Binding\Menu::getRestPlacementMap());
			$marketCode = mb_strtoupper(SectionCode::TIMELINE . '@' . CodeBuilder::getMenuCode($this->getContext()->getEntityTypeId()));

			return $placements[$marketCode] ?? null;
		}

		return null;
	}

	private function getUnpinAction(): Layout\Action
	{
		return (new RunAjaxAction('crm.timeline.item.unpin'))
			->addActionParamInt('id', $this->getModel()->getId())
			->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
			->addActionParamInt('ownerId', $this->getContext()->getEntityId())
		;
	}

	private function buildNoteBlock(): ?Note
	{
		if ($noteModel = $this->model->getNote())
		{
			$note = (new Note(
				$this->getContext(),
				$noteModel->getId(),
				$noteModel->getText(),
				$noteModel->getItemType(),
				$noteModel->getItemId(),
				$noteModel->getUpdatedBy() ? Layout\User::createFromArray($noteModel->getUpdatedBy()) : null,
			))->setSort(PHP_INT_MAX);
		}
		else
		{
			$note = Note::createEmpty(
				$this->getContext(),
				$this->getNoteItemType(),
				$this->getNoteItemId(),
			)
				->setSort(PHP_INT_MAX)
			;
		}

		return $note;
	}

	public function getNoteItemType(): int
	{
		return NoteTable::NOTE_TYPE_HISTORY;
	}

	public function getNoteItemId(): int
	{
		return $this->model->getId();
	}

	/**
	 * Returns true if item data allowed to be re-fetched (e.g. after push-message handling)
	 * @return bool
	 */
	protected function canBeReloaded(): bool
	{
		return true;
	}
}
