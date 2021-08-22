<?php

namespace Bitrix\Voximplant\Integration\UI\EntitySelector;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\Tab;
use Bitrix\Voximplant\Model\QueueTable;

Loader::requireModule('ui');

class GroupProvider extends BaseProvider
{
	public function __construct()
	{
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function getItems(array $ids): array
	{
		return [];
	}

	public function getSelectedItems(array $ids): array
	{
		return [];
	}

	public function fillDialog(Dialog $dialog): void
	{
		$cursor = QueueTable::getList([
			'select' => ['*']
		]);

		while ($row = $cursor->fetch())
		{
			$item = new Item([
				'id' => $row['ID'],
				'entityId' => 'voximplant_group',
				'title' => $row['NAME'],
				'tabs' => 'voximplant_groups',
				'searchable' => true,
				'availableInRecentTab' => true,
			]);
			$dialog->addItem($item);
		}

		$icon = 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2232%22%20height%3D%2232%22%20viewBox%3D%220%200'
			.'%2032%2032%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%3Cpath%20fil'
			.'l-rule%3D%22evenodd%22%20clip-rule%3D%22evenodd%22%20d%3D%22M19.289%2023.9787H19.8025C20.907%2023.9787%20'
			.'21.8025%2023.0832%2021.8025%2021.9787V10.0211C21.8025%208.91655%2020.907%208.02112%2019.8025%208.02112H19'
			.'.289C19.647%208.3825%2019.8681%208.87969%2019.8681%209.42852V22.5712C19.8681%2023.1201%2019.647%2023.6173'
			.'%2019.289%2023.9787ZM8.32874%208.02121C7.22417%208.02121%206.32874%208.91664%206.32874%2010.0212V21.9788C'
			.'6.32874%2023.0833%207.22417%2023.9788%208.32873%2023.9788H15.9342C17.0388%2023.9788%2017.9342%2023.0833%2'
			.'017.9342%2021.9788V10.0212C17.9342%208.91664%2017.0388%208.02121%2015.9342%208.02121H8.32874ZM14.0697%202'
			.'1.0845L13.8181%2019.3288C13.7295%2018.7045%2013.0714%2018.3044%2012.4437%2018.4119L11.7244%2018.5361C11.5'
			.'73%2018.5594%2011.4137%2018.455%2011.3749%2018.3101C11.0548%2016.7888%2011.1057%2015.1633%2011.5172%2013.'
			.'6619C11.5643%2013.511%2011.7205%2013.4087%2011.879%2013.4438L12.5816%2013.584C13.2105%2013.7094%2013.8792'
			.'%2013.3513%2014.0102%2012.7286L14.3349%2011C14.4383%2010.4711%2014.036%209.98291%2013.4969%209.96299C11.4'
			.'448%209.91647%209.24504%2011.2007%209.11331%2015.9359C8.98155%2020.6711%2011.1192%2022.0302%2013.1714%202'
			.'2.0768C13.7109%2022.0969%2014.1426%2021.6181%2014.0697%2021.0845ZM23.6714%2023.9787H23.158C23.516%2023.61'
			.'73%2023.7371%2023.1201%2023.7371%2022.5712V9.42852C23.7371%208.87969%2023.516%208.3825%2023.1581%208.0211'
			.'2H23.6714C24.7759%208.02112%2025.6714%208.91655%2025.6714%2010.0211V21.9787C25.6714%2023.0832%2024.7759%2'
			.'023.9787%2023.6714%2023.9787Z%22%20fill%3D%22%23ABB1B8%22%2F%3E%0A%3C%2Fsvg%3E%0A';

		$dialog->addTab(new Tab([
			'id' => 'voximplant_groups',
			'title' => Loc::getMessage("VOX_GROUP_PROVIDER_TELEPHONY_GROUPS"),
			'icon' => [
				'default' => $icon,
				'selected' => str_replace('ABB1B8', 'fff', $icon),
			]
		]));
	}


}