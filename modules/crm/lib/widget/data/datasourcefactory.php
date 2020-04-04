<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;

abstract class DataSourceFactory
{
	public static function checkSettings(array $settings)
	{
		return !empty($settings) && isset($settings['name']) && $settings['name'] !== '';
	}
	public static function create(array $settings, $userID = 0, $enablePermissionCheck = true)
	{
		$name = isset($settings['name']) ? strtoupper($settings['name']) : '';
		if($name === DealSumStatistics::TYPE_NAME)
		{
			return new DealSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadSumStatistics::TYPE_NAME)
		{
			return new LeadSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealInvoiceStatistics::TYPE_NAME)
		{
			return new DealInvoiceStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealActivityStatistics::TYPE_NAME)
		{
			return new DealActivityStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadActivityStatistics::TYPE_NAME)
		{
			return new LeadActivityStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealStageHistory::TYPE_NAME)
		{
			return new DealStageHistory($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadStatusHistory::TYPE_NAME)
		{
			return new LeadStatusHistory($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealInWork::TYPE_NAME)
		{
			return new DealInWork($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadInWork::TYPE_NAME)
		{
			return new LeadInWork($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealIdle::TYPE_NAME)
		{
			return new DealIdle($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadIdle::TYPE_NAME)
		{
			return new LeadIdle($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadNew::TYPE_NAME)
		{
			return new LeadNew($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadConversionStatistics::TYPE_NAME)
		{
			return new LeadConversionStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadConversionRate::TYPE_NAME)
		{
			return new LeadConversionRate($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadJunk::TYPE_NAME)
		{
			return new LeadJunk($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadChannelStatistics::TYPE_NAME)
		{
			return new LeadChannelStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealChannelStatistics::TYPE_NAME)
		{
			return new DealChannelStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === Activity\ChannelStatistics::TYPE_NAME)
		{
			return new Activity\ChannelStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === InvoiceInWork::TYPE_NAME)
		{
			return new InvoiceInWork($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === InvoiceSumStatistics::TYPE_NAME)
		{
			return new InvoiceSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === InvoiceOverdue::TYPE_NAME)
		{
			return new InvoiceOverdue($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === ExpressionDataSource::TYPE_NAME)
		{
			return new ExpressionDataSource($settings, $userID);
		}
		elseif($name === ActivityProviderStatus::TYPE_NAME)
		{
			return new ActivityProviderStatus($settings, $userID);
		}
		elseif ($name === Company\ActivityStatistics::TYPE_NAME)
		{
			return new Company\ActivityStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Company\GrowthStatistics::TYPE_NAME)
		{
			return new Company\GrowthStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Company\ActivityStreamStatistics::TYPE_NAME)
		{
			return new Company\ActivityStreamStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Company\ActivityMarkStatistics::TYPE_NAME)
		{
			return new Company\ActivityMarkStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Company\ActivitySumStatistics::TYPE_NAME)
		{
			return new Company\ActivitySumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Company\ActivityStatusStatistics::TYPE_NAME)
		{
			return new Company\ActivityStatusStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Company\DealSumStatistics::TYPE_NAME)
		{
			return new Company\DealSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Company\InvoiceSumStatistics::TYPE_NAME)
		{
			return new Company\InvoiceSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Company\DealConversionRate::TYPE_NAME)
		{
			return new Company\DealConversionRate($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\ActivityStatistics::TYPE_NAME)
		{
			return new Contact\ActivityStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\ActivityStreamStatistics::TYPE_NAME)
		{
			return new Contact\ActivityStreamStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\ActivityMarkStatistics::TYPE_NAME)
		{
			return new Contact\ActivityMarkStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\ActivitySumStatistics::TYPE_NAME)
		{
			return new Contact\ActivitySumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\ActivityStatusStatistics::TYPE_NAME)
		{
			return new Contact\ActivityStatusStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\GrowthStatistics::TYPE_NAME)
		{
			return new Contact\GrowthStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\DealSumStatistics::TYPE_NAME)
		{
			return new Contact\DealSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\InvoiceSumStatistics::TYPE_NAME)
		{
			return new Contact\InvoiceSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Contact\DealConversionRate::TYPE_NAME)
		{
			return new Contact\DealConversionRate($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Activity\Statistics::TYPE_NAME)
		{
			return new Activity\Statistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Activity\StreamStatistics::TYPE_NAME)
		{
			return new Activity\StreamStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Activity\MarkStatistics::TYPE_NAME)
		{
			return new Activity\MarkStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Activity\SumStatistics::TYPE_NAME)
		{
			return new Activity\SumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Activity\StatusStatistics::TYPE_NAME)
		{
			return new Activity\StatusStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Activity\ActivityDynamic::TYPE_NAME)
		{
			return new Activity\ActivityDynamic($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === Activity\ManagerCounters::TYPE_NAME)
		{
			return new Activity\ManagerCounters($settings, $userID, $enablePermissionCheck);
		}
		elseif ($name === DealSaleTarget::TYPE_NAME)
		{
			return new DealSaleTarget($settings, $userID, $enablePermissionCheck);
		}
		else
		{
			throw new Main\NotSupportedException("The data source '{$name}' is not supported in current context.");
		}
	}

	public static function getPresets()
	{
		return array_merge(
			DealSumStatistics::getPresets(),
			DealInWork::getPresets(),
			DealIdle::getPresets(),
			DealActivityStatistics::getPresets(),
			DealInvoiceStatistics::getPresets(),
			LeadSumStatistics::getPresets(),
			LeadActivityStatistics::getPresets(),
			LeadInWork::getPresets(),
			LeadIdle::getPresets(),
			LeadNew::getPresets(),
			LeadConversionStatistics::getPresets(),
			LeadConversionRate::getPresets(),
			LeadJunk::getPresets(),
			InvoiceSumStatistics::getPresets(),
			InvoiceInWork::getPresets(),
			InvoiceOverdue::getPresets(),
			Activity\Statistics::getPresets(),
			Activity\MarkStatistics::getPresets(),
			Activity\StreamStatistics::getPresets(),
			Activity\SumStatistics::getPresets(),
			Activity\StatusStatistics::getPresets(),
			Activity\ChannelStatistics::getPresets(),
			Company\ActivityStatistics::getPresets(),
			Company\ActivityMarkStatistics::getPresets(),
			Company\ActivityStreamStatistics::getPresets(),
			Company\ActivitySumStatistics::getPresets(),
			Company\ActivityStatusStatistics::getPresets(),
			Company\GrowthStatistics::getPresets(),
			Company\DealSumStatistics::getPresets(),
			Company\DealConversionRate::getPresets(),
			Company\InvoiceSumStatistics::getPresets(),
			Contact\ActivityStatistics::getPresets(),
			Contact\ActivityMarkStatistics::getPresets(),
			Contact\ActivityStreamStatistics::getPresets(),
			Contact\ActivitySumStatistics::getPresets(),
			Contact\ActivityStatusStatistics::getPresets(),
			Contact\GrowthStatistics::getPresets(),
			Contact\DealSumStatistics::getPresets(),
			Contact\DealConversionRate::getPresets(),
			Contact\InvoiceSumStatistics::getPresets()
		);
	}

	public static function getCategiries()
	{
		$categories = array();
		DealInWork::prepareCategories($categories);
		DealIdle::prepareCategories($categories);
		LeadNew::prepareCategories($categories);
		LeadInWork::prepareCategories($categories);
		LeadIdle::prepareCategories($categories);
		LeadConversionStatistics::prepareCategories($categories);
		LeadConversionRate::prepareCategories($categories);
		LeadJunk::prepareCategories($categories);
		InvoiceInWork::prepareCategories($categories);
		InvoiceOverdue::prepareCategories($categories);
		Activity\Statistics::prepareCategories($categories);
		Activity\ChannelStatistics::prepareCategories($categories);
		Company\ActivityStatistics::prepareCategories($categories);
		Contact\ActivityStatistics::prepareCategories($categories);
		return array_values($categories);
	}

	public static function getGroupingExtras()
	{
		$groupings = array();
		LeadSumStatistics::prepareGroupingExtras($groupings);
		Activity\Statistics::prepareGroupingExtras($groupings);
		Activity\MarkStatistics::prepareGroupingExtras($groupings);
		Activity\StreamStatistics::prepareGroupingExtras($groupings);
		Activity\StatusStatistics::prepareGroupingExtras($groupings);
		Activity\ChannelStatistics::prepareGroupingExtras($groupings);
		Company\ActivityStatistics::prepareGroupingExtras($groupings);
		Company\ActivityMarkStatistics::prepareGroupingExtras($groupings);
		Company\ActivityStreamStatistics::prepareGroupingExtras($groupings);
		Company\ActivityStatusStatistics::prepareGroupingExtras($groupings);
		Contact\ActivityStatistics::prepareGroupingExtras($groupings);
		Contact\ActivityMarkStatistics::prepareGroupingExtras($groupings);
		Contact\ActivityStreamStatistics::prepareGroupingExtras($groupings);
		Contact\ActivityStatusStatistics::prepareGroupingExtras($groupings);
		return array_values($groupings);
	}
}