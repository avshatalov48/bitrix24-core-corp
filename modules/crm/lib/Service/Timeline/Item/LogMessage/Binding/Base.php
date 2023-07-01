<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\Binding;

use Bitrix\Crm\Service\Timeline\Item\AssociatedEntityModel;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Main\Web\Uri;

abstract class Base extends LogMessage
{
	public function getIconCode(): ?string
	{
		return Icon::RELATION;
	}

	final public function getContentBlocks(): ?array
	{
		$result = [];

		/** @var AssociatedEntityModel $model */
		$model = $this->getAssociatedEntityModel();

		$result['content'] = (new LineOfTextBlocks())
			->addContentBlock(
				'title',
				ContentBlockFactory::createTitle((string)$model->get('ENTITY_TYPE_CAPTION'))
			)
			->addContentBlock(
				'data',
				ContentBlockFactory::createTextOrLink(
					(string)$model->get('TITLE'),
					empty($model->get('SHOW_URL'))
						? null
						: new Redirect(new Uri($model->get('SHOW_URL')))
				)->setIsBold(true)
			);

		return $result;
	}
}
