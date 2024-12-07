<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\SharePrompt\Dto\DeletePromptIdForUserByCodesDto;
use Bitrix\AI\SharePrompt\Service\ShareService;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\PromptValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method DeletePromptIdForUserByCodesDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?DeletePromptIdForUserByCodesDto $object
 */
class ChangeDeleteForCurrentUserByCodesRequest extends BaseRequest
{
	public function __construct(
		protected BaseValidator $baseValidator,
		protected PromptValidator $promptValidator,
		protected ShareService $shareService
	)
	{
	}

	protected function getObjectWithData(): DeletePromptIdForUserByCodesDto
	{
		$dto = new DeletePromptIdForUserByCodesDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->promptCodes = $this->getArray('selectedSharePromptsCodes');

		$this->baseValidator->isNotEmptyArray($dto->promptCodes, 'selectedSharePromptsCodes');

		$dto->promptCodes = array_unique($dto->promptCodes);

		$dto->promptIds = $this->promptValidator->getPromptByCodesNotSystems(
			$dto->promptCodes,
			'selectedSharePromptsCodes'
		);

		$dto->ownerIdsForSharingPrompts = $this->promptValidator->accessOnPrompts(
			$dto->promptIds,
			'selectedSharePromptsCodes',
			$dto->userId
		);

		$dto->needDeleted = $this->getBool('needDeleted', '1');

		return $dto;
	}
}
