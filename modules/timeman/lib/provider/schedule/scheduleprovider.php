<?php
namespace Bitrix\Timeman\Provider\Schedule;

use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;

/**
 * @method findByIdWith(int $getScheduleId, array $array)
 */
class ScheduleProvider
{
	/** @var ScheduleRepository */
	private $scheduleRepository;
	private $schedulesByUserIdsCached = [];

	public function __construct(ScheduleRepository $scheduleRepository)
	{
		$this->scheduleRepository = $scheduleRepository;
	}

	/**
	 * @param $userId
	 * @param array $options
	 * @return Schedule[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findSchedulesByUserId($userId, $options = [])
	{
		$key = $this->buildCachedKeyForScheduleByUser($userId, $options);
		if ($this->schedulesByUserIdsCached[$key] === null)
		{
			if ($existedKey = $this->getKeyForDataWithEnoughFields($userId, $options))
			{
				return $this->schedulesByUserIdsCached[$existedKey];
			}
			$this->schedulesByUserIdsCached[$key] = $this->scheduleRepository->findSchedulesByUserId($userId, $options);
		}
		return $this->schedulesByUserIdsCached[$key];
	}

	public function __call($name, $arguments)
	{
		if (method_exists($this->scheduleRepository, $name))
		{
			return call_user_func_array(
				[$this->scheduleRepository, $name],
				$arguments
			);
		}
		return [];
	}

	private function buildCachedKeyForScheduleByUser($userId, $options)
	{
		$key = $userId;
		if (is_array($options) && isset($options['select']) && is_array($options['select']))
		{
			foreach ($options['select'] as $fieldToSelect)
			{
				$key .= '_' . $fieldToSelect;
			}
		}
		return $key;
	}

	private function getKeyForDataWithEnoughFields($userId, $options)
	{
		if ($this->schedulesByUserIdsCached[$userId] !== null)
		{
			if (is_array($options) && isset($options['select']) && is_array($options['select']))
			{
				foreach ($options['select'] as $fieldToSelect)
				{
					foreach ($this->schedulesByUserIdsCached[$userId] as $schedule)
					{
						try
						{
							if (!$schedule->has($fieldToSelect))
							{
								return null;
							}
						}
						catch (\Exception $exc)
						{
							return null;
						}
					}
				}
			}

			return $userId;
		}
		return null;
	}
}