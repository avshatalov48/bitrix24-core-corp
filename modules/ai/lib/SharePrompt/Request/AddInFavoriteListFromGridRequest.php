<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\SharePrompt\Dto\PromptUserDto;
use Bitrix\AI\Validator\PromptValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method PromptUserDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?PromptUserDto $object
 */
class AddInFavoriteListFromGridRequest extends BaseRequest
{
	public function __construct(
		protected PromptValidator $promptValidator
	)
	{
	}

	protected function getObjectWithData(): PromptUserDto
	{
		$dto = new PromptUserDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->promptCode = $this->getString('promptCode');
		$dto->promptId = $this->promptValidator->getPromptByCode($dto->promptCode, 'promptCode');

		$this->promptValidator->inAccessibleIgnoreDelete(
			$dto->promptId,
			'promptCode',
			$dto->userId,
		);

		$this->promptValidator->hasNotInFavoriteList(
			$dto->promptId,
			'promptCode',
			$dto->userId,
		);

		return $dto;
	}
}
