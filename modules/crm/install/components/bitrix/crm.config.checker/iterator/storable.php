<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Config\Option;

trait Storable
{
	private function invokeData()
	{
		$result = Option::get("configurator.".static::$moduleId, static::class, "");
		if ($result !== "")
			$result = unserialize($result);
		if (is_array($result) && array_key_exists("props", $result))
		{
			foreach ($result["props"] as $name => $val)
			{
				if (property_exists($this, $name))
				{
					$this->{$name} = $val;
				}
			}
			if ($this instanceof Step)
			{
				$this->errorCollection = $result["errorCollection"];
				$this->noteCollection = $result["noteCollection"];
			}
			return $result["data"];
		}
		return [];
	}

	private function saveData(array $data)
	{
		$result = [
			"props" => array_filter(get_object_vars($this), "is_scalar"),
			"data" => $data
		];

		if ($this instanceof Step)
		{
			$result["errorCollection"] = $this->errorCollection;
			$result["noteCollection"] = $this->noteCollection;
		}
		Option::set("configurator.".static::$moduleId, static::class, serialize($result));
	}

	private function deleteData()
	{
		Option::delete("configurator.".static::$moduleId, array("name" => static::class));
	}
}