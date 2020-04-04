<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 *
 *
 *
 */

namespace Bitrix\Tasks\Item\Replicator\Task;

use Bitrix\Tasks\Item;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Util\Collection;

class Template extends \Bitrix\Tasks\Item\Replicator
{
	protected static function getItemClass()
	{
		return '\\Bitrix\\Tasks\\Item\\Task\\Template';
	}

	protected static function getConverterClass()
	{
		return '\\Bitrix\\Tasks\\Item\\Converter\\Task\\Template\\ToTemplate';
	}

	public function produceSubItemsFrom($source, $destination, array $parameters = array(), $userId = 0)
	{
		$result = new Result();

		$data = $this->getSubEntitiesData($source->getId());
		$order = $this->getCreationOrder($data, $source->getId(), $destination->getId());

		if(!$order)
		{
			$result->getErrors()->add('SUB_ITEM_TREE_LOOP', 'Sub-item tree loop detected while replicating');
		}
		else
		{
			$result->setData($this->produceReplicas($result, $source, $destination, $data, $order, $parameters, $userId));
		}

		return $result;
	}

	protected function getSubEntitiesData($id)
	{
		$result = array();

		$id = intval($id);
		if(!$id)
		{
			return $result;
		}

		// todo: move CTaskTemplates to orm then replace here
		$res = \CTaskTemplates::getList(array('BASE_TEMPLATE_ID' => 'asc'), array('BASE_TEMPLATE_ID' => $id), false, array('INCLUDE_TEMPLATE_SUBTREE' => true), array('*', 'UF_*', 'BASE_TEMPLATE_ID'));
		while($item = $res->fetch())
		{
			if($item['ID'] == $id)
			{
				continue;
			}

			$result[$item['ID']] = $item;
		}

		// get check lists
		// todo: convert getListByTemplateDependency() to a runtime mixin for the template entity
		$res = \Bitrix\Tasks\Internals\Task\Template\CheckListTable::getListByTemplateDependency($id, array(
			'order' => array('SORT' => 'ASC'),
			'select' => array('ID', 'TEMPLATE_ID', 'IS_COMPLETE', 'SORT_INDEX', 'TITLE')
		));
		while($item = $res->fetch())
		{
			if(isset($result[$item['TEMPLATE_ID']]))
			{
				$result[$item['TEMPLATE_ID']]['SE_CHECKLIST'][$item['ID']] = $item;
			}
		}

		return $result;
	}

	protected function doPostActions($srcInstance, array $parameters = array())
	{
		if($parameters['SPAWNED_BY_AGENT'] === true || $parameters['SPAWNED_BY_AGENT'] === 'Y')
		{
			// increase replication count of our template
			$templateInst = new \CTaskTemplates();
			$templateInst->update($srcInstance->getId(), array(
				'TPARAM_REPLICATION_COUNT' => intval($srcInstance['TPARAM_REPLICATION_COUNT']) + 1
			));
		}
	}

