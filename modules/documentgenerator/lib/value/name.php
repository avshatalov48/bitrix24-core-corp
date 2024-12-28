<?php
namespace Bitrix\DocumentGenerator\Value;

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Localization\Loc;

class Name extends Value implements Nameable
{
	protected $gender;

	/**
	 * Name constructor.
	 * @param array $value
	 * @param array $options
	 */
	public function __construct(array $value, array $options = null)
	{
		parent::__construct($value, $options);
		if(isset($this->value['GENDER']))
		{
			if($this->value['GENDER'] === 'F')
			{
				$this->gender = \Petrovich::GENDER_FEMALE;
			}
			elseif($this->value['GENDER'] === 'M')
			{
				$this->gender = \Petrovich::GENDER_MALE;
			}
		}
	}

	/**
	 * @param string $modifier
	 * @return string
	 */
	public function toString($modifier = null)
	{
		$options = $this->getOptions($modifier);
		if(isset($options['case']))
		{
			$fields = $this->changeCase($options['case']);
		}
		else
		{
			$fields = $this->value;
		}

		if(!isset($options['format']))
		{
			$options['format'] = $this->getDefaultOptions()['format'];
		}
		$result = \CUser::FormatName(
			$options['format'],
			array(
				'LOGIN' => '',
				'TITLE' => $fields['TITLE'],
				'NAME' => $fields['NAME'],
				'SECOND_NAME' => $fields['SECOND_NAME'],
				'LAST_NAME' => $fields['LAST_NAME'],
			),
			false,
			false
		);

		$emptyName = \CUser::FormatName(
			$options['format'],
			array(
				'LOGIN' => '',
				'TITLE' => '',
				'NAME' => '',
				'SECOND_NAME' => '',
				'LAST_NAME' => '',
			),
			false,
			false
		);

		if($result === $emptyName)
		{
			$result = '';
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected static function getDefaultOptions()
	{
		return [
			'format' => DataProviderManager::getInstance()->getCulture()->getNameFormat(),
		];
	}

	/**
	 * @param $case
	 * @return mixed
	 */
	protected function changeCase($case)
	{
		$fields = $this->value;
		if(!$this->isFieldsRussian($fields))
		{
			return $fields;
		}
		$gender = $this->getGender();
		if($gender)
		{
			$petrovich = new \Petrovich($gender);
			foreach($this->getLanguageFieldNames() as $name => $method)
			{
				$fields[$name] = $petrovich->$method(trim($fields[$name]), $case);
			}
		}

		return $fields;
	}

	/**
	 * @param array $fields
	 * @return bool
	 */
	protected function isFieldsRussian(array $fields)
	{
		$isFilledOne = false;

		foreach($this->getLanguageFieldNames() as $name => $method)
		{
			if(isset($fields[$name]))
			{
				$isFilledOne = true;
				if(!empty($fields[$name]) && !$this->isFieldRussian($fields[$name]))
				{
					return false;
				}
			}
		}

		return $isFilledOne;
	}

	/**
	 * @param $field
	 * @return bool
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	protected function isFieldRussian($field)
	{
		if(!is_string($field) || empty($field))
		{
			return false;
		}
		$regex = $this->getRussianLettersRegEx(mb_strlen($field));
		if($regex)
		{
			return (preg_match($regex, $field) === 1);
		}

		return false;
	}

	/**
	 * @param int $count
	 * @return bool|string
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	protected function getRussianLettersRegEx($count)
	{
		return "#([ёЁа-яА-Я -]{" . $count . "})$#u";
	}

	protected function getGender()
	{
		if($this->gender === null)
		{
			$this->gender = $this->detectGender();
		}

		return $this->gender;
	}

	/**
	 * @return bool
	 */
	protected function detectGender()
	{
		if(!isset($this->value['SECOND_NAME']) || empty($this->value['SECOND_NAME']))
		{
			return false;
		}

		$gender = \Petrovich::detectGender(trim($this->value['SECOND_NAME']));
		if($gender != \Petrovich::GENDER_ANDROGYNOUS)
		{
			return $gender;
		}

		return false;
	}

	/**
	 * @return array
	 */
	protected function getLanguageFieldNames()
	{
		static $fields;
		if($fields === null)
		{
			$fields = [
				'SECOND_NAME' => 'middlename',
				'NAME' => 'firstname',
				'LAST_NAME' => 'lastname',
				'TITLE' => 'title',
			];
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	protected static function getAliases()
	{
		return [
			'Format' => 'format',
			'Case' => 'case',
		];
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		return Loc::getMessage('DOCGEN_VALUE_NAME_TITLE');
	}
}