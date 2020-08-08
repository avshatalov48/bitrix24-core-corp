<?
namespace Bitrix\Crm\Update;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;
use Bitrix\Crm\Order;

class OrderSearchIndex extends Stepper
{
	protected static $moduleId = "crm";

	public function execute(array &$result)
	{
		if(!Loader::includeModule("crm"))
			return false;

		$className = get_class($this);
		$option = Option::get("crm", $className, 0);
		$result["steps"] = $option;

		$limit = 50;
		$result["steps"] = isset($result["steps"]) ? $result["steps"] : 0;
		$selectedRowsCount = 0;
		$objectQuery = Order\Order::getList([
			'limit' => $limit,
			'count_total' => true,
			'offset' => $result["steps"],
			'order' => ["ID" => "DESC"]
		]);
		if($objectQuery)
		{
			$selectedRowsCount = $objectQuery->getCount();
			$searchIndexInstance = Order\SearchIndex::getInstance();
			while($fields = $objectQuery->fetch())
			{
				$order = Order\Order::create(SITE_ID);
				$order->initFields($fields);
				$searchIndexInstance->setOrder($order)->add();
			}
		}

		if($selectedRowsCount < $limit)
		{
			Option::delete("crm", array("name" => $className));
			return false;
		}
		else
		{
			$result["steps"] = $result["steps"] + $selectedRowsCount;
			$option = $result["steps"];
			Option::set("crm", $className, $option);
			return true;
		}
	}
}