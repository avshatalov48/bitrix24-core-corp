<?php
namespace Bitrix\Sign\Blank;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sign\Blank;
use Bitrix\Sign\Document;
use Bitrix\Sign\Error;

Loc::loadMessages(__FILE__);

class Block extends \Bitrix\Sign\Internal\BaseTable
{
	/**
	 * Internal class.
	 * @var string
	 */
	public static $internalClass = 'BlockTable';

	/**
	 * Available blocks.
	 */
	private const BLOCKS = [
		Blank\Block\Text::class,
		Blank\Block\Date::class,
		Blank\Block\Number::class,
		Blank\Block\Reference::class,
		Blank\Block\MyReference::class,
		Blank\Block\MyRequisites::class,
		Blank\Block\MySign::class,
		Blank\Block\MyStamp::class,
		Blank\Block\Number::class,
		Blank\Block\Requisites::class,
		Blank\Block\Sign::class,
		Blank\Block\Stamp::class,
	];

	/**
	 * Block's id.
	 * @var int
	 */
	private $id;

	/**
	 * Block's code.
	 * @var string
	 */
	private $code;

	/**
	 * Block's title.
	 * @var string
	 */
	private $title;

	/**
	 * Block's hint (description).
	 * @var string
	 */
	private $hint;

	/**
	 * Block's section (category).
	 * @var string
	 */
	private $section;

	/**
	 * Block's position.
	 * @var array
	 */
	private $position;

	/**
	 * Block's style.
	 * @var array
	 */
	private $style;

	/**
	 * Block's data.
	 * @var array
	 */
	protected $data;

	/**
	 * Block's member part.
	 * @var int
	 */
	private $part;

	/**
	 * Block's Document. If we're in document context.
	 * @var Document
	 */
	private $document;

	/**
	 * Block's Blank.
	 * @var Blank
	 */
	private $blank;

	/**
	 * This block is for third party.
	 * @var bool
	 */
	private $thirdParty;

	/**
	 * Block's class.
	 * @var Block\Dummy
	 */
	private $class;

	/**
	 * Create instance for each block's type.
	 * @param Block\Dummy|string $blockClass Block's class.
	 */
	private function __construct(string $blockClass)
	{
		$this->class = $blockClass;
		$manifest = $blockClass::getManifest();

		$this->code = $manifest['code'] ?? '';
		$this->title = $manifest['title'] ?? '';
		$this->hint = $manifest['hint'] ?? '';
		$this->section = $manifest['section'] ?? '';
		$this->thirdParty = ($manifest['thirdParty'] ?? false) === true;
	}

	/**
	 * Sets id to the block.
	 * @param int $id Id.
	 * @return void
	 */
	public function setId(int $id): void
	{
		$this->id = $id;
	}

	/**
	 * Returns id of the block.
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * Returns true if block for third party only.
	 * @return bool
	 */
	public function isThirdParty(): bool
	{
		return $this->thirdParty;
	}

	/**
	 * Sets position to the block.
	 * @param array|null $position Position data (top / left / bottom / width / height / widthPx / heightPx / page).
	 * @return void
	 */
	public function setPosition(?array $position): void
	{
		$this->position = $position;
	}

	/**
	 * Returns position of the block.
	 * @return array|null
	 */
	public function getPosition(): ?array
	{
		return $this->position;
	}

	/**
	 * Sets style to the block.
	 * @param array|null $style Style data (cssProperty => cssPropertyValue).
	 * @return void
	 */
	public function setStyle(?array $style): void
	{
		$this->style = $style;
	}

	/**
	 * Returns style of the block.
	 * @return array|null
	 */
	public function getStyle(): ?array
	{
		return $this->style;
	}

	/**
	 * Sets data to the block.
	 * @param array|null $data Block data.
	 * @return void
	 */
	public function setPayload(?array $data): void
	{
		$this->data = $data;
	}

	/**
	 * Sets blank, within which this block.
	 * @param Blank $blank Blank instance.
	 * @return void
	 */
	public function setBlank(Blank $blank): void
	{
		$this->blank = $blank;
	}

	/**
	 * Returns block's Blank.
	 * @return Blank|null
	 */
	public function getBlank(): ?Blank
	{
		return $this->blank;
	}

