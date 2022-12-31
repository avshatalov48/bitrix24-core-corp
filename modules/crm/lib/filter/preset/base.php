<?php

namespace Bitrix\Crm\Filter\Preset;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

abstract class Base
{
	protected const ID_ALL = 'filter_all';
	protected const ID_MY = 'filter_my';
	protected const ID_IN_WORK = 'filter_in_work';
	protected const ID_IN_CLOSED = 'filter_closed';
	protected const ID_ROBOT_DEBUGGER = 'filter_robot_debugger';

	private const DEFAULT_FIELD_VALUE = '';

	/**
	 * Default filter values when reset it.
	 *
	 * @var array
	 */
	protected array $defaultValues = [];

	/**
	 * Current user ID.
	 *
	 * @var int
	 */
	protected int $userId;

	/**
	 * Current name of the user.
	 *
	 * @var string
	 */
	protected string $userName;

	/**
	 * Flag indicates whether stages are allowed for the entity.
	 *
	 * @var bool
	 */
	protected bool $isStagesEnabled = false;

	/** @var int|null */
	protected ?int $categoryId = null;

	/**
	 * Get default presets to entity filter
	 *
	 * @return array
	 */
	abstract public function getDefaultPresets(): array;

	public function __construct()
	{
		// default parameters
		$this->userId = CurrentUser::get()->getId();
		$this->userName = CurrentUser::get()->getFormattedName();

		Loc::loadMessages(__FILE__);
	}

	/**
	 * @param int $userId
	 *
	 * @return $this
	 */
	public function setUserId(int $userId): self
	{
		$this->userId = $userId;

		return $this;
	}

	/**
	 * @param string $userName
	 *
	 * @return $this
	 */
	public function setUserName(string $userName): self
	{
		$this->userName = $userName;

		return $this;
	}

	/**
	 * @param string[] $fields
	 *
	 * @return $this
	 */
	public function setDefaultValues(array $fields): self
	{
		if(!empty($fields))
		{
			$this->defaultValues = array_fill_keys($fields, self::DEFAULT_FIELD_VALUE);
		}

		return $this;
	}

	/**
	 * @param bool $isStagesEnabled
	 *
	 * @return $this
	 */
	public function setStagesEnabled(bool $isStagesEnabled): self
	{
		$this->isStagesEnabled = $isStagesEnabled;

		return $this;
	}

	/**
	 * @param int|null $categoryId
	 * @return Base
	 */
	public function setCategoryId(?int $categoryId): self
	{
		$this->categoryId = $categoryId;
		return $this;
	}
}
