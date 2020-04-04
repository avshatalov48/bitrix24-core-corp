<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2012 Bitrix
 * 
 * See tasks\tools.php to see legacy exception TasksException
 */
namespace Bitrix\Tasks;

use \Bitrix\Tasks\Util\Error\Collection;

/*
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
*/

class Exception extends \Bitrix\Main\SystemException
{
	protected $data = array();
	protected $additional = array();
	protected $errors = null;
	protected $messageOrigin = '';

	/**
	 * @param string $message The message to be shown in trace. If set to false, the default message for this class of exception will be used.
	 * @param mixed[] $data An array of special data. Could be:
	 * 		<li>AUX MESSAGE string|mixed[] String to be attached to the end of the message in round brackets
	 * 		<li>AUX ERROR mixed[] An array structure to be dumped with AddMessage2Log(), accompanied with unique-exception-id to be able to establish matching
	 * 		<li>ERROR \Bitrix\Tasks\Util\Error\Collection|string[] A collection or string array of high-level errors to show to user
	 * @param mixed[] $additional Some additional things, usually unused
	 */
	public function __construct($message = false, array $data = array(), array $additional = array())
	{
		if(!empty($data))
		{
			$this->data = $data;
		}
		if(!empty($additional))
		{
			$this->additional = $additional;
		}

		if($message === false)
		{
			$message = $this->getDefaultMessage();
		}
		$this->messageOrigin = $message;

		if(!isset($additional['FILE']))
		{
			$additional['FILE'] = '';
		}
		$additional['LINE'] = intval($additional['LINE']);
		$additional['CODE'] = intval($additional['CODE']);
		if(!isset($additional['PREVIOUS_EXCEPTION'])) // todo: remove?
		{
			$additional['PREVIOUS_EXCEPTION'] = null;
		}

		$doDump = $this->dumpAuxError();

		if($doDump)
		{
			$exceptionId = uniqid('', true);

			if(isset($this->data['AUX']['ERROR']))
			{
				if(!is_array($this->data['AUX']['ERROR']))
				{
					$this->data['AUX']['ERROR'] = array((string) $this->data['AUX']['ERROR']);
				}

				AddMessage2Log('Exception additional data: "'.$exceptionId.'": '.serialize($this->data['AUX']['ERROR']), 'tasks');
			}
		}

		parent::__construct(($doDump ? $exceptionId.': ' : '').$this->prepareMessage($message), $additional['CODE'], $additional['FILE'], $additional['LINE'], $additional['PREVIOUS_EXCEPTION']);
	}

	protected function dumpAuxError()
	{
		return true;
	}

	public function getErrors()
	{
		if(is_array($this->data['ERROR']))
		{
			return $this->data['ERROR'];
		}
		if($this->data['ERROR'] instanceof Collection)
		{
			return $this->data['ERROR']->getMessages();
		}
		return array();
	}

	protected function prepareMessage($message)
	{
		if(isset($this->data['AUX']['MESSAGE']))
		{
			if(is_array($this->data['AUX']['MESSAGE']))
			{
				$data = serialize($this->data['AUX']['MESSAGE']);
			}
			else
			{
				$data = (string) $this->data['AUX']['MESSAGE'];
			}

			$message .= ' ('.$data.')';
		}

		return $message;
	}

	/**
	 * Get "original" message with no debug info attached
	 *
	 * @return bool|string
	 */
	public function getMessageOrigin()
	{
		return $this->messageOrigin;
	}

	public function getDefaultMessage()
	{
		return '';
	}

	/**
	 * Get localized message
	 *
	 * @return string
	 */
	protected function getMessageLang()
	{
		return '';
	}

	/**
	 * Get human-readable friendly message, localized or not
	 *
	 * @return bool|string
	 */
	public function getMessageFriendly()
	{
		$lang = $this->getMessageLang();
		if($lang == '')
		{
			return $this->getMessageOrigin();
		}
		else
		{
			return $lang;
		}
	}

	/**
	 * todo: define this code for each exception, use it as Collection::add()`s first argument when translating exceptions into errors
	 *
	 * @return string
	 */
	public function getSymbolicCode()
	{
		return 'INTERNAL_ERROR';
	}
}

abstract class ActionException extends Exception
{
	public function __construct($message = false, array $data = array(), array $additional = array())
	{
		$data['AUX']['ERROR']['USER'] = $GLOBALS['USER']->GetId();

		parent::__construct($message, $data, $additional);
	}
}

/**
 * Exception is thrown when the current user has NO access to the entity at all
 */
final class AccessDeniedException extends ActionException
{
	public function getDefaultMessage()
	{
		return 'Access denied';
	}
}

/**
 * Exception is thrown when the current user has access to the entity, but some entity action is not allowed to perform by the current user
 */
class ActionNotAllowedException extends ActionException
{
	public function getDefaultMessage()
	{
		return 'Action is not allowed';
	}
}

/**
 * Exception is thrown when some legal entity action fails
 */
final class ActionFailedException extends ActionException
{
	public function getDefaultMessage()
	{
		return 'Action failed';
	}
}

/**
 * Exception is thrown when some legal action is restricted with the current plan
 */
final class ActionRestrictedException extends ActionNotAllowedException
{
	public function getDefaultMessage()
	{
		return 'Action is restricted';
	}
}