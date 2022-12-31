<?php declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxAction;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Localization\Loc;

class Note extends ContentBlock
{
	protected Context $context;
	protected string $text = '';

	protected ?int $id = null;

	protected int $itemType;

	private ?Layout\User $updatedBy = null;
	private int $itemId;

	public function __construct(
		Context $context,
		?int $id,
		string $text,
		int $itemType,
		int $itemId,
		?Layout\User $updatedBy = null
	)
	{
		$this->context = $context;
		$this->id = $id;
		$this->text = $text;
		if ($updatedBy)
		{
			$this->updatedBy = $updatedBy;
		}
		$this->itemType = $itemType;
		$this->itemId = $itemId;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function getProperties(): array
	{
		return [
			'id' => $this->getId(),
			'text' => $this->getText(),
			'deleteConfirmationText' => Loc::getMessage('CRM_NOTE_DELETE_CONFIRMATION'),
			'updatedBy' => $this->updatedBy,
			'saveNoteAction' => $this->buildSaveAction(),
			'deleteNoteAction' => $this->buildDeleteAction(),
		];
	}

	private function buildSaveAction(): RunAjaxAction {
		return $this->buildAction('crm.timeline.note.save');
	}

	private function buildDeleteAction(): RunAjaxAction {
		return $this->buildAction('crm.timeline.note.delete');
	}

	private function buildAction(string  $action): RunAjaxAction {
		return (new RunAjaxAction($action))
			->addActionParamInt('ownerTypeId', $this->context->getEntityTypeId())
			->addActionParamInt('ownerId', $this->context->getEntityId())
			->addActionParamInt('itemType', $this->itemType)
			->addActionParamInt('itemId', $this->itemId)
		;
	}

	public function getRendererName(): string
	{
		return 'Note';
	}

	public static function createEmpty(
		Context $context,
		int $itemType,
		int $itemId
	): self
	{
		return (new Note(
			$context,
			null,
			'',
			$itemType,
			$itemId,
			$context->getType() === \Bitrix\Crm\Service\Timeline\Context::PULL
				? null
				: Layout\User::current()
		));
	}
}