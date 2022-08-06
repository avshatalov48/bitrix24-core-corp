<?php
namespace Bitrix\Tasks\CheckList\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Result;

Loc::loadMessages(__FILE__);

/**
 * Class CheckListFields
 *
 * @package Bitrix\Tasks\CheckList\Internals
 */
class CheckListFields
{
	const MEMBER_TYPES = [
		'accomplice' => 'A',
		'auditor' => 'U',
	];

	private $id;
	private $copiedId;
	private $entityId;
	private $userId;
	private $createdBy;
	private $parentId;
	private $title = '';
	private $sortIndex;
	private $displaySortIndex = '';
	private $isComplete = false;
	private $isImportant = false;
	private $completedCount = 0;
	private $members = [];
	private $attachments = [];
	private $map;

	/**
	 * @param array $array
	 * @return string
	 */
	private static function parseArrayValueForOutput($array)
	{
		$stringValue = '';

		foreach ($array as $id => $value)
		{
			if (is_array($value))
			{
				$stringValue .= "[{$id}] => ".implode('|', $value).";\n";
				continue;
			}

			$stringValue .= "[{$id}] => {$value};\n";
		}

		return $stringValue;
	}

	/**
	 * CheckListFields constructor.
	 *
	 * @param array $fields
	 */
	public function __construct($fields)
	{
		$this->map = $this->buildMap();
		$this->setFields($fields);
	}

	/**
	 * Returns all checklist fields.
	 *
	 * @return array
	 */
	public function getFields()
	{
		$fields = [];

		foreach (array_keys($this->map) as $field)
		{
			$get = $this->convertToCamelCase('get_' . $field);
			$fields[$field] = $this->$get();
		}

		return $fields;
	}

	/**
	 * Sets values to fields after checking.
	 *
	 * @param array $fields
	 */
	public function setFields($fields)
	{
		foreach ($fields as $name => $value)
		{
			if (array_key_exists($name, $this->map))
			{
				$check = $this->map[$name]['SET_CHECK'];
				$checkResult = $check($value);

				if ($checkResult !== null)
				{
					$set = $this->convertToCamelCase('set_' . $name);
					$this->$set($checkResult);
				}
			}
		}
	}

