<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Request;

use Bitrix\AI\BaseRequest;
use Bitrix\AI\Facade\User;
use Bitrix\AI\SharePrompt\Dto\GetByCategoryDto;
use Bitrix\AI\Validator\BaseValidator;
use Bitrix\AI\Validator\PromptValidator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;

/**
 * @method GetByCategoryDto getData(HttpRequest $request, CurrentUser $currentUser = null)
 * @property ?GetByCategoryDto $object
 */
class GetByCategoryRequest extends BaseRequest
{
	public function __construct(
		protected BaseValidator   $baseValidator,
		protected PromptValidator $promptValidator
	)
	{
	}

	protected function getObjectWithData(): GetByCategoryDto
	{
		$dto = new GetByCategoryDto();
		$dto->userId = (int)$this->currentUser?->getId();
		$dto->category = $this->getString('category');
		$dto->moduleId = $this->getString('moduleId');
		$dto->context = $this->getString('context');
		$dto->userLang = User::getUserLanguage();

		return $dto;
	}

	protected function getValidateActions(): array
	{
		return [
			'category' => [
				[$this->baseValidator, 'strRequire'],
				[$this->promptValidator, 'getCategoryData'],
			],
			'moduleId' => [
				[$this->baseValidator, 'strRequire'],
			],
			'context' => [
				[$this->baseValidator, 'strRequire'],
			],
		];
	}
}
