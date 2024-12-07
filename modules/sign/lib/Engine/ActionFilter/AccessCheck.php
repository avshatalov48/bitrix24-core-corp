<?php
namespace Bitrix\Sign\Engine\ActionFilter;

use Bitrix\Main;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Item\Access\SimpleAccessibleItemWithOwner;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;
use BItrix\Sign\Type\Access\AccessibleItemType;

final class AccessCheck extends Main\Engine\ActionFilter\Base
{
	public const PREFILTER_KEY = 'ACCESS_CHECK';
	private const ERROR_INVALID_AUTHENTICATION = 'invalid_authentication';
	/**
	 * @var string[]
	 */
	protected array $b2c2b2eMap;
	/**
	 * @var int[]|string[]
	 */
	protected array $b2e2b2cMap;

	private AccessController $accessController;
	private DocumentRepository $documentRepository;
	/** @var array<string, RuleWithPayload>  */
	private array $rules = [];

	public function __construct()
	{
		parent::__construct();
		$this->accessController = new AccessController(Main\Engine\CurrentUser::get()->getId());

		$this->b2c2b2eMap = ActionDictionary::getRepeatActionB2b2B2e();
		$this->b2e2b2cMap = array_flip($this->b2c2b2eMap);

		$this->documentRepository = Container::instance()->getDocumentRepository();
	}

	public function addRule(
		string $accessPermission,
		?string $itemType = null,
		?string $itemIdRequestKey = null,
	): self
	{
		$this->rules[$accessPermission] = new RuleWithPayload($accessPermission, $itemType, $itemIdRequestKey);

		return $this;
	}

	public function onBeforeAction(Main\Event $event): ?Main\EventResult
	{
		foreach ($this->rules as $rule)
		{
			if ($this->checkRule($rule))
			{
				continue;
			}

			$mirrorRule = $this->getMirrorRule($rule->accessPermission);
			if (!$mirrorRule || !$this->checkRule($mirrorRule))
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
				self::ERROR_INVALID_AUTHENTICATION)
		);
		return new Main\EventResult(Main\EventResult::ERROR, null, null, $this);
	}

	private function checkRule(RuleWithPayload $rule): bool
	{
		if (!isset($rule->passes))
		{
			$rule->passes = $this->accessController->check($rule->accessPermission,  $this->createAccessibleItem($rule));
		}

		return $rule->passes;
	}

	private function getMirrorRule(string $accessPermission): ?RuleWithPayload
	{
		return $this->rules[$this->getMirrorAccessPermission($accessPermission)] ?? null;
	}

	private function getMirrorAccessPermission(string $accessPermission): ?string
	{
		return $this->b2c2b2eMap[$accessPermission] ?? $this->b2e2b2cMap[$accessPermission] ?? null;
	}

	private function createAccessibleItem(RuleWithPayload $rule): ?SimpleAccessibleItemWithOwner
	{
		if (empty($rule->itemType) || empty($rule->itemIdRequestKey))
		{
			return null;
		}

		$id = $this->getAction()->getController()->getRequest()->getJsonList()->get($rule->itemIdRequestKey);
		if (empty($id))
		{
			return null;
		}

		if ($rule->itemType === AccessibleItemType::DOCUMENT)
		{
			$document = $this->documentRepository->getByUid($id);
			if (!$document)
			{
				return null;
			}

			return new SimpleAccessibleItemWithOwner($document->id, $document->createdById, $document->entityId);
		}

		return null;
	}
}
