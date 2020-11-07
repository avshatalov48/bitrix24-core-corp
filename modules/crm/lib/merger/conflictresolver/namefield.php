<?php


namespace Bitrix\Crm\Merger\ConflictResolver;


class NameField extends StringField
{
	public const MAX_SEED_TO_CHECK_COUNT = 10;

	protected $isRelatedFieldsCheckRequired = false;
	protected $skipResolve = false;
	protected $skipSetNewTarget = false;

	public function setRelatedFieldsCheckRequired(bool $required): void
	{
		$this->isRelatedFieldsCheckRequired = $required;
	}

	public function setNewTargetValue($newTargetValue, $fieldId = null): void
	{
		if ($this->skipSetNewTarget)
		{
			return;
		}
		parent::setNewTargetValue($newTargetValue, $fieldId);
	}

	protected function doResolve(): bool
	{
		if (count($this->seeds) > static::MAX_SEED_TO_CHECK_COUNT)
		{
			// if seeds count is too large, will use only StringField resolves:
			$this->skipResolve = true;
			return parent::doResolve();
		}

		$this->skipSetNewTarget = true; // will only check, modifying seeds data is forbidden
		foreach (array_keys($this->seeds) as $targetId)
		{
			$this->curSeedId = $targetId;
			$targetValue = $this->getSeedValue();
			foreach (array_keys($this->seeds) as $seedId)
			{
				if ($seedId === $targetId)
				{
					continue;
				}
				$this->curSeedId = $seedId;
				$seedValue = $this->getSeedValue();

				if (!$this->resolveByValue($seedValue, $targetValue))
				{
					return false;
				}
			}
		}
		$this->skipSetNewTarget = false;
		$this->curSeedId = null;

		return parent::doResolve();
	}

	protected function resolveByValue(&$seedValue, &$targetValue): bool
	{
		$parentResolved = parent::resolveByValue($seedValue, $targetValue);

		// related fields check can redefine resolve results, so we can't exit here
		if ($parentResolved && !$this->isRelatedFieldsCheckRequired)
		{
			return true;
		}

		if ($this->skipResolve)
		{
			return $parentResolved;
		}

		if ($this->isRelatedFieldsCheckRequired && $this->checkRelatedFields($seedValue, $targetValue))
		{
			return true;
		}

		if ($parentResolved)
		{
			return true;
		}

		if ($this->isEmpty($seedValue))
		{
			return true;
		}

		if ($this->isEmpty($targetValue))
		{	// if target is empty, just use seed value
			$this->setNewTargetValue($seedValue);
			return true;
		}

		if ($this->checkSubstring($seedValue, $targetValue))
		{
			return true;
		}

		if ($this->checkTranslit($seedValue, $targetValue))
		{
			return true;
		}


		return false;
	}

	protected function checkSubstring(&$seedValue, &$targetValue): bool
	{
		if (mb_stripos($targetValue, $seedValue) !== false)
		{
			return true;
		}
		if (mb_stripos($seedValue, $targetValue) !== false)
		{
			$this->setNewTargetValue($this->getSeedValue());
			return true;
		}

		return false;
	}

	protected function checkTranslit(&$seedValue, &$targetValue): bool
	{
		if (!defined('LANGUAGE_ID'))
		{
			return false;
		}
		$lang = LANGUAGE_ID;
		$params = [
			"max_len" => 100,
			"change_case" => 'L',
			"replace_space" => ' ',
			"replace_other" => '_',
			"delete_repeat_replace" => false,
			"safe_chars" => ''
		];
		$translitedSeedVal = \CUtil::translit($seedValue, $lang, $params);
		$translitedTargetVal = \CUtil::translit($targetValue, $lang, $params);
		if ($translitedSeedVal === $translitedTargetVal)
		{
			if (mb_strtolower($targetValue) === $translitedTargetVal)
			{
				// if target is translited, use seed instead:
				$this->setNewTargetValueIfNeed($seedValue);
			}
			return true;
		}

		return false;
	}

