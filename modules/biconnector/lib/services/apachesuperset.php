<?php
namespace Bitrix\BIConnector\Services;

use Bitrix\BIConnector\DataSourceConnector\ApacheSupersetFieldDto;
use Bitrix\BIConnector\DataSourceConnector\Connector;
use Bitrix\BIConnector\DataSourceConnector\FieldDto;

class ApacheSuperset extends MicrosoftPowerBI
{
	protected static $serviceId = 'superset';
	private static string $consumer = 'bi-ctr';

	/**
	 * Event OnBIConnectorCreateServiceInstance habler.
	 *
	 * @param \Bitrix\Main\Event $event Event parameters.
	 *
	 * @return \Bitrix\Main\EventResult
	 */
	public static function createServiceInstance(\Bitrix\Main\Event $event)
	{
		$service = null;

		[$serviceId, $manager] = $event->getParameters();
		if ($serviceId === self::$consumer)
		{
			$service = new static($manager);
		}

		return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $service);
	}

	/**
	 * @param string $fieldName
	 * @param array $fieldInfo
	 *
	 * @return FieldDto
	 */
	protected function prepareFieldDto(string $fieldName, array $fieldInfo): FieldDto
	{
		$type = $fieldInfo['FIELD_TYPE_EX'] ?? $fieldInfo['FIELD_TYPE'];

		$parentDto = parent::prepareFieldDto($fieldName, $fieldInfo);

		return new ApacheSupersetFieldDto(
			$parentDto->id,
			$parentDto->name,
			$parentDto->description,
			$type ?? 'string',
			$parentDto->isMetric,
			$parentDto->isPrimary,
			$parentDto->isSystem,
			$parentDto->aggregationType,
			$parentDto->groupKey,
			$parentDto->groupConcat,
			$parentDto->groupCount
		);
	}

	/**
	 * Returns all available data sources descriptions.
	 *
	 * @return array
	 */
	protected function loadDataSourceConnectors(): array
	{
		$dataSourceConnectors = parent::loadDataSourceConnectors();

		$dataSources = [];
		$event = new \Bitrix\Main\Event('biconnector', 'OnBIBuilderDataSources', [
			$this->manager,
			&$dataSources,
			$this->languageId,
		]);
		$event->send();

		foreach ($dataSources as $source)
		{
			if ($source instanceof Connector\Base)
			{
				$dataSourceConnectors[$source->getName()] = $source;
			}
		}

		return $dataSourceConnectors;
	}

	/**
	 *
	 * @deprecated
	 *
	 * Type mapping is realised into Bitrix\BIConnector\DataSourceConnector\ApacheSupersetFieldDto
	 *
	 * Returns trino supported type by internal.
	 *
	 * @param string $internalType Internal (CSQLWhere) type.
	 * @param null $fieldName Field name - ID, TITLE, UF_CRM_100 etc.
	 *
	 * @see \CSQLWhere
	 */
	protected function mapType($internalType, $fieldName = null): string
	{
		if (is_string($fieldName) && str_starts_with($fieldName, 'UF_'))
		{
			return 'STRING';
		}

		return match ($internalType)
		{
			'file', 'enum', 'int' => 'INT',
			'double' => 'DOUBLE',
			'date' => 'DATE',
			'datetime' => 'DATETIME',
			'bool' => 'BOOLEAN',
			'array_string' => 'ARRAY_STRING',
			'map_string' => 'MAP_STRING',
			default => 'STRING',
		};
	}
}
