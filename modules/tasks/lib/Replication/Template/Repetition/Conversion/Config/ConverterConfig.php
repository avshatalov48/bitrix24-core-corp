<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Conversion\Config;

use Bitrix\Tasks\Replication\Template\Repetition\Conversion\ConverterInterface;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\DeadlineConverter;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\DependenceConverter;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\DescriptionBBCodeConverter;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\DescriptionConverter;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\EndDatePlanConverter;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\MemberConverter;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\StartDatePlanConverter;
use Bitrix\Tasks\Replication\Template\Repetition\Conversion\Converters\TagConverter;

final class ConverterConfig
{
	/** @return ConverterInterface[] */
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