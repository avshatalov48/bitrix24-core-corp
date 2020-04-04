<?php

namespace Bitrix\Disk\Bitrix24Disk;


use Bitrix\Main\Security\Sign\Signer;

final class PageState
{
	const SIGNER_SALT = 'pageState';

	const STEP_PERSONAL        = 'p';
	const STEP_SYMLINKS        = 's';
	const STEP_DELETED_OBJECTS = 'd';

	/** @var string */
	protected $step;
	/** @var string */
	protected $nextId;
	protected $cursor;
	/** @var int */
	protected $offset;
	/** @var array */
	protected $dataByStep;

	/**
	 * PageState constructor.
	 * @param string $step Step name.
	 * @param array|null $dataByStep Data associated with step.
	 */
	public function __construct($step, array $dataByStep = null)
	{
		$this->step = $step;
		$this->dataByStep = $dataByStep;
	}

	/**
	 * Creates the PageState from signedData.
	 *
	 * @param string $signedData String which contains signed data.
	 * @return static
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\Security\Sign\BadSignatureException
	 */
	public static function createFromSignedString($signedData)
	{
		$signer = new Signer;
		$signedData = unserialize(base64_decode($signer->unsign($signedData, static::SIGNER_SALT)));

		$pageState = new static($signedData['step'], $signedData['data']);
		$pageState
			->setNextId($signedData['nid'])
			->setCursor($signedData['c'])
			->setOffset($signedData['of'])
		;

		return $pageState;
	}

	/**
	 * Returns string representation (works with Signer).
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function __toString()
	{
		$signer = new Signer;

		return  $signer->sign(base64_encode(serialize(array(
			'step' => $this->step,
			'nid' => $this->nextId,
			'c' => $this->cursor,
			'of' => $this->offset,
			'data' => $this->dataByStep,
		))), static::SIGNER_SALT);
	}

	/**
	 * @return string
	 */
	public function getStep()
	{
		return $this->step;
	}

	/**
	 * @param string $step
	 * @return $this
	 */
	public function setStep($step)
	{
		$this->step = $step;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCursor()
	{
		return $this->cursor;
	}

	/**
	 * @param mixed $cursor
	 *
	 * @return $this
	 */
	public function setCursor($cursor)
	{
		$this->cursor = $cursor;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasCursor()
	{
		return (bool)$this->cursor;
	}

	/**
	 * Returns data.
	 *
	 * @return array
	 */
	public function getDataByStep()
	{
		return $this->dataByStep;
	}

	/**
	 * @param array $dataByStep
	 * @return $this
	 */
	public function setDataByStep(array $dataByStep)
	{
		$this->dataByStep = $dataByStep;

		return $this;
	}

	/**
	 * Returns next id.
	 *
	 * @return string
	 */
	public function getNextId()
	{
		return $this->nextId;
	}

	/**
	 * Tells if the page state has next id.
	 *
	 * @return bool
	 */
	public function hasNextId()
	{
		return (bool)$this->nextId;
	}

	/**
	 * Sets next id of entity which will be expected on the step.
	 * @param string $nextId Next id.
	 * @return $this
	 */
	public function setNextId($nextId)
	{
		$this->nextId = $nextId;

		return $this;
	}

	/**
	 * Returns offset. By default is 0.
	 *
	 * @return int
	 */
	public function getOffset()
	{
		return $this->offset?: 0;
	}

	/**
	 * Sets offset.
	 *
	 * @param int $offset Offset.
	 * @return $this
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Resets state (fields nextId, skipIds and dataByStep).
	 *
	 * @return $this
	 */
	public function reset()
	{
		$this->nextId = null;
		$this->offset = null;
		$this->dataByStep = null;
		$this->cursor = null;

		return $this;
	}

	/**
	 * Tells if the current page state is equal to another.
	 *
	 * @param PageState $pageState Page state for comparison.
	 * @return bool
	 */
	public function isEqual(PageState $pageState)
	{
		return
			$pageState->getStep() === $this->getStep() &&
			$pageState->getDataByStep() === $this->getDataByStep()
		;
	}
}