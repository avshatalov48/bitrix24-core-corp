<?php

namespace Bitrix\Rpa\Integration\Bizproc\Automation\Engine;

use Bitrix\Bizproc\Automation\Engine\TemplateScope;
use Bitrix\Rpa\Integration\Bizproc\Document\Item;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Model\TypeTable;

class TemplatesScheme extends \Bitrix\Bizproc\Automation\Engine\TemplatesScheme
{
	public function build(): void
	{
		$typesIterator = TypeTable::getList([]);

		/** @var Type $type */
		while ($type = $typesIterator->fetchObject())
		{
			$documentType = Item::makeComplexType($type->getId());

			foreach($type->getStages() as $stage)
			{
				$scope = new TemplateScope($documentType, null, $stage->getId());
				$scope->setNames(null, $stage->getName());
				$scope->setStatusColor($stage->getColor());

				$this->addTemplate($scope);
			}
		}
	}
}