	/**
	 * Sets document, within which this block now.
	 * @param Document|null $document Document instance.
	 * @return void
	 */
	public function setDocument(?Document $document): void
	{
		if ($document)
		{
			$this->blank = $document->getBlank();
			$this->document = $document;
		}
	}

	/**
	 * Sets member part to the block.
	 * @param int $part Member part.
	 * @return void
	 */
	public function setMemberPart(int $part): void
	{
		$this->part = $part;
	}

	/**
	 * Calls when block success added or updated on blank.
	 * @return void
	 */
	public function wasUpdatedOnBlank(): void
	{
		$this->class::wasUpdatedOnBlank($this);
	}

	/**
	 * Must return false, if block data is not correct for saving.
	 * @return Result
	 */
	public function checkBeforeSave(): Result
	{
		return $this->class::checkBeforeSave($this);
	}

	/**
	 * Returns block's title.
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * Returns block's hint.
	 * @return string
	 */
	public function getHint(): string
	{
		return $this->hint;
	}

	/**
	 * Returns block's section.
	 * @return string
	 */
	public function getSection(): string
	{
		return $this->section;
	}

	/**
	 * Returns block's code.
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * Returns block class' form manifest.
	 * @param bool $externalProcessing Before send data prepare by external block classes (true by default).
	 * @return array
	 */
	public function getData(bool $externalProcessing = true): array
	{
		$data = [
			'id' => $this->id,
			'code' => $this->code,
			'title' => $this->title,
			'hint' => $this->hint,
			'position' => $this->position,
			'style' => $this->style,
			'part' => $this->part,
			'data' => !empty($this->data) ? $this->data : $this->class::getDefaultData(),
		];
		if ($externalProcessing)
		{
			return $this->class::getData($data, $this->document);
		}

		return $data;
	}

	/**
	 * Returns block class' view data
	 * @param bool $externalProcessing Before send data prepare by external block classes (true by default).
	 * @return array
	 */
	public function getViewData(bool $externalProcessing = true): array
	{
		$data = [
			'id' => $this->id,
			'code' => $this->code,
			'title' => $this->title,
			'hint' => $this->hint,
			'position' => $this->position,
			'style' => $this->style,
			'part' => $this->part,
			'data' => !empty($this->data) ? $this->data : $this->class::getDefaultData(),
		];
		if ($externalProcessing)
		{
			return $this->class::getViewData($data, $this->document);
		}

		return $data;
	}

	/**
	 * @return Document
	 */
	public function getDocument(): Document
	{
		return $this->document;
	}

	/**
	 * Returns true, if block is empty (no data).
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return $this->class::isEmpty(
			$this->getData()
		);
	}

	/**
	 * Returns member part of this block.
	 * @return int|null
	 */
	public function getPart(): ?int
	{
		return $this->part;
	}

	/**
	 * Removes current block.
	 * @return bool
	 */
	public function remove(): bool
	{
		if ($this->id)
		{
			$res = self::delete($this->id);
			if ($res->isSuccess())
			{
				return true;
			}

			Error::getInstance()->addFromResult($res);
		}

		return false;
	}

	/**
	 * Returns sections as pairs of code and title.
	 *
	 * @return array<<code>, <title>>
	 */
	public static function getSections(): array
	{
		return [
			['code' => Section::INITIATOR, 'title' => Loc::getMessage('SIGN_CORE_BLOCK_SECTIONS_INITIATOR')],
			['code' => Section::PARTNER, 'title' => Loc::getMessage('SIGN_CORE_BLOCK_SECTIONS_PARTNER')],
			['code' => Section::GENERAL, 'title' => Loc::getMessage('SIGN_CORE_BLOCK_SECTIONS_GENERAL')],
		];
	}

	/**
	 * Returns repository of blocks.
	 * @return Block[]
	 */
	public static function getRepository(): array
	{
		static $blocks = [];

		if ($blocks)
		{
			return $blocks;
		}

		foreach (self::BLOCKS as $blockClass)
		{
			/** @var Block\Dummy $blockClass */
			$block = new self($blockClass);
			$blocks[$block->getCode()] = $block;
		}

		return $blocks;
	}

	/**
	 * Returns block instance by code.
	 * @param string $code Block code.
	 * @return self|null
	 */
	public static function getByCode(string $code): ?self
	{
		$repository = self::getRepository();

		if ($repository[$code] ?? null)
		{
			return clone $repository[$code];
		}

		return null;
	}
}
