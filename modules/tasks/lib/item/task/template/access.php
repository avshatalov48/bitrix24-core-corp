<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item\Task\Template;

use Bitrix\Tasks\Internals\Task\Template\AccessTable;
use Bitrix\Tasks\UI;

final class Access extends \Bitrix\Tasks\Item\SubItem
{
	private $entityPrefix = null;
	private $entityId = null;

	public static function getParentConnectorField()
	{
		return 'ENTITY_ID';
	}

	public static function getDataSourceClass()
	{
		return AccessTable::getClass();
	}

	public static function getCollectionClass()
	{
		return \Bitrix\Tasks\Item\Task\Template\Collection\Access::getClass();
	}

	protected static function getParentClass()
	{
		return \Bitrix\Tasks\Item\Task\Template::getClass();
	}

	public function getGroupPrefix()
	{
		$this->parseGroupCode();
		return $this->entityPrefix;
	}

	public function getGroupId()
	{
		$this->parseGroupCode();
		return $this->entityId;
	}

	protected function onChange()
	{
		$this->entityPrefix = null;
		$this->entityId = null;
	}

	private function parseGroupCode()
	{
		if($this->entityPrefix == null || $this->entityId == null)
		{
			if($this['GROUP_CODE'] != '')
			{
				$found = array();
				if(preg_match('#^([a-zA-Z_]+)(\d+)$#', trim($this['GROUP_CODE']), $found))
				{
					$prefix = (string) $found[1];
					$id = intval($found[2]);

					if($prefix !== '')
					{
						$this->entityPrefix = $prefix;
					}
					if(intval($id))
					{
						$this->entityId = $id;
					}
				}
			}
		}
	}
}