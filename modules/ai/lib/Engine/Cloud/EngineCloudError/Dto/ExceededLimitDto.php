<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud\EngineCloudError\Dto;

use Bitrix\Main\Type\Contract\Arrayable;

class ExceededLimitDto implements Arrayable
{
	public function __construct(
		public readonly bool $showSliderWithMsg,
		public readonly string $sliderCode,
		public readonly string $errorCode,
		public readonly string $msgForIm,
		public readonly bool $isAvailableBaas
	)
	{
	}

	public function toArray(): array
	{
		return [
			'showSliderWithMsg' => $this->showSliderWithMsg,
			'sliderCode' => $this->sliderCode,
			'errorCode' => $this->errorCode,
			'msgForIm' => $this->msgForIm,
			'isAvailableBaas' => $this->isAvailableBaas,
		];
	}
}
