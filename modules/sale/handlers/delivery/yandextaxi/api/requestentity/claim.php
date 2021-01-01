<?php

namespace Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity;

/**
 * Class Claim
 * @package Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity
 * @internal
 */
final class Claim extends RequestEntity
{
	/** @var Contact */
	protected $emergencyContact;

	/** @var ShippingItem[] */
	protected $items = [];

	/** @var RoutePoints */
	protected $routePoints;

	/** @var string */
	protected $id;

	/** @var string */
	protected $corpClientId;

	/** @var string */
	protected $status;

	/** @var int */
	protected $version;

	/** @var Pricing */
	protected $pricing;

	/** @var bool */
	protected $skipClientNotify;

	/** @var bool */
	protected $skipEmergencyNotify;

	/** @var bool */
	protected $skipDoorToDoor;

	/** @var bool */
	protected $optionalReturn;

	/** @var string */
	protected $comment;

	/** @var string */
	protected $availableCancelState;

	/** @var TransportClassification */
	protected $clientRequirements;

	/** @var TransportClassification[] */
	protected $matchedCars = [];

	/** @var PerformerInfo */
	protected $performerInfo;

	/** @var ErrorMessage[] */
	protected $errorMessages = [];

	/** @var Warning[] */
	protected $warnings = [];

	/** @var string */
	protected $createdTs;

	/** @var string */
	protected $updatedTs;

	/** @var string */
	protected $referralSource;

	/**
	 * @return Contact
	 */
	public function getEmergencyContact()
	{
		return $this->emergencyContact;
	}

	/**
	 * @param Contact $emergencyContact
	 * @return Claim
	 */
	public function setEmergencyContact(Contact $emergencyContact): Claim
	{
		$this->emergencyContact = $emergencyContact;

		return $this;
	}

	/**
	 * @return ShippingItem[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}

	/**
	 * @param ShippingItem $shippingItem
	 * @return Claim
	 */
	public function addItem(ShippingItem $shippingItem): Claim
	{
		$this->items[] = $shippingItem;

		return $this;
	}

	/**
	 * @return RoutePoints
	 */
	public function getRoutePoints(): RoutePoints
	{
		return $this->routePoints;
	}

	/**
	 * @param RoutePoints $routePoints
	 * @return Claim
	 */
	public function setRoutePoints(RoutePoints $routePoints): Claim
	{
		$this->routePoints = $routePoints;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 * @return Claim
	 */
	public function setId(string $id): Claim
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCorpClientId()
	{
		return $this->corpClientId;
	}

	/**
	 * @param string $corpClientId
	 * @return Claim
	 */
	public function setCorpClientId(string $corpClientId): Claim
	{
		$this->corpClientId = $corpClientId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param string $status
	 * @return Claim
	 */
	public function setStatus(string $status): Claim
	{
		$this->status = $status;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param int $version
	 * @return Claim
	 */
	public function setVersion(int $version): Claim
	{
		$this->version = $version;

		return $this;
	}

	/**
	 * @return Pricing
	 */
	public function getPricing()
	{
		return $this->pricing;
	}

	/**
	 * @param Pricing $pricing
	 * @return Claim
	 */
	public function setPricing(Pricing $pricing): Claim
	{
		$this->pricing = $pricing;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkipClientNotify()
	{
		return $this->skipClientNotify;
	}

	/**
	 * @param bool $skipClientNotify
	 * @return Claim
	 */
	public function setSkipClientNotify(bool $skipClientNotify): Claim
	{
		$this->skipClientNotify = $skipClientNotify;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkipEmergencyNotify()
	{
		return $this->skipEmergencyNotify;
	}

	/**
	 * @param bool $skipEmergencyNotify
	 * @return Claim
	 */
	public function setSkipEmergencyNotify(bool $skipEmergencyNotify): Claim
	{
		$this->skipEmergencyNotify = $skipEmergencyNotify;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkipDoorToDoor()
	{
		return $this->skipDoorToDoor;
	}

	/**
	 * @param bool $skipDoorToDoor
	 * @return Claim
	 */
	public function setSkipDoorToDoor(bool $skipDoorToDoor): Claim
	{
		$this->skipDoorToDoor = $skipDoorToDoor;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isOptionalReturn()
	{
		return $this->optionalReturn;
	}

	/**
	 * @param bool $optionalReturn
	 * @return Claim
	 */
	public function setOptionalReturn(bool $optionalReturn): Claim
	{
		$this->optionalReturn = $optionalReturn;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getComment()
	{
		return $this->comment;
	}

	/**
	 * @param string $comment
	 * @return Claim
	 */
	public function setComment(string $comment): Claim
	{
		$this->comment = $comment;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAvailableCancelState()
	{
		return $this->availableCancelState;
	}

	/**
	 * @param string $availableCancelState
	 * @return Claim
	 */
	public function setAvailableCancelState(string $availableCancelState): Claim
	{
		$this->availableCancelState = $availableCancelState;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCreatedTs()
	{
		return $this->createdTs;
	}

	/**
	 * @param string $createdTs
	 * @return Claim
	 */
	public function setCreatedTs(string $createdTs): Claim
	{
		$this->createdTs = $createdTs;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUpdatedTs()
	{
		return $this->updatedTs;
	}

	/**
	 * @param string $updatedTs
	 * @return Claim
	 */
	public function setUpdatedTs(string $updatedTs): Claim
	{
		$this->updatedTs = $updatedTs;

		return $this;
	}

	/**
	 * @return TransportClassification
	 */
	public function getClientRequirements()
	{
		return $this->clientRequirements;
	}

	/**
	 * @param TransportClassification $clientRequirements
	 * @return Claim
	 */
	public function setClientRequirements(TransportClassification $clientRequirements): Claim
	{
		$this->clientRequirements = $clientRequirements;

		return $this;
	}

	/**
	 * @return TransportClassification[]
	 */
	public function getMatchedCars(): array
	{
		return $this->matchedCars;
	}

	/**
	 * @param TransportClassification $transportClassification
	 * @return Claim
	 */
	public function addMatchedCar(TransportClassification $transportClassification): Claim
	{
		$this->matchedCars[] = $transportClassification;

		return $this;
	}

	/**
	 * @return PerformerInfo
	 */
	public function getPerformerInfo()
	{
		return $this->performerInfo;
	}

	/**
	 * @param PerformerInfo $performerInfo
	 * @return Claim
	 */
	public function setPerformerInfo(PerformerInfo $performerInfo): Claim
	{
		$this->performerInfo = $performerInfo;

		return $this;
	}

	/**
	 * @return ErrorMessage[]
	 */
	public function getErrorMessages(): array
	{
		return $this->errorMessages;
	}

	/**
	 * @param ErrorMessage $error
	 * @return Claim
	 */
	public function addErrorMessage(ErrorMessage $error): Claim
	{
		$this->errorMessages[] = $error;

		return $this;
	}

	/**
	 * @return Warning[]
	 */
	public function getWarnings(): array
	{
		return $this->warnings;
	}

	/**
	 * @param Warning $warning
	 * @return Claim
	 */
	public function addWarning(Warning $warning): Claim
	{
		$this->warnings[] = $warning;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getReferralSource()
	{
		return $this->referralSource;
	}

	/**
	 * @param string $referralSource
	 * @return Claim
	 */
	public function setReferralSource(string $referralSource): Claim
	{
		$this->referralSource = $referralSource;

		return $this;
	}
}
