<?php declare(strict_types=1);

namespace Bitrix\AI\Integration\Ui\EntitySelector;

use Bitrix\AI\Container;
use Bitrix\AI\Exception\ContainerException;
use Bitrix\AI\SharePrompt\Service\CategoryService;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;

class PromptCategoriesProvider extends BaseProvider
{
	protected const ENTITY_ID = 'prompt-category';

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * @return bool
	 */
	public function isAvailable(): bool
    {
        return $GLOBALS['USER']->isAuthorized();
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $ids): array
    {
		$list = $this->getCategoryService()->getCategoriesWithNameByCodes($ids);

		return $this->prepareDialogItems($list);
    }

	/**
	 * @param Dialog $dialog
	 *
	 * @return void
	 */
	public function fillDialog(Dialog $dialog): void
	{
		$categories = $this->getCategoryService()->getCategoryListWithTranslations();

		$dialog->addRecentItems($this->prepareDialogItems($categories));
	}

	/**
	 * @param array $categories
	 *
	 * @return array
	 */
	private function prepareDialogItems(array $categories): array
	{
		$items = [];

		foreach ($categories as $category)
		{
			$items[] = new Item([
				'id' => $category['code'],
				'entityId' => self::ENTITY_ID,
				'title' => $category['name'],
			]);
		}

		return $items;
	}

	/**
	 * @return CategoryService
	 * @throws ContainerException
	 */
	protected function getCategoryService(): CategoryService
	{
		return Container::init()->getItem(CategoryService::class);
	}
}
