<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\ScalarField;

class MssqlSqlHelper extends SqlHelper
{
	/**
	 * Returns an identificator escaping left character.
	 *
	 * @return string
	 */
	public function getLeftQuote()
	{
		return '[';
	}

	/**
	 * Returns an identificator escaping right character.
	 *
	 * @return string
	 */
	public function getRightQuote()
	{
		return ']';
	}

	/**
	 * Returns maximum length of an alias in a select statement
	 *
	 * @return integer
	 */
	public function getAliasLength()
	{
		return 28;
	}

	/**
	 * Returns database specific query delimiter for batch processing.
	 *
	 * @return string
	 */
	public function getQueryDelimiter()
	{
		return "\nGO";
	}

	/**
	 * Escapes special characters in a string for use in an SQL statement.
	 *
	 * @param string $value Value to be escaped.
	 * @param integer $maxLength Limits string length if set.
	 *
	 * @return string
	 */
	function forSql($value, $maxLength = 0)
	{
		if ($maxLength > 0)
		{
			$value = substr($value, 0, $maxLength);
		}
		$value = str_replace("'", "''", $value);
		$value = str_replace("\x00", "", $value);
		return $value;
	}

	/**
	 * Returns function for getting current time.
	 *
	 * @return string
	 */
	public function getCurrentDateTimeFunction()
	{
		return "GETDATE()";
	}

	/**
	 * Returns function for getting current date without time part.
	 *
	 * @return string
	 */
	public function getCurrentDateFunction()
	{
		return "convert(datetime, cast(year(getdate()) as varchar(4)) + '-' + cast(month(getdate()) as varchar(2)) + '-' + cast(day(getdate()) as varchar(2)), 120)";
	}

