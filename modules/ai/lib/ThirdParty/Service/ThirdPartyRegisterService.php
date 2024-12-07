<?php declare(strict_types=1);

namespace Bitrix\AI\ThirdParty\Service;

use Bitrix\AI\Engine;
use Bitrix\AI\Model\EngineTable;
use Bitrix\AI\ThirdParty\Dto\ThirdPartyEngineDto;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Rest\RestException;

class ThirdPartyRegisterService
{
	/**
	 * Returns DTO of a new custom Engine
	 * @param array $data
	 * @param string|null $appCode
	 * @return ThirdPartyEngineDto
	 * @throws RestException
	 */
	public function getValidatedData(array $data, ?string $appCode): ThirdPartyEngineDto
	{
		$data = array_change_key_case($data);
		$this->validateDataForRegistration($data);

		return $this
			->getNewDto()
			->setName($data['name'])
			->setCode($data['code'])
			->setCategory($data['category'])
			->setCompletionsUrl($data['completions_url'])
			->setSettings($data['settings'] ?? [])
			->setAppCode($appCode)
			->setEngineId($this->getExistsData($data, $appCode))
		;
	}

	private function getNewDto(): ThirdPartyEngineDto
	{
		return new ThirdPartyEngineDto();
	}

	/**
	 * Adds new Engine or updates existing one
	 * @param ThirdPartyEngineDto $thirdPartyDto
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult
	 * @throws \Exception
	 */
	public function save(ThirdPartyEngineDto $thirdPartyDto)
	{
		if ($thirdPartyDto->getExists())
		{
			return EngineTable::update(
				$thirdPartyDto->getEngineId(),
				$thirdPartyDto->getArray()
			);
		}

		return EngineTable::add($thirdPartyDto->getArray(new DateTime()));
	}

	/**
	 * Validates the data of a new custom Engine
	 *
	 * @param array{name: string, code: string, category: string, completions_url: string, settings: array|null} $data
	 * @return void
	 * @throws RestException
	 */
	private function validateDataForRegistration(array $data): void
	{
		$this->validateFieldsInput($data);
		$this->validateCodeFormat($data);
		$this->validateCategory($data);
		$this->validateUniqueCode($data);
		$this->validateCompletionsUrl($data);
		$this->validateSettings($data);
	}

	/**
	 * @param array{code: string, app_code: string|null} $data
	 * @return int|null
	 */
	private function getExistsData(array $data, ?string $appCode): ?int
	{
		$existing = EngineTable::query()
			->setSelect(['ID'])
			->where('code', $data['code'])
			->where('app_code', $appCode)
			->setLimit(1)
			->fetch()
		;

		return $existing ? (int)$existing['ID'] : null;
	}

	/**
	 * @param array{settings: array|null} $data
	 * @return void
	 * @throws RestException
	 */
	private function validateSettings(array $data): void
	{
		if (isset($data['settings']) && !is_array($data['settings']))
		{
			throw new RestException(
				Loc::getMessage('AI_REST_ENGINE_REGISTER_ERROR_SETTINGS_FORMAT'),
				"ENGINE_REGISTER_ERROR_SETTINGS_FORMAT"
			);
		}
	}

	/**
	 * @param array{name: string, code: string, category: string, completions_url: string} $data
	 * @return void
	 * @throws RestException
	 */
	private function validateFieldsInput(array $data): void
	{
		foreach (['name', 'code', 'category', 'completions_url'] as $code)
		{
			$data[$code] = $data[$code] ?? '';
			if (!is_string($data[$code]) || !mb_strlen(trim($data[$code])))
			{
				$codeUp = mb_strtoupper($code);
				throw new RestException(
					Loc::getMessage("AI_REST_ENGINE_REGISTER_ERROR_$codeUp"),
					"ENGINE_REGISTER_ERROR_$codeUp"
				);
			}
		}
	}

	/**
	 * @param array{code: string} $data
	 * @return void
	 * @throws RestException
	 */
	private function validateCodeFormat(array $data): void
	{
		$data['code'] = trim($data['code']);
		if (!preg_match('/^[A-Za-z0-9-_]+$/', $data['code']))
		{
			throw new RestException(
				Loc::getMessage('AI_REST_ENGINE_REGISTER_ERROR_CODE_FORMAT'),
				'ENGINE_REGISTER_ERROR_CODE_FORMAT'
			);
		}
	}

	/**
	 * @param array{completions_url: string} $data
	 * @return void
	 * @throws RestException
	 */
	private function validateCompletionsUrl(array $data): void
	{
		$http = new HttpClient();
		$http->get($data['completions_url']);
		if ($http->getStatus() !== 200)
		{
			throw new RestException(
				Loc::getMessage('AI_REST_ENGINE_REGISTER_ERROR_COMPLETIONS_URL_FAIL'),
				'ENGINE_REGISTER_ERROR_COMPLETIONS_URL_FAIL'
			);
		}
	}

	/**
	 * @param array{category: string, code: string} $data
	 * @return void
	 * @throws RestException
	 */
	private function validateUniqueCode(array $data): void
	{
		if (Engine::isExistByCode($data['category'], $data['code']))
		{
			throw new RestException(
				Loc::getMessage('AI_REST_ENGINE_REGISTER_ERROR_CODE_UNIQUE'),
				'ENGINE_REGISTER_ERROR_CODE_UNIQUE'
			);
		}
	}

	/**
	 * @param array{category: string} $data
	 * @return void
	 * @throws RestException
	 */
	private function validateCategory(array $data): void
	{
		$categories = Engine::getCategories();
		if (!in_array($data['category'], $categories))
		{
			throw new RestException(
				Loc::getMessage(
					'AI_REST_ENGINE_REGISTER_ERROR_CATEGORY_FORMAT',
					['{categories}' => implode(', ', $categories)]
				),
				'ENGINE_REGISTER_ERROR_CATEGORY_FORMAT'
			);
		}
	}
}