	/**
	 * @param Result $result
	 * @param $source
	 * @param $destination
	 * @param $data
	 * @param $tree
	 * @param array $parameters
	 * @param int $userId
	 * @return \Bitrix\Tasks\Util\Collection
	 */
	private function produceReplicas(Result $result, $source, $destination, $data, $tree, array $parameters = array(), $userId = 0)
	{
		$converter = $this->getConverter();

		// create list of responsibles to create tasks to
		$responsibles = array($source['RESPONSIBLE_ID']);
		$multiTask = $source['MULTITASK'] == 'Y' && !empty($source['RESPONSIBLES']);
		if($multiTask)
		{
			$responsibles = array_merge(array($source['CREATED_BY']), $source['RESPONSIBLES']);
		}

		$created = new Collection();
		$wereErrors = false;

		// for each responsible create a bunch of tasks
		foreach($responsibles as $targetUser)
		{
			$subRootId = false;
			$subRoot = $source->transformWith($converter);
			if($subRoot->isSuccess())
			{
				$srInstance = $subRoot->getInstance();
				$srInstance['PARENT_ID'] = $destination->getId();
				$srInstance['RESPONSIBLE_ID'] = $targetUser;

				$subRoot = $srInstance->save();
				if($subRoot)
				{
					$subRootId = $srInstance->getId();
				}
				else
				{
					$srInstance->abortTransformation($converter); // rolling back possible temporal data creation
				}
			}

			$created->push($subRoot); // add sub-root-item creation result
			if(!$subRoot->isSuccess())
			{
				$wereErrors = true;
			}

			if($subRootId) // sub-root created, go further
			{
				$itemId2ReplicaId = array($source->getId() => $subRootId);

				$cTree = $tree;

				$srcId = $source->getId();
				$walkQueue = array($srcId);
				while(!empty($walkQueue)) // walk sub-item tree
				{
					$topTemplate = array_shift($walkQueue);

					if(is_array($cTree[$topTemplate]))
					{
						foreach($cTree[$topTemplate] as $template)
						{
							$dataMixin = array_merge(array(
								'PARENT_ID' => $itemId2ReplicaId[$topTemplate],
								'RESPONSIBLE_ID' => $targetUser
							), $parameters);

							$creationResult = $this->createItemFromData($data[$template], $dataMixin, $userId);
							if($creationResult->isSuccess())
							{
								$walkQueue[] = $template;
								$itemId2ReplicaId[$template] = $creationResult->getInstance()->getId();
							} // or else dont go to the sub-item tree of this item
							else
							{
								$wereErrors = true;
							}

							$created->push($creationResult); // add sub-item creation result
						}
					}
					unset($cTree[$topTemplate]);
				}
			}
		}

		if($wereErrors)
		{
			$result->addError('SUB_ITEMS_CREATION_FAILURE', 'Some of the sub-items was not properly created');
		}

		return $created;
	}

	private function createItemFromData($data, $dataMixin, $userId)
	{
		$itemClass = static::getItemClass();
		$converter = $this->getConverter();

		$dstInstance = null;
		$item = new $itemClass(0, $userId); // created source instance
		$item->setData($data, true); // set data in raw format, as it came directly from database
		$errorPrefix = 'SUB_ITEM_REPLICATION.';

		$creationResult = new Result();

		$conversionResult = $item->transformWith($converter); // converted to the destination instance
		if($conversionResult->isSuccess()) // was able to produce an item
		{
			$dstInstance = $conversionResult->getInstance();
			$dstInstance->mixData($dataMixin);

			$saveResult = $dstInstance->save();
			if(!$saveResult->isSuccess()) // but was not able to save it
			{
				$dstInstance->abortTransformation($this->getConverter()); // rolling back possible temporal data creation
			}

			if(!$saveResult->getErrors()->isEmpty())
			{
				$creationResult->getErrors()->load($saveResult->getErrors());
			}
		}
		else
		{
			if(!$conversionResult->getErrors()->isEmpty())
			{
				$creationResult->getErrors()->load($conversionResult->getErrors());
			}
		}

		$creationResult->setInstance($dstInstance);
		//$result->getErrors()->load($creationResult->getErrors()->prefixCode($errorPrefix));

		return $creationResult;
	}

	/**
	 * Check if template->sub-templates relation tree is correct and return it
	 *
	 * @param array $subEntitiesData
	 * @param $srcId
	 * @return array|bool
	 */
	private function getCreationOrder(array $subEntitiesData, $srcId)
	{
		$walkQueue = array($srcId);
		$treeBundles = array();

		foreach($subEntitiesData as $subTemplate)
		{
			$treeBundles[$subTemplate['BASE_TEMPLATE_ID']][] = $subTemplate['ID'];
		}

		$tree = $treeBundles;
		$met = array();
		while(!empty($walkQueue))
		{
			$topTemplate = array_shift($walkQueue);
			if(isset($met[$topTemplate])) // hey, i`ve met this guy before!
			{
				return false;
			}
			$met[$topTemplate] = true;

			if(is_array($treeBundles[$topTemplate]))
			{
				foreach($treeBundles[$topTemplate] as $template)
				{
					$walkQueue[] = $template;
				}
			}
			unset($treeBundles[$topTemplate]);
		}

		return $tree;
	}
}