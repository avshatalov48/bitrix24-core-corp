<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Section;

use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\EntityForm\ScopeAccess;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Section\AbstractParameters;
use Bitrix\Crm\Integration\UI\EntityEditor\Configuration;
use Bitrix\Crm\Result;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

abstract class AbstractFunction implements AIFunction
{
	public function __construct(
		protected readonly int $currentUserId,
	)
	{
	}

	public function isAvailable(): bool
	{
		return true;
	}

	final public function invoke(...$args): Result
	{
		$parameters = $this->parseParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		if (!Loader::includeModule('ui'))
		{
			return Result::failModuleNotInstalled('ui');
		}

		$extras = [
			'USER_ID' => $this->currentUserId,
			'CATEGORY_ID' => $parameters->categoryId,
		];

		$config = EntityEditorConfig::createWithCurrentScope($parameters->entityTypeId, $extras);
		if (!$this->canUpdateView($config))
		{
			return Result::failAccessDenied();
		}

		$configuration = $config->getConfiguration(useDefaultIfNotExists: true);
		if ($configuration === null)
		{
			return Result::failEntityTypeNotSupported($parameters->entityTypeId);
		}

		return $this->doInvoke($parameters, $configuration);
	}

	protected function canUpdateView(EntityEditorConfig $config): bool
	{
		$permissions = Container::getInstance()->getUserPermissions($this->currentUserId)->entityEditor();
		$scopeAccess = ScopeAccess::getInstance('crm', $this->currentUserId);

		return match ($config->getScope()) {
			EntityEditorConfigScope::COMMON => $permissions->canEditCommonView(),
			EntityEditorConfigScope::PERSONAL => $permissions->canEditPersonalViewForUser($this->currentUserId),
			EntityEditorConfigScope::CUSTOM => $scopeAccess->canUpdate($config->getUserScopeId()),
			default => false,
		};
	}

	abstract protected function doInvoke(AbstractParameters $parameters, Configuration $configuration): Result;

	abstract protected function parseParameters(array $args): AbstractParameters;
}
