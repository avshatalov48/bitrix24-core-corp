<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\TimelineType;

class Maker
{
	/**
	 * Prepare data about an timeline entry. The data is used in interface to display timeline event
	 *
	 * @param array $data
	 * @param array|null $options = [
	 *     'ENABLE_USER_INFO' => false, // prepare detailed author info (link, image, name). Disabled by default
	 * ];
	 *
	 * @return array
	 */
	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		$timelineEntryType = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		$entityTypeId = (int)($data['ASSOCIATED_ENTITY_TYPE_ID'] ?? null);

		$presenter = $this->getPresenterForEntityType($timelineEntryType, $entityTypeId);

		return $presenter->prepareHistoryDataModel($data, $options);
	}

	protected function getPresenterForEntityType(int $timelineEntryType, int $entityTypeId): Presenter
	{
		$implementation = $this->getEntityImplementation($entityTypeId);

		return $this->getPresenter($timelineEntryType, $implementation);
	}

	public function getEntityImplementation(int $entityTypeId): EntityImplementation
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $this->isFactoryBasedApproachSupported($entityTypeId))
		{
			return new EntityImplementation\FactoryBased($factory);
		}

		return new EntityImplementation($entityTypeId);
	}

	/**
	 * @todo remove when deal and lead factories are ready to work with items
	 * @see \Bitrix\Crm\Service\Factory\Deal::getItems()
	 * @see \Bitrix\Crm\Service\Factory\Lead::getItems()
	 *
	 * @param int $entityTypeId
	 *
	 * @return bool
	 */
	protected function isFactoryBasedApproachSupported(int $entityTypeId): bool
	{
		return \CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId);
	}

	protected function getPresenter(int $timelineEntryType, EntityImplementation $entityImplementation): Presenter
	{
		if ($timelineEntryType === TimelineType::CREATION)
		{
			return new Presenter\Creation($entityImplementation);
		}
		if ($timelineEntryType === TimelineType::RESTORATION)
		{
			return new Presenter\Restoration($entityImplementation);
		}
		if ($timelineEntryType === TimelineType::MODIFICATION)
		{
			return new Presenter\Modification($entityImplementation);
		}
		if ($timelineEntryType === TimelineType::CONVERSION)
		{
			return new Presenter\Conversion($entityImplementation);
		}
		if ($timelineEntryType === TimelineType::FINAL_SUMMARY)
		{
			return new Presenter\FinalSummary($entityImplementation);
		}
		if ($timelineEntryType === TimelineType::FINAL_SUMMARY_DOCUMENTS)
		{
			return new Presenter\FinalSummaryDocuments($entityImplementation);
		}
		if ($timelineEntryType === TimelineType::SIGN_DOCUMENT)
		{
			return new Presenter\SignDocument($entityImplementation);
		}
		if ($timelineEntryType === TimelineType::SIGN_DOCUMENT_LOG)
		{
			return new Presenter\SignDocumentLog($entityImplementation);
		}

		return new Presenter($entityImplementation);
	}
}
