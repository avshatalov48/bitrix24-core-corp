<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 * 
 * @access private
 */

namespace Bitrix\Tasks\Dispatcher;

use \Bitrix\Tasks\Util\Error\Collection;

abstract class PublicAction
{
	protected $errors = null;

	// todo: the ability to specify according to which version API should behave
	// todo: transform number-dot notation (like '10.5.3') into an integer
	protected $version = 0;
	// todo
	protected $context = 'ajax'; // also could be 'rest', 'hit'

	public function __construct()
	{
		$this->errors = new Collection();
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function canExecute()
	{
		return true;
	}

	public function getErrorCollection()
	{
		return $this->errors;
	}

	// todo: implement current version check
	public function isVersionGT()
	{
	}

	/**
	 * todo: replace this method with some phpdoc notation
	 * @return array
	 */
	public static function getForbiddenMethods()
	{
		return array(
			'__construct',
			'getErrorCollection',
			'getErrors',
			'getForbiddenMethods',
			'canExecute',
			'isVersionGT',
			'getComponentHTML'
		);
	}

	/**
	 * @param $id
	 * @return bool|int
	 *
	 * @deprecated as entity-specific method, not general one
	 */
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

	public static function getComponentHTML($name, $template = '', array $callParameters = array(), array $parameters = array())
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			$name,
			$template,
			$callParameters,
			null,
			array("HIDE_ICONS" => "Y")
		);

		return ob_get_clean();
	}
}