	/**
	 * Returns function for adding seconds time interval to $from.
	 * <p>
	 * If $from is null or omitted, then current time is used.
	 * <p>
	 * $seconds and $from parameters are SQL unsafe.
	 *
	 * @param integer $seconds How many seconds to add.
	 * @param integer $from Datetime database field of expression.
	 *
	 * @return string
	 */
	public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return 'DATEADD(second, '.$seconds.', '.$from.')';
	}

	/**
	 * Returns function cast $value to datetime database type.
	 * <p>
	 * $value parameter is SQL unsafe.
	 *
	 * @param string $value Database field or expression to cast.
	 *
	 * @return string
	 */
	public function getDatetimeToDateFunction($value)
	{
		return 'DATEADD(dd, DATEDIFF(dd, 0, '.$value.'), 0)';
	}

	/**
	 * Returns database expression for converting $field value according the $format.
	 * <p>
	 * Following format parts converted:
	 * - YYYY   A full numeric representation of a year, 4 digits
	 * - MMMM   A full textual representation of a month, such as January or March
	 * - MM     Numeric representation of a month, with leading zeros
	 * - MI     Minutes with leading zeros
	 * - M      A short textual representation of a month, three letters
	 * - DD     Day of the month, 2 digits with leading zeros
	 * - HH     24-hour format of an hour with leading zeros
	 * - H      24-hour format of an hour without leading zeros
	 * - GG     12-hour format of an hour with leading zeros
	 * - G      12-hour format of an hour without leading zeros
	 * - SS     Seconds with leading zeros
	 * - TT     AM or PM
	 * - T      AM or PM
	 * <p>
	 * $field parameter is SQL unsafe.
	 *
	 * @param string $format Format string.
	 * @param string $field Database field or expression.
	 *
	 * @return string
	 */
	public function formatDate($format, $field = null)
	{
		if ($field === null)
		{
			return '';
		}

		$result = array();

		foreach (preg_split("#(YYYY|MMMM|MM|MI|M|DD|HH|H|GG|G|SS|TT|T)#", $format, -1, PREG_SPLIT_DELIM_CAPTURE) as $part)
		{
			switch ($part)
			{
				case "YYYY":
					$result[] = "\n\tCONVERT(varchar(4),DATEPART(yyyy, $field))";
					break;
				case "MMMM":
					$result[] = "\n\tdatename(mm, $field)";
					break;
				case "MM":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(mm, $field)))+CONVERT(varchar(2),DATEPART(mm, $field))";
					break;
				case "MI":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(mi, $field)))+CONVERT(varchar(2),DATEPART(mi, $field))";
					break;
				case "M":
					$result[] = "\n\tCONVERT(varchar(3), $field,7)";
					break;
				case "DD":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(dd, $field)))+CONVERT(varchar(2),DATEPART(dd, $field))";
					break;
				case "HH":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(hh, $field)))+CONVERT(varchar(2),DATEPART(hh, $field))";
					break;
				case "H":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 13 THEN RIGHT(REPLICATE('0',2) + CAST(datepart(HH, $field) AS VARCHAR(2)),2) ELSE RIGHT(REPLICATE('0',2) + CAST(datepart(HH, dateadd(HH, -12, $field)) AS VARCHAR(2)), 2) END";
					break;
				case "GG":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(hh, $field)))+CONVERT(varchar(2),DATEPART(hh, $field))";
					break;
				case "G":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 13 THEN RIGHT(REPLICATE('0',2) + CAST(datepart(HH, $field) AS VARCHAR(2)),2) ELSE RIGHT(REPLICATE('0',2) + CAST(datepart(HH, dateadd(HH, -12, $field)) AS VARCHAR(2)), 2) END";
					break;
				case "SS":
					$result[] = "\n\tREPLICATE('0',2-LEN(DATEPART(ss, $field)))+CONVERT(varchar(2),DATEPART(ss, $field))";
					break;
				case "TT":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 12 THEN 'AM' ELSE 'PM' END";
					break;
				case "T":
					$result[] = "\n\tCASE WHEN DATEPART(HH, $field) < 12 THEN 'AM' ELSE 'PM' END";
					break;
				default:
					$result[] = "'".$part."'";
					break;
			}
		}

		return implode("+", $result);
	}

	/**
	 * Returns function for getting part of string.
	 * <p>
	 * If length is null or omitted, the substring starting
	 * from start until the end of the string will be returned.
	 * <p>
	 * $str and $from parameters are SQL unsafe.
	 *
	 * @param string $str Database field or expression.
	 * @param integer $from Start position.
	 * @param integer $length Maximum length.
	 *
	 * @return string
	 */
	public function getSubstrFunction($str, $from, $length = null)
	{
		$sql = 'SUBSTRING('.$str.', '.$from;

		if (!is_null($length))
			$sql .= ', '.$length;
		else
			$sql .= ', LEN('.$str.') + 1 - '.$from;

		return $sql.')';
	}

	/**
	 * Returns function for concatenating database fields or expressions.
	 * <p>
	 * All parameters are SQL unsafe.
	 *
	 * @param string $field,... Database fields or expressions.
	 *
	 * @return string
	 */
	public function getConcatFunction()
	{
		return implode(" + ", func_get_args());
	}

	/**
	 * Returns function for testing database field or expressions
	 * against NULL value. When it is NULL then $result will be returned.
	 * <p>
	 * All parameters are SQL unsafe.
	 *
	 * @param string $expression Database field or expression for NULL test.
	 * @param string $result Database field or expression to return when $expression is NULL.
	 *
	 * @return string
	 */
	public function getIsNullFunction($expression, $result)
	{
		return "ISNULL(".$expression.", ".$result.")";
	}

	/**
	 * Returns function for getting length of database field or expression.
	 * <p>
	 * $field parameter is SQL unsafe.
	 *
	 * @param string $field Database field or expression.
	 *
	 * @return string
	 */
	public function getLengthFunction($field)
	{
		return "LEN(".$field.")";
	}

	/**
	 * Returns function for converting string value into datetime.
	 * $value must be in YYYY-MM-DD HH:MI:SS format.
	 * <p>
	 * $value parameter is SQL unsafe.
	 *
	 * @param string $value String in YYYY-MM-DD HH:MI:SS format.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\MssqlSqlHelper::formatDate
	 */
	public function getCharToDateFunction($value)
	{
		return "CONVERT(datetime, '".$value."', 120)";
	}

	/**
	 * Returns function for converting database field or expression into string.
	 * <p>
	 * Result string will be in YYYY-MM-DD HH:MI:SS format.
	 * <p>
	 * $fieldName parameter is SQL unsafe.
	 *
	 * @param string $fieldName Database field or expression.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\MssqlSqlHelper::formatDate
	 */
	public function getDateToCharFunction($fieldName)
	{
		return "CONVERT(varchar(19), ".$fieldName.", 120)";
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $fieldName
	 *
	 * return string
	 */
	public function castToChar($fieldName)
	{
		return 'CAST('.$fieldName.' AS varchar)';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $fieldName
	 *
	 * return string
	 */
	public function softCastTextToChar($fieldName)
	{
		return 'CONVERT(VARCHAR(8000), '.$fieldName.')';
	}

	/**
	 * Returns callback to be called for a field value on fetch.
	 * Used for soft conversion. For strict results @see ORM\Query\Result::setStrictValueConverters()
	 *
	 * @param ScalarField $field Type "source".
	 *
	 * @return false|callback
	 */
	public function getConverter(ScalarField $field)
	{
		if ($field instanceof ORM\Fields\DatetimeField)
		{
			return array($this, "convertFromDbDateTime");
		}
		elseif ($field instanceof ORM\Fields\DateField)
		{
			return array($this, "convertFromDbDate");
		}
		elseif ($field instanceof ORM\Fields\StringField)
		{
			return array($this, "convertFromDbString");
		}
		else
		{
			return parent::getConverter($field);
		}
	}

	/**
	 * @deprecated
	 * Converts string into \Bitrix\Main\Type\DateTime object.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\MssqlSqlHelper::getConverter
	 */
	public function convertDatetimeField($value)
	{
		return $this->convertFromDbDateTime($value);
	}

	/**
	 * @param $value
	 *
	 * @return Type\DateTime
	 * @throws Main\ObjectException
	 */
	public function convertFromDbDateTime($value)
	{
		if ($value !== null)
		{
			$value = new Type\DateTime(substr($value, 0, 19), "Y-m-d H:i:s");
		}

		return $value;
	}

	/**
	 * @deprecated
	 * Converts string into \Bitrix\Main\Type\Date object.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return \Bitrix\Main\Type\Date
	 * @see \Bitrix\Main\Db\MssqlSqlHelper::getConverter
	 */
	public function convertDateField($value)
	{
		return $this->convertFromDbDate($value);
	}

	/**
	 * @param $value
	 *
	 * @return Type\Date
	 * @throws Main\ObjectException
	 */
	public function convertFromDbDate($value)
	{
		if($value !== null)
		{
			$value = new Type\Date($value, "Y-m-d");
		}

		return $value;
	}

	/**
	 * @deprecated
	 * Converts string into \Bitrix\Main\Type\Date object if string has datetime specific format..
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\MssqlSqlHelper::getConverter
	 */
	public function convertStringField($value)
	{
		return $this->convertFromDbString($value);
	}

	/**
	 * @param string $value
	 * @param null   $length
	 *
	 * @return Type\DateTime|string
	 * @throws Main\ObjectException
	 */
	public function convertFromDbString($value, $length = null)
	{
		if ($value !== null)
		{
			if(preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\$#", $value))
			{
				return new Type\DateTime($value, "Y-m-d H:i:s");
			}
		}

		return parent::convertFromDbString($value, $length);
	}

	/**
	 * Returns a column type according to ScalarField object.
	 *
	 * @param ScalarField $field Type "source".
	 *
	 * @return string
	 */
	public function getColumnTypeByField(ScalarField $field)
	{
		if ($field instanceof ORM\Fields\IntegerField)
		{
			return 'int';
		}
		elseif ($field instanceof ORM\Fields\FloatField)
		{
			return 'float';
		}
		elseif ($field instanceof ORM\Fields\DatetimeField)
		{
			return 'datetime';
		}
		elseif ($field instanceof ORM\Fields\DateField)
		{
			return 'date';
		}
		elseif ($field instanceof ORM\Fields\TextField)
		{
			return 'text';
		}
		elseif ($field instanceof ORM\Fields\BooleanField)
		{
			$values = $field->getValues();

			if (preg_match('/^[0-9]+$/', $values[0]) && preg_match('/^[0-9]+$/', $values[1]))
			{
				return 'int';
			}
			else
			{
				return 'varchar('.max(strlen($values[0]), strlen($values[1])).')';
			}
		}
		elseif ($field instanceof ORM\Fields\EnumField)
		{
			return 'varchar('.max(array_map('strlen', $field->getValues())).')';
		}
		else
		{
			// string by default
			$defaultLength = false;
			foreach ($field->getValidators() as $validator)
			{
				if ($validator instanceof ORM\Fields\Validators\LengthValidator)
				{
					if ($defaultLength === false || $defaultLength > $validator->getMax())
					{
						$defaultLength = $validator->getMax();
					}
				}
			}
			return 'varchar('.($defaultLength > 0? $defaultLength: 255).')';
		}
	}

	/**
	 * Returns instance of a descendant from Entity\ScalarField
	 * that matches database type.
	 *
	 * @param string $name Database column name.
	 * @param mixed $type Database specific type.
	 * @param array $parameters Additional information.
	 *
	 * @return ScalarField
	 */
	public function getFieldByColumnType($name, $type, array $parameters = null)
	{
		switch($type)
		{
			case 4:
			case 5:
			case -6:
				//int SQL_INTEGER (4)
				//smallint SQL_SMALLINT (5)
				//tinyint SQL_TINYINT (-6)
				return new ORM\Fields\IntegerField($name);

			case 2:
			case 3:
			case 6:
			case 7:
				//numeric SQL_NUMERIC (2)
				//decimal SQL_DECIMAL (3)
				//smallmoney SQL_DECIMAL (3)
				//money SQL_DECIMAL (3)
				//float SQL_FLOAT (6)
				//real SQL_REAL (7)
				return new ORM\Fields\FloatField($name, array("scale" => $parameters["scale"]));

			case 93:
				//datetime - SQL_TYPE_TIMESTAMP (93)
				//datetime2 - SQL_TYPE_TIMESTAMP (93)
				//smalldatetime - SQL_TYPE_TIMESTAMP (93)
				return new ORM\Fields\DatetimeField($name);

			case 91:
				//date - SQL_TYPE_DATE (91)
				return new ORM\Fields\DateField($name);
		}
		//bigint SQL_BIGINT (-5)
		//binary SQL_BINARY (-2)
		//bit SQL_BIT (-7)
		//char SQL_CHAR (1)
		//datetimeoffset SQL_SS_TIMESTAMPOFFSET (-155)
		//image SQL_LONGVARBINARY (-4)
		//nchar SQL_WCHAR (-8)
		//ntext SQL_WLONGVARCHAR (-10)
		//nvarchar SQL_WVARCHAR (-9)
		//text SQL_LONGVARCHAR (-1)
		//time SQL_SS_TIME2 (-154)
		//timestamp SQL_BINARY (-2)
		//udt SQL_SS_UDT (-151)
		//uniqueidentifier SQL_GUID (-11)
		//varbinary SQL_VARBINARY (-3)
		//varchar SQL_VARCHAR (12)
		//xml SQL_SS_XML (-152)
		return new ORM\Fields\StringField($name, array("size" => $parameters["size"]));
	}

	/**
	 * Transforms Sql according to $limit and $offset limitations.
	 * <p>
	 * You must specify $limit when $offset is set.
	 *
	 * @param string $sql Sql text.
	 * @param integer $limit Maximum number of rows to return.
	 * @param integer $offset Offset of the first row to return, starting from 0.
	 *
	 * @return string
	 * @throws Main\ArgumentException
	 */
	public function getTopSql($sql, $limit, $offset = 0)
	{
		$offset = intval($offset);
		$limit = intval($limit);

		if ($offset > 0 && $limit <= 0)
			throw new Main\ArgumentException("Limit must be set if offset is set");

		if ($limit > 0)
		{
			if ($offset <= 0)
			{
				$sql = preg_replace("/^\\s*SELECT/i", "SELECT TOP ".$limit, $sql);
			}
			else
			{
				$orderBy = '';
				$sqlTmp = $sql;

				preg_match_all("#\\sorder\\s+by\\s#i", $sql, $matches, PREG_OFFSET_CAPTURE);
				if (isset($matches[0]) && is_array($matches[0]) && count($matches[0]) > 0)
				{
					$idx = $matches[0][count($matches[0]) - 1][1];
					$s = substr($sql, $idx);
					if (substr_count($s, '(') === substr_count($s, ')'))
					{
						$orderBy = $s;
						$sqlTmp = substr($sql, 0, $idx);
					}
				}

				if ($orderBy === '')
				{
					$orderBy = "ORDER BY (SELECT 1)";
					$sqlTmp = $sql;
				}

				// ROW_NUMBER() Returns the sequential number of a row within a partition of a result set, starting at 1 for the first row in each partition.
				$sqlTmp = preg_replace(
					"/^\\s*SELECT/i",
					"SELECT ROW_NUMBER() OVER (".$orderBy.") AS ROW_NUMBER_ALIAS,",
					$sqlTmp
				);

				$sql =
					"WITH ROW_NUMBER_QUERY_ALIAS AS (".$sqlTmp.") ".
					"SELECT * ".
					"FROM ROW_NUMBER_QUERY_ALIAS ".
					"WHERE ROW_NUMBER_ALIAS BETWEEN ".($offset + 1)." AND ".($offset + $limit);
			}
		}
		return $sql;
	}

	/**
	 * Builds the strings for the SQL MERGE command for the given table.
	 *
	 * @param string $tableName A table name.
	 * @param array $primaryFields Array("column")[] Primary key columns list.
	 * @param array $insertFields Array("column" => $value)[] What to insert.
	 * @param array $updateFields Array("column" => $value)[] How to update.
	 *
	 * @return array (merge)
	 */
	public function prepareMerge($tableName, array $primaryFields, array $insertFields, array $updateFields)
	{
		$insert = $this->prepareInsert($tableName, $insertFields);

		$updateColumns = array();
		$sourceSelectValues = array();
		$sourceSelectColumns = array();
		$targetConnectColumns = array();
		$tableFields = $this->connection->getTableFields($tableName);
		foreach($tableFields as $columnName => $tableField)
		{
			$quotedName = $this->quote($columnName);
			if (in_array($columnName, $primaryFields))
			{
				$sourceSelectValues[] = $this->convertToDb($insertFields[$columnName], $tableField);
				$sourceSelectColumns[] = $quotedName;
				if($insertFields[$columnName] === null)
				{
					//can't just compare NULLs
					$targetConnectColumns[] = "(source.".$quotedName." IS NULL AND target.".$quotedName." IS NULL)";
				}
				else
				{
					$targetConnectColumns[] = "(source.".$quotedName." = target.".$quotedName.")";
				}
			}

			if (isset($updateFields[$columnName]) || array_key_exists($columnName, $updateFields))
			{
				$updateColumns[] = "target.".$quotedName.' = '.$this->convertToDb($updateFields[$columnName], $tableField);
			}
		}

		if (
			$insert && $insert[0] != "" && $insert[1] != ""
			&& $updateColumns
			&& $sourceSelectValues && $sourceSelectColumns && $targetConnectColumns
		)
		{
			$sql = "
				MERGE INTO ".$this->quote($tableName)." AS target USING (
					SELECT ".implode(", ", $sourceSelectValues)."
				) AS source (
					".implode(", ", $sourceSelectColumns)."
				)
				ON
				(
					".implode(" AND ", $targetConnectColumns)."
				)
				WHEN MATCHED THEN
					UPDATE SET ".implode(", ", $updateColumns)."
				WHEN NOT MATCHED THEN
					INSERT (".$insert[0].")
					VALUES (".$insert[1].")
				;
			";
		}
		else
		{
			$sql = "";
		}

		return array(
			$sql
		);
	}
}
