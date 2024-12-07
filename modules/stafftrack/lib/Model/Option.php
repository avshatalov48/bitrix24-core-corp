<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Type\Contract\Arrayable;

class Option extends EO_Option implements Arrayable
{
	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'userId' => $this->getUserId(),
			'name' => $this->getName(),
			'value' => $this->getValue(),
		];
	}
}