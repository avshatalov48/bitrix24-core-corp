<?php

namespace Bitrix\Voximplant\Ivr;

use Bitrix\Main\Context;
use Bitrix\Main\SystemException;
use Bitrix\Voximplant\Model\IvrActionTable;
use Bitrix\Voximplant\Model\IvrItemTable;
use Bitrix\Voximplant\Tts;

final class Item
{
	const TYPE_MESSAGE = 'message';
	const TYPE_URL = 'url';
	const TYPE_FILE = 'file';

	const TIMEOUT_ACTION_REPEAT = 'repeat';
	const TIMEOUT_ACTION_EXIT = 'exit';

	protected $id = 0;
	protected $ivrId;
	protected $name;
	protected $type;
	protected $url;
	protected $fileId;
	protected $timeout = 15;
	protected $timeoutAction = self::TIMEOUT_ACTION_EXIT;
	protected $message;
	protected $ttsVoice;
	protected $ttsVolume;
	protected $ttsSpeed;

	/** @var Ivr */
	protected $ivr = null;
	/** @var Action[] */
	protected $actions = array();

	/** @var Action[] */
	protected $actionsToDelete = array();

	public function __construct($id = 0)
	{
		$this->ttsVoice = Tts\Language::getDefaultVoice(Context::getCurrent()->getLanguage());
		$this->ttsVolume = Tts\Volume::getDefault();
		$this->ttsSpeed = Tts\Speed::getDefault();
	}

	public static function createFromArray(array $parameters)
	{
		$item = new self();
		$item->setFromArray($parameters);

		return $item;
	}

	public function persist()
	{
		$item = $this->toArray();
		unset($item['ID']);
		unset($item['ACTIONS']);

		if($this->id > 0)
		{
			IvrItemTable::update($this->id, $item);
		}
		else
		{
			$insertResult = IvrItemTable::add($item);
			if(!$insertResult->isSuccess())
				throw new SystemException('Error while saving IVR item to database');

			$this->id = $insertResult->getId();
		}

		foreach ($this->actionsToDelete as $action)
			$action->delete();

		$this->actionsToDelete = array();

		foreach ($this->actions as $action)
		{
			$action->setItemId($this->id);
			$action->persist();
		}
	}

	public function delete()
	{
		foreach ($this->actions as $action)
		{
			$action->delete();
		}
		$this->actions = array();

		if($this->id > 0)
		{
			IvrItemTable::delete($this->id);
			$this->id = 0;
		}
	}

	public function toArray($resolveAdditionalFields = false)
	{
		$result = array(
			'ID' => $this->id,
			'IVR_ID' => $this->ivrId,
			'NAME' => $this->name,
			'TYPE' => $this->type,
			'URL' => $this->url,
			'MESSAGE' => $this->message,
			'FILE_ID' => $this->fileId,
			'TIMEOUT' => $this->timeout,
			'TIMEOUT_ACTION' => $this->timeoutAction,
			'TTS_VOICE' => $this->ttsVoice,
			'TTS_SPEED' => $this->ttsSpeed,
			'TTS_VOLUME' =>$this->ttsVolume,
			'ACTIONS' => array()
		);

		if($resolveAdditionalFields)
		{
			if($this->type = static::TYPE_FILE && $this->fileId > 0)
			{
				$fileRecord = \CFile::GetFileArray($this->fileId);
				if (substr($fileRecord['SRC'], 0, 4) == 'http' || substr($fileRecord['SRC'], 0, 2) == '//')
					$result['FILE_SRC'] = $fileRecord['SRC'];
				else
					$result['FILE_SRC'] = \CVoxImplantHttp::GetServerAddress().$fileRecord['SRC'];
			}
		}
		
		foreach ($this->actions as $action)
		{
			$result['ACTIONS'][] = $action->toArray();
		}
		
		return $result;
	}

