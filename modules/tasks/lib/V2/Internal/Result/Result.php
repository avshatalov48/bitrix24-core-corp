<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Result;

use Bitrix\Tasks\V2\Internal\Entity\EntityInterface;

class Result extends \Bitrix\Main\Result
{
	public function setId(int|string $id): self
	{
		$this->data['id'] = $id;

		return $this;
	}

	public function getId(): null|int|string
	{
		return $this->data['id'] ?? null;
	}

	public function setObject(EntityInterface $object): self
	{
		$this->data['object'] = $object;
		$this->data['id'] = $object->getId();

		return $this;
	}

	public function getObject(): ?EntityInterface
	{
		return $this->data['object'] ?? null;
	}

	public function getDataByKey(string $key): mixed
	{
		return $this->data[$key] ?? null;
	}
}
