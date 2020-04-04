<?
/**
 * 
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 * todo: create \Bitrix\Tasks\Internals\DataBase\Structure\ClosureMesh, and then make this class deprecated
 */
namespace Bitrix\Tasks\Internals\DataBase;

use \Bitrix\Main;
use \Bitrix\Main\DB;
use \Bitrix\Main\Entity;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Application;
use \Bitrix\Main\Entity\AddResult;
use \Bitrix\Main\Entity\DeleteResult;

use Bitrix\Tasks\Util\Assert;

//Loc::loadMessages(__FILE__);

abstract class Mesh extends Tree
{
	/**
	 * Links one item with another. Low-level method.
	 */
	public static function createLink($id, $parentId, $behaviour = array('LINK_DATA' => array()))
	{
		static::applyCreateRestrictions($id, $parentId);

		if(!is_array($behaviour))
		{
			$behaviour = array();
		}

		if(!is_array($behaviour['LINK_DATA']))
		{
			$behaviour['LINK_DATA'] = array();
		}

		// check if parent exists. if not - add it
		if(!static::insertEdge($parentId, $parentId, false, 0, array('INCREMENT_MP_WHEN_EXISTS' => false)))
		{
			static::throwExceptionCantAddEdge($parentId, $parentId, false);
		}

		// check if link to itself exists. if not - add it
		if(!static::insertEdge($id, $id, false, 0, array('INCREMENT_MP_WHEN_EXISTS' => false)))
		{
			static::throwExceptionCantAddEdge($id, $id, false);
		}

		$fromPart = 	static::getParents($parentId);
		$toPart = 		static::getChildren($id);

		/*
		print_r('Parent: ('.$parentId.')'.PHP_EOL);
		print_r($fromPart);
		print_r('Child: ('.$id.')'.PHP_EOL);
		print_r($toPart);
		*/

		// link each child (including $id) to each parent (including $parentId)
		foreach($fromPart as $parent)
		{
			foreach($toPart as $child)
			{
				$increment = 0;
				$behaviourEdge = array();

				if($parent['ID'] == $parentId && $child['ID'] == $id) // special, direct linking to parent
				{
					$direct = true;
					$behaviourEdge = $behaviour;
				}
				else
				{
					$direct = false;

					if(intval($parent['MPCITY']) > 1)
					{
						$increment += intval($parent['MPCITY']);
					}
					if(intval($child['MPCITY']) > 1)
					{
						$increment += intval($child['MPCITY']);
					}
					$behaviourEdge['INCREMENT_MP_WHEN_EXISTS'] = true;
				}

				//print_r('Make '.$parent['ID'].' => '.$child['ID'].' with MPCITY = '.$increment.' DIRECT = '.$direct.PHP_EOL);

				if(!static::insertEdge($parent['ID'], $child['ID'], $direct, $increment, $behaviourEdge))
				{
					static::throwExceptionCantAddEdge($parent['ID'], $child['ID'], true);
				}
			}
		}

		return new AddResult();
	}

	public static function moveLink($id, $parentId, $behaviour = array('CREATE_PARENT_NODE_ON_NOTFOUND' => true))
	{
		throw new \Bitrix\Main\NotImplementedException();
	}

	/**
	 * Breaks link between nodes. Low-level method.
	 */
	public static function deleteLink($id, $parentId = false, array $behaviour = array('CHILDREN' => 'unlink'))
	{
		static::applyDeleteRestrictions($id, $parentId);

		$fromPart = 	static::getParents($parentId);
		$toPart = 		static::getChildren($id);

		// UNlink each child (including $id) from each parent (including $parentId)
		foreach($fromPart as $parent)
		{
			foreach($toPart as $child)
			{
				$decrement = 0;

				if($parent['ID'] == $parentId && $child['ID'] == $id) // special, direct linking to parent
				{
				}
				else
				{
					if(intval($parent['MPCITY']) > 1)
					{
						$decrement += intval($parent['MPCITY']);
					}
					if(intval($child['MPCITY']) > 1)
					{
						$decrement += intval($child['MPCITY']);
					}
				}

				//print_r('UnMake '.$parent['ID'].' => '.$child['ID'].' with MPCITY = '.$decrement.PHP_EOL);

				static::markEdgeToRemove($parent['ID'], $child['ID'], $decrement);
			}
		}

		static::deleteMarkedEdges();

		return new DeleteResult();
	}

	protected static function throwExceptionCantAddEdge($from, $to, $direct, $type = '')
	{
		throw new \Bitrix\Tasks\Exception('Can not add a new edge', array('AUX' => array('MESSAGE' => array('FROM' => $from, 'TO' => $to, 'DIRECT' => $direct, 'TYPE' => $type))));
	}

