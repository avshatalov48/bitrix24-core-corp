<?
/**
 * Closure table tree implementation
 * 
 * Tree struct and data fields are kept in separate tables
 * 
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * Todo: move this class to AddResult & DeleteResult, if possible
 * 
 * @access private
 * @deprecated
 * @see \Bitrix\Tasks\Internals\DataBase\Structure\ClosureTree
 */
namespace Bitrix\Tasks\Internals\DataBase;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util\Assert;

Loc::loadMessages(__FILE__);

abstract class Tree extends Entity\DataManager
{
	public static function link($id, $parentId)
	{
		try
		{
			return static::moveLink($id, $parentId);
		}
		catch(Tree\TargetNodeNotFoundException $e)
		{
			return static::createLink($id, $parentId);
		}
	}

	public static function unlink($id)
	{
		return static::deleteLink($id);
	}

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

		// check here if there is a link already

		//_dump_r('Create: '.$parentId.' => '.$id.' ('.$behaviour['LINK_DATA']['TYPE'].')');

		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();

		// delete broken previous links, if any (but not the link to itself)
		$dbConnection = Main\HttpApplication::getConnection();
		$dbConnection->query("delete from ".static::getTableName()." where ".$idColName." = '".intval($id)."' and ".$parentColName." != '".intval($id)."'");

		$success = true;
		$lastAddResult = null;

		if($parentId)
		{
			// check if parent exists. if not - add it
			$item = static::getList(array('filter' => array('='.$parentColName => $parentId, '='.$idColName => $parentId), 'select' => array($idColName)))->fetch();
			if(!is_array($item))
			{
				//_dump_r('link: '.$parentId.' => '.$parentId);
				$lastAddResult = parent::add(array($parentColName => $parentId, $idColName => $parentId));
				if(!$lastAddResult->isSuccess())
				{
					$success = false;
				}
			}
		}

		// check if link to itself exists. if not - add it
		$item = static::getList(array('filter' => array('='.$parentColName => $id, '='.$idColName => $id), 'select' => array($idColName)))->fetch();
		if(!is_array($item))
		{
			//_dump_r('link: '.$id.' => '.$id);
			$lastAddResult = parent::add(array($parentColName => $id, $idColName => $id));
			if(!$lastAddResult->isSuccess())
			{
				$success = false;
			}
		}

		$linkedWithParent = false;

		// TODO: the following part could be rewritten using just db-side insert-select

		if($success && $parentId)
		{
			$subtree = array();
			$res = static::getSubTree($id);
			while($item = $res->fetch())
			{
				$subtree[] = $item[$idColName];
			}

			// link each child (including self) to each parent in the path(es)
			$res = static::getPathToNode($parentId, array('select' => array($parentColName)));
			while($item = $res->fetch())
			{
				foreach($subtree as $itemId)
				{
					if($item[$parentColName] == $parentId && $itemId == $id) // special, direct linking to parent
					{
						if(!is_array($behaviour['LINK_DATA']))
						{
							$behaviour['LINK_DATA'] = array();
						}

						$lastAddResult = parent::add(array_merge(array($idColName => $id, $parentColName => $parentId, static::getDIRECTColumnName() => true), $behaviour['LINK_DATA']));
						if(!$lastAddResult->isSuccess())
						{
							$success = false;
							break;
						}
					}
					else
					{
						$lastAddResult = parent::add(array($idColName => $itemId, $parentColName => $item[$parentColName]));
						if(!$lastAddResult->isSuccess())
						{
							$success = false;
							break;
						}
					}
				}
			}
		}

