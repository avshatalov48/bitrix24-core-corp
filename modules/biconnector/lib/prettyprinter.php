<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Diag;

class PrettyPrinter
{
	public static $allowedUnserializeClassesList = [
		\Bitrix\Main\Type\Date::class,
		\Bitrix\Main\Type\DateTime::class,
		\DateTime::class,
		\DateTimeZone::class,
		\Bitrix\Main\Web\Uri::class
	];

	/**
	 * Fetches all query rows and prints them.
	 *
	 * @param \Bitrix\Main\DB\Result $query Query to fetch rows from.
	 *
	 * @return void
	 */
	public static function printQueryResult($query)
	{
		$rows = $query->fetchAll();
		static::printRowsArray($rows);
	}

	/**
	 * Prints query result rows.
	 *
	 * @param array $rows Query result array.
	 *
	 * @return void
	 */
	public static function printRowsArray($rows)
	{
		if (!$rows)
		{
			echo "No rows.\n";
		}
		elseif (count($rows) == 1)
		{
			$ll = [];
			foreach ($rows[0] as $header => $value)
			{
				$ll[$header] = strlen($header);
			}
			$l = max($ll);
			foreach ($rows[0] as $header => $value)
			{
				echo str_pad($header, $l, ' ') . ': ' . $value . "\n";
			}
		}
		else
		{
			$ll = [];
			foreach ($rows[0] as $header => $value)
			{
				$ll[$header] = strlen($header);
			}
			foreach ($rows as $row)
			{
				foreach ($row as $header => $value)
				{
					$ll[$header] = max($ll[$header], strlen($value));
				}
			}
			$lastRow = count($rows) - 1;
			foreach ($rows as $i => $row)
			{
				if ($i == 0)
				{
					foreach ($row as $header => $value)
					{
						echo str_pad('+', $ll[$header] + 3, '-');
					}
					echo "+\n";
					foreach ($row as $header => $value)
					{
						echo str_pad('| ' . $header, $ll[$header] + 3, ' ');
					}
					echo "|\n";
					foreach ($row as $header => $value)
					{
						echo str_pad('+', $ll[$header] + 3, '-');
					}
					echo "+\n";
				}
				foreach ($row as $header => $value)
				{
					echo str_pad('| ' . $value, $ll[$header] + 3, ' ');
				}
				echo "|\n";
				if ($i == $lastRow)
				{
					foreach ($row as $header => $value)
					{
						echo str_pad('+', $ll[$header] + 3, '-');
					}
					echo "+\n";
				}
			}
		}
		echo "\n";
	}

	/**
	 * Formats database user field value according to the format.
	 *
	 * @param array $userField User field metadata.
	 * @param string|\Bitrix\Main\Type\Date $value User field database value.
	 * @param string $format PHP date format string.
	 *
	 * @return string
	 */
	public static function formatUserFieldAsDate($userField, $value, $format)
	{
		if ($userField['MULTIPLE'] == 'Y')
		{
			if ($value)
			{
				$values = unserialize($value, ['allowed_classes' => static::$allowedUnserializeClassesList]);
				if (is_array($values))
				{
					foreach ($values as $i => &$v)
					{
						if (is_object($v) && is_a($v, '\Bitrix\Main\Type\Date'))
						{
							$v = $v->format($format);
						}
						elseif (is_string($v))
						{
							$date = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($v));
							$v = $date->format($format);
						}
						else
						{
							unset($values[$i]);
						}
					}
					unset($v);

					return implode(', ', $values);
				}
			}
			return '';
		}
		elseif ($value)
		{
			if (is_object($value) && is_a($value, '\Bitrix\Main\Type\Date'))
			{
				return $value->format($format);
			}
			elseif (is_string($value))
			{
				$date = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($value));

				return $date->format($format);
			}
		}

		return '';
	}
}
