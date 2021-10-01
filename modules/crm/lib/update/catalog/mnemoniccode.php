<?php
namespace Bitrix\Crm\Update\Catalog;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Crm\Product;
use Bitrix\Iblock;

final class MnemonicCode extends Main\Update\Stepper
{
	public const PARENT_IBLOCK = 'P';
	public const OFFER_IBLOCK = 'O';

	public const UPDATE_ELEMENTS = 'E';
	public const UPDATE_SECTIONS = 'S';

	protected const INDEX_IBLOCK = 0;
	protected const INDEX_ACTION = 1;

	protected const PAGE_SIZE = 20;

	protected $iblockId;
	/** @var \CIBlockElement */
	protected $element;
	/** @var \CIBlockSection */
	protected $section;

	public function __destruct()
	{
		unset($this->section);
		unset($this->element);

		parent::__destruct();
	}

	public static function getTitle()
	{
		return Loc::getMessage('CRM_MNEMONIC_CODE_STEPPER_TITLE');
	}

	public function execute(array &$option): bool
	{
		$result = Main\Update\Stepper::FINISH_EXECUTION;
		if (
			!ModuleManager::isModuleInstalled('bitrix24')
			|| !Loader::includeModule('iblock')
			|| !Loader::includeModule('catalog')
			|| !Loader::includeModule('crm')
		)
		{
			return $result;
		}

		if (!$this->initialize())
		{
			return $result;
		}

		if (empty($option) || empty($option['count']))
		{
			$option = $this->getCurrentStatus();
		}

		if (empty($option))
		{
			return $result;
		}

		$option = $this->normalizeOptions($option);

		if ($option['count'] > 0)
		{
			$option = $this->createMnemonicCode($option);
		}
		if (!empty($option) && $option['count'] > 0)
		{
			$result = Main\Update\Stepper::CONTINUE_EXECUTION;
		}
		else
		{
			$nextState = $this->getNextState($this->getOuterParams());
			$this->setOuterParams($nextState);
			if (!empty($nextState))
			{
				$result = Main\Update\Stepper::CONTINUE_EXECUTION;
			}
		}

		return $result;
	}

	protected function normalizeOuterParams(): bool
	{
		$result = true;
		$parameters = $this->getOuterParams();
		if (empty($parameters))
		{
			$this->setOuterParams([
				self::INDEX_IBLOCK => self::PARENT_IBLOCK,
				self::INDEX_ACTION => self::UPDATE_SECTIONS,
			]);
		}
		else
		{
			if (
				!isset($parameters[self::INDEX_IBLOCK])
				|| (
					$parameters[self::INDEX_IBLOCK] !== self::PARENT_IBLOCK
					&& $parameters[self::INDEX_IBLOCK] !== self::OFFER_IBLOCK
				)
			)
			{
				$result = false;
			}
			if (
				!isset($parameters[self::INDEX_ACTION])
				|| (
					$parameters[self::INDEX_ACTION] !== self::UPDATE_ELEMENTS
					&& $parameters[self::INDEX_ACTION] !== self::UPDATE_SECTIONS
				)
			)
			{
				$result = false;
			}
			if (!$result)
			{
				$this->setOuterParams([]);
			}
		}

		return $result;
	}

	protected function normalizeOptions(array $options): array
	{
		return [
			'lastId' => (int)($options['lastId'] ?? 0),
			'steps' => (int)($options['steps'] ?? 0),
			'errors' => (int)($options['errors'] ?? 0),
			'count' => (int)($options['count'] ?? 0),
		];
	}

	protected function initialize(): bool
	{
		$this->element = new \CIBlockElement();
		$this->section = new \CIBlockSection();

		if (!$this->normalizeOuterParams())
		{
			return false;
		}

		$parameters = $this->getOuterParams();
		switch ($parameters[self::INDEX_IBLOCK])
		{
			case self::PARENT_IBLOCK:
				$iblockId = Product\Catalog::getDefaultId();
				break;
			case self::OFFER_IBLOCK:
				$iblockId = Product\Catalog::getDefaultOfferId();
				break;
			default:
				$iblockId = null;
				break;
		}
		if ($iblockId === null)
		{
			return false;
		}
		$this->setIblockId($iblockId);

		return true;
	}

	protected function getCurrentStatus(): array
	{
		$result = [];
		$iblockId = $this->getIblockId();
		$parameters = $this->getOuterParams();
		if ($iblockId === null || empty($parameters))
		{
			return $result;
		}

		$filter = [
			'=IBLOCK_ID' => $iblockId,
			'=CODE' => '',
		];
		switch ($parameters[self::INDEX_ACTION])
		{
			case self::UPDATE_SECTIONS:
				$result = $this->getDefaultProgress((int)IBlock\SectionTable::getCount($filter));
				break;
			case self::UPDATE_ELEMENTS:
				$result = $this->getDefaultProgress((int)IBlock\ElementTable::getCount($filter));
				break;
		}

		return $result;
	}

