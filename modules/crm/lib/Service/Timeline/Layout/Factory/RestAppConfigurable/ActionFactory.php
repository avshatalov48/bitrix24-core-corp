<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Factory\RestAppConfigurable;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ActionDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\EventHandler;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Rest\AppTable;

class ActionFactory
{
	public function __construct(
		private readonly Item\Configurable $item,
		private readonly string|null $clientId = null,
		private readonly int|null $restAppId = null,
	)
	{
	}

	public function createByDto(ActionDto|null $actionDto): Action|null
	{
		if ($actionDto === null || $actionDto->hasValidationErrors())
		{
			return null;
		}

		if ($actionDto->type === ActionDto::TYPE_REDIRECT)
		{
			$uri = new Uri($actionDto->uri);
			$action = new Action\Redirect($uri);
			if ($uri->getHost()) // open external links in new window
			{
				$action->addActionParamString('target', '_blank');
			}

			return $action;
		}
		if (
			$actionDto->type === ActionDto::TYPE_REST_EVENT
			|| $actionDto->type === ActionDto::TYPE_OPEN_REST_APP
		)
		{
			$actionParams = $actionDto->actionParams ?? [];
			if ($actionDto->type === ActionDto::TYPE_REST_EVENT)
			{
				$actionParams['entityTypeId'] = $this->getContext()->getEntityTypeId();
				$actionParams['entityId'] = $this->getContext()->getEntityId();
				$actionParams['activityId'] = $this->getActivityId();
				$actionParams['id'] = $actionDto->id;

				$actionParams['APP_ID'] = $this->getRestAppClientId();
				$signedParams = EventHandler::signParams($actionParams);
				$animation = null;
				switch ($actionDto->animationType)
				{
					case ActionDto::ANIMATION_TYPE_DISABLE:
						$animation = Action\Animation::disableBlock()->setForever(true);
						break;
					case ActionDto::ANIMATION_TYPE_LOADER:
						$animation = Action\Animation::showLoaderForItem()->setForever(true);
						break;
				}

				return (new Action\RunAjaxAction('crm.activity.configurable.emitRestEvent'))
					->addActionParamString('signedParams', $signedParams)
					->setAnimation($animation)
				;

			}

			$action = $this->createOpenAppAction();
			foreach ($actionParams as $actionParamName => $actionParamValue)
			{
				$action->addActionParamString((string)$actionParamName, (string)$actionParamValue);
			}

			if ($actionDto->sliderParams)
			{
				if ($actionDto->sliderParams->title)
				{
					$action->addActionParamString('bx24_title', $actionDto->sliderParams->title);
				}
				if ($actionDto->sliderParams->width)
				{
					$action->addActionParamInt('bx24_width', $actionDto->sliderParams->width);
				}
				if ($actionDto->sliderParams->leftBoundary)
				{
					$action->addActionParamInt('bx24_leftBoundary', $actionDto->sliderParams->leftBoundary);
				}
				$labelParams = [];
				if ($actionDto->sliderParams->labelText)
				{
					$labelParams['text'] = $actionDto->sliderParams->labelText;
				}
				if ($actionDto->sliderParams->labelColor)
				{
					$labelParams['color'] = $actionDto->sliderParams->labelColor;
				}
				if ($actionDto->sliderParams->labelBgColor)
				{
					$labelParams['bgColor'] = $actionDto->sliderParams->labelBgColor;
				}
				if (!empty($labelParams))
				{
					$action->addActionParamString('bx24_label', Json::encode($labelParams));
				}
			}

			return $action;
		}

		return null;
	}

	public function createOpenAppAction(): Action\JsEvent
	{
		$action = (new Action\JsEvent('Activity:ConfigurableRestApp:OpenApp'));
		$action->addActionParamInt('restAppId',$this->getRestAppId());
		$this->appendContextActionParams($action);

		return $action;
	}

	private function appendContextActionParams(Action $action): void
	{
		$action->addActionParamInt('entityTypeId', $this->getContext()->getEntityTypeId());
		$action->addActionParamInt('entityId', $this->getContext()->getEntityId());
		$action->addActionParamInt('activityId', $this->getActivityId());
	}

	private function getContext(): Context
	{
		return $this->item->getContext();
	}

	private function getModel(): Item\Model
	{
		return $this->item->getModel();
	}

	private function getActivityId(): int
	{
		return $this->getModel()->getAssociatedEntityId();
	}

	private function getRestAppClientId(): string|null
	{
		return $this->clientId;
	}

	private function getRestAppId(): string|null
	{
		return $this->restAppId;
	}
}
