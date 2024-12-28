<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\Exception\ValidateException;
use Bitrix\AI\ShareRole\Dto\CreateDto;
use Bitrix\AI\ShareRole\Service\ShareService;
use Bitrix\AI\Validator\AccessCodeValidator;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\RoleValidator;
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
		protected RoleValidator $roleValidator,
		protected BaseValidator $baseValidator,
		protected AccessCodeValidator $accessCodeValidator,
		protected ShareService $shareService
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	protected function getObjectWithData(): CreateDto
	{
		$dto = new CreateDto();

		$dto->accessCodes = $this->getArray('accessCodes');
		$dto->userCreatorId = (int)$this->currentUser?->getId();
		$dto->dateCreate = new DateTime();
		$dto->roleTitle = trim($this->getString('roleTitle'));
		$dto->roleDescription = $this->changeToLF($this->getString('roleDescription'));
		$dto->roleAvatarFile = $this->getFile('roleAvatar');
		$dto->roleText = $this->changeToLF($this->getString('roleText'));

		return $dto;
	}

	protected function getValidateActions(): array
	{
		return [
			'accessCodes' => [
				[$this->baseValidator, 'isArray'],
				[$this->accessCodeValidator, 'checkAccessCodes'],
			],
			'roleTitle' => [
				[$this->baseValidator, 'strRequire'],
				[$this, 'checkLenTitle']
			],
			'roleText' => [
				[$this->baseValidator, 'strRequire'],
				[$this, 'checkLenText'],
			],
			'roleDescription' => [
				[$this->baseValidator, 'strRequire'],
				[$this, 'checkLenDescription'],
			],
			'roleAvatarFile' => [
				[$this->baseValidator, 'isArray'],
			]
		];
	}

	protected function prepareData(): CreateDto
	{
		$this->object->accessCodesData = $this->shareService->prepareAccessCodes(
			$this->object->accessCodes,
			$this->object->userCreatorId
		);

		$this->object->usersIdsInAccessCodes = $this->shareService->getUsersIdsFromListRawCodes(
			$this->object->accessCodes
		);

		$this->object->shareType = $this->shareService->getShareType(
			$this->object->accessCodesData,
			$this->object->userCreatorId
		);

		$this->checkCreator();

		$this->object->roleAvatarFile['MODULE_ID'] = 'ai';

		return $this->object;
	}

	protected function checkCreator()
	{
		if (!in_array($this->object->userCreatorId, $this->object->usersIdsInAccessCodes, true))
		{
			throw new ValidateException('accessCodes', Loc::getMessage('AI_REQUEST_NO_FOUND_ROLE_AUTHOR'));
		}
	}

	public function checkLenTitle($title, string $fieldName): void
	{
		$this->baseValidator->minLen($title, 1, $fieldName);
		$this->baseValidator->maxLen($title, 70, $fieldName);
	}

	public function checkLenText($title, string $fieldName): void
	{
		$this->baseValidator->minLen($title, 1, $fieldName);
		$this->baseValidator->maxLen($title, 2000, $fieldName);
	}

	public function checkLenDescription($title, string $fieldName): void
	{
		$this->baseValidator->minLen($title, 1, $fieldName);
		$this->baseValidator->maxLen($title, 150, $fieldName);
	}

	/**
	 * @param string $text
	 * @return array|string|string[]
	 */
	public function changeToLF(string $text): string|array
	{
		return str_replace("\r\n", "\n", $text);
	}
}
