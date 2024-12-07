<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlockDto;
use Bitrix\Crm\Integration\Intranet\BindingMenu\CodeBuilder;
use Bitrix\Crm\Integration\Intranet\BindingMenu\SectionCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxAction;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Note;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Converter;
use Bitrix\Crm\Service\Timeline\Layout\Factory\RestAppConfigurable\ActionFactory;
use Bitrix\Crm\Service\Timeline\Layout\Factory\RestAppConfigurable\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Header\ChangeStreamButton;
use Bitrix\Crm\Service\Timeline\Layout\MarketPanel;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Crm\Timeline\Entity\NoteTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use Bitrix\Rest\AppTable;

abstract class Configurable extends Item
{
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
			'timestamp' => $this->getModel()->getDate()?->getTimestamp(),
			'sort' => $this->getSort(),
			'languageId' => \Bitrix\Main\Context::getCurrent()?->getLanguage(),
			'targetUsersList' => $this->getListOfTargetUsers(),
			'canBeReloaded' => $this->canBeReloaded(),
			'color' => $this->getColor(),
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
				->setBackgroundColor($this->isScheduled() ? $this->getIconBackgroundColor() : null)
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

	public function getIconBackgroundColor(): ?string
	{
		$color = $this->getColor();

		if (!$color)
		{
			return null;
		}

		return $color['iconBackground'] ?? null;
	}

	public function canUseColorSelector(): bool
	{
		return false;
	}

	public function getColor(): ?array
	{
		return null;
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
			&& !$this->isScheduled()
			&& $this->getModel()->isFixed()
		;
		if (!$canBeUnFixed)
		{
			return null;
		}

		return (new ChangeStreamButton())
			->setTypeUnpin()
			->setDisableIfReadonly()
			->setAction($this->getUnpinAction())
		;
	}

	/**
	 * By default, item is pinnable if it has title
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

		if ($this->needShowRestAppLayoutBlocks())
		{
			foreach ($this->getBuiltRestAppLayoutBlocks() as $restAppLayoutBlock)
			{
				$blocks[$restAppLayoutBlock->getContentBlockName()] = $restAppLayoutBlock;
			}
		}

		if ($this->needShowNotes())
		{
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

	public function needShowRestAppLayoutBlocks(): bool
	{
		return true;
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
			&& !$this->isScheduled()
			&& !$this->getModel()->isFixed()
		;

		$canBeUnFixed =
			$this->isPinnable()
			&& !$this->isScheduled()
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
				->setAction($this->getPinAction())
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

	public function isScheduled(): bool
	{
		return $this->getModel()->isScheduled();
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
						? new Redirect(new Uri(\Bitrix\Crm\Integration\Market\Router::getBasePath() . '?placement=' . $placementCode))
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
		$communication = $this->getAssociatedEntityModel()?->get('COMMUNICATION') ?? [];
		if (empty($communication))
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\Client($communication, $options))
			->setTitle($blockTitle ?? Loc::getMessage("CRM_TIMELINE_CLIENT_TITLE"))
			->build()
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

	protected function getUnpinAction(): Layout\Action
	{
		return (new RunAjaxAction('crm.timeline.item.unpin'))
			->addActionParamInt('id', $this->getModel()->getId())
			->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
			->addActionParamInt('ownerId', $this->getContext()->getEntityId())
		;
	}

	protected function getPinAction(): Layout\Action
	{
		return (new RunAjaxAction('crm.timeline.item.pin'))
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

	/**
	 * @return Layout\Body\ContentBlock\RestAppLayoutBlocks[]
	 */
	protected function getBuiltRestAppLayoutBlocks(): array
	{
		$layoutBlocksModels = $this->model->getRestAppLayoutBlocksModels();

		$builtRestAppLayoutBlocks = [];
		foreach ($layoutBlocksModels as $layoutBlocksModel)
		{
			$clientId = $layoutBlocksModel->getClientId();
			$restApp = $this->getAppInfo($clientId);
			if ($restApp === null)
			{
				continue;
			}

			$actionFactory = new ActionFactory($this, $clientId, $restApp['ID'] ?? 0);
			$contentBlockFactory = new ContentBlockFactory($this, $actionFactory);

			$createContentBlock = static fn(ContentBlockDto $dto) => $contentBlockFactory->createByDto($dto);
			$contentBlocks = array_map($createContentBlock, $layoutBlocksModel->getContentBlocks());

			$builtRestAppLayoutBlocks[] = (new Layout\Body\ContentBlock\RestAppLayoutBlocks(
				$layoutBlocksModel->getItemTypeId(),
				$layoutBlocksModel->getItemId(),
				$restApp,
				$contentBlocks,
			))->setSort(PHP_INT_MAX - 1);
		}

		return $builtRestAppLayoutBlocks;
	}

	private function getAppInfo(string $clientId): ?array
	{
		if (Loader::includeModule('rest'))
		{
			return AppTable::getByClientId($clientId) ?? null;
		}

		return null;
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
	 * Returns list of user ids that this item was built for
	 *
	 * @return int[]
	 */
	protected function getListOfTargetUsers(): array
	{
		if ($this->isBuiltOnlyForCurrentUser())
		{
			return [$this->getContext()->getUserId()];
		}

		return [];
	}

	protected function isBuiltOnlyForCurrentUser(): bool
	{
		return false;
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
