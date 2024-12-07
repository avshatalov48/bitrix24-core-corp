<?php

namespace Bitrix\Tasks\Flow\Access;

/**
 * @method canRead(): bool
 * @method canCreate(): bool
 * @method canDelete(): bool
 * @method canUpdate(): bool
 */
class SimpleFlowAccessController extends FlowAccessController
{
	protected ?FlowModel $model = null;
	protected ?array $params = null;

	public function __construct(int $userId, ?FlowModel $model = null, ?array $params = null)
	{
		parent::__construct($userId);
		$this->model = $model;
		$this->params = $params;
	}

	public function __call(string $name, array $args): bool
	{
		if ($this->model === null)
		{
			return false;
		}

		$operation = substr($name, 0, 3);
		if ($operation !== 'can')
		{
			return false;
		}

		$action = lcfirst(substr($name, 3));
		$rule = 'flow_' . $action;
		if (FlowAction::has($rule) === false)
		{
			return false;
		}

		return $this->check($rule, $this->model, $this->params);
	}
}