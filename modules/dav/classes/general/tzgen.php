<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

abstract class TZConvModel implements ArrayAccess
{
	public function __construct(array $data = array())
	{
		$this->append($data);
	}

	public function __set($property, $value)
	{
		throw new \Exception("no such property $property");
	}

	public function __get($property)
	{
		throw new \Exception("no such property $property");
	}

	public function append(array $data = array())
	{
		foreach ($data as $property => $value)
		{
			$this->{$property} = $value;
		}
	}

	public function toArray()
	{
		$array = array();
		foreach ($this as $prop => $value)
		{
			$array[$prop] = $value;
		}

		return $array;
	}

	public function offsetExists ($offset)
	{
		return property_exists($this, $offset);
	}

	public function offsetGet ($offset)
	{
		return $this->$offset;
	}

	public function offsetSet ($offset, $value)
	{
		$this->$offset = $value;
	}

	public function offsetUnset ($offset)
	{
		$this->$offset = null;
	}
}

class TZConvSet implements ArrayAccess, IteratorAggregate, Countable
{
	protected $models = array();

	public static function create($models=NULL)
	{
		return new self($models);
	}

	public function __construct($models=NULL)
	{
		if ($models)
		{
			$this->addModels($models);
		}
	}

	public function addModels($models)
	{
		foreach ($models as $model)
		{
			$this->addModel($model);
		}

		return $this;
	}

	public function addModel($model)
	{
		$this->models[] = $model;
		return $this;
	}

	public function getFirst()
	{
		$keys = array_keys($this->models);
		return $this->models[$keys[0]];
	}

	public function getLast()
	{
		$keys = array_keys($this->models);
		return $this->models[$keys[count($keys) - 1]];
	}

	public function filter($property, $value)
	{
		$filteredSet = new self;

		foreach ($this->models as $model)
		{
			if ($model[$property] == $value)
			{
				$filteredSet->addModel($model);
			}
		}

		return $filteredSet;
	}

	public function sort($property, $direction='ASC')
	{
		$map = $this->{$property};
		$fn = $direction == 'ASC' ? 'asort' : 'arsort';
		$fn($map);

		$sortedModels = array();
		foreach ($map as $key => $value)
		{
			$sortedModels[$key] = $this->models[$key];
		}

		$this->models = $sortedModels;

		return $this;
	}

	public function toArray()
	{
		$array = $this->models;
		foreach ($array as $key => $value)
		{
			if (is_object($value) && method_exists($value, 'toArray'))
			{
				$array[$key] = $value->toArray();
			}
		}

		return $array;
	}

	public function __get($property)
	{
		$properties = array();
		foreach ($this->models as $key => $model)
		{
			$properties[$key] = $model[$property];
		}

		return $properties;
	}


	public function __set($property, $value)
	{
		foreach ($this->models as $key => $model)
		{
			$this->models[$key] = $value;
		}
	}

	public function offsetExists ($offset)
	{
		return array_key_exists($offset, $this->models);
	}


	public function offsetGet ($offset)
	{
		return $this->models[$offset];
	}


	public function offsetSet ($offset, $value)
	{
		$this->models[$offset] = $value;
	}


	public function offsetUnset ($offset)
	{
		unset ($this->models[$offset]);
	}


	public function getIterator ()
	{
		return new ArrayIterator($this->models);
	}

	public function count ()
	{
		return count($this->models);
	}
}

class TZConvTransitionRule extends TZConvModel
{
	public $month;
	public $wkday;
	public $numwk;
	public $hour;
	public $minute;
	public $second;
	public $from;
	public $until;
	public $offset;
	public $isdst;
	public $abbr;
	protected $transitionDates = array();

	public function addTransitionDate(DateTime $dateTime)
	{
		$this->transitionDates[] = $dateTime;
	}

	public function clearTransitionDates()
	{
		$this->transitionDates = array();
		return $this;
	}

	public function getTransitionDates()
	{
		$transitionDates = array();
		foreach ($this->transitionDates as $date)
		{
			$transitionDates[] = clone $date;
		}

		return $transitionDates;
	}

