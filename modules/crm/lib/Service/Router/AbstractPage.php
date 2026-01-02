<?php

namespace Bitrix\Crm\Service\Router;

use Bitrix\Crm\Service\Router\Component\SidePanelWrapper;
use Bitrix\Crm\Service\Router\Contract\Page\HasComponent;
use Bitrix\Crm\Service\Router\Enum\Scope;
use Bitrix\Crm\Service\Router\PageValidator\ScopeAvailabilityValidator;
use Bitrix\Crm\Tour\Base;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Page\Asset;
use CCrmViewHelper;

abstract class AbstractPage implements Contract\Page\StaticPage, HasComponent
{
	protected bool $isPlainView = false;
	protected ?string $title = null;
	protected ?bool $canUseFavoriteStar = null;
	protected string $workAreaInvisibleCss = '/bitrix/js/crm/css/workareainvisible.css';

	public function __construct(
		protected readonly HttpRequest $request,
		protected readonly ?Scope $currentScope,
	)
	{
	}

	abstract public function component(): Contract\Component;

	final public function render(?\CBitrixComponent $parentComponent = null): void
	{
		$validators = $this->getPageValidators();
		foreach ($validators as $validator)
		{
			if (!$validator->isAvailable())
			{
				$validator->showError();

				return;
			}
		}

		if ($this->isDisplayDetailsFrameWrapperScript())
		{
			echo $this->getDetailsFrameWrapperScript();

			return;
		}

		foreach ($this->tours() as $tour)
		{
			echo $tour->build();
		}

		$this
			->getPreparedComponent()
			->setParent($parentComponent)
			->render();
	}

	/**
	 * @return Base[]
	 */
	protected function tours(): array
	{
		return [];
	}

	public static function getSidePanelAnchorRules(): array
	{
		return [];
	}

	public static function isActive(): bool
	{
		return true;
	}

	final protected function isIframe(): bool
	{
		return ($this->request->get('IFRAME') === 'Y');
	}

	final protected function isDisplayDetailsFrameWrapperScript(): bool
	{
		$target = $this->getDetailsFrameWrapperTarget();
		if ($target === null)
		{
			return false;
		}

		return !$this->isIframe()
			&& $target->isAvailable()
		;
	}

	final protected function getDetailsFrameWrapperScript(): string
	{
		$target = $this->getDetailsFrameWrapperTarget();
		if ($target === null)
		{
			return '';
		}

		Asset::getInstance()->addCss($this->workAreaInvisibleCss);

		return CCrmViewHelper::getDetailFrameWrapperScript(
			$target->getEntityTypeId(),
			$target->getEntityId(),
			$target->getCategoryId(),
			$target->getCategoryId(),
		);
	}

	final protected function getPreparedComponent(): Contract\Component
	{
		$baseComponent = $this->component();

		$sidePanel = new SidePanelWrapper($baseComponent);
		$sidePanel->isPlainView = $this->isPlainView;

		$isExternal = $this->currentScope === Scope::Automation || $baseComponent->parameter('isExternal');
		$sidePanel->setParameter('isExternal', $isExternal);

		$this->configureSidePanel($sidePanel);

		return $sidePanel;
	}

	public function title(): ?string
	{
		return $this->title;
	}

	public function canUseFavoriteStar(): ?bool
	{
		return $this->canUseFavoriteStar;
	}

	protected function getDetailsFrameWrapperTarget(): ?DetailsFrameScriptTarget
	{
		return null;
	}

	/**
	 * @return Contract\PageValidator[]
	 */
	protected function getPageValidators(): array
	{
		return [
			new ScopeAvailabilityValidator($this->currentScope),
		];
	}

	protected function configureSidePanel(SidePanelWrapper $sidePanel): void
	{
	}
}
