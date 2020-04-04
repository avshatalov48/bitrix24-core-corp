<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class ElementValues extends BaseValues
{
	protected $sectionId = 0;
	protected $elementId = 0;

	/**
	 * @param integer $iblockId Iblock identifier.
	 * @param integer $elementId Element identifier.
	 */
	public function __construct($iblockId, $elementId)
	{
		parent::__construct($iblockId);
		$this->elementId = (int)$elementId;
		$this->queue->addElement($this->iblockId, $this->elementId);
	}

	/**
	 * Returns the table name where values will be stored.
	 *
	 * @return string
	 */
	public function getValueTableName()
	{
		return "b_iblock_section_iprop";
	}

	/**
	 * Returns type of the entity which will be stored into DB.
	 *
	 * @return string
	 */
	public function getType()
	{
		return "E";
	}

	/**
	 * Returns unique identifier of the element.
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->elementId;
	}

	/**
	 * Creates an entity which will be used to process the templates.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	public function  createTemplateEntity()
	{
		return new \Bitrix\Iblock\Template\Entity\Element($this->elementId);
	}

	/**
	 * Sets parent to minimal value from array or to $sectionId.
	 *
	 * @param array[]integer|integer $sectionId Section identifier.
	 *
	 * @return void
	 */
	public function setParents($sectionId)
	{
		if (is_array($sectionId))
		{
			if (!empty($sectionId))
			{
				$sectionId = array_map("intval", $sectionId);
				$this->sectionId = min($sectionId);
			}
		}
		else
		{
			$this->sectionId = (int)$sectionId;
		}
	}

	/**
	 * Returns all the parents of the element which is
	 * array with one element: parent section with minimal identifier or iblock.
	 *
	 * @return array[]\Bitrix\Iblock\InheritedProperty\BaseValues
	 */
	public function getParents()
	{
		$parents = array();
		if ($this->elementId > 0)
		{
			$elementList = \Bitrix\Iblock\ElementTable::getList(array(
				"select" => array("IBLOCK_SECTION_ID"),
				"filter" => array("=ID" => $this->elementId),
			));
			$element = $elementList->fetch();
			if ($element && $element["IBLOCK_SECTION_ID"] > 0)
				$parents[] = new SectionValues($this->iblockId, $element["IBLOCK_SECTION_ID"]);
			else
				$parents[] = new IblockValues($this->iblockId);
		}
		elseif ($this->sectionId > 0)
		{
			$parents[] = new SectionValues($this->iblockId, $this->sectionId);
		}
		else
		{
			$parents[] = new IblockValues($this->iblockId);
		}
		return $parents;
	}

	/**
	 * Returns all calculated values of inherited properties
	 * for this element.
	 *
	 * @return array[string]string
	 */
	public function queryValues()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$result = array();
		if ($this->hasTemplates())
		{
			if ($this->queue->getElement($this->iblockId, $this->elementId) === false)
			{
				$ids = $this->queue->get($this->iblockId);
				$query = $connection->query("
					SELECT
						P.ID
						,P.CODE
						,P.TEMPLATE
						,P.ENTITY_TYPE
						,P.ENTITY_ID
						,IP.VALUE
						,IP.ELEMENT_ID
					FROM
						b_iblock_element_iprop IP
						INNER JOIN b_iblock_iproperty P ON P.ID = IP.IPROP_ID
					WHERE
						IP.IBLOCK_ID = ".$this->iblockId."
						AND IP.ELEMENT_ID in (".implode(", ", $ids).")
				");
				$result = array();
				while ($row = $query->fetch())
				{
					$result[$row["ELEMENT_ID"]][$row["CODE"]] = $row;
				}
				$this->queue->set($this->iblockId, $result);
			}
			$result = $this->queue->getElement($this->iblockId, $this->elementId);

			if (empty($result))
			{
				$result = parent::queryValues();
				if (!empty($result))
				{
					$sqlHelper = $connection->getSqlHelper();
					$elementList = \Bitrix\Iblock\ElementTable::getList(array(
						"select" => array("IBLOCK_SECTION_ID"),
						"filter" => array("=ID" => $this->elementId),
					));
					$element = $elementList->fetch();
					$element['IBLOCK_SECTION_ID'] = (int)$element['IBLOCK_SECTION_ID'];

					$fields = array(
						"IBLOCK_ID",
						"SECTION_ID",
						"ELEMENT_ID",
						"IPROP_ID",
						"VALUE",
					);
					$rows = array();
					foreach ($result as $CODE => $row)
					{
						$rows[] = array(
							$this->iblockId,
							$element["IBLOCK_SECTION_ID"],
							$this->elementId,
							$row["ID"],
							$sqlHelper->forSql($row["VALUE"]),
						);
					}
					$this->insertValues("b_iblock_element_iprop", $fields, $rows);
				}
			}
		}
		return $result;
	}

	/**
	 * Clears element values DB cache
	 *
	 * @return void
	 */
	function clearValues()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("
			DELETE FROM b_iblock_element_iprop
			WHERE IBLOCK_ID = ".$this->iblockId."
			AND ELEMENT_ID = ".$this->elementId."
		");
		$this->queue->deleteElement($this->iblockId, $this->elementId);
	}
}
