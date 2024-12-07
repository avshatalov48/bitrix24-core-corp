<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item\Group;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Crm\Conversion\LeadConversionScheme;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Action\Group\GroupChildAction;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Types;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class ConvertChildAction extends GroupChildAction
{
	public static function isEntityTypeSupported(int $entityTypeId): bool
	{
		return $entityTypeId === \CCrmOwnerType::Lead;
	}

	public static function isConversionPermitted(int $entityTypeId, UserPermissions $userPermissions): bool
	{
		$schemes = (new self($entityTypeId, $userPermissions))->getConversionSchemesDropdownItems();

		return !empty($schemes);
	}

	public function __construct(private int $entityTypeId, private UserPermissions $userPermissions)
	{
	}

	public static function getId(): string
	{
		return 'convert';
	}

	public function getName(): string
	{
		return (string)Loc::getMessage('CRM_GRID_PANEL_GROUP_ACTION_CONVERT');
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
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

		$dropdownContainerId = 'action_batch_convert';
		$dropdownValueId = $dropdownContainerId . '_control';

		$onchange->addAction([
			'ACTION' => Actions::CREATE,
			'DATA' => [
				[
					'TYPE' => Types::DROPDOWN,
					'ID' => $dropdownContainerId,
					'MULTIPLE' => 'N',
					'ITEMS' => $this->getConversionSchemesDropdownItems(),
				],

				(new Snippet())->getApplyButton([
					'ONCHANGE' => [
						[
							'ACTION' => Actions::CALLBACK,
							'DATA' => [
								[
									'JS' =>
										(new Event('BatchManager:executeConversion'))
											->addValueElementId($dropdownValueId)
											->buildJsCallback()
									,
								]
							],
						]
					],
				])
			],
		]);

		return $onchange;
	}

	private function getConversionSchemesDropdownItems(): array
	{
		$schemes = LeadConversionScheme::getJavaScriptDescriptions(
			true,
			['PERMISSIONS' => $this->userPermissions->getCrmPermissions()],
		);

		$dropdownItems = [];
		foreach($schemes as $schemeName => $singleScheme)
		{
			$dropdownItems[] = ['NAME' => $singleScheme, 'VALUE' => $schemeName];
		}

		return $dropdownItems;
	}
}
