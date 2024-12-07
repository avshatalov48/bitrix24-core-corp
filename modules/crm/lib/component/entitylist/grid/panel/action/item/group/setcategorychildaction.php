<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class SetCategoryChildAction extends GroupChildAction
{
	public function __construct(private Factory $factory)
	{
		if (!$this->factory->isCategoriesEnabled())
		{
			throw new InvalidOperationException('SetCategory action is not available for types without categories');
		}
	}

	public static function getId(): string
	{
		return 'set_category';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_SET_CATEGORY');
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		// action is done on frontend via crm.autorun
		return null;
	}

	protected function getOnchange(): Onchange
	{
		$onchange = new Onchange();

		$onchange->addAction([
			'ACTION' => Actions::SHOW,
			'DATA' => [
				['ID' => \Bitrix\Main\Grid\Panel\DefaultValue::FOR_ALL_CHECKBOX_ID],
			],
		]);

		$dropdownContainerId = 'action_set_category';
		$dropdownValueId = $dropdownContainerId . '_control';

		$categoriesList = [];
		$permissions = Container::getInstance()->getUserPermissions();
		foreach ($this->factory->getCategories() as $category)
		{
			if ($permissions->checkAddPermissions($this->factory->getEntityTypeId(), $category->getId()))
			{
				$categoriesList[] = [
					'NAME' => $category->getName(),
					'VALUE' => $category->getId(),
				];
			}
		}

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				[
					'TYPE' => Types::DROPDOWN,
					'ID' => $dropdownContainerId,
					'NAME' => Item::FIELD_NAME_CATEGORY_ID,
					'MULTIPLE' => 'N',
					'ITEMS' => $categoriesList,
				],
				(new Snippet())->getApplyButton([
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'DATA' => [
								[
									'JS' =>
										(new Event('BatchManager:executeSetCategory'))
											->addEntityTypeId($this->factory->getEntityTypeId())
											->addValueElementId($dropdownValueId)
											->buildJsCallback()
									,
								]
							],
						]
					]
				]),
			]
		]);

		return $onchange;
	}
}
