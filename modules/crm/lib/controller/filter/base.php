<?
namespace Bitrix\Crm\Controller\Filter;

use Bitrix\Crm\Filter\EntitySettings;
use Bitrix\Crm\Filter\Factory;
use Bitrix\Crm\Filter\Filter;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class Base extends \Bitrix\Main\Engine\Controller
{
	protected ?Filter $entityFilter = null;

	protected function getList($filterSettings)
	{
		$result = [];
		$filter = $this->getFilter($filterSettings);

		if ($filter === null)
		{
			return null;
		}

		foreach($filter->getFields() as $field)
		{
			$result[] = \Bitrix\Main\UI\Filter\FieldAdapter::adapt($field->toArray([
				'lightweight' => true
			]));
		}

		return $result;
	}

	protected function getField($filterSettings, $id): ?array
	{
		$filter = $this->getFilter($filterSettings);

		if ($filter === null)
		{
			return null;
		}

		$field = $filter->getField($id);
		if ($field)
		{
			return \Bitrix\Main\UI\Filter\FieldAdapter::adapt($field->toArray());
		}

		$this->addError(new Error(Loc::getMessage("CRM_CONTROLLER_FILTER_FIELD_NOT_FOUND"), "CRM_CONTROLLER_FILTER_FIELD_NOT_FOUND"));

		return null;
	}

	protected function getFilter(EntitySettings $filterSettings): ?Filter
	{
		if ($this->entityFilter === null)
		{
			$this->entityFilter = Factory::createEntityFilter($filterSettings);
		}

		return $this->entityFilter;
	}

	public function getListAction($filterId)
	{
		return [];
	}

	public function getFieldAction($filterId, $id)
	{
		return [];
	}

	public function getFieldsAction(?string $filterId, array $ids): ?array
	{
		return [];
	}
}

