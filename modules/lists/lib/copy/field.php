<?php
namespace Bitrix\Lists\Copy;

use Bitrix\Main\Copy\CompositeImplementation;
use Bitrix\Main\Copy\ContainerManager;
use Bitrix\Main\Copy\Copyable;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class Field implements Copyable
{
	use CompositeImplementation;

	/**
	 * @var Result
	 */
	private $result;

	public function __construct()
	{
		$this->result = new Result();
	}

	/**
	 * Copies iblock fields.
	 *
	 * @param ContainerManager $containerManager
	 * @return Result
	 */
	public function copy(ContainerManager $containerManager)
	{
		$containers = $containerManager->getContainers();

		/** @var Container[] $containers */
		foreach ($containers as $container)
		{
			$copiedFields = $this->getFieldsToCopy($container);
			$this->addListsFields($container->getCopiedEntityId(), $copiedFields);
		}

		return $this->result;
	}

	private function getFieldsToCopy(Container $container)
	{
		$iblockId = $container->getEntityId();
		$copiedIblockId = $container->getCopiedEntityId();

		$copiedFields = [];

		$object = new \CList($iblockId);

		foreach($object->getFields() as $fieldId => $field)
		{
			$copiedField = [
				"ID" => $fieldId,
				"NAME" => $field["NAME"],
				"SORT" => $field["SORT"],
				"MULTIPLE" => $field["MULTIPLE"],
				"IS_REQUIRED" => $field["IS_REQUIRED"],
				"IBLOCK_ID" => $copiedIblockId,
				"SETTINGS" => $field["SETTINGS"],
				"DEFAULT_VALUE" => $field["DEFAULT_VALUE"],
				"TYPE" => $field["TYPE"],
				"PROPERTY_TYPE" => $field["PROPERTY_TYPE"],
			];

			if (!$object->is_field($fieldId))
			{
				if ($field["TYPE"] == "L")
				{
					$enum = \CIBlockPropertyEnum::getList([], ["PROPERTY_ID" => $field["ID"]]);
					while ($listData = $enum->fetch())
					{
						$copiedField["VALUES"][] = [
							"XML_ID" => $listData["XML_ID"],
							"VALUE" => $listData["VALUE"],
							"DEF" => $listData["DEF"],
							"SORT" => $listData["SORT"]
						];
					}
				}

				$copiedField["CODE"] = $field["CODE"];
				$copiedField["LINK_IBLOCK_ID"] = $field["LINK_IBLOCK_ID"];
				if (!empty($field["PROPERTY_USER_TYPE"]["USER_TYPE"]))
					$copiedField["USER_TYPE"] = $field["PROPERTY_USER_TYPE"]["USER_TYPE"];
				if (!empty($field["ROW_COUNT"]))
					$copiedField["ROW_COUNT"] = $field["ROW_COUNT"];
				if (!empty($field["COL_COUNT"]))
					$copiedField["COL_COUNT"] = $field["COL_COUNT"];
				if (!empty($field["USER_TYPE_SETTINGS"]))
					$copiedField["USER_TYPE_SETTINGS"] = $field["USER_TYPE_SETTINGS"];
			}
			$copiedFields[] = $copiedField;
		}

		return $copiedFields;
	}

	private function addListsFields($iblockId, array $fields)
	{
		$object = new \CList($iblockId);

		foreach ($fields as $field)
		{
			if ($field["ID"] == "NAME")
			{
				$result = $object->updateField("NAME", $field);
			}
			else
			{
				$result = $object->addField($field);
			}

			if ($result)
			{
				$object->save();
			}
			else
			{
				$this->result->addError(new Error(Loc::getMessage("COPY_FIELD_ERROR", ["#NAME#" => $field["NAME"]])));
			}
		}
	}
}