<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task\Template;

use Bitrix\Tasks\UI;
use Bitrix\Tasks\Item;

final class CheckList extends \Bitrix\Tasks\Dispatcher\PublicAction
{
	/**
	 * Add a new check list item to a specified task template
	 */
	public function add(array $data, array $parameters = array())
	{
		$result = array();

		if($this->errors->checkNoFatals())
		{
			// todo: check $data here, check for publicly-readable\writable keys\values

			$connectorField = Item\Task\Template\CheckList::getParentConnectorField();
			if(array_key_exists('_OWNER_ENTITY_ID_', $data))
			{
				$data[$connectorField] = $data['_OWNER_ENTITY_ID_'];
				unset($data['_OWNER_ENTITY_ID_']);
			}

			$data['TITLE'] = htmlspecialcharsback($data['TITLE']);
			$item = new Item\Task\Template\CheckList($data);
			$saveResult = $item->save();

			$this->errors->load($saveResult->getErrors());

			if($item->getId())
			{
				$display = $data['TITLE'];
//				$display = UI::sanitizeString($display, array('a'=>array('href'), 'img'=>array('src')));
				$display = UI::convertBBCodeToHtml($display, array('PRESET'=>'BASIC'));

				$result = array('DATA'=>array('ID' => $item->getId(), 'DISPLAY'=>$display, 'TITLE'=>strip_tags($display)));
			}
		}

		return $result;
	}

	/**
	 * Update a check list item
	 */
	public function update($id, array $data, array $parameters = array())
	{
		$result = array();

		if(!($id = $this->checkId($id)))
		{
			return $result;
		}
		$result['ID'] = $id;

		$connectorField = Item\Task\Template\CheckList::getParentConnectorField();
		if(array_key_exists('_OWNER_ENTITY_ID_', $data) || array_key_exists($connectorField, $data))
		{
			$this->errors->add('OWNER_ENTITY_ID_IS_READONLY', 'Can not change owner entity for an existing item');
		}

		// todo: check $data here, check for publicly-readable\writable keys\values

		if($this->errors->checkNoFatals())
		{
			$item = new Item\Task\Template\CheckList($id);

			if($data['TITLE'])
			{
				$data[ 'TITLE' ] = htmlspecialcharsback($data[ 'TITLE' ]);
			}

			$item->setData($data);
			$saveResult = $item->save();

			$this->errors->load($saveResult->getErrors());

			if($data['TITLE'])
			{
				$display = $data[ 'TITLE' ];
//				$display = UI::sanitizeString($display, array('a' => array('href'), 'img' => array('src')));
				$display = UI::convertBBCodeToHtml($display, array('PRESET' => 'BASIC'));

				$result = array('DATA' => array('ID' => $item->getId(), 'DISPLAY' => $display, 'TITLE' => strip_tags($display)));
			}
			else
			{
				$result = array('ID' => $item->getId());
			}
		}

		return $result;
	}

	/**
	 * Delete a check list item
	 */
	public function delete($id)
	{
		$result = array();

		if(!($id = $this->checkId($id)))
		{
			return $result;
		}
		$result['ID'] = $id;

		$item = new Item\Task\Template\CheckList($id);
		$deleteResult = $item->delete();
		$this->errors->load($deleteResult->getErrors());

		return $result;
	}

	/**
	 * Set a specified check list item complete
	 */
	public function complete($id)
	{
		return $this->update($id, array('CHECKED' => 1));
	}

	/**
	 * Set a specified check list item uncomplete
	 */
	public function renew($id)
	{
		return $this->update($id, array('CHECKED' => 0));
	}

	/**
	 * Move a specified check list item after another check list item
	 */
	public function moveAfter($id, $afterId)
	{
		// you can move check list items ONLY when you have write access to the task
		$result = array();

		if($id = $this->checkId($id))
		{
			$item = new Item\Task\Template\CheckList($id);
			$parent = $item->getParent(); // get parent by item

			if ($parent == null)
			{
				return $result;
			}

			// get the entire collection, move items in it and then save
			$moveResult = $parent['SE_CHECKLIST']->moveItemAfter($id, $afterId);
			if($moveResult->isSuccess())
			{
				$parent->setFieldModified('SE_CHECKLIST'); // temporal spike, just to tell $parent that collection was changed
				$saveResult = $parent->save();
				$this->errors->load($saveResult->getErrors());
			}
			else
			{
				$this->errors->load($moveResult->getErrors());
			}
		}

		return $result;
	}
}