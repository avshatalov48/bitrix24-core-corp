<?php
namespace Bitrix\Crm\Widget;
use Bitrix\Main;

abstract class WidgetFactory
{
	const FUNNEL = 'FUNNEL';
	const GRAPH = 'GRAPH';
	const BAR = 'BAR';
	const NUMBER = 'NUMBER';
	const RATING = 'RATING';
	const PIE = 'PIE';
	const CUSTOM = 'CUSTOM';

	/**
	* @return Widget
	*/
	public static function create(array $settings, Filter $filter, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeName = isset($settings['typeName']) ? strtoupper($settings['typeName']) : '';
		if($typeName === self::FUNNEL)
		{
			return new FunnelWidget($settings, $filter);
		}
		elseif($typeName === self::GRAPH || $typeName === self::BAR)
		{
			if(isset($options['maxGraphCount']))
			{
				$settings['maxGraphCount'] = $options['maxGraphCount'];
			}
			return new GraphWidget($settings, $filter);
		}
		elseif($typeName === self::NUMBER)
		{
			return new NumericWidget($settings, $filter);
		}
		elseif($typeName === self::RATING)
		{
			return new RatingWidget($settings, $filter);
		}
		elseif($typeName === self::PIE)
		{
			return new PieWidget($settings, $filter);
		}
		elseif($typeName === self::CUSTOM)
		{
			return new CustomWidget($settings, $filter);
		}

		throw new Main\NotSupportedException("The widget type '{$typeName}' is not supported in current context.");
	}
}