	/**
	 * Check if the item is linked with some other items
	 */
	public static function checkItemLinked($id)
	{
		global $DB;

		$id = Assert::expectIntegerPositive($id, '$id');

		$directCN = 	static::getDIRECTColumnName();
		$idCN = 		static::getIDColumnName();
		$parentCN = 	static::getPARENTIDColumnName();

		$item = $DB->query("
			select 
				1 
				from 
					".static::getTableName()."
				where 
					(".$directCN." = '1' and ".$idCN." = '".$id."')
					or
					(".$directCN." = '1' and ".$parentCN." = '".$id."')
		")->fetch();

		return !empty($item);
	}

	protected static function applyDeleteRestrictions(&$id, &$parentId)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		if($parentId !== false) // parent id === false means that all links with all parents will be broken
		{
			$parentId = Assert::expectIntegerPositive($parentId, '$parentId');
		}

		if(!static::checkNodeExists($id))
		{
			throw new Tree\TargetNodeNotFoundException(false, array('NODES' => array($id)));
		}

		if($parentId !== false && !static::checkLinkExists($id, $parentId))
		{
			throw new Tree\LinkNotExistException(false, array('NODES' => array($id, $parentId)));
		}
	}

	private static function getParents($id)
	{
		$idColName = 		static::getIDColumnName();
		$parentIdColName = 	static::getPARENTIDColumnName();
		$mpcityColName = 	static::getMPCITYColumnName();

		$fromPart = array();
		$res = static::getList(array('filter' => array($idColName => $id), 'select' => array('ID' => $parentIdColName, $mpcityColName)));
		while($item = $res->fetch())
		{
			$fromPart[] = $item;
		}

		return $fromPart;
	}

	private static function getChildren($id)
	{
		$idColName = 		static::getIDColumnName();
		$mpcityColName = 	static::getMPCITYColumnName();

		$toPart = array();
		$res = static::getSubTree($id, array('select' => array('ID' => $idColName, $mpcityColName)));
		while($item = $res->fetch())
		{
			$toPart[] = $item;
		}

		return $toPart;
	}

	private static function insertEdge($from, $to, $direct = true, $increment = 0, array $behaviour = array('INCREMENT_MP_WHEN_EXISTS' => true))
	{
		//$behaviour['LINK_DATA']
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$idColName = static::getIDColumnName();
		$parentIdColName = static::getPARENTIDColumnName();
		$mpcityColName = static::getMPCITYColumnName();
		$directColName = static::getDIRECTColumnName();

		$additionalFldsMap = array();
		$additionalFldsValues = array();
		if(is_array($behaviour['LINK_DATA']) && !empty($behaviour['LINK_DATA']))
		{
			$additionalFldsMap = array_keys($behaviour['LINK_DATA']);
			$additionalFldsValues = $behaviour['LINK_DATA'];
		}

		$values = array_merge(array(
			$idColName => 		$to,
			$parentIdColName => $from,
			$directColName => 	$direct,
			$mpcityColName => 	$increment > 0 ? $increment : 1,
		), $additionalFldsValues);

		if($behaviour['INCREMENT_MP_WHEN_EXISTS'] === true)
		{
			$plus = '+'.($increment > 0 ? $increment : '1');
		}
		else
		{
			$plus = '';
		}

		$merge = $helper->prepareMerge(
			static::getTableName(),
			array($idColName, $parentIdColName), // primary key
			$values,
			array(
				$mpcityColName => new \Bitrix\Main\DB\SqlExpression($mpcityColName.$plus),
			)
		);

		if ($merge[0] != "")
		{
			$connection->query($merge[0]);
			return true;
		}

		return false;
	}

	private static function markEdgeToRemove($from, $to, $decrement = 0)
	{
		if($decrement == 0)
		{
			$decrement = 1;
		}

		$idColName = 		static::getIDColumnName();
		$parentIdColName = 	static::getPARENTIDColumnName();
		$mpcityColName = 	static::getMPCITYColumnName();

		Application::getConnection()->query("
			update ".static::getTableName()." set ".$mpcityColName." = ".$mpcityColName." - ".intval($decrement)." where ".$idColName." = '".$to."' and ".$parentIdColName." = '".$from."'
		");
	}

	private static function deleteMarkedEdges()
	{
		$mpcityColName = static::getMPCITYColumnName();

		$sql = "
			delete 
				from ".static::getTableName()."
				where 
					".$mpcityColName." <= '0'
		";
		Application::getConnection()->query($sql);
	}

	protected static function applyCreateRestrictions(&$id, &$parentId)
	{
		$id = 			Assert::expectIntegerPositive($id, '$id');
		$parentId = 	Assert::expectIntegerNonNegative($parentId, '$parentId'); // parent id might be equal to 0

		if(static::checkLinkExists($id, $parentId))
		{
			throw new Tree\LinkExistsException(false, array('NODES' => array($id, $parentId)));
		}
	}

	public static function getMPCITYColumnName()
	{
		return 'MPCITY';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		// to avoid warnings in php7
		$entityName = '';
		if(func_num_args() > 0)
		{
			$entityName = func_get_arg(0);
		}

		$map = parent::getMap($entityName);

		return array_merge($map, array(
			new Entity\IntegerField(static::getMPCITYColumnName(), array(
			))
		));
	}
}