<?php
namespace Bitrix\Bizproc\Automation\Engine;

use Bitrix\Bizproc\Automation\Helper;

class DelayInterval
{
	const TYPE_BEFORE = 'before';
	const TYPE_AFTER = 'after';
	const TYPE_IN = 'in';

	protected $type = 'after'; //TYPE_AFTER
	protected $value;
	protected $valueType;
	protected $basis;
	protected $workTime = false;
	protected $localTime = false;

	/**
	 * DelayInterval constructor.
	 * @param array|null $params Initial parameters.
	 */
	public function __construct(array $params = null)
	{
		if ($params)
		{
			if (isset($params['type']))
			{
				$this->setType($params['type']);
			}
			if (isset($params['value']))
			{
				$this->setValue($params['value']);
			}
			if (isset($params['valueType']))
			{
				$this->setValueType($params['valueType']);
			}

			$this->setBasis(isset($params['basis']) ? $params['basis'] : Helper::CURRENT_DATETIME_BASIS);

			if (isset($params['workTime']))
			{
				$this->setWorkTime($params['workTime']);
			}
			if (isset($params['localTime']))
			{
				$this->setLocalTime($params['localTime']);
			}
		}
	}

	/**
	 * @param array $properties Activity properties.
	 * @return DelayInterval
	 */
	public static function createFromActivityProperties(array $properties)
	{
		$params = [];
		if (is_array($properties))
		{
			if (isset($properties['TimeoutTime']))
			{
				$params = Helper::parseDateTimeInterval($properties['TimeoutTime']);
			}
			elseif
			(
				isset($properties['TimeoutDuration'])
				&& isset($properties['TimeoutDurationType'])
				&& is_numeric($properties['TimeoutDuration'])
				&& $properties['TimeoutDurationType'] !== 's'
			)
			{
				if ($properties['TimeoutDurationType'] === 'm')
				{
					$properties['TimeoutDurationType'] = 'i';
				}
				$params = array(
					'type' => static::TYPE_AFTER,
					'value' => (int)$properties['TimeoutDuration'],
					'valueType' => $properties['TimeoutDurationType'],
				);
			}

			if (isset($properties['TimeoutTimeIsLocal']))
			{
				$params['localTime'] = ($properties['TimeoutTimeIsLocal'] === 'Y');
			}
		}

		return new static($params);
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param mixed $type Interval type (before, after, in time).
	 * @return DelayInterval
	 */
	public function setType($type)
	{
		$type = (string)$type;
		if ($type === static::TYPE_BEFORE || $type === static::TYPE_AFTER || $type === static::TYPE_IN)
		{
			$this->type = $type;
		}

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param int $value Delay interval value.
	 * @return DelayInterval
	 */
	public function setValue($value)
	{
		$this->value = (int)$value;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValueType()
	{
		return $this->valueType;
	}

	/**
	 * @param string $valueType Delay interval value type.
	 * @return DelayInterval
	 */
	public function setValueType($valueType)
	{
		if ($valueType === 'i' || $valueType === 'h' || $valueType === 'd')
		{
			$this->valueType = $valueType;
		}

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getBasis()
	{
		return $this->basis;
	}

	/**
	 * @param mixed $basis Delay interval basis (date/time field etc.).
	 * @return DelayInterval
	 */
	public function setBasis($basis)
	{
		$this->basis = $basis;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function isWorkTime()
	{
		return $this->workTime;
	}

	/**
	 * @param bool $flag True of false.
	 * @return $this
	 */
	public function setWorkTime($flag)
	{
		$this->workTime = (bool)$flag;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function isLocalTime()
	{
		return $this->localTime;
	}

	/**
	 * @param bool $flag True or false.
	 * @return $this
	 */
	public function setLocalTime($flag)
	{
		$this->localTime = (bool)$flag;
		return $this;
	}

	/**
	 * Converts instance to array.
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'type' => $this->getType(),
			'value' => $this->getValue(),
			'valueType' => $this->getValueType(),
			'basis' => $this->getBasis(),
			'workTime' => $this->isWorkTime(),
			'localTime' => $this->isLocalTime(),
		);
	}

	/**
	 * Converts instance to activity properties array.
	 * @param array $documentType Document type.
	 * @return array
	 */
	public function toActivityProperties(array $documentType = null)
	{
		$properties = [
			'TimeoutTimeIsLocal' => 'N',//$this->isLocalTime() ? 'Y' : 'N'
		];

		if (
			$this->getBasis() === Helper::CURRENT_DATETIME_BASIS
			&& $this->getType() === static::TYPE_AFTER
			&& !$this->isWorkTime()
		)
		{
			$valueType = $this->getValueType();
			if ($valueType === 'i')
			{
				$valueType = 'm';
			}

			return ['TimeoutDuration' => $this->getValue(), 'TimeoutDurationType' => $valueType];
		}
		elseif ($this->getType() === static::TYPE_IN && !$this->isWorkTime())
		{
			$properties['TimeoutTime'] = $this->getBasis();
		}
		else
		{
			$intervalProperties = $this->toArray();
			if ($documentType)
			{
				$intervalProperties['worker'] = Helper::getResponsibleUserExpression($documentType);
			}

			$properties['TimeoutTime'] = Helper::getDateTimeIntervalString($intervalProperties);
		}

		return $properties;
	}

	/**
	 * Checks if interval equals to Now.
	 * @return bool
	 */
	public function isNow()
	{
		return (
			!$this->isWorkTime()
			&& $this->getBasis() === Helper::CURRENT_DATETIME_BASIS
			&& $this->getValue() === 0
		);
	}
}