<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicCallable\Socialnetwork;

final class Group extends \Bitrix\Tasks\Dispatcher\PublicCallable
{
	/**
	 * Get a social network group by ID
	 */
	public function get($id)
	{
		$result = array();

		$id = intval($id);

		if(!$id)
		{
			$this->errors->add('ILLEGAL_GROUP_ID', 'Illegal group id');
		}
		else
		{
			$data = \CSocNetGroup::GetByID($id, $bCheckPermissions = false);
			if(is_array($data) && !empty($data))
			{
				$result = $data;
			}
		}

		return $result;
	}
}