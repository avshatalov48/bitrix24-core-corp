<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Dto;

use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\AI\Facade\User;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\UI\EntitySelector\Converter;
use Bitrix\Main\Web\Json;

class RoleForUpdateDto implements Arrayable
{

	use TranslateTrait;

	protected string $code = '';
	protected string $nameTranslate = '';
	protected string $descriptionTranslate = '';
	protected string $avatar = '';
	protected string $instruction = '';
	protected array $accessCodes = [];
	protected string $authorId = '';
	protected string $avatarUrl = '';

	public function __construct(array $data)
	{
		$this->prepareData($data);
	}

	protected function prepareData(array $data): void
	{
		if (array_key_exists('CODE', $data) && is_string($data['CODE']))
		{
			$this->code = $data['CODE'];
		}

		if (array_key_exists('DEFAULT_NAME', $data) && is_string($data['DEFAULT_NAME']))
		{
			$this->nameTranslate = $data['DEFAULT_NAME'];
		}

		if (array_key_exists('NAME_TEXT', $data) && is_string($data['NAME_TEXT']))
		{
			$this->nameTranslate = $data['NAME_TEXT'];
		}

		if (array_key_exists('DEFAULT_DESCRIPTION', $data) && is_string($data['DEFAULT_DESCRIPTION']))
		{
			$this->descriptionTranslate = $data['DEFAULT_DESCRIPTION'];
		}

		if (array_key_exists('DESCRIPTION_TEXT', $data) && is_string($data['DESCRIPTION_TEXT']))
		{
			$this->descriptionTranslate = $data['DESCRIPTION_TEXT'];
		}

		if (array_key_exists('AVATAR', $data) && is_string($data['AVATAR']))
		{
			$this->avatar = $data['AVATAR'];
		}

		if (array_key_exists('AVATAR_URL', $data) && is_string($data['AVATAR_URL']))
		{
			$this->avatarUrl = $data['AVATAR_URL'];
		}

		if (array_key_exists('INSTRUCTION', $data) && is_string($data['INSTRUCTION']))
		{
			$this->instruction = $data['INSTRUCTION'];
		}

		if (array_key_exists('ACCESS_CODES', $data) && is_string($data['ACCESS_CODES']))
		{
			$this->accessCodes = Converter::convertFromFinderCodes(
				array_unique(
					explode(',', $data['ACCESS_CODES'])
				)
			);
		}

		if (array_key_exists('AUTHOR_ID', $data) && is_string($data['AUTHOR_ID']))
		{
			$this->authorId = $data['AUTHOR_ID'];
		}
	}

	public function toArray(): array
	{
		return [
			'code' => $this->code,
			'nameTranslate' => $this->nameTranslate,
			'descriptionTranslate' => $this->descriptionTranslate,
			'avatar' => $this->avatar,
			'instruction' => $this->instruction,
			'accessCodes' => $this->accessCodes,
			'authorId' => $this->authorId,
			'avatarUrl' => $this->avatarUrl,
		];
	}
}
