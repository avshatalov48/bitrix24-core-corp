<?php
namespace Bitrix\BIConnector\Services;

use Bitrix\BIConnector\DataSourceConnector\ApacheSupersetFieldDto;
use Bitrix\BIConnector\DataSourceConnector\Connector;
use Bitrix\BIConnector\DataSourceConnector\FieldDto;

class ApacheSuperset extends MicrosoftPowerBI
{
	public const TYPE_STRING = 'STRING';
	public const TYPE_INT = 'INT';
	public const TYPE_DOUBLE = 'DOUBLE';
	public const TYPE_BOOLEAN = 'BOOLEAN';
	public const TYPE_DATE = 'DATE';
	public const TYPE_DATETIME = 'DATETIME';
	public const TYPE_ARRAY_STRING = 'ARRAY_STRING';
	public const TYPE_MAP_STRING = 'MAP_STRING';


	protected static $serviceId = 'superset';

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
			$parentDto->groupCount,
			$parentDto->isValueSplitable
		);
	}

	/**
	 * @param string $name
	 *
	 * @return Connector\Base|null
	 */
	public function getDataSourceConnector(string $name): ?Connector\Base
	{
		$connector = parent::getDataSourceConnector($name);

		if (!empty($connector))
		{
			return $connector;
		}

		$dataSources = [];
		$event = new \Bitrix\Main\Event('biconnector', 'OnBIBuilderDataSources', [
			$this->manager,
			&$dataSources,
			$this->languageId,
			$name,
		]);
		$event->send();

		return (!empty($dataSources[$name]) && $dataSources[$name] instanceof Connector\Base) ? $dataSources[$name] : null;
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
			null,
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
			return self::TYPE_STRING;
		}

		return match ($internalType)
		{
			'file', 'enum', 'int' => self::TYPE_INT,
			'double' => self::TYPE_DOUBLE,
			'date' => self::TYPE_DATE,
			'datetime' => self::TYPE_DATETIME,
			'bool' => self::TYPE_BOOLEAN,
			'array_string' => self::TYPE_ARRAY_STRING,
			'map_string' => self::TYPE_MAP_STRING,
			default => self::TYPE_STRING,
		};
	}
}
