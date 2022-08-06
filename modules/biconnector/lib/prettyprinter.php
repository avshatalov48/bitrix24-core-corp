<?php
namespace Bitrix\BIConnector;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Diag;

class PrettyPrinter
{
	public static function printQueryResult($query)
	{
		$rows = $query->fetchAll();
		static::printRowsArray($rows);
	}

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
			}
			foreach ($row as $header => $value)
			{
				echo str_pad('+', $ll[$header] + 3, '-');
			}
			echo "+\n";
		}
		echo "\n";
	}

	public static function formatUserFieldAsDate($userField, $value, $format)
	{
		if ($userField['MULTIPLE'] == 'Y')
		{
			$values = unserialize($value, ['allowed_classes' => false]);
			if (is_array($values))
			{
				foreach ($values as &$value)
				{
					$date = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($value));
					$value = $date->format($format);
				}
				unset($value);
				return implode(', ', $values);
			}
			return '';
		}
		elseif ($value)
		{
			$date = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($value));
			return $date->format($format);
		}
		else
		{
			return '';
		}
	}
}
