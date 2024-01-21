<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Bizproc\FieldType;

class SelectField extends BaseField
{
	public function getType(): string
	{
		return 'select';
	}

	public function getConfig(): array
	{
		$options = [];

		if ($this->fieldTypeObject)
		{
			$property = $this->fieldTypeObject->convertPropertyToView(FieldType::RENDER_MODE_JN_MOBILE);
			if (isset($property['Options']) && is_array($property['Options']))
			{
				$options = $property['Options'];
			}
		}
		elseif (isset($property['Options']) && is_array($property['Options']))
		{
			foreach ($property['Options'] as $key => $value)
			{
				$options[] = ['value' => $key, 'name' => $value];
			}
		}

		return ['items' => $options];
	}

	protected function convertToMobileType($value): mixed
	{
		return $value;
	}

	protected function convertToWebType($value): mixed
	{
		return $value;
	}
}
