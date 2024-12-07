<?php

namespace Bitrix\Sign\Document\Result\Entity\Factory;

use Bitrix\Main;

final class SaveNewEntityResult extends Main\Result
{
	public function setId(int $id): self
	{
		return $this->setData([
			'id' => $id,
		]);
	}

	public function getId(): int
	{
		return $this->getData()['id'] ?? 0;
	}
}