<?php

namespace Bitrix\Sign\Engine\ActionFilter;

use Bitrix\Main;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicAnd;
use Bitrix\Sign\Attribute\Access\LogicOr;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;
use BItrix\Sign\Type\Access\AccessibleItemType;

final class AccessCheck extends Main\Engine\ActionFilter\Base
{
	public const PREFILTER_KEY = 'ACCESS_CHECK';
	private const ERROR_INVALID_AUTHENTICATION = 'invalid_authentication';

	private AccessController $accessController;
	private readonly DocumentRepository $documentRepository;
	/** @var array<string, RuleWithPayload>  */
	private array $rules = [];
	/** @var list<LogicRule>  */
	private array $logicRules = [];
	private readonly TemplateRepository $templateRepository;

	public function __construct()
	{
		parent::__construct();
		$this->accessController = new AccessController(Main\Engine\CurrentUser::get()->getId());

		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->templateRepository = Container::instance()->getDocumentTemplateRepository();
	}

	public function addRuleFromAttribute(ActionAccess|LogicOr|LogicAnd $attribute): self
	{
		if ($attribute instanceof ActionAccess)
		{
			return $this->addRule($attribute->permission, $attribute->itemType, $attribute->itemIdOrUidRequestKey);
		}

		return $this->addLogicRuleFromAttribute($attribute);
	}

	private function addRule(
		string $accessPermission,
		?string $itemType = null,
		?string $itemIdOrUidRequestKey = null,
	): self
	{
		$this->rules[$accessPermission] = $this->createRuleWithPayload($accessPermission, $itemType, $itemIdOrUidRequestKey);

		return $this;
	}

	private function addLogicRuleFromAttribute(LogicAnd|LogicOr $logicAttribute): self
	{
		$rules = array_map(
			fn(ActionAccess $condition) => $this->createRuleWithPayload(
				$condition->permission,
				$condition->itemType,
				$condition->itemIdOrUidRequestKey
			),
			$logicAttribute->conditions,
		);

		$this->logicRules[] = new LogicRule(
			$logicAttribute instanceof LogicOr ? AccessController::RULE_OR : AccessController::RULE_AND,
			...$rules,
		);

		return $this;
	}

	public function onBeforeAction(Main\Event $event): ?Main\EventResult
	{
		foreach ($this->rules as $rule)
		{
			if ($this->hasInvalidItemIdentifier($rule))
			{
				return $this->getAuthErrorResult();
			}
			if (!$this->checkRuleWithPayload($rule))
			{
				return $this->getAuthErrorResult();
			}
		}

		foreach ($this->logicRules as $logicRule)
		{
			if (!$this->checkLogicRule($logicRule))
			{
				return $this->getAuthErrorResult();
			}
		}

		return null;
	}

	private function getAuthErrorResult(): Main\EventResult
	{
		Main\Context::getCurrent()->getResponse()->setStatus(401);
		$this->addError(new Main\Error(
			Main\Localization\Loc::getMessage("MAIN_ENGINE_FILTER_AUTHENTICATION_ERROR"),
			self::ERROR_INVALID_AUTHENTICATION),
		);

		return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
	}

	private function checkLogicRule(LogicRule $rule): bool
	{
		if ($rule->logicOperator === AccessController::RULE_OR)
		{
			foreach ($rule->rules as $ruleWithPayload)
			{
				if ($this->checkRuleWithPayload($ruleWithPayload))
				{
					return true;
				}
			}

			return false;
		}

		foreach ($rule->rules as $ruleWithPayload)
		{
			if (!$this->checkRuleWithPayload($ruleWithPayload))
			{
				return false;
			}
		}

		return true;
	}

	private function checkRuleWithPayload(RuleWithPayload $rule): bool
	{
		if (!isset($rule->passes))
		{
			$rule->passes = $this->accessController->check($rule->accessPermission,  $this->createAccessibleItem($rule));
		}

		return $rule->passes;
	}

	private function createAccessibleItem(RuleWithPayload $rule): ?Main\Access\AccessibleItem
	{
		if (empty($rule->itemType) || empty($rule->itemIdOrUidRequestKey))
		{
			return null;
		}

		$idOrUid = $this->getRequestJson()->get($rule->itemIdOrUidRequestKey);
		if ($idOrUid === null)
		{
			return null;
		}

		$item = null;
		if ($rule->itemType === AccessibleItemType::DOCUMENT)
		{
			if (is_numeric($idOrUid))
			{
				$item = $this->documentRepository->getById((int)$idOrUid);
			}
			elseif (is_string($idOrUid))
			{
				$item = $this->documentRepository->getByUid($idOrUid);
			}
		}

		if ($rule->itemType === AccessibleItemType::TEMPLATE)
		{
			if (is_numeric($idOrUid))
			{
				$item = $this->templateRepository->getById((int)$idOrUid);
			}
			elseif (is_string($idOrUid))
			{
				$item = $this->templateRepository->getByUid($idOrUid);
			}
		}
		if ($item === null)
		{
			return null;
		}

		return Container::instance()->getAccessibleItemFactory()->createFromItem($item);
	}

	private function createRuleWithPayload(string $accessPermission, ?string $itemType, ?string $itemIdOrUidRequestKey): RuleWithPayload
	{
		return new RuleWithPayload($accessPermission, $itemType, $itemIdOrUidRequestKey);
	}

	private function hasInvalidItemIdentifier(RuleWithPayload $rule): bool
	{
		return $rule->itemType !== null &&
			(
				$rule->itemIdOrUidRequestKey === null
				|| $this->getRequestJson()->get($rule->itemIdOrUidRequestKey) === null
			);
	}

	private function getRequestJson(): Main\Type\ParameterDictionary
	{
		return $this->getAction()->getController()->getRequest()->getJsonList();
	}
}
