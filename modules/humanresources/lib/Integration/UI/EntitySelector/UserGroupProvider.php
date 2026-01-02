<?php

namespace Bitrix\HumanResources\Integration\UI\EntitySelector;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Selector\UserGroups;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\Tab;

class UserGroupProvider extends BaseProvider
{
	public function __construct()
	{
		parent::__construct();
	}

	public function isAvailable(): bool
	{
		return true;
	}

	/**
	 * @param array<int> $ids
	 *
	 * @return Item[]
	 */
	public function getItems(array $ids): array
	{
		$result = [];

		$userGroupsItems = $this->getItemsData();

		$itemsToProcess = !empty($ids)
			? array_intersect_key($userGroupsItems, array_flip($ids))
			: $userGroupsItems
		;

		foreach ($itemsToProcess as $id => $groupData)
		{
			$result[] = new Item(
				[
					'id' => $id,
					'entityId' => 'user-groups',
					'tabs' => ['user-groups'],
					'title' => $this->buildTitle($id, $groupData['name']),
				],
			);
		}

		return $result;
	}

	public function fillDialog(Dialog $dialog): void
	{
		parent::fillDialog($dialog);

		$dialog->addTab(
			new Tab(
				[
					'id' => 'user-groups',
					'title' => Loc::getMessage('HUMANRESOURCES_UI_ENTITYSELECTOR_USERGROUPPROVIDER_TAB_TITLE'),
					'icon' => [
						'default' => $this->getCityIcon(),
						'selected' => $this->getSelectedCityIcon(),
					],
				],
			),
		);

		$dialog->addItems($this->getItems([]));
	}

	private function buildTitle(string $id, ?string $userGroupOriginalName): string
	{
		return match ($id)
		{
			AccessCode::ACCESS_DIRECTOR . '0' => Loc::getMessage('HUMANRESOURCES_UI_ENTITYSELECTOR_USERGROUPPROVIDER_TITLE_DIRECTOR_MSGVER_1'),
			AccessCode::ACCESS_EMPLOYEE . '0' => Loc::getMessage('HUMANRESOURCES_UI_ENTITYSELECTOR_USERGROUPPROVIDER_TITLE_EMPLOYEE_MSGVER_1'),
			AccessCode::ACCESS_DEPUTY . '0' => Loc::getMessage('HUMANRESOURCES_UI_ENTITYSELECTOR_USERGROUPPROVIDER_TITLE_DEPUTY_MSGVER_1'),
			default => $userGroupOriginalName ?? '',
		};
	}

	private function getCityIcon(): string
	{
		$svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M15.0369 10.3196C15.0369 10.2708 15.0428 10.2221 15.0543 10.1747C15.1327 9.85457 15.4504 9.65992 15.7639 9.73996L18.4943 10.437C18.7548 10.5035 18.9376 10.7425 18.9376 11.0167V16.5253H19.9127V18.517H4.31006V16.5253H5.28523V6.87877C5.28523 6.39044 5.63197 5.97415 6.10396 5.89582L12.9301 4.7629C12.9819 4.75432 13.0341 4.75 13.0865 4.75C13.6251 4.75 14.0618 5.19586 14.0618 5.74585V16.5253H15.0369V10.3196ZM9.35806 16.6811V13.4688H7.52246V16.6811H9.35806ZM12.1115 15.3044V13.4688H10.2759V15.3044H12.1115ZM18.0771 13.0098H16.2415V14.8454H18.0771V13.0098ZM12.1115 7.50293H10.2759V9.33853H12.1115V7.50293ZM9.35806 7.50293H7.52246V9.33853H9.35806V7.50293ZM12.1114 10.7157H10.2758V12.5513H12.1114V10.7157ZM9.35796 10.7157H7.52236V12.5513H9.35796V10.7157Z" fill="#ABB1B8"/>
		</svg>'
		;

		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}

	private function getSelectedCityIcon(): string
	{
		$svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M15.0369 10.3196C15.0369 10.2708 15.0428 10.2221 15.0543 10.1747C15.1327 9.85457 15.4504 9.65992 15.7639 9.73996L18.4943 10.437C18.7548 10.5035 18.9376 10.7425 18.9376 11.0167V16.5253H19.9127V18.517H4.31006V16.5253H5.28523V6.87877C5.28523 6.39044 5.63197 5.97415 6.10396 5.89582L12.9301 4.7629C12.9819 4.75432 13.0341 4.75 13.0865 4.75C13.6251 4.75 14.0618 5.19586 14.0618 5.74585V16.5253H15.0369V10.3196ZM9.35806 16.6811V13.4688H7.52246V16.6811H9.35806ZM12.1115 15.3044V13.4688H10.2759V15.3044H12.1115ZM18.0771 13.0098H16.2415V14.8454H18.0771V13.0098ZM12.1115 7.50293H10.2759V9.33853H12.1115V7.50293ZM9.35806 7.50293H7.52246V9.33853H9.35806V7.50293ZM12.1114 10.7157H10.2758V12.5513H12.1114V10.7157ZM9.35796 10.7157H7.52236V12.5513H9.35796V10.7157Z" fill="#FFFFFF"/>
		</svg>'
		;

		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}

	/**
	 * @return array<string, array{id: int, entityId: 0, name: string, desc: string}>
	 */
	private function getItemsData(): array
	{
		return [
			...((new UserGroups())->getData()['ITEMS'] ?? []),
			...$this->getTeamItems(),
		];
	}

	/**
	 * @return array<string, array{id: int, entityId: 0, name: string, desc: string}>
	 */
	private function getTeamItems(): array
	{
		return [
			AccessCode::ACCESS_TEAM_DIRECTOR . '0' => [
				'id' => AccessCode::ACCESS_TEAM_DIRECTOR . '0',
				'entityId' => 0,
				'name' => Loc::getMessage('HUMANRESOURCES_UI_ENTITYSELECTOR_USERGROUPPROVIDER_TITLE_TEAM_DIRECTOR'),
				'desc' => '',
			],
			AccessCode::ACCESS_TEAM_EMPLOYEE. '0' => [
				'id' => AccessCode::ACCESS_EMPLOYEE . '0',
				'entityId' => 0,
				'name' => Loc::getMessage('HUMANRESOURCES_UI_ENTITYSELECTOR_USERGROUPPROVIDER_TITLE_TEAM_MEMBER'),
				'desc' => '',
			],
			AccessCode::ACCESS_TEAM_DEPUTY . '0' => [
				'id' => AccessCode::ACCESS_TEAM_DEPUTY . '0',
				'entityId' => 0,
				'name' => Loc::getMessage('HUMANRESOURCES_UI_ENTITYSELECTOR_USERGROUPPROVIDER_TITLE_TEAM_DEPUTY'),
				'desc' => '',
			],
		];
	}
}