	public function setFromArray(array $parameters)
	{
		if(isset($parameters['ID']))
			$this->id = $parameters['ID'];

		if(isset($parameters['IVR_ID']))
			$this->setIvrId($parameters['IVR_ID']);

		if(isset($parameters['NAME']))
			$this->setName($parameters['NAME']);

		if(isset($parameters['TYPE']))
			$this->setType($parameters['TYPE']);

		if(isset($parameters['URL']))
			$this->setUrl($parameters['URL']);

		if(isset($parameters['FILE_ID']))
			$this->setFileId($parameters['FILE_ID']);

		if(isset($parameters['TIMEOUT']))
			$this->setTimeout($parameters['TIMEOUT']);

		if(isset($parameters['TIMEOUT_ACTION']))
			$this->setTimeoutAction($parameters['TIMEOUT_ACTION']);

		if(isset($parameters['MESSAGE']))
			$this->setMessage($parameters['MESSAGE']);

		if(isset($parameters['TTS_VOICE']))
			$this->setTtsVoice($parameters['TTS_VOICE']);

		if(isset($parameters['TTS_SPEED']))
			$this->setTtsSpeed($parameters['TTS_SPEED']);

		if(isset($parameters['TTS_VOLUME']))
			$this->setTtsVolume($parameters['TTS_VOLUME']);

		if(isset($parameters['ACTIONS']))
			$this->setActions($parameters['ACTIONS']);

		return $this;
	}

	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getIvrId()
	{
		return $this->ivrId;
	}

	/**
	 * @param mixed $ivrId
	 */
	public function setIvrId($ivrId)
	{
		$this->ivrId = $ivrId;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param mixed $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return mixed
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param mixed $url
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * @return mixed
	 */
	public function getMessage()
	{
		return $this->message;
	}

	/**
	 * @param mixed $message
	 */
	public function setMessage($message)
	{
		$this->message = $message;
	}

	/**
	 * @return mixed
	 */
	public function getTtsVoice()
	{
		return $this->ttsVoice;
	}

	/**
	 * @param mixed $ttsVoice
	 */
	public function setTtsVoice($ttsVoice)
	{
		$this->ttsVoice = $ttsVoice;
	}

	/**
	 * @return mixed
	 */
	public function getTtsVolume()
	{
		return $this->ttsVolume;
	}

	/**
	 * @param mixed $ttsVolume
	 */
	public function setTtsVolume($ttsVolume)
	{
		$this->ttsVolume = $ttsVolume;
	}

	/**
	 * @return mixed
	 */
	public function getTtsSpeed()
	{
		return $this->ttsSpeed;
	}

	/**
	 * @param mixed $ttsSpeed
	 */
	public function setTtsSpeed($ttsSpeed)
	{
		$this->ttsSpeed = $ttsSpeed;
	}

	/**
	 * @return mixed
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * @param mixed $fileId
	 */
	public function setFileId($fileId)
	{
		$this->fileId = $fileId;
	}

	/**
	 * @return mixed
	 */
	public function getTimeout()
	{
		return $this->timeout;
	}

	/**
	 * @param mixed $timeout
	 */
	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
	}

	/**
	 * @return mixed
	 */
	public function getTimeoutAction()
	{
		return $this->timeoutAction;
	}

	/**
	 * @param mixed $timeoutAction
	 */
	public function setTimeoutAction($timeoutAction)
	{
		$this->timeoutAction = $timeoutAction;
	}

	/**
	 * @return Ivr
	 */
	public function getIvr()
	{
		return $this->ivr;
	}

	/**
	 * @param Ivr $ivr
	 */
	public function setIvr($ivr)
	{
		$this->ivr = $ivr;

		return $this;
	}

	/**
	 * @return Action[]
	 */
	public function getActions()
	{
		return $this->actions;
	}

	/**
	 * @param Action[] $newActions
	 */
	public function setActions(array $newActions)
	{
		$oldActions = array();
		foreach ($this->actions as $action)
		{
			if($action->getId() > 0)
			{
				$oldActions[$action->getId()] = $action;
			}
		}
		$this->actions = array();

		foreach ($newActions as $action)
		{
			if(is_array($action))
			{
				$action = Action::createFromArray($action);
			}

			if($action->getId() > 0 && count($oldActions) > 0)
			{
				if(isset($oldActions[$action->getId()]))
				{
					$tmpNewAction = $oldActions[$action->getId()];
					$tmpNewAction->setFromArray($action->toArray());
					$this->actions[] = $tmpNewAction;
					unset($oldActions[$action->getId()]);
				}
				else
				{
					$action->setId(0);
					$this->actions[] = $action;
				}
			}
			else
			{
				$this->actions[] = $action;
			}
		}

		foreach ($oldActions as $action)
		{
			$this->actionsToDelete[] = $action;
		}

		return $this;
	}

	/**
	 * @param $ivrId
	 * @return Item[]
	 */
	public static function getItemsByIvrId($ivrId)
	{
		$result = array();

		$cursor = IvrItemTable::getList(array(
			'filter' => array(
				'IVR_ID' => $ivrId
			)
		));

		while ($row = $cursor->fetch())
		{
			$item = self::createFromArray($row);
			$item->actions = Action::getActionsByItemId($row['ID']);
			$result[] = $item;
		}

		return $result;
	}
}