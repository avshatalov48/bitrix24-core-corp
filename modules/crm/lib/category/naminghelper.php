<?php

namespace Bitrix\Crm\Category;

use Bitrix\Crm\Category\Entity\ItemCategory;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class NamingHelper
 *
 * @package Bitrix\Crm\Category
 */
class NamingHelper
{
	private const SYSTEM_CATEGORY_LANG_PHRASE_PREFIX = 'SC_PFX';

	/** @var bool */
	private bool $isCategoriesMapLoaded = false;

	/** @var ItemCategory[] */
	private array $categories = [];

	/** @var NamingHelper */
	private static $instance;

	private function __construct()
	{
	}

	/**
	 * @return NamingHelper
	 */
	public static function getInstance(): NamingHelper
	{
		if (is_null(static::$instance))
		{
			static::$instance = new NamingHelper();
		}

		return static::$instance;
	}

	/**
	 * @param int $categoryId
	 * @return string|null
	 */
	public function getSingleName(int $categoryId): ?string
	{
		if (!$this->isCategoriesMapLoaded)
		{
			$this->loadCategoriesMap();
		}

		return isset($this->categories[$categoryId]) ? $this->categories[$categoryId]->getSingleName() : null;
	}

	/**
	 * @param int $categoryId
	 * @return string|null
	 */
	public function getPluralName(int $categoryId): ?string
	{
		if (!$this->isCategoriesMapLoaded)
		{
			$this->loadCategoriesMap();
		}

		return isset($this->categories[$categoryId]) ? $this->categories[$categoryId]->getName() : null;
	}

	/**
	 * @param string $code
	 * @param int|null $categoryId
	 * @param array|null $replace
	 * @return string|null
	 */
	public function getLangPhrase(string $code, ?int $categoryId, ?array $replace = null): ?string
	{
		if (!$categoryId)
		{
			return Loc::getMessage($code, $replace);
		}

		if (!$this->isCategoriesMapLoaded)
		{
			$this->loadCategoriesMap();
		}

		if (!isset($this->categories[$categoryId]))
		{
			return Loc::getMessage($code, $replace);
		}

		$category = $this->categories[$categoryId];
		if (!$category->getIsSystem())
		{
			return Loc::getMessage($code, $replace);
		}

		$result = Loc::getMessage(
			sprintf(
				'%s_%s_%s',
				$code,
				self::SYSTEM_CATEGORY_LANG_PHRASE_PREFIX,
				$category->getCode()
			),
			$replace
		);
		if ($result)
		{
			return $result;
		}

		return Loc::getMessage($code, $replace);
	}

	private function loadCategoriesMap(): void
	{
		$list = ItemCategoryTable::query()
			->setSelect(['*'])
			->exec()
		;

		while ($item = $list->fetchObject())
		{
			$category = new ItemCategory($item);

			$this->categories[$category->getId()] = $category;
		}

		$this->isCategoriesMapLoaded = true;
	}
}
