<?php

namespace Bitrix\Crm\UI\Barcode\Payment;

final class TransactionPartyData
{
	//since some values could be very big (e.g., accountNumber),
	// all numeric values here are stored as strings to prevent overflow

	/** @var string|null */
	private $name;
	/** @var string|null */
	private $inn;
	/** @var string|null */
	private $kpp;
	/** @var string|null */
	private $accountNumber;
	/** @var string|null */
	private $bankName;
	/** @var string|null */
	private $bic;
	/** @var string|null */
	private $corrAccountNumber;

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string|null - taxpayer number
	 */
	public function getInn(): ?string
	{
		return $this->inn;
	}

	/**
	 * @param string $inn - taxpayer number
	 * @return $this
	 */
	public function setInn(string $inn): self
	{
		$this->inn = $inn;

		return $this;
	}

	/**
	 * @return string|null - tax registration reason code
	 */
	public function getKpp(): ?string
	{
		return $this->kpp;
	}

	/**
	 * @param string $kpp - tax registration reason code
	 * @return $this
	 */
	public function setKpp(string $kpp): self
	{
		$this->kpp = $kpp;

		return $this;
	}

	public function getAccountNumber(): ?string
	{
		return $this->accountNumber;
	}

	public function setAccountNumber(string $accountNumber): self
	{
		$this->accountNumber = $accountNumber;

		return $this;
	}

	public function getBankName(): ?string
	{
		return $this->bankName;
	}

	public function setBankName(string $bankName): self
	{
		$this->bankName = $bankName;

		return $this;
	}

	public function getBic(): ?string
	{
		return $this->bic;
	}

	public function setBic(string $bic): self
	{
		$this->bic = $bic;

		return $this;
	}

	public function getCorrAccountNumber(): ?string
	{
		return $this->corrAccountNumber;
	}

	public function setCorrAccountNumber(string $corrAccountNumber): self
	{
		$this->corrAccountNumber = $corrAccountNumber;

		return $this;
	}
}
