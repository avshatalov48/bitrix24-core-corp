<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\SharePrompt\Dto\PromptUserDto;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\PromptValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method PromptUserDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?PromptUserDto $object
 */
class GetPromptDataByCodeRequest extends BaseRequest
{
	public function __construct(
		protected PromptValidator $promptValidator,
		protected BaseValidator $baseValidator
	)
	{
	}

	protected function getObjectWithData(): PromptUserDto
	{
		$dto = new PromptUserDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->promptCode = $this->getString('promptCode');

		$this->baseValidator->strRequire($dto->promptCode, 'promptCode');

		$dto->promptId = $this->promptValidator->getPromptIdNotSystemByCode($dto->promptCode, 'promptCode');

		$this->promptValidator->accessOnPrompt(
			$dto->promptId,
			'promptCode',
			(int)$this->currentUser?->getId()
		);

		return $dto;
	}
}
