<?php

namespace Bitrix\Crm\Service\Router\Component;

final class SidePanelWrapper extends Component
{
	public bool $isUsePadding = true;
	public bool $isPlainView = false;
	public bool $isUseBitrix24Theme = false;
	public ?string $defaultBitrix24Theme = null;
	public bool $isUseToolbar = true;
	public bool $isUseBackgroundContent = true;
	public bool $pageMode = true;
	public ?string $pageModeBackUrl = null;
	public bool $isHideToolbar = false;

	private const SIDEPANEL_WRAPPER = 'bitrix:ui.sidepanel.wrapper';

	public function __construct(
		public \Bitrix\Crm\Service\Router\Contract\Component $targetComponent,
	)
	{
		parent::__construct(self::SIDEPANEL_WRAPPER);
	}

	public function name(): string
	{
		return self::SIDEPANEL_WRAPPER;
	}

	public function template(): string
	{
		return '';
	}

	public function parameters(): array
	{
		return [
			'POPUP_COMPONENT_NAME' => $this->targetComponent->name(),
			'POPUP_COMPONENT_TEMPLATE_NAME' => $this->targetComponent->template(),
			'POPUP_COMPONENT_PARAMS' => $this->targetComponent->parameters(),
			'USE_PADDING' => $this->isUsePadding,
			'PLAIN_VIEW' => $this->isPlainView,
			'USE_UI_TOOLBAR' => $this->isUseToolbar ? 'Y' : 'N',
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => $this->isUseBitrix24Theme ? 'Y' : 'N',
			'DEFAULT_THEME_ID' => $this->defaultBitrix24Theme,
			'USE_BACKGROUND_CONTENT' => $this->isUseBackgroundContent,
			'PAGE_MODE' => $this->pageMode,
			'PAGE_MODE_OFF_BACK_URL' => $this->pageModeBackUrl,
			'HIDE_TOOLBAR' => $this->isHideToolbar,
		];
	}

	public function setParameter(string $name, mixed $value): static
	{
		$this->targetComponent->setParameter($name, $value);

		return $this;
	}

	public function parameter(string $name): mixed
	{
		return $this->targetComponent->parameter($name);
	}
}
