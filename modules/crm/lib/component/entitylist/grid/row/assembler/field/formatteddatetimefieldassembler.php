<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Type\DateTime;

class FormattedDateTimeFieldAssembler extends FieldAssembler
{
	/**
	 * @var string|string[]
	 */
	private string|array $format;
	private DateTime $userNow;

	public function __construct(array $columnIds, ?Settings $settings = null, ?string $format = null)
	{
		parent::__construct($columnIds, $settings);

		$this->format = $format ?? \CCrmDateTimeHelper::getDefaultDateTimeFormat();

		$this->userNow = \CCrmDateTimeHelper::getUserTime(new DateTime());
	}

	protected function prepareColumn($value)
	{
		if (!($value instanceof DateTime))
		{
			return '';
		}

		return FormatDate($this->format, \CCrmDateTimeHelper::getUserTime($value), $this->userNow);
	}
}
