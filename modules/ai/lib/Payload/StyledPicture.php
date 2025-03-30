<?php

namespace Bitrix\AI\Payload;

use Bitrix\AI\Facade\User;
use Bitrix\AI\ImageStylePrompt\ImageStylePromptManager;

class StyledPicture extends Payload implements IPayload
{

	public function __construct(
		protected string $payload,
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function getData(): array
	{
		$data = [
			'prompt' => $this->payload,
		];

		if (isset($this->markers['style']))
		{
			$data['style'] = $this->prepareStylePrompt($this->markers['style']);
		}

		if (isset($this->markers['format']))
		{
			$data['format'] = $this->markers['format'];
		}

		if (isset($this->markers['images_number']))
		{
			$data['images_number'] = $this->markers['images_number'];
		}

		return $data;
	}

	public function pack(): string
	{
		return json_encode([
			'prompt' => $this->payload,
			'markers' => $this->markers,
			static::PROPERTY_CUSTOM_COST => $this->customCost
		]);
	}

	public static function unpack(string $packedData): ?static
	{
		$unpackedData = json_decode($packedData, true);

		$prompt = $unpackedData['prompt'] ?? '';
		$markers = $unpackedData['markers'] ?? [];

		$payload = (new self($prompt))->setMarkers($markers);

		static::setCustomCost($payload, $unpackedData);

		return $payload;
	}

	private function prepareStylePrompt(string $promptCode): string
	{
		$stylePromptManager = new ImageStylePromptManager(User::getUserLanguage());
		$stylePrompt = $stylePromptManager->getByCode($promptCode)?->getPrompt() ?? '';
		$formatter = new Formatter($stylePrompt, $this->engine);

		return $formatter->format();
	}
}