	/**
	 * Checks if fields are correct and ready to save
	 *
	 * @return Result
	 */
	public function checkFields()
	{
		$checkResult = new Result();

		$fieldsToCheck = [
			'ENTITY_ID',
			'USER_ID',
			'ID',
			'COPIED_ID',
			'CREATED_BY',
			'PARENT_ID',
			'TITLE',
			'SORT_INDEX',
			'IS_COMPLETE',
			'IS_IMPORTANT',
			'MEMBERS',
			'ATTACHMENTS',
		];

		foreach ($fieldsToCheck as $field)
		{
			$get = $this->convertToCamelCase('get_' . $field);
			$value = $this->$get();
			$saveCheck = $this->map[$field]['SAVE_CHECK'];

			if (!$saveCheck($value))
			{
				if (is_array($value))
				{
					$value = static::parseArrayValueForOutput($value);
				}

				$search = ['#VALUE#', '#FIELD#', '#ID#', '#TITLE#'];
				$replace = [$value, $field, $this->id, $this->title];
				$message = str_replace($search, $replace, Loc::getMessage('TASKS_CHECKLIST_FIELDS_CHECKING_FAILED'));

				$checkResult->addError('CHECK_FIELDS_FAILED', $message);
				return $checkResult;
			}
		}

		return $checkResult;
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	private function convertToCamelCase($name)
	{
		return str_replace('_', '', lcfirst(ucwords(mb_strtolower($name), '_')));
	}

	/**
	 * @return array
	 */
	private function buildMap()
	{
		$setCheckFunctions = $this->getSetCheckFunctions();
		$saveCheckFunctions = $this->getSaveCheckFunctions();

		return [
			'ID' => [
				'SET_CHECK' => $setCheckFunctions['INT'],
				'SAVE_CHECK' => $saveCheckFunctions['STRICT_INT'],
			],
			'COPIED_ID' => [
				'SET_CHECK' => $setCheckFunctions['INT'],
				'SAVE_CHECK' => $saveCheckFunctions['INT'],
			],
			'ENTITY_ID' => [
				'SET_CHECK' => $setCheckFunctions['INT'],
				'SAVE_CHECK' => $saveCheckFunctions['STRICT_REQUIRED'],
			],
			'USER_ID' => [
				'SET_CHECK' => $setCheckFunctions['INT'],
				'SAVE_CHECK' => $saveCheckFunctions['STRICT_REQUIRED'],
			],
			'CREATED_BY' => [
				'SET_CHECK' => $setCheckFunctions['INT'],
				'SAVE_CHECK' => $saveCheckFunctions['STRICT_INT'],
			],
			'PARENT_ID' => [
				'SET_CHECK' => $setCheckFunctions['INT'],
				'SAVE_CHECK' => $saveCheckFunctions['REQUIRED'],
			],
			'TITLE' => [
				'SET_CHECK' => $setCheckFunctions['TITLE'],
				'SAVE_CHECK' => $saveCheckFunctions['TITLE'],
			],
			'SORT_INDEX' => [
				'SET_CHECK' => $setCheckFunctions['INT'],
				'SAVE_CHECK' => $saveCheckFunctions['INT'],
			],
			'DISPLAY_SORT_INDEX' => [
				'SET_CHECK' => $setCheckFunctions['STRING'],
				'SAVE_CHECK' => $saveCheckFunctions['STRING'],
			],
			'IS_COMPLETE' => [
				'SET_CHECK' => $setCheckFunctions['BOOLEAN'],
				'SAVE_CHECK' => $saveCheckFunctions['BOOLEAN'],
			],
			'IS_IMPORTANT' => [
				'SET_CHECK' => $setCheckFunctions['BOOLEAN'],
				'SAVE_CHECK' => $saveCheckFunctions['BOOLEAN'],
			],
			'COMPLETED_COUNT' => [
				'SET_CHECK' => $setCheckFunctions['INT'],
				'SAVE_CHECK' => $saveCheckFunctions['INT'],
			],
			'MEMBERS' => [
				'SET_CHECK' => $setCheckFunctions['MEMBERS'],
				'SAVE_CHECK' => $saveCheckFunctions['MEMBERS'],
			],
			'ATTACHMENTS' => [
				'SET_CHECK' => $setCheckFunctions['ATTACHMENTS'],
				'SAVE_CHECK' => $saveCheckFunctions['ATTACHMENTS'],
			],
		];
	}

	/**
	 * @return array
	 */
	private function getSetCheckFunctions()
	{
		$checkInt = static function($value)
		{
			return (in_array($value, ['null', null], true)? null : (int)$value);
		};

		$checkString = static function($value)
		{
			if (is_string($value) && trim($value) !== '')
			{
				return trim($value);
			}

			return null;
		};

		$checkTitle = static function($value)
		{
			if (is_string($value) && trim($value) !== '')
			{
				return trim($value);
			}

			return null;
		};

		$checkBoolean = static function($value)
		{
			return in_array($value, ['Y', 'true', true, 1], true);
		};

		$checkMembers = static function($value)
		{
			$result = $value;

			if (!is_array($result) && !$result)
			{
				$result = [];
			}

			if (is_array($result))
			{
				foreach ($result as $id => $data)
				{
					$type = static::getCorrectType($data['TYPE']);

					if ($type)
					{
						$data['TYPE'] = $type;
						$result[$id] = $data;
					}
					else
					{
						return null;
					}
				}

				return $result;
			}

			return null;
		};

		$checkAttachments = static function($value)
		{
			$result = $value;

			if (is_array($result))
			{
				foreach ($result as $id => $fileId)
				{
					if (is_array($fileId) && isset($fileId['FILE_ID']))
					{
						$fileId = $fileId['FILE_ID'];
					}

					$fileId = (string)($fileId[0] === 'n' ? $fileId : 'n'.$fileId);
					$result[$id] = $fileId;

					if (!preg_match('/(^n\d+$)/', $fileId))
					{
						unset($result[$id]);
					}
				}

				return $result;
			}

			return null;
		};

		return [
			'INT' => $checkInt,
			'STRING' => $checkString,
			'BOOLEAN' => $checkBoolean,
			'TITLE' => $checkTitle,
			'MEMBERS' => $checkMembers,
			'ATTACHMENTS' => $checkAttachments,
		];
	}

	/**
	 * @param $type
	 * @return null|string
	 */
	private static function getCorrectType($type)
	{
		if (array_key_exists($type, self::MEMBER_TYPES))
		{
			$type = self::MEMBER_TYPES[$type];
		}

		if (!in_array($type, self::MEMBER_TYPES, true))
		{
			$type = null;
		}

		return $type;
	}

	/**
	 * @return array
	 */
	private function getSaveCheckFunctions()
	{
		$checkRequired = static function($value)
		{
			return isset($value) && $value >= 0;
		};

		$checkStrictRequired = static function($value)
		{
			return isset($value) && $value > 0;
		};

		$checkInt = static function($value)
		{
			return !isset($value) || $value >= 0;
		};

		$checkStrictInt = static function($value)
		{
			return !isset($value) || $value > 0;
		};

		$checkString = static function($value)
		{
			return $value !== '';
		};

		$checkTitle = static function($value)
		{
			return $value !== '';
		};

		$checkBoolean = static function($value)
		{
			return is_bool($value);
		};

		$checkMembers = static function($value)
		{
			if (is_array($value))
			{
				foreach ($value as $data)
				{
					if (!in_array($data['TYPE'], self::MEMBER_TYPES, true))
					{
						return false;
					}
				}

				return true;
			}

			return false;
		};

		$checkAttachments = static function($value)
		{
			if (is_array($value))
			{
				foreach ($value as $id)
				{
					if (!preg_match('/(^n\d+$)/', (string)$id))
					{
						return false;
					}
				}

				return true;
			}

			return false;
		};

		return [
			'REQUIRED' => $checkRequired,
			'STRICT_REQUIRED' => $checkStrictRequired,
			'INT' => $checkInt,
			'STRICT_INT' => $checkStrictInt,
			'STRING' => $checkString,
			'BOOLEAN' => $checkBoolean,
			'TITLE' => $checkTitle,
			'MEMBERS' => $checkMembers,
			'ATTACHMENTS' => $checkAttachments,
		];
	}

	/**
	 * @return null|int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return null|int
	 */
	public function getCopiedId()
	{
		return $this->copiedId;
	}

	/**
	 * @param $copiedId
	 */
	public function setCopiedId($copiedId)
	{
		$this->copiedId = $copiedId;
	}

	/**
	 * @return null|int
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * @param $entityId
	 */
	public function setEntityId($entityId)
	{
		$this->entityId = $entityId;
	}

	/**
	 * @return null|int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @return null|int
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * @param $createdBy
	 */
	public function setCreatedBy($createdBy)
	{
		$this->createdBy = $createdBy;
	}

	/**
	 * @return null|int
	 */
	public function getParentId()
	{
		return $this->parentId;
	}

	/**
	 * @param $parentId
	 */
	public function setParentId($parentId)
	{
		$this->parentId = $parentId;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return null|int
	 */
	public function getSortIndex()
	{
		return $this->sortIndex;
	}

	/**
	 * @param $sortIndex
	 */
	public function setSortIndex($sortIndex)
	{
		$this->sortIndex = $sortIndex;
	}

	/**
	 * @return string
	 */
	public function getDisplaySortIndex()
	{
		return $this->displaySortIndex;
	}

	/**
	 * @param $displaySortIndex
	 */
	public function setDisplaySortIndex($displaySortIndex)
	{
		$this->displaySortIndex = $displaySortIndex;
	}

	/**
	 * @return bool
	 */
	public function getIsComplete()
	{
		return $this->isComplete;
	}

	/**
	 * @param $isComplete
	 */
	public function setIsComplete($isComplete)
	{
		$this->isComplete = $isComplete;
	}

	/**
	 * @return bool
	 */
	public function getIsImportant()
	{
		return $this->isImportant;
	}

	/**
	 * @param $isImportant
	 */
	public function setIsImportant($isImportant)
	{
		$this->isImportant = $isImportant;
	}

	/**
	 * @return int
	 */
	public function getCompletedCount()
	{
		return $this->completedCount;
	}

	/**
	 * @param $completedCount
	 */
	public function setCompletedCount($completedCount)
	{
		$this->completedCount = $completedCount;
	}

	/**
	 * @return array
	 */
	public function getMembers()
	{
		return $this->members;
	}

	/**
	 * @param $members
	 */
	public function setMembers($members)
	{
		$this->members = $members;
	}

	/**
	 * @param $memberId
	 */
	public function removeMember($memberId)
	{
		unset($this->members[$memberId]);
	}

	/**
	 * @return array
	 */
	public function getAttachments()
	{
		return $this->attachments;
	}

	/**
	 * @param $attachments
	 */
	public function setAttachments($attachments)
	{
		$this->attachments = $attachments;
	}

	/**
	 * @param $fileId
	 */
	public function addAttachment($fileId)
	{
		$this->attachments[$fileId] = $fileId;
	}

	/**
	 * @param $attachmentId
	 */
	public function removeAttachment($attachmentId)
	{
		unset($this->attachments[$attachmentId]);
	}
}