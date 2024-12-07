<?php

namespace Bitrix\Tasks\Replication\Template\Common\Parameter;

use Bitrix\Tasks\Replication\Template\AbstractParameter;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Config\ConverterConfig;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\ConverterCollection;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\UfConverter;
use Bitrix\Tasks\Util\Type\DateTime;

class TemplateParameter extends AbstractParameter
{
	public function getData(): array
	{
		$template = $this->repository->getEntity();
		if (is_null($template))
		{
			return [];
		}
		$templateFields = $template->collectValues();
		$taskFields = array_intersect_key($templateFields, $this->getCommonFields());

		$taskFields['FORKED_BY_TEMPLATE_ID'] = $template->getId();
		$taskFields['STATUS_CHANGED_BY'] = $template->getCreatedBy();
		$taskFields['STATUS_CHANGED_DATE'] = new DateTime();
		$templateFields['RESPONSIBLES'] = unserialize($templateFields['RESPONSIBLES'], ['allowed_classes' => false]);

		if((int)$templateFields['RESPONSIBLE_ID'] <= 0) // for broken templates
		{
			$taskFields['RESPONSIBLE_ID'] = is_countable($taskFields['RESPONSIBLES']) ? $taskFields['RESPONSIBLES'][0] : 0;
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

		$ufConverter = new UfConverter();

		return array_merge($taskFields, $ufConverter->convert($this->repository));
	}
}