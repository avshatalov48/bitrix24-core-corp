<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\SharePrompt\Dto\ChangeActivityByCodesDto;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\PromptValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method ChangeActivityByCodesDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?ChangeActivityByCodesDto $object
 */
class ChangeActivityByCodesRequest extends BaseRequest
{
	public function __construct(
		protected BaseValidator $baseValidator,
		protected PromptValidator $promptValidator
	)
	{
	}

	protected function getObjectWithData(): ChangeActivityByCodesDto
	{
		$dto = new ChangeActivityByCodesDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->promptCodes = $this->getArray('selectedSharePromptsCodes');
		$this->baseValidator->isNotEmptyArray($dto->promptCodes, 'selectedSharePromptsCodes');

		$dto->promptCodes = array_unique($dto->promptCodes);

		$dto->promptIds = $this->promptValidator->getPromptByCodesNotSystems(
			$dto->promptCodes,
			'selectedSharePromptsCodes'
		);

		$this->promptValidator->accessOnPrompts($dto->promptIds, 'selectedSharePromptsCodes', $dto->userId);
		$dto->needActivate = $this->getBool('needActivate', '1');

		return $dto;
	}
}
