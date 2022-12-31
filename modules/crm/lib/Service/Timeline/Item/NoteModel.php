<?php declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item;

class NoteModel
{

	private ?int $id;
	private int $itemId;
	private int $itemType;
	private string $text;
	private ?array $updatedBy = null;

	public function __construct(?int $id, int $itemType, int $itemId, string $text, ?array $updatedBy)
	{
		$this->text = $text;
		$this->updatedBy = $updatedBy;
		$this->id = $id;
		$this->itemId = $itemId;
		$this->itemType = $itemType;
	}

	public static function createFromArray(array $data): self
	{
		return new self(
			(int)$data['ID'],
			(int)$data['ITEM_TYPE'],
			(int)$data['ITEM_ID'],
			(string)$data['TEXT'],
			is_array($data['UPDATED_BY']) ? $data['UPDATED_BY'] : null,
		);
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function getUpdatedBy(): ?array
	{
		return $this->updatedBy;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getItemId(): int
	{
		return $this->itemId;
	}

	public function getItemType(): int
	{
		return $this->itemType;
	}
}