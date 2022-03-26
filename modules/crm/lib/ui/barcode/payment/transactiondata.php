<?php

namespace Bitrix\Crm\UI\Barcode\Payment;

final class TransactionData
{
	/** @var TransactionPartyData */
	private $receiverData;
	/** @var TransactionPartyData */
	private $senderData;

	/** @var float|null */
	private $sum;

	public function __construct(TransactionPartyData $receiverData, ?TransactionPartyData $senderData = null)
	{
		$this->receiverData = $receiverData;
		$this->senderData = $senderData ?? new TransactionPartyData();
	}

	public function getReceiverData(): TransactionPartyData
	{
		return $this->receiverData;
	}

	public function getSenderData(): TransactionPartyData
	{
		return $this->senderData;
	}

	public function getSum(): ?float
	{
		return $this->sum;
	}

	public function setSum(float $sum): self
	{
		$this->sum = $sum;

		return $this;
	}
}