	public function computeTransitionDate($year)
	{
		$minute = str_pad($this->minute, 2, STR_PAD_LEFT);
		$second = str_pad($this->second, 2, STR_PAD_LEFT);

		$transition = DateTime::createFromFormat('Y-m-d G:i:s',
			"{$year}-{$this->month}-1 {$this->hour}:{$minute}:{$second}",
			new DateTimeZone('UTC')
		);

		if ($transition == FALSE)
		{
			throw new Exception('invalid transition rule');
		}

		$sign = $this->numwk < 0 ? '-' : '+';

		if ($sign == '-')
		{
			$transition->modify("+1 month -1 day");
		}

		while($transition->format('w') != $this->wkday)
		{
			$transition->modify($sign . '1 day');
		}
		$transition->modify($sign . (abs($this->numwk) -1) . ' weeks');

		return $transition;
	}

	public function isRecurringRule()
	{
		return !is_null($this->month);
	}

	public static function createFromTransition(array $transition, $deduceRecurringRule=TRUE)
	{
		$date = new DateTime($transition['time'], new DateTimeZone('UTC'));

		$transitionRule = new self(array(
			 'isdst'  => $transition['isdst'],
			 'offset' => $transition['offset'],
			 'abbr'   => $transition['abbr'],
			 'from'   => clone $date,
		));

		if (!$deduceRecurringRule)
		{
			$transitionRule->addTransitionDate($date);
		}
		else
		{
			$transitionRule->append(array(
				'month'   => $date->format('n'),
				'hour'	=> $date->format('G'),
				'minute'  => (int) $date->format('i'),
				'second'  => (int) $date->format('s'),
				'wkday'   => (int) $date->format('w'),
				'numwk'   => self::getNumWk($date),
			));
		}

		return $transitionRule;
	}

	public static function getNumWk(DateTime $date)
	{
		$num = ceil($date->format('j') / 7);

		if ($num > 2)
		{
			$tester = clone $date;
			$tester->modify("last {$date->format('l')} of this month");

			$num = -1 * (1 + (($tester->format('j') - $date->format('j')) / 7));
		}

		return $num;
	}
}

class TZConvTransition extends TZConvModel
{
	public $ts;
	public $date;
	public $offset;
	public $isdst;
	public $abbr;

	public static function getMatchingTimezone($transitions, $expectedTimeZone=NULL)
	{
		if ($expectedTimeZone && self::matchTimezone($transitions, $expectedTimeZone))
		{
			return $expectedTimeZone;
		}

		$tzlist = DateTimeZone::listIdentifiers();

		foreach ($tzlist as $tzid)
		{
			$timezone = new DateTimeZone($tzid);
			if (self::matchTimezone($transitions, $timezone))
			{
				return $timezone;
			}
		}

		return null;
	}

	public static function matchTimezone($transitions, $timezone)
	{
		$transitionsTss = $transitions->ts;

		$referenceTransitions = self::getTransitions($timezone, min($transitionsTss), max($transitionsTss) + 1);
		$referenceTss = $referenceTransitions->ts;

		$matchingReferenceTransitions = array_intersect($referenceTss, $transitionsTss);

		if (count($matchingReferenceTransitions) == count($transitionsTss))
		{
			asort($transitionsTss, SORT_NUMERIC);
			asort($matchingReferenceTransitions, SORT_NUMERIC);

			foreach ($matchingReferenceTransitions as $refKey => $refTs)
			{
				$refOffset = $referenceTransitions[$refKey]['offset'];
				$matchOffset = $transitions[key($transitionsTss)]['offset'];

				if ($refOffset != $matchOffset)
				{
					return FALSE;
				}
				next($transitionsTss);
			}

			return TRUE;
		}

		return FALSE;
	}

	public static function getTransitions($tzid, $from, $until)
	{
		$timezone = $tzid instanceof DateTimeZone ? $tzid : new DateTimeZone($tzid);
		$beginTS = $from instanceof DateTime ? $from->getTimestamp() : $from;
		$endTS = $until instanceof DateTime ? $until->getTimestamp() : $until;

		// NOTE: DateTimeZone::getTransitions first "transition" reflects $beginTS
		//	   so we make sure to not match a transition with it and throw it away
		$transitions = $endTS ? $timezone->getTransitions(--$beginTS, $endTS) : $timezone->getTransitions(--$beginTS);
		array_shift($transitions);

		$transitions = new TZConvSet($transitions);

		return $transitions;
	}
}

class TZConvVTimeZoneRule extends TZConvModel
{
	const WDAY_SUNDAY	= 'SU';
	const WDAY_MONDAY	= 'MO';
	const WDAY_TUESDAY   = 'TU';
	const WDAY_WEDNESDAY = 'WE';
	const WDAY_THURSDAY  = 'TH';
	const WDAY_FRIDAY	= 'FR';
	const WDAY_SATURDAY  = 'SA';

