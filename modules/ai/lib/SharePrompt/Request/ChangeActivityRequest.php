<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\SharePrompt\Dto\ChangeActivityDto;
use Bitrix\AI\SharePrompt\Service\ShareService;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\PromptValidator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * @method ChangeActivityDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?ChangeActivityDto $object
 */
class ChangeActivityRequest extends BaseRequest
{
	public function __construct(
		protected BaseValidator $baseValidator,
		protected PromptValidator $promptValidator,
		protected ShareService $shareService
	)
	{
	}

	protected function getObjectWithData(): ChangeActivityDto
	{
		$dto = new ChangeActivityDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->promptCode = $this->getString('promptCode');
		$this->baseValidator->strRequire($dto->promptCode, 'promptCode');

		$dto->promptId = $this->promptValidator->getPromptIdNotSystemByCode($dto->promptCode, 'promptCode');
		$this->promptValidator->hasPromptIdInShare($dto->promptId, 'promptCode');
		$this->promptValidator->accessOnPrompt($dto->promptId, 'promptCode', $dto->userId);

		$dto->needActivate = $this->getBool('needActivate', '1');

		return $dto;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	protected function prepareData(): ChangeActivityDto
	{
		$this->object->shareType = $this->shareService->getShareType(
			$this->shareService->getAccessCodesForPrompt($this->object->promptId),
			(int)$this->currentUser?->getId()
		);

		return $this->object;
	}
}
