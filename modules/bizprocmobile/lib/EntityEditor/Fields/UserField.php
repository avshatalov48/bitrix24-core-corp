<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

class UserField extends BaseField
{
	protected bool $isEmployeeField;
	protected ?array $uniqueUserIds = null;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		$this->isEmployeeField = $property['Type'] === 'S:employee';
	}

	public function getType(): string
	{
		return 'user';
	}

	public function getConfig(): array
	{
		return [
			'entityList' => $this->getEntityList(),
			'hasSolidBorder' => false,
		];
	}

	protected function getEntityList(): array
	{
		if ($this->value === null)
		{
			return [];
		}

		$ids = $this->getUniqueUserIds();
		if (!$ids)
		{
			return [];
		}

		$list = [];

		$userFields = ['ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE', 'EMAIL', 'PERSONAL_PHOTO'];
		$users = \CUser::GetList('id', 'asc', ['ID' => implode('|', $ids)], ['FIELDS' => $userFields]);
		while ($user = $users->Fetch())
		{
			$fullName = \CUser::FormatName(\CSite::GetNameFormat(false), $user, true, false);
			$photoSrc = null;
			if ((int)$user['PERSONAL_PHOTO'] > 0)
			{
				$fileInfo = \CFile::ResizeImageGet(
					$user['PERSONAL_PHOTO'],
					['width' => 100, 'height' => 100],
					BX_RESIZE_IMAGE_EXACT
				);
				if (is_array($fileInfo) && isset($fileInfo['src']))
				{
					$photoSrc = $fileInfo['src'];
				}
			}

			$list[] = ['id' => (int)$user['ID'], 'title' => $fullName, 'imageUrl' => $photoSrc];
		}

		return $list;
	}

	protected function convertToMobileType($value): ?int
	{
		$prefix = 'user_';
		$prefixLength = mb_strlen($prefix);

		if ($this->isCorrectUserId($value))
		{
			return (int)$value;
		}

		if (str_starts_with($value, $prefix))
		{
			$value = mb_substr($value, $prefixLength);
			if ($this->isCorrectUserId($value))
			{
				return (int)$value;
			}
		}

		return null;
	}

	protected function convertToWebType($value): string
	{
		if ($this->isEmployeeCompatibleMode())
		{
			return $this->isCorrectUserId($value) ? $value : '';
		}

		// return $this->isCorrectUserId($value) ? 'user_' . (int)$value : '';
		return $this->isCorrectUserId($value) ? '[' . (int)$value . ']' : '';
	}

	public function convertValueToMobile(): mixed
	{
		$value = $this->getUniqueUserIds();

		return ($this->isMultiple() ? $value : ($value[0] ?? ''));
	}

	public function convertValueToWeb(): string|array
	{
		if (!$this->isMultiple())
		{
			return $this->convertToWebType($this->value);
		}

		$multipleValue = [];
		if (is_array($this->value))
		{
			foreach ($this->value as $singleValue)
			{
				$multipleValue[] = $this->convertToWebType($singleValue);
			}
		}

		if ($this->isEmployeeCompatibleMode())
		{
			return $multipleValue;
		}

		return implode(',', $multipleValue);
	}

	protected function getUniqueUserIds(): array
	{
		if ($this->uniqueUserIds === null)
		{
			$ids = [];

			$draftIds = (array)$this->value;
			foreach ($draftIds as $id)
			{
				$id = $this->convertToMobileType($id);
				if ($id)
				{
					$ids[$id] = true;

					if (!$this->isMultiple())
					{
						break;
					}
				}
			}

			$this->uniqueUserIds = array_keys($ids);
		}

		return $this->uniqueUserIds;
	}

	protected function isCorrectUserId($id): bool
	{
		return is_numeric($id) && (int)$id > 0;
	}

	public function isEmployeeCompatibleMode(): bool
	{
		return $this->isEmployeeField && $this->fieldTypeObject?->getBaseType() === 'string';
	}
}
