<?
namespace Bitrix\Crm\Update;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

/**
 * Class BarStackAvatar
 * The class is designed to update the settings of the wigdet "Histogram with avatars".
 * An example of how this miracle works can be seen here: crm,18.7.11;
 * @package Bitrix\Crm\Update
 */
class BarStackAvatar extends Stepper
{
	protected static $moduleId = "crm";

	protected $limit = 100;

	public function execute(array &$option)
	{
		$offset = intval(Option::get(self::$moduleId, "bar_stack_avatar_offset", 0));

		$connection = Application::getInstance()->getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$queryObject = $connection->query("SELECT ID, VALUE FROM b_user_option WHERE CATEGORY='crm.widget_panel' ORDER BY ID LIMIT ".$this->limit." OFFSET ".$offset);
		$selectedRowsCount = $queryObject->getSelectedRowsCount();
		while ($userOption = $queryObject->fetch())
		{
			$optionValue = unserialize($userOption["VALUE"], ['allowed_classes' => false]);
			if (!$optionValue)
			{
				continue;
			}

			$arrayIterator = new \RecursiveArrayIterator($optionValue);
			$recursiveIterator = new \RecursiveIteratorIterator($arrayIterator, \RecursiveIteratorIterator::SELF_FIRST);

			foreach ($recursiveIterator as $key => $value)
			{
				if (is_array($value) && array_key_exists("enableStack", $value))
				{
					if (
						$value["typeName"] == "bar" &&
						$value["group"] == "USER" &&
						$value["enableStack"] == "N" &&
						is_array($value["configs"])
					)
					{
						$update = false;
						foreach ($value["configs"] as $config)
						{
							if (
								!empty($config["display"]["graph"]["clustered"]) &&
								$config["display"]["graph"]["clustered"] == "N"
							)
							{
								$update = true;
								break;
							}
						}
						if ($update)
						{
							$value["enableStack"] = "Y";
							$currentDepth = $recursiveIterator->getDepth();
							for ($subDepth = $currentDepth; $subDepth >= 0; $subDepth--)
							{
								$subIterator = $recursiveIterator->getSubIterator($subDepth);
								$subIterator->offsetSet(
									$subIterator->key(),
									($subDepth === $currentDepth ?
										$value : $recursiveIterator->getSubIterator(($subDepth + 1))->getArrayCopy())
								);
							}
						}
					}
				}
			}

			$newOptionValue = $recursiveIterator->getArrayCopy();
			if ($newOptionValue)
			{
				$connection->query("UPDATE b_user_option SET VALUE = '".$sqlHelper->forSql(
					serialize($newOptionValue))."' WHERE ID = '".$sqlHelper->forSql($userOption["ID"])."'");
			}
		}

		$GLOBALS["CACHE_MANAGER"]->cleanDir("user_option");

		if ($selectedRowsCount < $this->limit)
		{
			Option::delete(self::$moduleId, array("name" => "bar_stack_avatar_offset"));
			return false;
		}
		else
		{
			Option::set(self::$moduleId, "bar_stack_avatar_offset", ($selectedRowsCount + $offset));
			return true;
		}
	}
}