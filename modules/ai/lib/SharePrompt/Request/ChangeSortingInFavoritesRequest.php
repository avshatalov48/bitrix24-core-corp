<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\SharePrompt\Dto\ChangeSortingInFavoritesDto;
use Bitrix\AI\Exception\ValidateException;
use Bitrix\AI\SharePrompt\Service\PromptService;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;

/**
 * @method ChangeSortingInFavoritesDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?ChangeSortingInFavoritesDto $object
 */
class ChangeSortingInFavoritesRequest extends BaseRequest
{
	public function __construct(
		protected PromptService $promptService,
		protected BaseValidator $baseValidator
	)
	{
	}

	protected function getObjectWithData(): ChangeSortingInFavoritesDto
	{
		$dto = new ChangeSortingInFavoritesDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->promptCodes = $this->getArray('promptCodes');
		$dto->promptIds = $this->promptService->getPromptIdsByCodes($dto->promptCodes);

		$this->checkCodes($dto->promptIds, $dto->promptCodes, 'promptCodes');

		return $dto;
	}

	public function checkCodes(array $promptIds, array $promptCodes, string $fieldName): void
	{
		$diff = array_diff($promptCodes, array_keys($promptIds));
		if (!empty($diff))
		{
			throw new ValidateException(
				$fieldName,
				Loc::getMessage('AI_REQUEST_NO_FOUND_CODES_IN_LIST') . implode(',', $diff)
			);
		}
	}
}
