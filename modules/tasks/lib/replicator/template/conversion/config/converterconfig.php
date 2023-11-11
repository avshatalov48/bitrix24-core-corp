<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Config;

use Bitrix\Tasks\Replicator\Template\Conversion\Converter;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\DeadlineConverter;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\DependenceConverter;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\DescriptionBBCodeConverter;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\DescriptionConverter;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\EndDatePlanConverter;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\MemberConverter;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\StartDatePlanConverter;
use Bitrix\Tasks\Replicator\Template\Conversion\Converters\TagConverter;

final class ConverterConfig
{
	/** @return Converter[] */
	public static function getDefaultConverters(): array
	{
		return [
			new TagConverter(),
			new MemberConverter(),
			new DependenceConverter(),
			new DeadlineConverter(),
			new StartDatePlanConverter(),
			new EndDatePlanConverter(),
			new DescriptionConverter(),
			new DescriptionBBCodeConverter(),
		];
	}
}