<?php

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock;

class CIBlockPropertyDate extends CIBlockPropertyDateTime
{
	public const USER_TYPE = 'Date';

	private const INTERNAL_FORMAT = 'YYYY-MM-DD';

	public static function GetUserTypeDescription()
	{
		return [
			"PROPERTY_TYPE" => Iblock\PropertyTable::TYPE_STRING,
			"USER_TYPE" => self::USER_TYPE,
			"DESCRIPTION" => Loc::getMessage("IBLOCK_PROP_DATE_DESC"),
			//optional handlers
			"GetPublicViewHTML" => [__CLASS__, "GetPublicViewHTML"],
			"GetPublicEditHTML" => [__CLASS__, "GetPublicEditHTML"],
			"GetAdminListViewHTML" => [__CLASS__, "GetAdminListViewHTML"],
			"GetPropertyFieldHtml" => [__CLASS__, "GetPropertyFieldHtml"],
			"CheckFields" => [__CLASS__, "CheckFields"],
			"ConvertToDB" => [__CLASS__, "ConvertToDB"],
			"ConvertFromDB" => [__CLASS__, "ConvertFromDB"],
			"GetSettingsHTML" => [__CLASS__, "GetSettingsHTML"],
			"GetAdminFilterHTML" => [__CLASS__, "GetAdminFilterHTML"],
			"GetPublicFilterHTML" => [__CLASS__, "GetPublicFilterHTML"],
			"AddFilterFields" => [__CLASS__, "AddFilterFields"],
			"GetUIFilterProperty" => [__CLASS__, "GetUIFilterProperty"],
			'GetUIEntityEditorProperty' => [__CLASS__, 'GetUIEntityEditorProperty'],
			//"GetORMFields" => array(__CLASS__, "GetORMFields"),
		];
	}

	/**
	 * @param \Bitrix\Main\ORM\Entity $valueEntity
	 * @param Iblock\Property         $property
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function GetORMFields($valueEntity, $property)
	{
		$valueEntity->addField(
			(new \Bitrix\Main\ORM\Fields\DateField('DATE'))
				->configureFormat(parent::FORMAT_SHORT)
				->configureColumnName($valueEntity->getField('VALUE')->getColumnName())
		);
	}

	public static function ConvertToDB($arProperty, $value)
	{
		$dateTimeValue = (string)($value['VALUE'] ?? '');
		if ($dateTimeValue !== '')
		{
			if (!static::checkInternalFormatValue($dateTimeValue))
			{
				$value['VALUE'] = CDatabase::FormatDate(
					$dateTimeValue,
					CLang::GetDateFormat('SHORT'),
					self::INTERNAL_FORMAT
				);
			}
			else
			{
				$value['VALUE'] = $dateTimeValue;
			}
		}

		return $value;
	}

	public static function ConvertFromDB($arProperty, $value, $format = '')
	{
		$dateTimeValue = (string)($value['VALUE'] ?? '');
		if ($dateTimeValue !== '')
		{
			$value['VALUE'] = CDatabase::FormatDate(
				$dateTimeValue,
				self::INTERNAL_FORMAT,
				CLang::GetDateFormat('SHORT')
			);
		}

		return $value;
	}

	public static function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
	{
		/** @var CMain $APPLICATION*/
		global $APPLICATION;

		$s = '<input type="text" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'" size="25" value="'.htmlspecialcharsbx($value["VALUE"]).'" />';
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:main.calendar',
			'',
			array(
				'FORM_NAME' => $strHTMLControlName["FORM_NAME"],
				'INPUT_NAME' => $strHTMLControlName["VALUE"],
				'INPUT_VALUE' => $value["VALUE"],
				'SHOW_TIME' => "N",
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
		$s .= ob_get_contents();
		ob_end_clean();
		return  $s;
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		return  CAdminCalendar::CalendarDate($strHTMLControlName["VALUE"], $value["VALUE"], 20, false).
		($arProperty["WITH_DESCRIPTION"]=="Y" && '' != trim($strHTMLControlName["DESCRIPTION"]) ?
			'&nbsp;<input type="text" size="20" name="'.$strHTMLControlName["DESCRIPTION"].'" value="'.htmlspecialcharsbx($value["DESCRIPTION"]).'">'
			:''
		);
	}

	/**
	 * @param array $property
	 * @param array $control
	 * @param array &$fields
	 * @return void
	 */
	public static function GetUIFilterProperty($property, $control, &$fields)
	{
		parent::GetUIFilterProperty($property, $control, $fields);
		unset($fields["time"]);
	}

	/**
	 * @param $settings
	 * @param $value
	 *
	 * @return array
	 */
	public static function GetUIEntityEditorProperty($settings, $value)
	{
		$culture = Context::getCurrent()->getCulture();

		$dateTimeResult = parent::GetUIEntityEditorProperty($settings, $value);
		$dateTimeResult['data'] = [
			'enableTime' => false,
			'dateViewFormat' =>  $culture->getLongDateFormat(),
		];

		return $dateTimeResult;
	}

	protected static function checkInternalFormatValue(string $value): bool
	{
		if ($value === '')
		{
			return false;
		}

		$correctValue = date_parse_from_format(parent::FORMAT_SHORT, $value);

		return ($correctValue['warning_count'] === 0 && $correctValue['error_count'] === 0);
	}
}