	protected function checkRelatedFields(&$seedValue, &$targetValue): bool
	{
		$seed = $this->getSeed();
		$target = $this->getTarget();
		$seedLastNameOriginalValue = isset($seed['LAST_NAME']) ? (string)$seed['LAST_NAME'] : '';
		$targetLastNameOriginalValue = isset($target['LAST_NAME']) ? (string)$target['LAST_NAME'] : '';

		$seedLastNameValue = mb_strtolower(trim($seedLastNameOriginalValue));
		$targetLastNameValue = mb_strtolower(trim($targetLastNameOriginalValue));

		$seedNameValue = mb_strtolower($seedValue);
		$targetNameValue = mb_strtolower($targetValue);

		// region Name or lastname in wrong field
		if ($seedLastNameValue === '' && $seedNameValue === $targetLastNameValue)
		{ // for example $seed = ['NAME' => 'lastname', 'LAST_NAME' => '']; $target = ['NAME' => 'name', 'LAST_NAME' => 'lastname'];
			return true;
		}
		if ($seedNameValue === '' && $seedLastNameValue === $targetNameValue)
		{ // for example $seed = ['NAME' => '', 'LAST_NAME' => 'name']; $target = ['NAME' => 'name', 'LAST_NAME' => 'lastname'];
			return true;
		}

		if ($targetLastNameValue === '' && $targetNameValue === $seedLastNameValue)
		{ // for example $seed = ['NAME' => 'name', 'LAST_NAME' => 'lastname']; $target = ['NAME' => 'lastname', 'LAST_NAME' => ''];
			$this->setNewTargetValue($this->getSeedValue());
			$this->setNewTargetValue($seedLastNameOriginalValue, 'LAST_NAME');
			return true;
		}
		if ($targetNameValue === '' && $targetLastNameValue === $seedNameValue)
		{ // for example $seed = ['NAME' => 'name', 'LAST_NAME' => 'lastname']; $target = ['NAME' => '', 'LAST_NAME' => 'name'];
			$this->setNewTargetValue($this->getSeedValue());
			$this->setNewTargetValue($seedLastNameOriginalValue, 'LAST_NAME');
			return true;
		}
		// endregion

		// region Name and lastname in the same field
		if ($seedLastNameValue === '' && (
			($seedNameValue === $targetNameValue.' '.$targetLastNameValue) ||
			($seedNameValue === $targetLastNameValue.' '.$targetNameValue)
			))
		{ // for example $seed = ['NAME' => 'name lastname', 'LAST_NAME' => '']; $target = ['NAME' => 'name', 'LAST_NAME' => 'lastname'];
			return true;
		}
		if ($seedNameValue === '' && (
				($seedLastNameValue === $targetNameValue.' '.$targetLastNameValue) ||
				($seedLastNameValue === $targetLastNameValue.' '.$targetNameValue)
			))
		{ // for example $seed = ['NAME' => '', 'LAST_NAME' => 'name lastname']; $target = ['NAME' => 'name', 'LAST_NAME' => 'lastname'];
			return true;
		}
		if ($targetLastNameValue === '' && (
				($targetNameValue === $seedNameValue.' '.$seedLastNameValue) ||
				($targetNameValue === $seedLastNameValue.' '.$seedNameValue)
			))
		{ // for example $seed = ['NAME' => 'name', 'LAST_NAME' => 'lastname']; $target = ['NAME' => 'name lastname', 'LAST_NAME' => ''];
			$this->setNewTargetValue($this->getSeedValue());
			$this->setNewTargetValue($seedLastNameOriginalValue, 'LAST_NAME');
			return true;
		}
		if ($targetNameValue === '' && (
				($targetLastNameValue === $seedNameValue.' '.$seedLastNameValue) ||
				($targetLastNameValue === $seedLastNameValue.' '.$seedNameValue)
			))
		{ // for example $seed = ['NAME' => 'name', 'LAST_NAME' => 'lastname']; $target = ['NAME' => '', 'LAST_NAME' => 'name lastname'];
			$this->setNewTargetValue($this->getSeedValue());
			$this->setNewTargetValue($seedLastNameOriginalValue, 'LAST_NAME');
			return true;
		}
		// endregion

		if($this->checkRedundantSpacesInAllFields(
			$seedValue,
			trim($seedLastNameOriginalValue),
			$targetValue,
			trim($targetLastNameOriginalValue))
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * This is somewhat complex peace of code, maybe some refactoring needed.
	 *
	 * @param string $seedNameValue
	 * @param string $seedLastNameValue
	 * @param string $targetNameValue
	 * @param string $targetLastNameValue
	 * @return bool
	 */
	protected function checkRedundantSpacesInAllFields(
		string $seedNameValue,
		string $seedLastNameValue,
		string $targetNameValue,
		string $targetLastNameValue
	): bool
	{
		$seedNameValueWithoutSpaces = $this->removeSpaces($seedNameValue);
		$seedLastNameValueWithoutSpaces = $this->removeSpaces($seedLastNameValue);
		$targetNameValueWithoutSpaces = $this->removeSpaces($targetNameValue);
		$targetLastNameValueWithoutSpaces = $this->removeSpaces($targetLastNameValue);

		if(
			$seedNameValueWithoutSpaces === $targetNameValueWithoutSpaces
			&& $seedLastNameValueWithoutSpaces === $targetLastNameValueWithoutSpaces
		)
		{
			if($targetLastNameValue !== $targetLastNameValueWithoutSpaces)
			{
				$this->setNewTargetValue($targetNameValue);
				$this->setNewTargetValue($targetLastNameValueWithoutSpaces, 'LAST_NAME');
			}
			if($targetNameValue !== $targetNameValueWithoutSpaces)
			{
				$this->setNewTargetValue($targetNameValueWithoutSpaces);
			}

			
			return true;
		}
		
		return false;
	}
}