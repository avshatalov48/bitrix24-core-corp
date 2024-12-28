<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights;

/**
 * Permission value variants with extra params of any variant
 */
class Variants
{
	private array $values = [];

	/**
	 * Create instance from simple key-value array
	 *
	 * @param array $values
	 * @return self
	 */
	public static function createFromArray(array $values): self
	{
		$instance = new self();
		foreach ($values as $key => $value)
		{
			$instance->add($key, $value);
		}

		return $instance;
	}

	/**
	 * Add new variant with extra params
	 *
	 * @param string|null $id
	 * @param string $value
	 * @param array $params
	 * @return void
	 */
	public function add(?string $id, string $value, array $params = []): void
	{
		$this->values[$id] = array_merge(
			['id' => $id, 'title' => $value],
			$params,
		);
	}

	public function remove(string $id): void
	{
		unset($this->values[$id]);
	}

	/**
	 * Check if variant $id exists
	 *
	 * @param string $id
	 * @return bool
	 */
	public function has(string $id): bool
	{
		return isset($this->values[$id]);
	}

	/**
	 * Convert variants to simple key-value array (for old permissions interface)
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$result = [];
		foreach ($this->values as $value)
		{
			$result[$value['id']] = $value['title'];
		}

		return $result;
	}

	public function moveToTopOfList(string $id): void
	{
		$existedValue = $this->values[$id] ?? null;
		if ($existedValue)
		{
			$this->values = [$id => $existedValue] + $this->values;
		}
	}

	/**
	 * Move variant $id to the end of variants list
	 *
	 * @param string $id
	 * @return void
	 */
	public function moveToEndOfList(string $id): void
	{
		$existedValue = $this->values[$id] ?? null;
		if ($existedValue)
		{
			unset($this->values[$id]);
			$this->values[$id] = $existedValue;
		}
	}

	/**
	 * Get variants for permission in base entity
	 *
	 * @return array
	 */
	public function getValuesForSection(): array
	{
		$result = [];
		foreach ($this->values as $data)
		{
			$data['id'] = (string)($data['id'] ?? null);

			if (($data['hideInSection'] ?? false))
			{
				continue;
			}

			if ($data['useAsEmptyInSection'] ?? false)
			{
				$data['useAsEmpty'] = true;
				unset($data['useAsEmptyInSection']);
			}

			if ($data['useAsNothingSelectedInSection'] ?? false)
			{
				$data['useAsNothingSelected'] = true;
				unset($data['useAsNothingSelectedInSection']);
			}

			if ($data['defaultInSection'] ?? false)
			{
				$data['default'] = true;
				unset($data['defaultInSection']);
			}

			$result[] = $data;

		}

		return $result;
	}

	/**
	 * Get variants for permission in entity stage permissions
	 *
	 * @param string $subsectionCode
	 * @return array
	 */
	public function getValuesForSubsection(string $subsectionCode): array
	{
		$result = [];
		foreach ($this->values as $data)
		{
			$data['id'] = (string)($data['id'] ?? null);

			$hideInSubsection = $data['hideInSubsection'] ?? null;
			if (
				($hideInSubsection !== null && (string)$hideInSubsection === $subsectionCode)
				|| (is_array($hideInSubsection) && in_array($subsectionCode, $hideInSubsection, true))
			)
			{
				continue;
			}

			if ($data['useAsEmptyInSubsection'] ?? false)
			{
				$data['useAsEmpty'] = true;
				unset($data['useAsEmptyInSubsection']);
			}

			if ($data['useAsNothingSelectedInSubsection'] ?? false)
			{
				$data['useAsNothingSelected'] = true;
				unset($data['useAsNothingSelectedInSubsection']);
			}

			if ($data['defaultInSubsection'] ?? false)
			{
				$data['default'] = true;
				unset($data['defaultInSubsection']);
			}

			$result[] = $data;
		}

		return $result;
	}
}
