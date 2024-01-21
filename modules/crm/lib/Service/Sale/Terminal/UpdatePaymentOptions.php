<?php

namespace Bitrix\Crm\Service\Sale\Terminal;

final class UpdatePaymentOptions
{
	private ?int $responsibleId = null;

	public static function createFromArray(array $options): self
	{
		$result = new self();

		if (array_key_exists('responsibleId', $options))
		{
			$result->setResponsibleId((int)$options['responsibleId']);
		}

		return $result;
	}

	public function getResponsibleId(): ?int
	{
		return $this->responsibleId;
	}

	public function setResponsibleId(?int $responsibleId): UpdatePaymentOptions
	{
		$this->responsibleId = $responsibleId;

		return $this;
	}
}
