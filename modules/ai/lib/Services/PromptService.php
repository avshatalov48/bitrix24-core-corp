<?php declare(strict_types=1);

namespace Bitrix\AI\Services;

use Bitrix\AI\Enum\PromptCode;
use Bitrix\AI\Payload;
use Bitrix\AI\Payload\IPayload;
use Bitrix\AI\SharePrompt\Repository\PromptRepository;

class PromptService
{
	public function __construct(
		protected PromptRepository $promptRepository,
	)
	{
	}

	public function getPayloadForTextPrompt($prompt, int $userId, array $markers, array $parameters): IPayload|null
	{
		if (!isset($prompt['code']))
		{
			return (new Payload\Text((string)$prompt))->setMarkers($markers);
		}

		if ($prompt['code'] === PromptCode::ZeroPrompt->value)
		{
			return (new Payload\Prompt($prompt['code']))
				->setMarkers($markers)
				->setPromptCategory($parameters['promptCategory'] ?? '')
			;
		}

		$data = $this->promptRepository->getByCode($prompt['code']);
		if (empty($data) || empty($data['ID']))
		{
			return null;
		}

		if ($data['IS_SYSTEM'] === PromptRepository::IS_SYSTEM)
		{
			return (new Payload\Prompt($prompt['code']))
				->setMarkers($markers)
				->setPromptCategory($parameters['promptCategory'] ?? '')
			;
		}

		$data = $this->promptRepository->getPromptDataInAccessibleList($userId, (int)$data['ID']);
		if (empty($data['PROMPT']))
		{
			return null;
		}

		$markers['user_message'] = $data['PROMPT'];

		return (new Payload\Prompt(PromptCode::ZeroPrompt->value))
			->setMarkers($markers)
			->setPromptCategory($parameters['promptCategory'] ?? '')
		;
	}
}
