<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\ContentType;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\Csrf;
use Bitrix\Main\Engine\ActionFilter\FilterType;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\V2\Internal\Service\TaskLegacyFeatureService;

class LegacyComment extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.LegacyComment.get
	 */
	#[Csrf(type: FilterType::DisablePrefilter)]
	#[ContentType(type: FilterType::DisablePrefilter)]
	#[CloseSession]
	public function getAction(
		$taskId,
		TaskLegacyFeatureService $legacyFeatureService,
	): ?HttpResponse
	{
		$taskId = (int)$taskId;

		$model = TaskModel::createFromId($taskId);
		$canRead = TaskAccessController::getInstance($this->userId)
			->check(ActionDictionary::ACTION_TASK_READ, $model)
		;

		if (!$canRead)
		{
			$this->addError(new Error('Access denied'));
		}

		if (!$legacyFeatureService->hasForumComments($taskId))
		{
			return null;
		}

		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:forum.comments',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'FORUM_ID' => \Bitrix\Tasks\Integration\Forum\Task\Comment::getForumId(),
					'ENTITY_TYPE' => "TK",
					'ENTITY_ID' => $taskId,
					'ENTITY_XML_ID' => 'TASK_' . $taskId,
					'PUBLIC_MODE' => true,
					'SHOW_RATING' => 'N',
					'SHOW_POST_FORM' => 'N',
					'URL_TEMPLATES_PROFILE_VIEW' => "/company/personal/user/#user_id#/",
				],
				'IFRAME_MODE' => true,
				'USE_UI_TOOLBAR' => 'Y',
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}
}