	protected function setIblockId(int $iblockId): void
	{
		$this->iblockId = $iblockId;

		$this->element->setIblock($this->iblockId);
		$this->section->setIblock($this->iblockId);
	}

	protected function getIblockId(): ?int
	{
		return $this->iblockId;
	}

	protected function getNextState(array $state): array
	{
		$result = [];
		switch ($state[self::INDEX_ACTION])
		{
			case self::UPDATE_SECTIONS:
				$result = [
					self::INDEX_IBLOCK => $state[self::INDEX_IBLOCK],
					self::INDEX_ACTION => self::UPDATE_ELEMENTS,
				];
				break;
			case self::UPDATE_ELEMENTS:
				if ($state[self::INDEX_IBLOCK] === self::PARENT_IBLOCK)
				{
					$result = [
						self::INDEX_IBLOCK => self::OFFER_IBLOCK,
						self::INDEX_ACTION => self::UPDATE_SECTIONS,
					];
				}
				break;
		}

		return $result;
	}

	protected function getDefaultProgress(int $count): array
	{
		return [
			'lastId' => 0,
			'steps' => 0,
			'errors' => 0,
			'count' => $count,
		];
	}

	protected function getCurrentAction(): ?string
	{
		$parameters = $this->getOuterParams();
		if (isset($parameters[self::INDEX_ACTION]))
		{
			return $parameters[self::INDEX_ACTION];
		}

		return null;
	}

	protected function createMnemonicCode(array $options): array
	{
		\CIBlock::disableClearTagCache();
		\CTimeZone::Disable();
		switch ($this->getCurrentAction())
		{
			case self::UPDATE_SECTIONS:
				$options = $this->createSectionMnemonicCode($options);
				break;
			case self::UPDATE_ELEMENTS:
				$options = $this->createElementMnemonicCode($options);
				break;
			default:
				$options = [];
				break;
		}
		\CTimeZone::Enable();
		\CIBlock::enableClearTagCache();
		\CIBlock::clearIblockTagCache($this->getIblockId());

		return $options;
	}

	protected function createSectionMnemonicCode(array $options): array
	{
		$found = false;
		$config = [
			'CHECK_UNIQUE' => 'Y',
			'CHECK_SIMILAR' => 'Y',
		];
		$iterator = Iblock\SectionTable::getList([
			'select' => [
				'ID',
				'IBLOCK_ID',
				'NAME',
				'TIMESTAMP_X',
				'MODIFIED_BY',
			],
			'filter' => $this->createFilter($options),
			'order' => ['ID' => 'ASC'],
			'limit' => self::PAGE_SIZE
		]);
		while ($row = $iterator->fetch())
		{
			$found = true;
			$row['ID'] = (int)$row['ID'];
			$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
			$code = $this->section->createMnemonicCode(
				$row,
				$config
			);
			if ($code !== null)
			{
				$this->section->LAST_ERROR = '';
				$internalResult = $this->section->Update(
					$row['ID'],
					[
						'CODE' => $code,
						'MODIFIED_BY' => $row['MODIFIED_BY'],
					]
				);
				if (!$internalResult)
				{
					$options['errors']++;
				}
			}
			else
			{
				$options['errors']++;
			}
			$options['lastId'] = $row['ID'];
			$options['steps']++;
		}
		unset($row, $iterator);

		if (!$found)
		{
			$options = [];
		}

		return $options;
	}

	protected function createElementMnemonicCode(array $options): array
	{
		$found = false;
		$config = [
			'CHECK_UNIQUE' => 'Y',
			'CHECK_SIMILAR' => 'Y',
		];
		$iterator = Iblock\ElementTable::getList([
			'select' => [
				'ID',
				'NAME',
				'IBLOCK_ID',
			],
			'filter' => $this->createFilter($options),
			'order' => ['ID' => 'ASC'],
			'limit' => self::PAGE_SIZE
		]);
		while ($row = $iterator->fetch())
		{
			$found = true;
			$row['ID'] = (int)$row['ID'];
			$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
			$code = $this->element->createMnemonicCode(
				$row,
				$config
			);
			if ($code !== null)
			{
				$this->element->LAST_ERROR = '';
				$internalResult = $this->element->Update(
					$row['ID'],
					[
						'CODE' => $code,
						'TIMESTAMP_X' => null,
					]
				);
				if (!$internalResult)
				{
					$options['errors']++;
				}
			}
			else
			{
				$options['errors']++;
			}
			$options['lastId'] = $row['ID'];
			$options['steps']++;
		}
		unset($row, $iterator);

		if (!$found)
		{
			$options = [];
		}

		return $options;
	}

	protected function createFilter(array $options): array
	{
		$result = [
			'=IBLOCK_ID' => $this->getIblockId(),
			'=CODE' => '',
		];

		if (isset($options['lastId']))
		{
			$id = (int)$options['lastId'];
			if ($id > 0)
			{
				$result['>ID'] = $id;
			}
		}

		return $result;
	}
}
