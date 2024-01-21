<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

class SampleSettings extends AbstractSettings
{
	public const TYPE = 'sample';

	public function validate(): ErrorCollection
	{
		$requireFields = ['site_title'];
		$errors = new ErrorCollection();
		foreach ($requireFields as $field)
		{
			if (!array_key_exists($field, $this->data) || empty($this->data[$field]))
			{
				$errors->setError(
					new Error(
						"Field $field is required",
						0,
						[
							'page' => $this->getType(),
							'field' => $field,
						]
					)
				);
			}
		}
		if (isset($this->data['site_title']) && $this->data['site_title'] === 'debug')
		{
			$errors->setError(
				new Error("This is an error message for debugging",
					0,
					[
						'page' => $this->getType(),
						'field' => 'site_title',
					]
				)
			);
		}

		return $errors;
	}

	public function save(): Result
	{
		if (Loader::includeModule('bitrix24'))
		{
			\COption::SetOptionString("bitrix24", "site_title", $this->data['site_title']);
		}
		else
		{
			\COption::SetOptionString("bitrix24", "site_title", $this->data['site_title'], false, SITE_ID);
		}
		return new Result();
	}

	public function get(): SettingsInterface
	{
		return new static(['site_title' => \Coption::GetOptionString('bitrix24', 'site_title', 'debug')]);
	}

	public function toArray(): array
	{
		return $this->data;
	}

	public function set(array $data): SettingsInterface
	{
		return new static($data);
	}
}