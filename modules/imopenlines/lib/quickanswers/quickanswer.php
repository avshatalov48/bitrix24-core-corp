<?php

namespace Bitrix\ImOpenlines\QuickAnswers;

class QuickAnswer
{
	protected $id;
	protected $category;
	protected $name;
	protected $text;
	protected $rating;
	protected $messageId;

	/** @var DataManager */
	protected static $dataManager;

	/**
	 * Adds new record through dataManager, returns new self on success or false on failure.
	 *
	 * @param array $data
	 * @return QuickAnswer|bool
	 */
	public static function add(array $data)
	{
		if(!self::$dataManager)
		{
			self::setDataManager(self::getDefaultDataManager());
		}
		if(!empty($data['TEXT']))
		{
			return new self($data);
		}

		return false;
	}

	protected function __construct($data)
	{
		if(!isset($data['NAME']))
		{
			$data['NAME'] = self::generateNameFromText($data['TEXT']);
		}
		if(!isset($data['RATING']))
		{
			$data['RATING'] = 1;
		}
		if(!isset($data['ID']))
		{
			$data['ID'] = self::$dataManager->add($data);
		}
		if($data['ID'] > 0)
		{
			$this->initFromArray($data);
		}
	}

	/**
	 * Returns array of self on filter.
	 *
	 * @param array $filter
	 * @param int $limit Maximum size of the result array.
	 * @param int $offset
	 * @return QuickAnswer[]
	 */
	public static function getList(array $filter = array(), $offset = 0, $limit = 10)
	{
		if(!self::$dataManager)
		{
			self::setDataManager(self::getDefaultDataManager());
		}
		$result = array();

		$items = self::$dataManager->getList($filter, $offset, $limit);
		foreach($items as $item)
		{
			$result[] = new self($item);
		}

		return $result;
	}

	/**
	 * Update record through dataManager, update $this attributes.
	 * Returns true on success, false on failure.
	 *
	 * @param array $data
	 * @return bool
	 */
	public function update($data)
	{
		if(!self::$dataManager)
		{
			self::setDataManager(self::getDefaultDataManager());
		}
		$data['ID'] = $this->id;
		if(self::$dataManager->update($this->id, $data))
		{
			$this->initFromArray($data);
			return true;
		}
		else
		{
			$this->id = 0;
			return false;
		}
	}

	/**
	 * Deletes record of $this object through dataManager
	 *
	 * @return mixed
	 */
	public function delete()
	{
		if(!self::$dataManager)
		{
			self::setDataManager(self::getDefaultDataManager());
		}
		return self::$dataManager->delete($this->id);
	}

	/**
	 * Sets dataManager that provide access to records in DB.
	 *
	 * @param DataManager $dataManager
	 */
	public static function setDataManager(DataManager $dataManager)
	{
		self::$dataManager = $dataManager;
	}

	/**
	 * Tries to find record in DB through dataManager on id.
	 * Returns new self on success, false on failure.
	 *
	 * @param $id
	 * @return QuickAnswer|bool
	 */
	public static function getById($id)
	{
		if(!self::$dataManager)
		{
			self::setDataManager(self::getDefaultDataManager());
		}
		$data = self::$dataManager->getById($id);
		if(!empty($data))
		{
			return new self($data);
		}

		return false;
	}

	/**
	 * Fills attributes of $this from array.
	 *
	 * @param $data
	 */
	protected function initFromArray($data)
	{
		foreach($data as $key => $value)
		{
			$attribute = strtolower($key);
			if(property_exists($this, $attribute))
			{
				$this->$attribute = $value;
			}
		}
	}

	/**
	 * Returns default DataManager() to work with DB.
	 *
	 * @return DataManager
	 */
	protected static function getDefaultDataManager()
	{
		return new ListsDataManager();
	}

	private static function generateNameFromText($text)
	{
		return substr($text, 0, 100);
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns url to public page to manage records.
	 *
	 * @return string
	 */
	public static function getUrlToList()
	{
		if(!self::$dataManager)
		{
			self::setDataManager(self::getDefaultDataManager());
		}
		return self::$dataManager->getUrlToList();
	}

	/**
	 * Returns array of sections.
	 *
	 * @return array
	 */
	public static function getSectionList()
	{
		if(!self::$dataManager)
		{
			self::setDataManager(self::getDefaultDataManager());
		}
		return self::$dataManager->getSectionList();
	}

	/**
	 * @return int
	 */
	public function getCategory()
	{
		if($this->category > 0)
		{
			return $this->category;
		}

		return 0;
	}

	/**
	 * Returns count of records on $filter.
	 *
	 * @param array $filter
	 * @return mixed
	 */
	public static function getCount(array $filter = array())
	{
		if(!self::$dataManager)
		{
			self::setDataManager(self::getDefaultDataManager());
		}
		return self::$dataManager->getCount($filter);
	}

	/**
	 * Increment rating of $this and update record in DB.
	 */
	public function incrementRating()
	{
		$rating = $this->rating + 1;
		$this->update(array('RATING' => $rating));
	}
}