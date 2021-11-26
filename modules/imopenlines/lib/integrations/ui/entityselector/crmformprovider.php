<?php
namespace Bitrix\ImOpenlines\Integrations\UI\EntitySelector;

use Bitrix\Main\Loader;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class CrmFormProvider extends BaseProvider
{
	protected const ENTITY_ID = 'imopenlines-crm-form';

	public function __construct(array $options = [])
	{
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		return $GLOBALS['USER']->isAuthorized();
	}

	public function getItems(array $ids): array
	{
		return $this->getForms([
			'formIds' => $ids
		]);
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->getItems($ids);
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$forms = $this->getForms([
			'searchQuery' => $searchQuery->getQuery(),
		]);

		$dialog->addItems($this->prepareDialogItems($forms));
	}

	public function fillDialog(Dialog $dialog): void
	{
		$forms = $this->getForms();
		$dialog->addRecentItems($this->prepareDialogItems($forms));
	}

	private function getForms(array $options = []): array
	{
		$forms = [];
		if (!Loader::includeModule('crm'))
		{
			return $forms;
		}

		$query = \Bitrix\Crm\WebForm\Internals\FormTable::query();

		$query->addSelect('*');
		$query->where([
			['ACTIVE', 'Y'],
			['IS_CALLBACK_FORM', 'N']
	  	]);

		$query->addOrder('ID', 'DESC');

		if (isset($options['formIds']) && is_array($options['formIds']))
		{
			$query->whereIn('ID', $options['formIds']);
		}

		if (isset($options['searchQuery']))
		{
			$query->whereLike('NAME', $options['searchQuery'] . '%');
		}

		$forms = $query->exec()->fetchAll();

		return $forms;
	}

	private function prepareDialogItems(array $forms): array
	{
		$items = [];
		foreach ($forms as $form)
		{
			$formCustomData = [
				'ID' => $form['ID'],
				'CODE' => $form['CODE'],
				'SEC' => $form['SECURITY_CODE'],
				'NAME' => $form['NAME']
			];

			$items[] = new Item([
				'id' => $form['ID'],
				'entityId' => self::ENTITY_ID,
				'title' => $form['NAME'],
				'customData' => $formCustomData,
			]);
		}

		return $items;
	}
}
