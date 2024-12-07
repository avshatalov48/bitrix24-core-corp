<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\Exception\ValidateException;
use Bitrix\AI\SharePrompt\Dto\CreateDto;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;

/**
 * @method CreateDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?CreateDto $object
 */
class ChangeRequest extends CreateRequest
{
	protected function getObjectWithData(): CreateDto
	{
		$dto = parent::getObjectWithData();
		$dto->promptCode = $this->getString('promptCode');
		$this->baseValidator->strRequire($dto->promptCode, 'promptCode');

		list($promptId, $authorId) = $this->promptValidator
			->getPromptIdAndAuthorNotSystemByCode($dto->promptCode, 'promptCode')
		;

		$dto->promptId = $promptId;
		$dto->authorIdInPrompt = $authorId;

		$this->promptValidator->hasPromptIdInShare($dto->promptId, 'promptCode');
		$this->promptValidator->accessOnPrompt($dto->promptId, 'promptCode', (int)$this->currentUser?->getId());

		return $dto;
	}

	protected function prepareData(): CreateDto
	{
		$this->object = parent::prepareData();

		if (empty($this->object->authorIdInPrompt))
		{
			$this->object->needChangeAuthor = true;
			$this->object->authorIdInPrompt = $this->object->userCreatorId;
		}

		if (!in_array($this->object->authorIdInPrompt, $this->object->usersIdsInAccessCodes))
		{
			throw new ValidateException('accessCodes', Loc::getMessage('AI_REQUEST_NO_FOUND_AUTHOR'));
		}

		return $this->object;
	}

	protected function checkCreator()
	{
	}

	protected function prepareAnalyticData(): void
	{
	}
}
