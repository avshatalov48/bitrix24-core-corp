<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base;

use Bitrix\Main\Grid\Row\Assembler\Field\StringFieldAssembler;
use Bitrix\Main\Grid\Settings;

class TrimmedTextFieldAssembler extends StringFieldAssembler
{
	private int $maxLength;

	public function __construct(array $columnIds, ?Settings $settings = null, ?int $maxLength = 300)
	{
		parent::__construct($columnIds, $settings);
		$this->maxLength = $maxLength;
	}

	protected function prepareColumn($value): ?string
	{
		$value = (string)$value;
		if (!$value)
		{
			return null;
		}

		$trimmedValue = htmlspecialcharsbx(mb_substr($value, 0, $this->maxLength));
		$needTrim = mb_strlen($value) > $this->maxLength;
		$titleValue = htmlspecialcharsbx($value);

		return "<span title=\"$titleValue\">" . $trimmedValue . ($needTrim ? '...</span>' : '</span>');
	}
}
