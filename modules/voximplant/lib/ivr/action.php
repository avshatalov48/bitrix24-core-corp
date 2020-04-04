<?php

namespace Bitrix\Voximplant\Ivr;

use Bitrix\Main\SystemException;
use Bitrix\Voximplant\Model\IvrActionTable;

final class Action
{
	const ACTION_REPEAT = 'repeat';
	const ACTION_QUEUE = 'queue';
	const ACTION_ITEM = 'item';
	const ACTION_USER = 'user';
	const ACTION_PHONE = 'phone';
	const ACTION_DIRECT_CODE = 'directCode';
	const ACTION_VOICEMAIL = 'voicemail';
	const ACTION_MESSAGE = 'message';
	const ACTION_EXIT = 'exit';

	protected $id = 0;
	protected $itemId;
	protected $digit;
	protected $action;
	protected $parameters;
	protected $leadFields;

	public function __construct($id = 0)
	{
		if ($id > 0)
		{
			$row = IvrActionTable::getById($id)->fetch();
			if ($row)
			{
				$this->setFromArray($row);
			}
		}
	}

	public static function createFromArray(array $parameters)
	{
		$action = new self();
		$action->setFromArray($parameters);

		return $action;
	}

	public function persist()
	{
		if ($this->id > 0)
		{
			IvrActionTable::update($this->id, $this->toArray());
		}
		else
		{
			$insertResult = IvrActionTable::add($this->toArray());
			if (!$insertResult->isSuccess())
			{
				throw new SystemException('Error while saving IVR action to database');
			}
			$this->id = $insertResult->getId();
		}
	}

	public function delete()
	{
		if($this->id > 0)
		{
			IvrActionTable::delete($this->id);
		}

		$this->id = 0;
	}

	public function toArray()
	{
		return array(
			'ID' => $this->id,
			'ITEM_ID' => $this->itemId,
			'ACTION' => $this->action,
			'DIGIT' => $this->digit,
			'PARAMETERS' => $this->parameters,
			'LEAD_FIELDS' => $this->leadFields
		);
	}

	public function setFromArray(array $parameters)
	{
		if (isset($parameters['ID']))
			$this->id = $parameters['ID'];

		if (isset($parameters['ITEM_ID']))
			$this->itemId = $parameters['ITEM_ID'];

		if (isset($parameters['ACTION']))
			$this->setAction($parameters['ACTION']);

		if (isset($parameters['DIGIT']))
			$this->setDigit($parameters['DIGIT']);

		if (is_array($parameters['PARAMETERS']))
			$this->setParameters($parameters['PARAMETERS']);

		if (is_array($parameters['LEAD_FIELDS']))
			$this->setLeadFields($parameters['LEAD_FIELDS']);

		return $this;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getItemId()
	{
		return $this->itemId;
	}

	/**
	 * @param int $itemId
	 */
	public function setItemId($itemId)
	{
		$this->itemId = $itemId;
	}

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param string $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @return mixed
	 */
	public function getDigit()
	{
		return $this->digit;
	}

	/**
	 * @param mixed $digit
	 */
	public function setDigit($digit)
	{
		$this->digit = $digit;
	}

	/**
	 * @return mixed
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param mixed $parameters
	 */
	public function setParameters(array $parameters)
	{
		$this->parameters = $parameters;
	}

	/**
	 * @return mixed
	 */
	public function getLeadFields()
	{
		return $this->leadFields;
	}

	/**
	 * @param mixed $leadFields
	 */
	public function setLeadFields(array $leadFields)
	{
		$this->leadFields = $leadFields;
	}



	/**
	 * @param $itemId
	 * @return Action[]
	 */
	public static function getActionsByItemId($itemId)
	{
		$result = array();
		$cursor = IvrActionTable::getList(array(
			'filter' => array(
				'ITEM_ID' => $itemId
			),
			'order' => array(
				'DIGIT' => 'ASC'
			)
		));

		while ($row = $cursor->fetch())
		{
			$action = self::createFromArray($row);
			$result[] = $action;
		}

		return $result;
	}
}