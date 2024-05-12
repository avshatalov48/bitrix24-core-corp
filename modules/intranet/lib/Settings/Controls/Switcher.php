<?php

namespace Bitrix\Intranet\Settings\Controls;

use Bitrix\Intranet\Settings\SettingsPermission;

class Switcher extends Field
{
	const TYPE = 'switcher';

	public function __construct(
		string $id,
		string $name,
		string $label,
		?string $value = null,
		?array $hints = null,
		?SettingsPermission $permission = null,
		bool $isEnable = true,
		?string $helpDesk = null
	)
	{
		parent::__construct($id, $name, $label, self::TYPE, $permission, $isEnable, $value, $hints, $helpDesk);
	}

	public function jsonSerialize(): array
	{
		$result = [
			'id' => $this->getId(),
			'inputName' => $this->getName(),
			'label' => $this->getLabel(),
			'title' => $this->getLabel(),
			'type' => $this->getType(),
			'isEnable' => $this->isEnable(),
			'current' => $this->getValue(),
			'hints' => $this->getHints(),
			'helpDesk' => $this->getHelpDesk(),
			'checked' => $this->getValue() === 'Y',
		];
		if (isset($this->getHints()['on']) && is_string($this->getHints()['on']))
		{
			$result['hintOn'] = $this->getHints()['on'];
		}
		if (isset($this->getHints()['off']) && is_string($this->getHints()['off']))
		{
			$result['hintOff'] = $this->getHints()['off'];
		}
		if (isset($this->getHints()['hintTitle']) && is_string($this->getHints()['hintTitle']))
		{
			$result['hintTitle'] = $this->getHints()['hintTitle'];
		}

		return $result;
	}
}