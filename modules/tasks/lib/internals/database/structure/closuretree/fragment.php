<?
namespace Bitrix\Tasks\Internals\DataBase\Structure\ClosureTree;

//use Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

use Bitrix\Main\NotImplementedException;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Result;

final class Fragment extends \Bitrix\Tasks\Util\Collection
{
	protected $index = null;
	protected $errors = null;

	public function getData()
	{
		return $this->values;
	}

	public function getErrors()
	{
		if($this->errors === null)
		{
			$this->errors = new Collection();
		}

		return $this->errors;
	}

	public function addError($code, $message, $type = Error::TYPE_FATAL, $data = null)
	{
		$this->getErrors()->add($code, $message, $type);
	}

	/**
	 * Returns true, if there are no cycles and insanely deep nodes, and other possible errors
	 *
	 * @return bool
	 */
	public function isCorrect()
	{
		return $this->getErrors()->checkNoFatals();
	}

	public function getIds()
	{
		$result = array();
		foreach($this->values as $node)
		{
			$result[$node['__ID']] = true;
		}

		return array_keys($result);
	}

	public function getSubTree($point = 0)
	{
		$point = intval($point);

		$result = new static();

		// todo: use ->walkWidth() here
		$data = array();
		if(array_key_exists($point, $this->values))
		{
			$index = $this->getIndex();

			$queue = array($point);
			$met = array();
			$limit = 0;
			while(count($queue))
			{
				$limit++;
				if($limit > 10000)
				{
					break;
				}

				$nextId = array_shift($queue);
				if(isset($met[$nextId]))
				{
					continue;
				}
				$met[$nextId] = true;

				$data[$nextId] = $this->values[$nextId];
				if(array_key_exists($nextId, $index))
				{
					foreach($index[$nextId] as $subNodeId => $subNode)
					{
						$queue[] = $subNodeId;
					}
				}
			}

			if($limit > 10000)
			{
				$result->addError('ILLEGAL_STRUCTURE.DEPTH', 'Illegal fragment structure');
			}
		}

		$result->set($data);

		return $result;
	}

	public function addNode($id, $parentId, $data = array())
	{
		// todo: strict check of $id and $parentId here?

		if(!is_array($data))
		{
			$data = array();
		}
		$data['__PARENT_ID'] = intval($parentId);
		$data['__ID'] = intval($id);

		$this->values[$id] = $data;
	}

	public function getNode($id)
	{
		throw new NotImplementedException();
	}

	public function getNodeData($id)
	{
		return $this->values[$id];
	}

	public function walkDepth($cb)
	{
		$result = new Result();
		if(!is_callable($cb))
		{
			return $result;
		}

		$data = array();
		$met = array();
		$index = $this->getIndex();
		$this->walkDepthStep(0, 0, $cb, $index, $result, $data, $met);

		$result->setData($data);

		return $result;
	}

	private function walkDepthStep($id, $parentId, $cb, array $index, Result $result, array &$data, array &$met, $depth = 1)
	{
		if($depth > 10000)
		{
			$result->addError('ILLEGAL_STRUCTURE.DEPTH', 'Illegal fragment structure');
			return;
		}

		if(isset($met[$id]))
		{
			return; // do not go the same way twice
		}
		$met[$id] = true;

		if(array_key_exists($id, $index))
		{
			foreach($index[$id] as $nextId)
			{
				$this->walkDepthStep($nextId, $id, $cb, $index, $result, $data, $met, $depth + 1);
			}
		}

		if($id)
		{
			$data[$id] = call_user_func_array(
				$cb,
				array(
					null/** todo: object will be here some day, on which we can do ->getParentId() or even ->getParent() */,
					$id,
					$this->values[$id],
					$parentId
				)
			);
		}
	}

	public function walkWidth($cb, $rootId = 0)
	{
		$result = new Result();
		if(!is_callable($cb))
		{
			return $result;
		}

		$index = $this->getIndex();
		$data = array();

		$rootId = intval($rootId);

		$queue = array($rootId);
		$met = array();
		$limit = 0;
		while(count($queue))
		{
			$limit++;
			if($limit > 10000)
			{
				break;
			}

			$nextId = array_shift($queue);
			if(isset($met[$nextId]))
			{
				continue; // do not go the same way twice
			}
			$met[$nextId] = true;

			if($nextId)
			{
				$data[$nextId] = call_user_func_array(
					$cb,
					array(
						null/** todo: object will be here some day, on which we can do ->getParentId() or even ->getParent() */,
						$nextId,
						$this->values[$nextId],
						$this->getParentIdFor($nextId)
					)
				);
			}
			if(array_key_exists($nextId, $index))
			{
				foreach($index[$nextId] as $subNodeId => $subNode)
				{
					array_push($queue, $subNodeId);
				}
			}
		}

		$result->setData($data);

		if($limit > 10000)
		{
			$result->addError('ILLEGAL_STRUCTURE.DEPTH', 'Illegal fragment structure');
		}

		return $result;
	}

	public function getChildrenCount($id)
	{
		$index = $this->getIndex();
		if(!array_key_exists($id, $index))
		{
			return 0;
		}

		return count($index[$id]);
	}

	public function setParentFor($id, $newParentId)
	{
		$newParentId = intval($newParentId);

		if(array_key_exists($id, $this->values))
		{
			$this->values[$id]['__PARENT_ID'] = $newParentId;
		}

		$this->onChange();
	}

	/**
	 * @param static|mixed[] $fragment
	 * @throws NotImplementedException
	 */
	public function includeSubTreeFragment($fragment, $parentId = 0)
	{
		throw new NotImplementedException();
	}

	/**
	 * @param static|mixed[] $fragment
	 */
	public function includeFragment($fragment)
	{
		if(static::isA($fragment))
		{
			foreach($fragment as $item)
			{
				$this->values[$item['__ID']] = $item;
			}

			$this->onChange();
		}
	}

	public function excludeSubTreeFragment($id, array $settings = array())
	{
		$subSet = array();
		$this->walkWidth(function($item, $id) use(&$subSet) {
			$subSet[] = $id;
		}, $id);

		$ignoreSelf = $settings['IGNORE_SELF'] == true;

		foreach($subSet as $itemId)
		{
			if($ignoreSelf && $itemId == $id)
			{
				continue;
			}
			unset($this->values[$itemId]);
		}

		$this->onChange();
	}

	protected function onChange()
	{
		parent::onChange();

		$this->index = null;
	}

	protected function getIndex()
	{
		if($this->index === null)
		{
			$this->index = array();

			foreach($this->values as $node)
			{
				$this->index[intval($node['__PARENT_ID'])][intval($node['__ID'])] = $node['__ID'];
			}
		}

		return $this->index;
	}

	private function getParentIdFor($id)
	{
		return $this->values[$id]['__PARENT_ID'];
	}
}