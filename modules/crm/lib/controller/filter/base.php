<?
namespace Bitrix\Crm\Controller\Filter;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class Base extends \Bitrix\Main\Engine\Controller
{
	protected function getList($filterSettings)
	{
		$result = [];
		$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter($filterSettings);

		foreach($filter->getFields() as $field)
		{
			$result[] = \Bitrix\Main\UI\Filter\FieldAdapter::adapt($field->toArray([
				'lightweight' => true
			]));
		}

		return $result;
	}

	protected function getField($filterSettings, $id)
	{
		$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter($filterSettings);

		$field = $filter->getField($id);
		if($field)
		{
			$result = \Bitrix\Main\UI\Filter\FieldAdapter::adapt($field->toArray());
		}
		else
		{
			$this->addError(new Error(Loc::getMessage("CRM_CONTROLLER_FILTER_FIELD_NOT_FOUND"), "CRM_CONTROLLER_FILTER_FIELD_NOT_FOUND"));
			return null;
		}

		return $result;
	}

	public function getListAction($filterId)
	{
		return [];
	}

	public function getFieldAction($filterId, $id)
	{
		return [];
	}
}

