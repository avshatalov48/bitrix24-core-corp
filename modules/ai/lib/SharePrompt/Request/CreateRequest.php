<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\Exception\ValidateException;
use Bitrix\AI\SharePrompt\Dto\CreateDto;
use Bitrix\AI\SharePrompt\Service\ShareService;
use Bitrix\AI\Validator\AccessCodeValidator;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\PromptValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

/**
 * @method CreateDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?CreateDto $object
 */
class CreateRequest extends BaseRequest
{
	public function __construct(
		protected PromptValidator $promptValidator,
		protected BaseValidator $baseValidator,
		protected AccessCodeValidator $accessCodeValidator,
		protected ShareService $shareService
	)
	{
	}

	protected function getObjectWithData(): CreateDto
	{
		$dto = new CreateDto();

		$dto->accessCodes = $this->getArray('accessCodes');
		$dto->userCreatorId = (int)$this->currentUser?->getId();
		$dto->dateCreate = new DateTime();
		$dto->promptType = $this->getString('promptType');
		$dto->promptTitle = trim($this->getString('promptTitle'));
		$dto->promptDescription = $this->getString('promptDescription');
		$dto->promptIcon = $this->getString('promptIcon');
		$dto->categoriesForSave = $this->getArray('categoriesForSave');

		$dto->analyticCategory = $this->getString('analyticCategory');

		return $dto;
	}

	protected function getValidateActions(): array
	{
		return [
			'promptType' => [
				[$this->baseValidator, 'strRequire'],
				[$this->promptValidator, 'hasPromptType'],
			],
			'accessCodes' => [
				[$this->baseValidator, 'isArray'],
				[$this->accessCodeValidator, 'checkAccessCodes'],
			],
			'promptTitle' => [
				[$this->baseValidator, 'strRequire'],
				[$this, 'checkLenTitle'],
			],
			'promptDescription' => [
				[$this->baseValidator, 'strRequire'],
				[$this->promptValidator, 'isNotMinPromptLength'],
				[$this->promptValidator, 'isNotMaxPromptLength'],
			],
			'promptIcon' => [
				[$this->baseValidator, 'strRequire'],
			],
			'analyticCategory' => [
				[$this->baseValidator, 'strRequire'],
			],
			'categoriesForSave' => [
				[$this->baseValidator, 'isNotEmptyArray'],
			]
		];
	}

	/**
	 * @return CreateDto
	 * @throws ValidateException
	 * @throws \Bitrix\Main\Routing\Exceptions\ParameterNotFoundException
	 */
	protected function prepareData(): CreateDto
	{
		$this->object->accessCodesData = $this->shareService->prepareAccessCodes(
			$this->object->accessCodes,
			$this->object->userCreatorId
		);

		$this->object->categoriesForSaveData = $this->promptValidator->getAvailableCategoriesForSave(
			$this->object->categoriesForSave, 'categoriesForSave'
		);

		$this->object->shareType = $this->shareService->getShareType(
			$this->object->accessCodesData,
			$this->object->userCreatorId
		);

		$this->prepareAnalyticData();

		$this->object->usersIdsInAccessCodes = $this->shareService->getUsersIdsFromListRawCodes(
			$this->object->accessCodes
		);

		$this->checkCreator();

		return $this->object;
	}

	protected function checkCreator()
	{
		if (!in_array($this->object->userCreatorId, $this->object->usersIdsInAccessCodes))
		{
			throw new ValidateException('accessCodes', Loc::getMessage('AI_REQUEST_NO_FOUND_AUTHOR'));
		}
	}

	protected function prepareAnalyticData(): void
	{
		$this->object->analyticCategoryData = $this->promptValidator->getCategoryData(
			$this->object->analyticCategory, 'analyticCategory'
		);
	}

	public function checkLenTitle($title, string $fieldName)
	{
		$this->baseValidator->minLen($title, 1, $fieldName);
		$this->baseValidator->maxLen($title, 70, $fieldName);
	}
}
