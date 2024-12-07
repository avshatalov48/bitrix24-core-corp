<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\SharePrompt\Dto\DeletePromptIdForUserDto;
use Bitrix\AI\SharePrompt\Service\ShareService;
use Bitrix\AI\Validator\PromptValidator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * @method DeletePromptIdForUserDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?DeletePromptIdForUserDto $object
 */
class ChangeDeleteForCurrentUserRequest extends BaseRequest
{
	public function __construct(
		protected PromptValidator $promptValidator,
		protected ShareService $shareService
	)
	{
	}

	protected function getObjectWithData(): DeletePromptIdForUserDto
	{
		$dto = new DeletePromptIdForUserDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->promptCode = $this->getString('promptCode');
		$dto->promptId = $this->getPromptByCode($dto->promptCode, 'promptCode');

		$dto->hasInOwnerTable = $this->promptValidator->accessOnPrompt(
			$dto->promptId,
			'promptCode',
			(int)$this->currentUser?->getId()
		);

		$dto->needDeleted = $this->getBool('needDeleted', '1');

		return $dto;
	}

	protected function getPromptByCode(string $code, string $fieldName): int
	{
		return $this->promptValidator->getPromptByCode($code, $fieldName);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function prepareData(): DeletePromptIdForUserDto
	{
		$this->object->shareType = $this->shareService->getShareType(
			$this->shareService->getAccessCodesForPrompt($this->object->promptId),
			(int)$this->currentUser?->getId()
		);

		return $this->object;
	}
}