	static $WEEKDAY_DIGIT_MAP = array(
		self::WDAY_SUNDAY	 => 0,
		self::WDAY_MONDAY	 => 1,
		self::WDAY_TUESDAY	=> 2,
		self::WDAY_WEDNESDAY  => 3,
		self::WDAY_THURSDAY   => 4,
		self::WDAY_FRIDAY	 => 5,
		self::WDAY_SATURDAY   => 6
	);

	public $wkday = null;
	public $numwk = null;
	public $month = null;
	public $until = null;

	protected static $cache = array();

	public static function createFromString($rruleString)
	{
		if (!array_key_exists($rruleString, self::$cache))
		{
			$rrule = new self();

			$parts = explode(';', $rruleString);
			foreach ($parts as $part)
			{
				list($key, $value) = explode('=', $part);
				switch (strtolower($key))
				{
					case 'bymonth':
						$rrule->month = (int) $value;
						if (!$rrule->month)
						{
							throw new \Exception('invalid BYDAY month');
						}
						break;
					case 'byday':
						$icsWkDay = substr($value, -2);
						if (!array_key_exists($icsWkDay, self::$WEEKDAY_DIGIT_MAP))
						{
							throw new \Exception('invalid BYDAY wkday');
						}
						$rrule->wkday = self::$WEEKDAY_DIGIT_MAP[$icsWkDay];
						$rrule->numwk = (int) substr($value, 0, -2);
						if (!$rrule->numwk)
						{
							throw new \Exception('invalid BYDAY numwk');
						}
						break;
					case 'until':
						$rrule->until = new DateTime($value);
						$rrule->until->setTimezone(new DateTimeZone('UTC'));
						break;
				}
			}
			self::$cache[$rruleString] = $rrule;
		}

		return clone self::$cache[$rruleString];
	}

	public static function createFromTransitionRule(TZConvTransitionRule $transitionRule)
	{
		if (!$transitionRule->isRecurringRule())
		{
			throw new \Exception('transition rule does not describe a rrule');
		}

		$rrule = new self(array(
			'wkday' => array_search($transitionRule->wkday, self::$WEEKDAY_DIGIT_MAP),
			'numwk' => $transitionRule->numwk,
			'month' => $transitionRule->month,
			'until' => $transitionRule->until,
		));

		return $rrule;
	}

	public function __toString()
	{
		$rruleString = "FREQ=YEARLY;BYMONTH={$this->month};BYDAY={$this->numwk}{$this->wkday}";

		if ($this->until)
		{
			$rruleString .= ";UNTIL={$this->until->format('Ymd\THis\Z')}";
		}

		return $rruleString;
	}
}


class TZGen
{
	const EOL = "\r\n";

	public static function toVTimeZone($tzid, $from = NULL, $until = NULL)
	{
		$vTimeZone  = 'BEGIN:VTIMEZONE' . self::EOL;
		$vTimeZone .= 'TZID:' . $tzid . self::EOL;

		$from = $from ?: date_create('now', new DateTimeZone('UTC'));

		$timezone = new DateTimeZone($tzid);

		$transitions = TZConvTransition::getTransitions($timezone, $from, $until);

		if ($transitions->count() > 0)
		{
			$splitedTransitions = array(
			'DAYLIGHT' => $transitions->filter('isdst', TRUE),
			'STANDART' => $transitions->filter('isdst', FALSE),
			);

			foreach ($splitedTransitions as $transitions)
			{
				if (count ($transitions) == 0)
					continue;

				$useRrule = TRUE;
				$transitionRule = TZConvTransitionRule::createFromTransition($transitions->getFirst());
				foreach ($transitions as $transition)
				{
					$expectedTransitionDate = $transitionRule->computeTransitionDate(substr($transition['time'], 0, 4));
					if ($expectedTransitionDate->format(DateTime::ISO8601) != $transition['time'])
					{
						$useRrule = FALSE;
						break;
					}
				}

				if ($useRrule)
				{
					$backTransitions = TZConvTransition::getTransitions($timezone, NULL, $transitionRule->from)
					->filter('isdst', $transitionRule->isdst)
					->sort('time', 'DESC');

					foreach ($backTransitions as $backTransition)
					{
						$expectedTransitionDate = $transitionRule->computeTransitionDate(substr($backTransition['time'], 0, 4));
						if ($expectedTransitionDate->format(DateTime::ISO8601) != $backTransition['time'])
						{
							break;
						}

						$transitionRule->from = $expectedTransitionDate;
					}
				}

				else
				{
					$transitionRule = TZConvTransitionRule::createFromTransition($transitions->getFirst(), FALSE);
					$transitionRule->clearTransitionDates();
					foreach ($transitions as $transition)
					{
					//	$transitionRule->addTransitionDate(new DateTime($transition['time'], new DateTimeZone('UTC')));
					}
				}

				$offsetFromDate = clone $transitionRule->from;
				$offsetFromDate->setTimezone($timezone);
				$offsetFromDate->modify("-1 day");
				$offsetFrom = $offsetFromDate->getOffset();

				$vTimeZone .= self::transitionRuleToVTransitionRule($transitionRule, $offsetFrom);
			}
		}
		else
		{
			$transitions = TZConvTransition::getTransitions($timezone, 1, null);

			if ($transitions->count() > 0)
			{
				$transitionRule = TZConvTransitionRule::createFromTransition($transitions->getLast(), FALSE);
				$transitionRule->clearTransitionDates();

				$offsetFromDate = clone $transitionRule->from;
				$offsetFromDate->setTimezone($timezone);
				$offsetFrom = $offsetFromDate->getOffset();

				$vTimeZone .= self::transitionRuleToVTransitionRule($transitionRule, $offsetFrom);
			}

		}

		$vTimeZone .= 'END:VTIMEZONE' . self::EOL;

		return $vTimeZone;
	}

