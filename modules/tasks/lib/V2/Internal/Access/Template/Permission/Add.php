<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Template\Permission;

use Attribute;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\V2\Internal\Access\Adapter\TemplateModelAdapter;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Add implements AttributeAccessInterface
{
	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		$accessController = TemplateAccessController::getInstance($context->getUserId());

		$adapter = new TemplateModelAdapter($entity);
		$model = $adapter->transform();

		return $accessController->check(ActionDictionary::ACTION_TEMPLATE_SAVE, null, $model);
	}
}
