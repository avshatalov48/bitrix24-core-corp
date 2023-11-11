<?php

namespace Bitrix\Tasks\Replicator\Template;

use Bitrix\Tasks\Replicator\Template\Conversion\Config\ConverterConfig;
use Bitrix\Tasks\Replicator\Template\Conversion\ConverterCollection;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\UFConverter;
use Bitrix\Tasks\Util\Type\DateTime;

class TemplateParameter extends Parameter
{
	public function getData(): array
	{
		$template = $this->repository->getTemplate();
		if (is_null($template))
		{
			return [];
		}
		$templateFields = $template->collectValues();
		$taskFields = array_intersect_key($templateFields, $this->getCommonFields());

		$taskFields['FORKED_BY_TEMPLATE_ID'] = $template->getId();
		$taskFields['STATUS_CHANGED_BY'] = $template->getCreatedBy();
		$taskFields['STATUS_CHANGED_DATE'] = new DateTime();

		if((int)$templateFields['RESPONSIBLE_ID'] <= 0 && count($templateFields['RESPONSIBLES'])) // for broken templates
		{
			$taskFields['RESPONSIBLE_ID'] = $taskFields['RESPONSIBLES'][0];
		}

		if ($template->getMultitask())
		{
			$taskFields['RESPONSIBLE_ID'] = $template->getCreatedBy();
		}

		return $this->getConvertedData($templateFields, $taskFields);
	}

	private function getCommonFields(): array
	{
		return [
			'TITLE' => true,
			'PRIORITY' => true,
			'TIME_ESTIMATE' => true,
			'XML_ID' => true,
			'CREATED_BY' => true,
			'RESPONSIBLE_ID' => true,
			'REQUIRE_RESULT' => true,
			'ALLOW_CHANGE_DEADLINE' => true,
			'ALLOW_TIME_TRACKING' => true,
			'TASK_CONTROL' => true,
			'MATCH_WORK_TIME' => true,
			'GROUP_ID' => true,
			'PARENT_ID' => true,
			'SITE_ID' => true,
			'DEPENDS_ON' => true,
		];
	}

	private function getConvertedData(array $templateFields, array $taskFields): array
	{
		$converters = new ConverterCollection(...ConverterConfig::getDefaultConverters());
		foreach ($templateFields as $name => $data)
		{
			$converter = $converters->find($name);
			if (!is_null($converter))
			{
				$convertedData = $converter->convert($this->repository);
				$taskFields = array_merge($taskFields, $convertedData);
			}
		}

		$ufConverter = new UFConverter();

		return array_merge($taskFields, $ufConverter->convert($this->repository));
	}
}