	public function transitionRuleToVTransitionRule(TZConvTransitionRule $transitionRule, $offsetFrom)
	{
		$zone = $transitionRule->isdst ? 'DAYLIGHT' : 'STANDARD';
		$dtstart = clone $transitionRule->from;

		$offsetFromSign = $offsetFrom >=0 ? '+' : '-';
		$offsetFromString = $offsetFromSign .
		str_pad(floor(abs($offsetFrom)/3600), 2, '0', STR_PAD_LEFT) .
		str_pad((abs($offsetFrom)%3600)/60, 2, '0', STR_PAD_LEFT);

		$offsetToSign = $transitionRule->offset >=0 ? '+' : '-';
		$offsetToString = $offsetToSign .
		str_pad(floor(abs($transitionRule->offset)/3600), 2, '0', STR_PAD_LEFT) .
		str_pad((abs($transitionRule->offset)%3600)/60, 2, '0', STR_PAD_LEFT);

		$dtstart = $dtstart->modify("$offsetFrom seconds");

		$rule = '';
		if ($transitionRule->isRecurringRule())
		{
			$rule = 'RRULE:' . TZConvVTimeZoneRule::createFromTransitionRule($transitionRule);
		}
		else
		{
			$rdates = $transitionRule->getTransitionDates();
			$rdatesArray = array();
			foreach ($rdates as $rdate)
			{
				$rdate = clone $rdate;
				$rdate->modify("$offsetFrom seconds");

				$rdatesArray[] = $rdate->format('Ymd\THis');
			}

			if (count($rdatesArray) > 0)
				$rule = str_replace(' ', '', wordwrap('RDATE;VALUE=DATE-TIME:'. implode(', ', $rdatesArray), 90, self::EOL));
		}

		$vTransitionRule  = "BEGIN:$zone" . self::EOL;
		$vTransitionRule .= "TZOFFSETFROM:$offsetFromString" . self::EOL;
		if ($rule !== "")
			$vTransitionRule .= "$rule" . self::EOL;
		$vTransitionRule .= "DTSTART:{$dtstart->format('Ymd\THis')}" . self::EOL;
		$vTransitionRule .= "TZNAME:{$transitionRule->abbr}" . self::EOL;
		$vTransitionRule .= "TZOFFSETTO:$offsetToString" . self::EOL;
		$vTransitionRule .= "END:$zone" . self::EOL;

		return $vTransitionRule;
	}
}


$timezone_identifiers = DateTimeZone::listIdentifiers();
foreach ($timezone_identifiers as $ttt)
{
	$vvv = TZGen::toVTimeZone($ttt);
	if (strlen($vvv) > 80)
	{
		$tempFile = fopen($_SERVER["DOCUMENT_ROOT"]."/1111111111_1.111", "a");
		fwrite($tempFile, "\t\"".$ttt."\" => \"");
		fwrite($tempFile, $vvv);
		fwrite($tempFile, "\",\n");
		fclose($tempFile);
	}
}