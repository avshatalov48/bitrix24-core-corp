<?php
namespace Bitrix\Crm\Integration\Recyclebin;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Recyclebin;

class RecyclingManager
{
	public static function getEntityNames()
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		$entities = [
			\CCrmOwnerType::Lead => Crm\Integration\Recyclebin\Lead::getEntityName(),
			\CCrmOwnerType::Deal => Crm\Integration\Recyclebin\Deal::getEntityName(),
			\CCrmOwnerType::Contact => Crm\Integration\Recyclebin\Contact::getEntityName(),
			\CCrmOwnerType::Company => Crm\Integration\Recyclebin\Company::getEntityName(),
			\CCrmOwnerType::Activity => Crm\Integration\Recyclebin\Activity::getEntityName()
		];

		if (Crm\Settings\InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			$entities[\CCrmOwnerType::SmartInvoice] = Crm\Integration\Recyclebin\SmartInvoice::getEntityName();
		}

		return $entities;
	}

	/**
	 * Get Recyclable Entity Type Name by Entity Type ID.
	 * If Entity Type is not supported empty string will be returned.
	 * @param int $entityTypeID Entity Type ID.
	 * @return string
	 * @throws Main\InvalidOperationException
	 * @throws Main\LoaderException
	 */
	public static function resolveRecyclableEntityType($entityTypeID)
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		$entities = static::getEntityNames();

		return $entities[$entityTypeID] ?? '';
	}

	//region Access to RecycleBin module
	/**
	 * Restore Recycle Bin Entity.
	 * @param int $recycleBinEntityID Recycle Bin entity ID.
	 * @throws Main\AccessDeniedException
	 * @throws Main\InvalidOperationException
	 * @throws Main\LoaderException
	 */
	public static function restoreRecycleBinEntity($recycleBinEntityID)
	{
		if($recycleBinEntityID <= 0)
		{
			return;
		}

		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		Recyclebin\Recyclebin::restore($recycleBinEntityID);
	}

	/**
	 * Remove Recycle Bin Entity.
	 * @param $recycleBinEntityID
	 * @throws Main\AccessDeniedException
	 * @throws Main\InvalidOperationException
	 * @throws Main\LoaderException
	 */
	public static function removeRecycleBinEntity($recycleBinEntityID)
	{
		if($recycleBinEntityID <= 0)
		{
			return;
		}

		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		$params['SKIP_TASKS'] = ActivitySettings::getValue(ActivitySettings::KEEP_UNBOUND_TASKS);

		Recyclebin\Recyclebin::remove($recycleBinEntityID, $params);
	}
	//endregion

	public static function resolveEntityTitle($entityTypeID, $entityID)
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		$recycleBinEntityID = Recyclebin\Recyclebin::findId(
			'crm',
			self::resolveRecyclableEntityType($entityTypeID),
			$entityID
		);

		if($recycleBinEntityID <= 0)
		{
			return '';
		}

		$entityData = Recyclebin\Recyclebin::getEntityData($recycleBinEntityID);
		return $entityData ? $entityData->getTitle() : '';
	}

	/**
	 * RecycleBin module OnModuleSurvey event handler.
	 * @return Main\EventResult
	 * @throws Main\LoaderException
	 */
	public static function onModuleSurvey(): Main\EventResult
	{
		//Ensure module "RecycleBin" is included.
		if(!Main\Loader::includeModule('recyclebin'))
		{
			return new Main\EventResult(
				Main\EventResult::ERROR,
				'Could not load RecycleBin module.'
			);
		}

		$data = array_merge(
			Crm\Integration\Recyclebin\Lead::prepareSurveyInfo(),
			Crm\Integration\Recyclebin\Contact::prepareSurveyInfo(),
			Crm\Integration\Recyclebin\Company::prepareSurveyInfo(),
			Crm\Integration\Recyclebin\Deal::prepareSurveyInfo(),
			Crm\Integration\Recyclebin\Activity::prepareSurveyInfo(),
			Crm\Integration\Recyclebin\Dynamic::prepareSurveyInfo(),
			Crm\Integration\Recyclebin\SmartInvoice::prepareSurveyInfo(),
		);

		return new Main\EventResult(
			Main\EventResult::SUCCESS,
			[
				'NAME' => 'CRM',
				'LIST' => $data
			],
			'crm'
		);
	}

	/**
	 * RecycleBin module OnAdditionalDataRequest event handler.
	 * @return Main\EventResult
	 * @throws Main\LoaderException
	 */
	public static function onAdditionalDataRequest()
	{
		$data = array_merge(
			Crm\Integration\Recyclebin\Lead::getAdditionalData(),
			Crm\Integration\Recyclebin\Contact::getAdditionalData(),
			Crm\Integration\Recyclebin\Company::getAdditionalData(),
			Crm\Integration\Recyclebin\Deal::getAdditionalData(),
			Crm\Integration\Recyclebin\Activity::getAdditionalData()
		);

		return new Main\EventResult(
			Main\EventResult::SUCCESS,
			['NAME' => 'CRM', 'ADDITIONAL_DATA' => $data],
			'crm'
		);
	}
}
