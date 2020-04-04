<?
/**
 * @deprecated
 */

namespace Bitrix\Tasks\Internals\DataBase\Tree;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Exception	extends \Bitrix\Tasks\Exception
{
	public function __construct($message = false, array $data = array(), array $additional = array())
	{
		if(is_array($data['NODES']) && (string) $data['AUX']['MESSAGE'] == '')
		{
			$data['AUX']['MESSAGE'] = 'nodes: '.implode(', ', $data['NODES']);
		}

		parent::__construct($message, $data, $additional);
	}

	public function getDefaultMessage()
	{
		return 'Internal failure';
	}
}
class NodeNotFoundException	extends Exception
{
	public function getDefaultMessage()
	{
		return 'Node not found';
	}
}
class TargetNodeNotFoundException extends NodeNotFoundException
{
	public function getDefaultMessage()
	{
		return 'Node not found';
	}
}
class ParentNodeNotFoundException extends NodeNotFoundException
{
	public function getDefaultMessage()
	{
		return 'Parent node not found';
	}
}
class LinkExistsException extends Exception
{
	public function getDefaultMessage()
	{
		return 'Link already exists';
	}

	protected function getMessageLang()
	{
		return Loc::getMessage('TASK_TREE_EXCEPTION_LINK_EXISTS_EXCEPTION');
	}
}
class LinkNotExistException	extends Exception
{
	public function getDefaultMessage()
	{
		return 'Link does not exist';
	}

	protected function getMessageLang()
	{
		return Loc::getMessage('TASK_TREE_EXCEPTION_LINK_DOES_NOT_EXIST_EXCEPTION');
	}
}