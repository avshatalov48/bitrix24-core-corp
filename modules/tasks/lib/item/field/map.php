<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Field;

use Bitrix\Tasks\Util\Collection;

final class Map extends \Bitrix\Tasks\Util\Collection
{
	protected $cache = array();

	/**
	 * @param Scalar $field
	 * @param null $key
	 */
	public function placeField($field, $key = null)
	{
		$this->values[$key !== null ? $key : $field->getName()] = $field;
		$this->onChange();
	}

	public function placeFields(array $fields)
	{
		/**
		 * @var Scalar $field
		 */
		foreach($fields as $field)
		{
			$this->placeField($field, $field->getName());
		}
	}

	public function getField($key)
	{
		if(array_key_exists($key, $this->values))
		{
			return $this->values[$key];
		}

		return null;
	}

	public function decodeCamelFieldName($field)
	{
		/**
		 * @var Scalar $f
		 */
		foreach($this->values as $f)
		{
			if($f->isCamelName($field))
			{
				return $f->getName();
			}
		}

		return null;
	}

	public function getFieldDBNamesBySourceType(array $types)
	{
		$types = array_flip($types);
		$fields = array();
		/**
		 * @var Scalar $v
		 */
		foreach($this->values as $k => $v)
		{
			if($v->isDBReadable() && array_key_exists($v->getSource(), $types))
			{
				$fields[] = $v->getDBName();
			}
		}

		return $fields;
	}

	public function getFieldDBNamesByNames(array $names)
	{
		$names = array_flip($names);
		$fields = array();
		/**
		 * @var Scalar $v
		 */
		foreach($this->values as $k => $v)
		{
			if($v->isDBReadable() && array_key_exists($v->getName(), $names))
			{
				$fields[] = $v->getDBName();
			}
		}

		return $fields;
	}

	/**
	 * @return string[]
	 */
	public function getTabletFieldNames()
	{
		if(!array_key_exists('TABLET', $this->cache))
		{
			$this->cache['TABLET'] = $this->getFieldsBySource(Scalar::SOURCE_TABLET);
		}

		return $this->cache['TABLET'];
	}

	/**
	 * @return string[]
	 */
	public function getUserFieldNames()
	{
		if(!array_key_exists('UF', $this->cache))
		{
			$this->cache['UF'] = $this->getFieldsBySource(Scalar::SOURCE_UF);
		}

		return $this->cache['UF'];
	}

	/**
	 * @param int
	 * @return string[]
	 */
	private function getFieldsBySource($source)
	{
		$fields = $this->find(
			array('=SOURCE' => $source),
			array('CONTAINER' => new Collection())
		);
		$names = array();
		/** @var Scalar $field */
		foreach($fields as $field)
		{
			$names[] = $field->getName();
		}

		return $names;
	}

	public function onChange()
	{
		parent::onChange();
		$this->cache = array();
	}
}