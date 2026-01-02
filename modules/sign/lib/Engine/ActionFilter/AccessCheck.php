<?php

namespace Bitrix\Sign\Engine\ActionFilter;

use Bitrix\Main;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Attribute\Access\LogicAnd;
use Bitrix\Sign\Attribute\Access\LogicOr;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Item\Document\TemplateCollection;
use Bitrix\Sign\Item\Document\TemplateFolderCollection;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Item\SignersListCollection;
use Bitrix\Sign\Repository\Document\TemplateFolderRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\SignersListService;
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
	private readonly TemplateFolderRepository $templateFolderRepository;
	private readonly SignersListService $signersListService;

	public function __construct()
	{
		parent::__construct();
		$this->accessController = new AccessController(Main\Engine\CurrentUser::get()->getId());

		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->templateRepository = Container::instance()->getDocumentTemplateRepository();
		$this->templateFolderRepository = Container::instance()->getTemplateFolderRepository();
		$this->signersListService = Container::instance()->getSignersListService();
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
			$rule->passes = $this->checkPermission($rule->accessPermission,	$this->createAccessibleItems($rule));
		}

		return $rule->passes;
	}


	/**
	 * @param RuleWithPayload $rule
	 *
	 * @return list<Main\Access\AccessibleItem>
	 */
	private function createAccessibleItems(RuleWithPayload $rule): array
	{
		if (empty($rule->itemType) || empty($rule->itemIdOrUidRequestKey))
		{
			return [];
		}

		$idOrUid = $this->getRequestJson()->get($rule->itemIdOrUidRequestKey);
		if ($idOrUid === null)
		{
			return [];
		}

		$idsOrUids = $this->convertIdOrUidToArray($idOrUid);
		$items = match ($rule->itemType)
		{
			AccessibleItemType::DOCUMENT => $this->getDocumentByIds($idsOrUids),
			AccessibleItemType::TEMPLATE => $this->getTemplatesByIds($idsOrUids),
			AccessibleItemType::TEMPLATE_FOLDER => $this->getTemplateFoldersByIds($idsOrUids),
			AccessibleItemType::SIGNERS_LIST => $this->getSignersListsByIds($idsOrUids),
			default => null,
		};

		$accessItems = [];
		if ($items instanceof \Traversable)
		{
			foreach ($items as $item)
			{
				$accessItem = Container::instance()->getAccessibleItemFactory()->createFromItem($item);
				if ($accessItem instanceof Main\Access\AccessibleItem)
				{
					$accessItems[] = $accessItem;
				}
			}
		}

		return $accessItems;
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


	private function convertIdOrUidToArray(mixed $idOrUid): array
	{
		if (is_array($idOrUid))
		{
			return $idOrUid;
		}

		if (is_scalar($idOrUid) && !empty($idOrUid))
		{
			return [$idOrUid];
		}

		return [];
	}

	private function getDocumentByIds(array $idsOrUids): DocumentCollection
	{
		$firstIdOrUid = $idsOrUids[array_key_first($idsOrUids)] ?? null;
		if (empty($firstIdOrUid))
		{
			return new DocumentCollection();
		}

		if (is_numeric($firstIdOrUid))
		{
			$ids = array_map(static fn(mixed $value) => (int)$value, $idsOrUids);

			return $this->documentRepository->listByIds($ids);
		}
		elseif (is_string($firstIdOrUid))
		{
			$uids = array_map(static fn(mixed $value) => (string)$value, $idsOrUids);

			return $this->documentRepository->listByUids($uids);
		}

		return new DocumentCollection();
	}

	private function getTemplatesByIds(array $idsOrUids): TemplateCollection
	{
		$firstIdOrUid = $idsOrUids[array_key_first($idsOrUids)] ?? null;
		if (empty($firstIdOrUid))
		{
			return new TemplateCollection();
		}

		if (is_numeric($firstIdOrUid))
		{
			$ids = array_map(static fn(mixed $value) => (int)$value, $idsOrUids);

			return $this->templateRepository->getByIds($ids);
		}
		elseif (is_string($firstIdOrUid))
		{
			$uids = array_map(static fn(mixed $value) => (string)$value, $idsOrUids);

			return $this->templateRepository->listByUids($uids);
		}

		return new TemplateCollection();
	}

	private function getTemplateFoldersByIds(array $idsOrUids): TemplateFolderCollection
	{
		$firstIdOrUid = $idsOrUids[array_key_first($idsOrUids)] ?? null;
		if (empty($firstIdOrUid))
		{
			return new TemplateFolderCollection();
		}

		if (is_numeric($firstIdOrUid))
		{
			$ids = array_map(static fn(mixed $value) => (int)$value, $idsOrUids);

			return $this->templateFolderRepository->getByIds($ids);
		}

		return new TemplateFolderCollection();
	}

	private function getSignersListsByIds(array $idsOrUids): SignersListCollection
	{
		$firstIdOrUid = $idsOrUids[array_key_first($idsOrUids)] ?? null;

		if (empty($firstIdOrUid))
		{
			return new SignersListCollection();
		}

		if (is_numeric($firstIdOrUid))
		{
			$ids = array_map(static fn(mixed $value) => (int)$value, $idsOrUids);

			return $this->signersListService->listByIds($ids);
		}

		return new SignersListCollection();
	}

	/**
	 * @param string $accessPermission
	 * @param list<Main\Access\AccessibleItem> $accessibleItems
	 *
	 * @return bool
	 */
	private function checkPermission(string $accessPermission, array $accessibleItems): bool
	{
		if (empty($accessibleItems))
		{
			return $this->accessController->check($accessPermission);
		}

		foreach ($accessibleItems as $accessibleItem)
		{
			if (!$this->accessController->check($accessPermission, $accessibleItem))
			{
				return false;
			}
		}

		return true;
	}
}
