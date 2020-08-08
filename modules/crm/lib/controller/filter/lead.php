<?
namespace Bitrix\Crm\Controller\Filter;

class Lead extends \Bitrix\Crm\Controller\Filter\Base
{
	public function getListAction($filterId)
	{
		$filterId = trim($filterId);

		$filterSettings = new \Bitrix\Crm\Filter\LeadSettings([
			'ID' => $filterId <> '' ? $filterId : 'CRM_LEAD_LIST_V12'
		]);

		return $this->getList($filterSettings);
	}

	public function getFieldAction($filterId, $id)
	{
		$filterId = trim($filterId);
		$id = trim($id);


		$filterSettings = new \Bitrix\Crm\Filter\LeadSettings([
			'ID' => $filterId <> '' ? $filterId : 'CRM_LEAD_LIST_V12'
		]);

		return $this->getField($filterSettings, $id);
	}
}

