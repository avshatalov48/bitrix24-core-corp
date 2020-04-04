<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 */

namespace Bitrix\Tasks\Dispatcher;

use \Bitrix\Tasks\Util\Error\Collection;

abstract class PublicCallable
{
	protected $errors = null;

	public function __construct()
	{
		$this->errors = new Collection();
	}

	public function getErrorCollection()
	{
		return $this->errors;
	}

	public static function getForbiddenMethods()
	{
		return array(
			'__construct' => true,
			'getErrorCollection' => true,
			'getForbiddenMethods' => true,
		);
	}

	protected function checkTaskId($id)
	{
		return $this->checkId($id, 'Task item');
	}

	protected function checkId($id, $itemName = 'Item')
	{
		$id = intval($id);
		if(!$id)
		{
			$this->errors->add('ILLEGAL_ID', $itemName.' ID is illegal');
			return false;
		}

		return $id;
	}
}