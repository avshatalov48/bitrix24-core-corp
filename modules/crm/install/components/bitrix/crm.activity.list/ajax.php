<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Filter\ActivitySettings;
use Bitrix\Crm\Filter\Factory;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\UI\Filter\FieldAdapter;

class CrmActivityListAjaxController extends Controller
{
	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		Loader::includeModule('crm');
	}

	public function configureActions(): array
	{
		return [
			'getList' => [
				'+prefilters' => [new CloseSession()],
			],
			'getField' => [
				'+prefilters' => [new CloseSession()],
			],
		];
	}

	public function getListAction(?string $filterId = null): ?array
	{
		$filter = Factory::createEntityFilter(
			new ActivitySettings(['ID' => $filterId ?? 'CRM_ACTIVITY_LIST_V12'])
		);

		$fields = [];
		foreach($filter->getFields() as $field)
		{
			$fields[] = FieldAdapter::adapt($field->toArray(['lightweight' => true]));
		}

		return [
			'fields' => $fields,
		];
	}

	public function getFieldAction(string $fieldId, ?string $filterId = null): ?array
	{
		$filter = Factory::createEntityFilter(
			new ActivitySettings(['ID' => $filterId ?? 'CRM_ACTIVITY_LIST_V12'])
		);

		$field = $filter->getField($fieldId);
		if ($field)
		{
			return [
				'field' => FieldAdapter::adapt($field->toArray()),
			];
		}

		$this->addError(new Error(Loc::getMessage('CRM_ACTIVITY_LIST_FILTER_FIELD_NOT_FOUND')));

		return null;
	}
}