		return array('RESULT' => $success, 'LAST_DB_RESULT' => $lastAddResult);
	}

	/**
	 * Moves subtree. Low-level method.
	 */
	public static function moveLink($id, $parentId, $behaviour = array('CREATE_PARENT_NODE_ON_NOTFOUND' => true))
	{
		$id = 				Assert::expectIntegerPositive($id, '$id');
		$parentId = 		Assert::expectIntegerNonNegative($parentId, '$parentId'); // 0 allowed - means "detach into a separate branch"
		if(!is_array($behaviour))
			$behaviour = array();
		if(!isset($behaviour['CREATE_PARENT_NODE_ON_NOTFOUND']))
			$behaviour['CREATE_PARENT_NODE_ON_NOTFOUND'] = true;

		$parentColName = 	static::getPARENTIDColumnName();
		$idColName = 		static::getIDColumnName();

		if(!static::checkNodeExists($id))
		{
			throw new Tree\TargetNodeNotFoundException(false, array('NODES' => array($id, $parentId)));
		}

		$dbConnection = Main\HttpApplication::getConnection();

		if($parentId > 0)
		{
			if(!static::checkNodeExists($parentId))
			{
				if($behaviour['CREATE_PARENT_NODE_ON_NOTFOUND'])
				{
					if(!static::add(array($parentColName => $parentId, $idColName => $parentId))->isSuccess())
					{
						throw new Tree\Exception('Can not create node', array('NODES' => array($parentId)));
					}
				}
				else
				{
					throw new Tree\ParentNodeNotFoundException(false, array('NODES' => array($parentId)));
				}
			}

			if(static::checkLinkExists($id, $parentId))
			{
				throw new Tree\LinkExistsException(false, array('NODES' => array($id, $parentId)));
			}

			$check = $dbConnection->query("select ".$idColName." from ".static::getTableName()." where ".$parentColName." = '".intval($id)."' and ".$idColName." = '".intval($parentId)."'")->fetch();
			if(is_array($check))
			{
				throw new Main\ArgumentException('Can not move tree inside itself');
			}
		}

		// TODO: rewrite this using deleteLink() and createLink()

		// detach subtree
		$dbConnection->query("delete 
			from ".static::getTableName()." 
			where 
				".$idColName." in (
					".Helper::getTemporaryTableSubQuerySql(static::getSubTreeSql($id), $idColName)."
				) 
				and
				".$parentColName." in (
					".Helper::getTemporaryTableSubQuerySql(static::getPathToNodeSql($id), $parentColName)."
				)
				and 
				".$idColName." != ".$parentColName./*exclude links to selves*/"
				and
				".$parentColName." != '".intval($id)./*exclude link to root node of a tree being detached*/"'
		");

		if($parentId > 0)
		{
			// reattach subtree to other path
			$res = static::getPathToNode($parentId, array('select' => array('ID' => $parentColName)));
			while($item = $res->fetch())
			{
				$dbConnection->query(
					"insert into ".static::getTableName()." 
						
						(".$parentColName.", ".$idColName.", ".static::getDIRECTColumnName().") 
						
						select 
							'".$item['ID']."',
							T.".$idColName.",

							".(
								$item['ID'] == $parentId
								?
								"
								CASE 
									WHEN 
										T.".$idColName." = '".$id."'
									THEN
										'1'
									ELSE
										'0'
								END
								"
								:
								"'0'"
							)."
						from 
							".static::getTableName()." T 
						where 
							".$parentColName." = '".intval($id)."'"
				);
			}
		}

		return array('RESULT' => true);
	}

	/**
	 * Breaks link between nodes. Low-level method.
	 */
	public static function deleteLink($id, $parentId = false, array $behaviour = array('CHILDREN' => 'unlink'))
	{
		static::applyDeleteRestrictions($id, $parentId);

		if(!is_array($behaviour))
		{
			$behaviour = array();
		}
		if(!isset($behaviour['CHILDREN']))
		{
			$behaviour['CHILDREN'] = 'unlink';
		}

		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();
		$directColName = static::getDIRECTColumnName();

		$dbConnection = Main\HttpApplication::getConnection();

		if($behaviour['CHILDREN'] == 'unlink')
		{
			// remove all links that connect all children of $id (including $id itself) and all parents of $parentId (including $parentId itself)

			// delete => select *
			$sql = "
				delete
				from 
						".static::getTableName()." 
					where 
						".$parentColName." in (
							".Helper::getTemporaryTableSubQuerySql("
								select 
									".$parentColName." 
								from 
									".static::getTableName()." 
								where 
									".$idColName.(
										$parentId !== false ?
										/*parent is known*/" = '".intval($parentId)."'" :
										/*detect parent*/" in (select ".$parentColName." from ".static::getTableName()." where ".$idColName." = '".intval($id)."' and ".$directColName." = '1')"
									)."
							", $parentColName)."
						)
						and
						".$idColName." in (
							".Helper::getTemporaryTableSubQuerySql("select ".$idColName." from ".static::getTableName()." where ".$parentColName." = '".intval($id)."'", $idColName)."
						)
			";

			$res = $dbConnection->query($sql);
			/*
			while($item = $res->fetch())
			{
				print_r('UNlink: '.$item[$parentColName].' => '.$item[$idColName].PHP_EOL);
			}
			*/
		}
		elseif($behaviour['CHILDREN'] == 'relocate')
		{
			throw new Main\NotImplementedException();
		}
	}

	public static function deleteSubtree($id)
	{
		$parentId = false;
		static::applyDeleteRestrictions($id, $parentId);

		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();
		$directColName = static::getDIRECTColumnName();

		$dbConnection = Main\HttpApplication::getConnection();

		// delete subtree itself
		$sql = "
			delete from ".static::getTableName()." where ".$idColName." in (
				".Helper::getTemporaryTableSubQuerySql("select ".$idColName." from ".static::getTableName()." where ".$parentColName." = '".intval($id)."'", $idColName)."
			)
		";

		$res = $dbConnection->query($sql);
		/*
		while($item = $res->fetch())
		{
			print_r('UNlink: '.$item[$parentColName].' => '.$item[$idColName].PHP_EOL);
		}
		*/
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

	public static function getPathToNode($id, $parameters = array()) // $behaviour = array('SHOW_LEAF' => true)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		if(!is_array($parameters))
			$parameters = array();

		$parameters['filter']['='.static::getIDColumnName()] = $id;

		return static::getList($parameters);
	}

	// returns an sql that selects all children of a particular node
	public static function getPathToNodeSql($id)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		$parentColName = 	static::getPARENTIDColumnName();
		$idColName = 		static::getIDColumnName();

		return "select ".$parentColName." from ".static::getTableName()." where ".$idColName." = '".intval($id)."'";
	}

	public static function getSubTree($id, $parameters = array(), array $behaviour = array('INCLUDE_SELF' => true))
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		$parameters['filter']['='.static::getPARENTIDColumnName()] = $id;
		if(!$behaviour['INCLUDE_SELF'])
		{
			$parameters['filter']['!='.static::getIDColumnName()] = $id;
		}

		return self::getList($parameters);
	}

	// returns an sql that selects all children of a particular node
	public static function getSubTreeSql($id)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		$parentColName = 	static::getPARENTIDColumnName();
		$idColName = 		static::getIDColumnName();

		return "select ".$idColName." from ".static::getTableName()." where ".$parentColName." = '".intval($id)."'";
	}

	public static function getLinkCount()
	{
		$item = Main\HttpApplication::getConnection()->query("select count(*) as CNT from ".static::getTableName()." where ".static::getDIRECTColumnName()." = '1'")->fetch();

		return intval($item['CNT']);
	}

	public static function getParentId($id)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		$parameters = [
			'select' => ['PARENT_TEMPLATE_ID'],
			'filter' => [
				'=' . static::getIDColumnName() => $id,
				'=' . static::getDIRECTColumnName() => 1
			]
		];

		return static::getList($parameters);
	}

	protected static function checkNodeExists($id)
	{
		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();

		$id = intval($id);
		if(!$id)
		{
			return false;
		}

		$item = Main\HttpApplication::getConnection()->query("select ".$idColName." from ".static::getTableName()." where ".$idColName." = '".$id."' and ".$parentColName." = '".$id."'")->fetch();
		return is_array($item);
	}

	protected static function checkLinkExists($id, $parentId)
	{
		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();
		$directColName = static::getDIRECTColumnName();

		$id = intval($id);
		$parentId = intval($parentId);
		if(!$id || !$parentId)
		{
			return false; // link to non-existed nodes does not exist
		}

		$item = Main\HttpApplication::getConnection()->query("select ".$idColName." from ".static::getTableName()." where ".$idColName." = '".$id."' and ".$parentColName." = '".$parentId."' and ".$directColName." = '1'")->fetch();
		return is_array($item);
	}

	public static function getIDColumnName()
	{
		return 'ID';
	}

	public static function getPARENTIDColumnName()
	{
		return 'PARENT_ID';
	}

	public static function getDIRECTColumnName()
	{
		return 'DIRECT';
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

		$directColumnName = 	static::getDIRECTColumnName();

		return array(

			new Entity\BooleanField($directColumnName, array(
			)),

			// parent node
			'PARENT_NODE' => array(
				'data_type' => $entityName,
				'reference' => array(
					'=ref.'.$directColumnName => array('?', '1'),
					'=this.'.static::getIDColumnName() => 'ref.'.static::getIDColumnName(),
				),
				'join_type' => 'inner'
			),

			// all parent nodes (path to root node)
			'PARENT_NODES' => array(
				'data_type' => $entityName,
				'reference' => array(
					'=this.'.static::getIDColumnName() => 'ref.'.static::getIDColumnName()
				),
				'join_type' => 'inner'
			),

			// all subtree
			'CHILD_NODES' => array(
				'data_type' => $entityName,
				'reference' => array(
					'=this.'.static::getIDColumnName() => 'ref.'.static::getPARENTIDColumnName()
				),
				'join_type' => 'inner'
			),

			// only direct ancestors
			'CHILD_NODES_DIRECT' => array(
				'data_type' => $entityName,
				'reference' => array(
					'=ref.'.$directColumnName => array('?', '1'),
					'=this.'.static::getIDColumnName() => 'ref.'.static::getPARENTIDColumnName(),
				),
				'join_type' => 'inner'
			),
		);
	}

	/**
	 * @deprecated
	 */
	public static function dropLinkL($id, $parentId = false, $behaviour = array('CHILDREN' => 'unlink'))
	{
		return static::deleteLink($id, $parentId, $behaviour);
	}
}