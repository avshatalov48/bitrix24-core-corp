<?php
namespace Bitrix\Sign\Blank;

class Form
{
	/**
	 * Collected fields to build.
	 * @var array
	 */
	private static $fields = [];

	/**
	 * Sets field to build.
	 * @param int $blankId Blank id.
	 * @param int $memberPart Member part.
	 * @param string $fieldCode Field code.
	 * @return void
	 */
	public static function setField(int $blankId, int $memberPart, string $fieldCode): void
	{
		if (!isset(self::$fields[$blankId]))
		{
			self::$fields[$blankId] = [];
		}

		if (!isset(self::$fields[$blankId][$memberPart]))
		{
			self::$fields[$blankId][$memberPart] = [];
		}

		self::$fields[$blankId][$memberPart][] = $fieldCode;
	}

	/**
	 * Clear fields set.
	 * @return void
	 */
	public static function clearFields(): void
	{
		self::$fields = [];
	}

	/**
	 * Builds form from fields.
	 * @return void
	 */
	public static function buildForm(int $presetId = 0): void
	{
		foreach (self::$fields as $blankId => $memberData)
		{
			foreach ($memberData as $memberPart => $fieldsCodes)
			{
				if ($fieldsCodes)
				{
					\Bitrix\Sign\Integration\CRM\Form::create($blankId, $memberPart, array_unique($fieldsCodes), $presetId);
				}
			}
		}
	}
}
