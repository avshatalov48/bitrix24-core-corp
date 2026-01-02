<?php

namespace Bitrix\Crm\ItemMiniCard\Builder;

use Bitrix\Crm\ItemMiniCard\Factory\ProviderFactory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Security\Random;
use Bitrix\Main\UI\Extension;
use CCrmOwnerType;
use CCrmViewHelper;

final class MiniCardHtmlBuilder
{
	private ?string $title = null;
	private string $linkClassName = '';
	private bool $isRenderScript = true;

	public function __construct(
		private readonly int $entityTypeId,
		private readonly int $entityId,
		private readonly ProviderFactory $providerFactory = new ProviderFactory(),
	)
	{
	}

	public function build(): string
	{
		if (!Container::getInstance()->getUserPermissions()->item()->canRead($this->entityTypeId, $this->entityId))
		{
			return CCrmViewHelper::GetHiddenEntityCaption($this->entityTypeId);
		}

		$id = $this->getId();
		$title = htmlspecialcharsbx($this->getTitle());
		$link = htmlspecialcharsbx($this->getLink());
		$linkClassName = htmlspecialcharsbx($this->linkClassName);

		if (!$this->providerFactory->isAvailable($this->entityTypeId))
		{
			return <<<COMMON_LINK
				<a
					id="{$id}"
					href="{$link}"
					target="_blank"
					title="{$title}"
					class="{$linkClassName}"
				>
					{$title}
				</a>
			COMMON_LINK;
		}

		Extension::load('crm.mini-card');

		$script = '';
		if ($this->isRenderScript)
		{
			$script = <<<JS
				<script>
					BX.ready(() => {
						BX.Runtime
							.loadExtension('crm.mini-card')
							.then(({ EntityMiniCard }) => {
								const bindElement = document.querySelector('#{$id}');
								if (bindElement)
								{
									new EntityMiniCard({
										entityTypeId: {$this->entityTypeId},
										entityId: {$this->entityId},
										bindElement,
									});
								}
							});
					});
				</script>
			JS;
		}

		return <<<MINI_CARD
			<a
				id="{$id}"
				href="{$link}"
				target="_blank"
				title="{$title}"
				class="{$linkClassName}"
				data-mini-card="true"
				data-entity-type-id="{$this->entityTypeId}"
				data-entity-id="{$this->entityId}"
			>
				{$title}
			</a>
			{$script}
		MINI_CARD;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function setLinkClassName(string $linkClassName): self
	{
		$this->linkClassName = $linkClassName;

		return $this;
	}

	public function setIsRenderScript(bool $isRenderScript): self
	{
		$this->isRenderScript = $isRenderScript;

		return $this;
	}

	private function getId(): string
	{
		$entityName = CCrmOwnerType::ResolveName($this->entityTypeId);
		$salt = Random::getString(16);

		return "{$entityName}_{$this->entityId}_{$salt}";
	}

	private function getTitle(): string
	{
		if (empty($this->title))
		{
			return CCrmOwnerType::GetCaption($this->entityTypeId, $this->entityId, false);
		}

		return $this->title;
	}

	private function getLink(): ?string
	{
		return Container::getInstance()->getRouter()->getItemDetailUrl($this->entityTypeId, $this->entityId);
	}
}
