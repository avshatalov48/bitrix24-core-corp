<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Type\Dictionary;

trait Storable
{
	private function invokeData()
	{
		$result = Option::get("configurator.".static::$moduleId, static::class, "");
		if ($result !== "")
			$result = unserialize($result, ['allowed_classes' => false]);
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
				$this->errorCollection = $this->unserializeErrors($result["errorCollection"]);
				$this->noteCollection = $this->unserializeNotes($result["noteCollection"]);
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
			$result["errorCollection"] = $this->serializeErrors($this->errorCollection);
			$result["noteCollection"] = $this->serializeNotes($this->noteCollection);
		}
		Option::set("configurator.".static::$moduleId, static::class, serialize($result));
	}

	private function deleteData()
	{
		Option::delete("configurator.".static::$moduleId, array("name" => static::class));
	}

	private function serializeErrors(ErrorCollection $errorCollection)
	{
		return array_map(function ($error)
		{
			/* @var $error Error*/
			return $error->jsonSerialize();
		}, $errorCollection->toArray());
	}

	protected function serializeNotes(Dictionary $noteCollection)
	{
		return $noteCollection->toArray();
	}

	private function unserializeErrors($errors)
	{
		$errorCollection = new ErrorCollection();
		if (is_array($errors))
		{
			foreach ($errors as $offset => $error)
			{
				if (is_array($error))
				{
					$errorCollection->setError(
						new Error($error['message'], $error['code'], $error['customData']),
						$offset
					);
				}
			}
		}

		return $errorCollection;
	}

	protected function unserializeNotes($notes)
	{
		$noteCollection = new Dictionary();
		if (is_array($notes))
		{
			foreach ($notes as $id=>$value)
			{
				if (!is_object($value))
				{
					$noteCollection->set($id, $value);
				}
			}
		}
		return $noteCollection;
	}
}