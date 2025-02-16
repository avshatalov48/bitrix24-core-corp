<?php

IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Activity\ToDo\CalendarSettings\CalendarSettingsProvider;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Color\PhaseColorScheme;
use Bitrix\Crm\Conversion\LeadConversionType;
use Bitrix\Crm\Integration\OpenLineManager;
use Bitrix\Crm\Order;
use Bitrix\Crm\Security\StagePermissions;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Workflow\PaymentStage;
use Bitrix\Main\UI\Extension;

class CCrmViewHelper
{
	private static $DEAL_STAGES = null;
	private static $LEAD_STATUSES = null;
	private static $QUOTE_STATUSES = null;
	private static $INVOICE_STATUSES = null;
	private static $ORDER_STATUSES = null;
	private static $ORDER_SHIPMENT_STATUSES = null;

	private static $USER_INFO_PROVIDER_MESSAGES_REGISTRED = false;

	const PROCESS_COLOR = PhaseColorScheme::PROCESS_COLOR; //former: #4C99DA
	const SUCCESS_COLOR = PhaseColorScheme::SUCCESS_COLOR; //former: #96B833
	const FAILURE_COLOR = PhaseColorScheme::FAILURE_COLOR; //former: #F54819

	public static function PrepareClientBaloonHtml($arParams)
	{
		return self::PrepareEntityBaloonHtml($arParams);
	}
	public static function PrepareEntityBaloonHtml($arParams)
	{
		if(!is_array($arParams))
		{
			return '';
		}

		$entityTypeID = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : 0;
		$entityID = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$className = isset($arParams['CLASS_NAME']) ? $arParams['CLASS_NAME'] : '';

		if($entityTypeID <= 0 || $entityID <= 0)
		{
			return '';
		}

		$showPath = isset($arParams['SHOW_URL']) ? $arParams['SHOW_URL'] : '';

		if($entityTypeID === CCrmOwnerType::Company)
		{
			if($showPath === '')
			{
				$showPath = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Company, $entityID, false);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			return '<a href="'.htmlspecialcharsbx($showPath).'"'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').' bx-tooltip-user-id="COMPANY_'.$entityID.'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.company.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon_company">'.htmlspecialcharsbx($title).'</a>';
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			if($showPath === '')
			{
				$showPath = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Contact, $entityID, false);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			return '<a href="'.htmlspecialcharsbx($showPath).'"'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').' bx-tooltip-user-id="CONTACT_'.$entityID.'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.contact.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon_contact">'.htmlspecialcharsbx($title).'</a>';
		}
		elseif($entityTypeID === CCrmOwnerType::Lead)
		{
			if($showPath === '')
			{
				$showPath = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Lead, $entityID, false);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			return '<a href="'.htmlspecialcharsbx($showPath).'" '.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').' bx-tooltip-user-id="LEAD_'.$entityID.'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.lead.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon_no_photo">'.htmlspecialcharsbx($title).'</a>';
		}
		elseif($entityTypeID === CCrmOwnerType::Deal)
		{
			if($showPath === '')
			{
				$showPath = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $entityID, false);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			return '<a href="'.htmlspecialcharsbx($showPath).'" '.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').' bx-tooltip-user-id="DEAL_'.$entityID.'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.deal.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon_no_photo">'.htmlspecialcharsbx($title).'</a>';

		}
		elseif($entityTypeID === CCrmOwnerType::Quote)
		{
			if($showPath === '')
			{
				$showPath = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Quote, $entityID, false);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Quote, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			return '<a href="'.htmlspecialcharsbx($showPath).'" '.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').' bx-tooltip-user-id="QUOTE_'.$entityID.'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.quote.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon_no_photo">'.htmlspecialcharsbx($title).'</a>';
		}
		return '';
	}
	public static function PrepareUserBaloonHtml($arParams)
	{
		if(!is_array($arParams))
		{
			return '';
		}

		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$userID = isset($arParams['USER_ID']) ? (int)$arParams['USER_ID'] : 0;
		$userName = isset($arParams['USER_NAME']) ? $arParams['USER_NAME'] : "[{$userID}]";
		if(isset($arParams['ENCODE_USER_NAME']) && $arParams['ENCODE_USER_NAME'])
		{
			$userName = htmlspecialcharsbx($userName);
		}
		$profilePath = isset($arParams['USER_PROFILE_URL']) ? $arParams['USER_PROFILE_URL'] : '';
		$baloonID = $prefix !== '' ? "BALLOON_{$prefix}_U_{$userID}" : "BALLOON_U_{$userID}";
		return '<a href="'.htmlspecialcharsbx($profilePath).'" id="'.$baloonID.'" target="_blank" bx-tooltip-user-id="'.$userID.'">'.$userName.'</a>';
	}
	public static function GetFormattedUserName($userID, $format = '', $htmlEncode = false)
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			return '';
		}

		$format = strval($format);
		if($format === '')
		{
			$format = CSite::GetNameFormat(false);
		}

		$user = Container::getInstance()->getUserBroker()->getById($userID);
		return is_array($user) ? CUser::FormatName($format, $user, true, $htmlEncode) : '';
	}
	public static function RenderInfo($url, $titleHtml, $descriptionHtml, array $options = null)
	{
		$url = strval($url);
		$titleHtml = strval($titleHtml);
		$descriptionHtml = strval($descriptionHtml);

		if(!is_array($options))
		{
			$options = array();
		}

		$legendHtml = isset($options['LEGEND']) ? strval($options['LEGEND']) : '';
		$target = isset($options['TARGET']) ? strval($options['TARGET']) : '';
		$onclick = isset($options['ONCLICK']) ? strval($options['ONCLICK']) : '';

		$result = '';
		if($url !== '' || $titleHtml !== '')
		{
			$result .= '<div class="crm-info-title-wrapper">';
			if($url !== '')
			{
				$result .= '<a target="'.htmlspecialcharsbx($target).'" href="'.$url.'"';
				if($onclick !== '')
				{
					$result .= ' onclick="'.htmlspecialcharsbx($onclick).'"';
				}

				$result .= '>'.($titleHtml !== '' ? $titleHtml : $url).'</a>';
			}
			elseif($titleHtml !== '')
			{
				$result .= $titleHtml;
			}
			$result .= '</div>';
		}
		if($descriptionHtml !== '')
		{
			$result .= '<div class="crm-info-description-wrapper">'.$descriptionHtml.'</div>';
		}

		if($legendHtml !== '')
		{
			$result .= '<div class="crm-info-legend-wrapper ">'.$legendHtml.'</div>';
		}

		return '<div class="crm-info-wrapper">'.$result.'</div>';
	}
	public static function RenderInfo1($url, $titleHtml, $descriptionHtml, $target = '_blank', $onclick = '')
	{
		$url = strval($url);
		$titleHtml = strval($titleHtml);
		$descriptionHtml = strval($descriptionHtml);
		$target = strval($target);
		$onclick = strval($onclick);

		$containerOpenHtml = $containerCloseHtml = '';
		if ($url !== '')
		{
			$containerOpenHtml .=
				'<a class="crm-info-wrapper" target="'.htmlspecialcharsbx($target).'" href="'.$url.'"';
			if($onclick !== '')
				$containerOpenHtml .= ' onclick="'.CUtil::JSEscape($onclick).'"';
			$containerOpenHtml .= '>';
			$containerCloseHtml .= '</a>';
		}
		else
		{
			$containerOpenHtml .=
				'<div class="crm-info-wrapper">';
			$containerCloseHtml .= '</div>';
		}
		$titleWrapperHtml = '';
		if($url !== '' || $titleHtml !== '')
		{
			$titleWrapperHtml .= '<div class="crm-info-title-wrapper">';
			if($url !== '')
				$titleWrapperHtml .= '<span>'.($titleHtml !== '' ? $titleHtml : $url).'</span>';
			$titleWrapperHtml .= '</div>';
		}

		$result = $containerOpenHtml.$titleWrapperHtml;
		if($descriptionHtml !== '')
			$result .= '<div class="crm-info-description-wrapper">'.$descriptionHtml.'</div>';
		$result .= $containerCloseHtml;

		return $result;
	}

	public static function GetHiddenEntityCaption($entityTypeID)
	{
		if($entityTypeID === CCrmOwnerType::Company)
		{
			return GetMessage('CRM_CLIENT_SUMMARY_HIDDEN_COMPANY');
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			return GetMessage('CRM_CLIENT_SUMMARY_HIDDEN_CONTACT');
		}
		elseif($entityTypeID === CCrmOwnerType::Lead)
		{
			return GetMessage('CRM_CLIENT_SUMMARY_HIDDEN_LEAD');
		}
		elseif($entityTypeID === CCrmOwnerType::Deal)
		{
			return GetMessage('CRM_CLIENT_SUMMARY_HIDDEN_DEAL');
		}
		elseif($entityTypeID === CCrmOwnerType::Quote)
		{
			return GetMessage('CRM_CLIENT_SUMMARY_HIDDEN_QUOTE_MSGVER_1');
		}
		return GetMessage('CRM_CLIENT_SUMMARY_HIDDEN');
	}
	public static function PrepareClientInfo($arParams)
	{
		$result = '<div class="crm-info-title-wrapper">';

		$entityTypeID = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : 0;
		$isHidden = isset($arParams['IS_HIDDEN']) ? $arParams['IS_HIDDEN'] : false;
		if($isHidden)
		{
			$result .= self::GetHiddenEntityCaption($entityTypeID);
		}
		else
		{
			$result .= self::PrepareClientBaloonHtml($arParams);
		}

		$result .= '</div>';

		$description = isset($arParams['DESCRIPTION']) ? $arParams['DESCRIPTION'] : '';
		if($description !== '')
		{
			$result .= '<div class="crm-info-description-wrapper">'.htmlspecialcharsbx($description).'</div>';
		}

		return '<div class="crm-info-wrapper">'.$result.'</div>';
	}
	public static function PrepareClientInfoV2($arParams)
	{
		$showUrl = isset($arParams['SHOW_URL']) ? $arParams['SHOW_URL'] : '';
		if($showUrl === '')
		{
			$entityTypeID = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : 0;
			$entityID = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
			if($entityTypeID > 0 && $entityID > 0)
			{
				$showUrl = CCrmOwnerType::GetEntityShowPath($entityTypeID, $entityID);
			}
		}

		$photoID = isset($arParams['PHOTO_ID']) ? intval($arParams['PHOTO_ID']) : 0;
		$photoUrl = $photoID > 0
			? CFile::ResizeImageGet($photoID, array('width' => 30, 'height' => 30), BX_RESIZE_IMAGE_EXACT)
			: '';

		$name = isset($arParams['NAME']) ? $arParams['NAME'] : '';
		$description = isset($arParams['DESCRIPTION']) ? $arParams['DESCRIPTION'] : '';
		$html = isset($arParams['ADDITIONAL_HTML']) ? $arParams['ADDITIONAL_HTML'] : '';

		if($showUrl !== '')
		{
			return '<a class="crm-item-client-block" href="'
				.htmlspecialcharsbx($showUrl).'"><div class="crm-item-client-img">'
				.(isset($photoUrl['src']) ? '<img alt="" src="'.htmlspecialcharsbx($photoUrl['src']).'"/>' : '')
				.'</div>'
				.'<span class="crm-item-client-alignment"></span>'
				.'<span class="crm-item-client-alignment-block">'
				.'<div class="crm-item-client-name">'
				.htmlspecialcharsbx($name).'</div><div class="crm-item-client-description">'
				.htmlspecialcharsbx($description).$html.'</div></span></a>';
		}

		return '<span class="crm-item-client-block"><div class="crm-item-client-img">'
			.(isset($photoUrl['src']) ? '<img alt="" src="'.htmlspecialcharsbx($photoUrl['src']).'"/>' : '')
			.'</div>'
			.'<span class="crm-item-client-alignment"></span>'
			.'<span class="crm-item-client-alignment-block">'
			.'<div class="crm-item-client-name">'
			.htmlspecialcharsbx($name).'</div><div class="crm-item-client-description">'
			.htmlspecialcharsbx($description).$html.'</div></span></span>';
	}
	public static function RenderClientSummary($url, $titleHtml, $descriptionHtml, $photoHtml = '', $target = '_self')
	{
		$url = strval($url);
		$titleHtml = strval($titleHtml);
		$descriptionHtml = strval($descriptionHtml);
		$photoHtml = strval($photoHtml);

		$result = '<div class="crm-client-photo-wrapper">'.($photoHtml !== ''
				? $photoHtml
				: '<div class="ui-icon ui-icon-common-user crm-avatar crm-avatar-user"><i></i></div>').'</div>';

		$result .= '<div class="crm-client-info-wrapper">';
		if($url !== '' || $titleHtml !== '')
		{
			$result .= '<div class="crm-client-title-wrapper">';
			if($url !== '')
			{
				$result .= '<a target="'.htmlspecialcharsbx($target).'" href="'.$url.'">'
					.($titleHtml !== '' ? $titleHtml : htmlspecialcharsbx($url)).'</a>';
			}
			elseif($titleHtml !== '')
			{
				$result .= $titleHtml;
			}
			$result .= '</div>';
		}
		if($descriptionHtml !== '')
		{
			$result .= '<div class="crm-client-description-wrapper">'.$descriptionHtml.'</div>';
		}
		$result .= '</div>';

		return '<div class="crm-client-summary-wrapper">'.$result.'<div style="clear:both;"></div></div>';
	}

	public static function RenderMultipleClientSummaryPanel($arParams, $arOptions = array())
	{
		$count = isset($arOptions['COUNT']) ? (int)$arOptions['COUNT'] : 0;
		$selectedIndex = isset($arOptions['SELECTED_INDEX']) ? (int)$arOptions['SELECTED_INDEX'] : 0;
		$boxWidth = 100 * $count;

		$slideWidth = round(100 / $count, 6);

		echo '<div class="crm-detail-info-resp-slider-container">';
		echo '<div class="crm-detail-info-resp-slider-container-overflow">';
		echo '<div class="crm-detail-info-resp-slide-box" style="width: '.$boxWidth.'%;">';

		for($i = 0; $i < $count; $i++)
		{
			$childParams = isset($arParams[$i]) ? $arParams[$i] : null;

			if(is_array($childParams))
			{
				//region Client slide
				$prefix = isset($childParams['PREFIX']) ? $childParams['PREFIX'] : '';
				$entityID = isset($childParams['ENTITY_ID']) ? $childParams['ENTITY_ID'] : '';
				$wrapperID = $prefix !== '' ? "{$prefix}_{$entityID}" : $entityID;
				$childParams['CONTAINER_ID'] = "{$wrapperID}_container";
				echo '<div class="crm-detail-info-resp-block crm-detail-info-resp-slide" id="'.$wrapperID.'" style="width: '.$slideWidth.'%">';
				self::RenderClientSummaryPanel($childParams, array_merge(array('ENABLE_WRAPPER' => false), $arOptions));
				//region Counter
				echo '<div class="crm-detail-info-resp-slide-counter-container">',
				'<div class="crm-detail-info-resp-slide-counter">', ($selectedIndex + 1), ' / ', $count, '</div>',
				'</div>';
				//endregion
				echo '</div>';
				//endregion

			}
		}
		echo '</div>'; //...slide-box
		echo '</div>'; //...slider-container-overflow
		echo '<div class="crm-detail-info-resp-slider-arrow crm-detail-info-resp-slider-arrow-left"></div>';
		echo '<div class="crm-detail-info-resp-slider-arrow crm-detail-info-resp-slider-arrow-right"></div>';
		echo '</div>'; //...slider-container
	}

	public static function RenderClientSummaryPanel($arParams, $arOptions = array())
	{
		$entityTypeName = isset($arParams['ENTITY_TYPE_NAME']) ? $arParams['ENTITY_TYPE_NAME'] : '';
		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$showUrl = isset($arParams['SHOW_URL']) ? $arParams['SHOW_URL'] : '';

		//region Beginning of wrapper
		$enableWrapper = !isset($arOptions['ENABLE_WRAPPER']) || $arOptions['ENABLE_WRAPPER'] === true;
		if($enableWrapper)
		{
			echo '<div class="crm-detail-info-resp-block">';
		}
		//endregion
		//region Header
		$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
		if($title !== '')
		{
			echo '<div class="crm-detail-info-resp-header">';
			echo '<span class="crm-detail-info-resp-text">', htmlspecialcharsbx($title), '</span>';
			echo '</div>';
		}
		//endregion
		//region Beginning of container
		$containerID = isset($arParams['CONTAINER_ID']) ? $arParams['CONTAINER_ID'] : '';
		if($containerID === '')
		{
			$containerID = $prefix !== '' ? "{$prefix}_container" : 'client_container';
		}

		$containerClassName = 'crm-detail-info-resp';
		if($entityTypeName === CCrmOwnerType::ContactName)
		{
			$containerClassName .= ' crm-detail-info-head-cont';
		}
		elseif($entityTypeName === CCrmOwnerType::CompanyName)
		{
			$containerClassName .= ' crm-detail-info-head-firm';
		}

		if($showUrl !== '')
		{
			echo '<a class="', htmlspecialcharsbx($containerClassName), '" id="', htmlspecialcharsbx($containerID), '" target="_blank" href="', htmlspecialcharsbx($showUrl), '">';
		}
		else
		{
			echo '<span class="', htmlspecialcharsbx($containerClassName), '" id="', htmlspecialcharsbx($containerID), '">';
		}
		//endregion
		//region Client photo/logo
		$imageUrl = isset($arParams['IMAGE_URL']) ? $arParams['IMAGE_URL'] : '';
		$imageID = isset($arParams['IMAGE']) ? intval($arParams['IMAGE']) : 0;
		if($imageUrl === '' && $imageID > 0)
		{
			$imageInfo = CFile::ResizeImageGet($imageID, array('width' => 38, 'height' => 38), BX_RESIZE_IMAGE_EXACT);
			$imageUrl = is_array($imageInfo) && isset($imageInfo['src']) ? $imageInfo['src'] : '';
		}

		$imageContainerClassName = $entityTypeName === CCrmOwnerType::CompanyName && $imageUrl !== '' ? 'crm-lead-header-company-img' : 'crm-detail-info-resp-img';
		echo '<div class="', $imageContainerClassName, '">';
		if($imageUrl !== '')
		{
			echo '<img alt="" src="', htmlspecialcharsbx($imageUrl), '"/>';
		}

		echo '</div>';
		//endregion
		//region Client denomination
		$name = isset($arParams['NAME']) ? $arParams['NAME'] : '';
		$description = isset($arParams['DESCRIPTION']) ? $arParams['DESCRIPTION'] : '';

		if($showUrl !== '')
		{
			echo '<span class="crm-detail-info-resp-name">', htmlspecialcharsbx($name), '</span>';
			echo '<span class="crm-detail-info-resp-descr">', htmlspecialcharsbx($description), '</span>';
		}
		else
		{
			if($name === '')
			{
				$name = GetMessage(
					$entityTypeName === CCrmOwnerType::CompanyName
						? "CRM_CLIENT_SUMMARY_COMPANY_NOT_SPECIFIED"
						: "CRM_CLIENT_SUMMARY_CONTACT_NOT_SPECIFIED"
				);
			}

			echo '<div class="crm-detail-info-empty">', htmlspecialcharsbx($name), '</div>';
		}
		//endregion
		//region End of container
		if($showUrl !== '')
		{
			echo '</a>';
		}
		else
		{
			echo '</span>';
		}
		//endregion
		//region Multifiels
		$arEntityTypes = CCrmFieldMulti::GetEntityTypes();

		$fields = isset($arParams['FM']) ? $arParams['FM'] : null;
		if(!isset($arOptions['ENABLE_MULTIFIELDS']) || $arOptions['ENABLE_MULTIFIELDS'] === true)
		{
			if(isset($fields['PHONE']) && is_array($fields['PHONE']) && !empty($fields['PHONE']))
			{
				echo '<div class="crm-detail-info-item">';
				echo '<span class="crm-detail-info-item-name">', GetMessage('CRM_ENTITY_INFO_PHONE'), ':', '</span>';
				echo self::PrepareFormMultiField(array('FM'=>array('PHONE' => $fields['PHONE'])), 'PHONE', $prefix, $arEntityTypes, $arOptions);
				echo '</div>';
			}

			if(isset($fields['EMAIL']) && is_array($fields['EMAIL']) && !empty($fields['EMAIL']))
			{
				echo '<div class="crm-detail-info-item">';
				echo '<span class="crm-detail-info-item-name">', GetMessage('CRM_ENTITY_INFO_EMAIL'), ':', '</span>';
				echo self::PrepareFormMultiField(array('FM'=>array('EMAIL' => $fields['EMAIL'])), 'EMAIL', $prefix, $arEntityTypes, $arOptions);
				echo '</div>';
			}
		}
		//endregion
		//region End of wrapper
		if($enableWrapper)
		{
			echo '</div>';
		}
		//endregion
	}

	/**
	 * @deprecated Will be removed soon
	 * @see \Bitrix\Crm\Component\EntityList\NearestActivity\Manager::appendNearestActivityBlock
	 */
	public static function RenderNearestActivity($arParams)
	{
		$gridManagerID = isset($arParams['GRID_MANAGER_ID']) ? $arParams['GRID_MANAGER_ID'] : '';
		$preparedGridId = htmlspecialcharsbx(CUtil::JSescape($gridManagerID));
		$mgrID = mb_strtolower($gridManagerID);

		$entityTypeName = isset($arParams['ENTITY_TYPE_NAME'])? mb_strtolower($arParams['ENTITY_TYPE_NAME']) : '';
		$entityTypeId = CCrmOwnerType::ResolveID($arParams['ENTITY_TYPE_NAME']);
		$entityID = $arParams['ENTITY_ID'] ?? '';
		$categoryId = $arParams['CATEGORY_ID'] ?? 0;

		$allowEdit = isset($arParams['ALLOW_EDIT']) ? $arParams['ALLOW_EDIT'] : false;
		$menuItems = isset($arParams['MENU_ITEMS']) ? $arParams['MENU_ITEMS'] : array();
		$menuID = CUtil::JSEscape("bx_{$mgrID}_{$entityTypeName}_{$entityID}_activity_add");

		$useGridExtension = isset($arParams['USE_GRID_EXTENSION']) ? $arParams['USE_GRID_EXTENSION'] : false;

		$ID = isset($arParams['ACTIVITY_ID']) ? intval($arParams['ACTIVITY_ID']) : 0;
		if($ID > 0)
		{
			$subject = isset($arParams['ACTIVITY_SUBJECT']) ? $arParams['ACTIVITY_SUBJECT'] : '';
			$subject = \Bitrix\Main\Text\Emoji::decode($subject); // possible double decode is not a problem

			$time = isset($arParams['ACTIVITY_TIME']) ? $arParams['ACTIVITY_TIME'] : '';
			if($time !== '' && CCrmDateTimeHelper::IsMaxDatabaseDate($time))
			{
				$time = '';
			}

			$timestamp = $time !== '' ? MakeTimeStamp($time) : 0;
			$timeFormatted = $timestamp > 0 ? CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', $timestamp)) : GetMessage('CRM_ACTIVITY_TIME_NOT_SPECIFIED_MSGVER_1');
			$isExpired = $arParams['ACTIVITY_EXPIRED'] ?? ($timestamp <= (time() + CTimeZone::GetOffset()));
			$isDetailExist = true;
			if (isset($arParams['ACTIVITY_PROVIDER_ID']))
			{
				$provider = \CCrmActivity::GetProviderById($arParams['ACTIVITY_PROVIDER_ID']);
				if ($provider)
				{
					$isDetailExist = $provider::hasPlanner($arParams);
					$subject = $provider::getActivityTitle(array_merge($arParams, ['COMPLETED' => 'N']));
				}
			}

			$activityEl = '<span class="crm-link">' . htmlspecialcharsbx($timeFormatted) . '</span>';
			if ($isDetailExist)
			{
				$activityEl = $useGridExtension
					? '<a class="crm-link" target = "_self"href = "#"
							onclick="BX.CrmUIGridExtension.viewActivity(\''
							. CUtil::JSEscape($gridManagerID) . '\', ' . $ID . ', { enableEditButton:'
							. ($allowEdit ? 'true' : 'false') . ' }); return false;"
						>' . htmlspecialcharsbx($timeFormatted) . '
						</a>'
					: '<a class="crm-link" target = "_self" href = "#"
							onclick="BX.CrmInterfaceGridManager.viewActivity(\''
							. CUtil::JSEscape($gridManagerID) . '\', ' . $ID . ', { enableEditButton:'
							. ($allowEdit ? 'true' : 'false') . ' }); return false;"
						>' . htmlspecialcharsbx($timeFormatted) . '
						</a>';
			}

			$result = '
				<div class="crm-nearest-activity-wrapper">
					<div class="crm-list-deal-date crm-nearest-activity-time' . ($isExpired ? '-expiried' : '') . '">' . $activityEl . '</div>
					<div class="crm-nearest-activity-subject">' . htmlspecialcharsbx($subject) . '</div>
			';

			if($allowEdit && !empty($menuItems))
			{
				if($useGridExtension)
				{
					if (\Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled())
					{
						$currentUser = CUtil::PhpToJSObject(static::getUserInfo(true, false));

						$pingSettings = (new TodoPingSettingsProvider(
							$entityTypeId,
							$categoryId
						))->fetchForJsComponent();
						$calendarSettings = (new CalendarSettingsProvider())->fetchForJsComponent();
						$colorSettings = (new ColorSettingsProvider())->fetchForJsComponent();

						$settings = CUtil::PhpToJSObject([
							'pingSettings' => $pingSettings,
							'calendarSettings' => $calendarSettings,
							'colorSettings' => $colorSettings,
						]);

						$jsOnClick = "BX.CrmUIGridExtension.showActivityAddingPopup(this, '" . $preparedGridId . "', " . (int)$entityTypeId . ", " . (int)$entityID . ", " . $currentUser . ", " . $settings . ");";
					}
					else
					{
						$menuID = htmlspecialcharsbx($menuID);
						$menuParams = "{offsetLeft: 30, autoHide: true, closeByEsc: true, angle: { position: 'top', offset: 10 }}";
						$menuItems = array_map('array_change_key_case', $menuItems);
						$menuItems = CUtil::PhpToJSObject($menuItems);
						$jsOnClick = "BX.Main.MenuManager.show('{$menuID}', this, {$menuItems}, {$menuParams});";
					}

					$result .= '<div 
									class="crm-nearest-activity-plus" 
									onclick="'.$jsOnClick.' return false;"
								></div>';
				}
				else
				{
					$result .= '<div class="crm-nearest-activity-plus" onclick="BX.CrmInterfaceGridManager.showMenu(\''.htmlspecialcharsbx($menuID).'\', this);"></div>
					<script>BX.CrmInterfaceGridManager.createMenu("'.$menuID.'", '.CUtil::PhpToJSObject($menuItems).');</script>';
				}
			}

			$result .= '</div>';

			$responsibleID = isset($arParams['ACTIVITY_RESPONSIBLE_ID']) ? intval($arParams['ACTIVITY_RESPONSIBLE_ID']) : 0;
			if($responsibleID > 0)
			{
				$nameTemplate = isset($arParams['NAME_TEMPLATE']) ? $arParams['NAME_TEMPLATE'] : '';
				if($nameTemplate === '')
				{
					$nameTemplate = CSite::GetNameFormat(false);
				}

				$responsibleFullName = CUser::FormatName(
					$nameTemplate,
					array(
						'LOGIN' => isset($arParams['ACTIVITY_RESPONSIBLE_LOGIN']) ? $arParams['ACTIVITY_RESPONSIBLE_LOGIN'] : '',
						'NAME' => isset($arParams['ACTIVITY_RESPONSIBLE_NAME']) ? $arParams['ACTIVITY_RESPONSIBLE_NAME'] : '',
						'LAST_NAME' => isset($arParams['ACTIVITY_RESPONSIBLE_LAST_NAME']) ? $arParams['ACTIVITY_RESPONSIBLE_LAST_NAME'] : '',
						'SECOND_NAME' => isset($arParams['ACTIVITY_RESPONSIBLE_SECOND_NAME']) ? $arParams['ACTIVITY_RESPONSIBLE_SECOND_NAME'] : ''
					),
					true, false
				);

				$responsibleShowUrl = '';
				$pathToUserProfile = isset($arParams['PATH_TO_USER_PROFILE']) ? $arParams['PATH_TO_USER_PROFILE'] : '';
				if($pathToUserProfile !== '')
				{
					$responsibleShowUrl = CComponentEngine::MakePathFromTemplate(
						$pathToUserProfile,
						array('user_id' => $responsibleID)
					);
				}
				$result .= '<div class="crm-list-deal-responsible"><span class="crm-list-deal-responsible-grey">'.htmlspecialcharsbx(GetMessage('CRM_ENTITY_ACTIVITY_FOR_RESPONSIBLE')).'</span><a class="crm-list-deal-responsible-name" target="_blank" href="'.htmlspecialcharsbx($responsibleShowUrl).'">'.htmlspecialcharsbx($responsibleFullName).'</a></div>';
			}
			return $result;
		}
		elseif($allowEdit && !empty($menuItems))
		{
			$hintText = isset($arParams['HINT_TEXT']) && $arParams['HINT_TEXT'] !== ''
				? $arParams['HINT_TEXT'] : GetMessage('CRM_ENTITY_ADD_ACTIVITY_HINT');

			if($useGridExtension)
			{
				if (\Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled())
				{
					$currentUser = CUtil::PhpToJSObject(static::getUserInfo(true, false));

					$pingSettings = (new TodoPingSettingsProvider(
						$entityTypeId,
						$categoryId
					))->fetchForJsComponent();
					$calendarSettings = (new CalendarSettingsProvider())->fetchForJsComponent();
					$colorSettings = (new ColorSettingsProvider())->fetchForJsComponent();

					$settings = CUtil::PhpToJSObject([
						'pingSettings' => $pingSettings,
						'calendarSettings' => $calendarSettings,
						'colorSettings' => $colorSettings,
					]);

					$jsOnClick = "BX.CrmUIGridExtension.showActivityAddingPopup(this, '" . $preparedGridId . "', " . (int)$entityTypeId . ", " . (int)$entityID . ", " . $currentUser . ", " . $settings . ");";
				}
				else
				{
					$menuID = htmlspecialcharsbx($menuID);
					$menuParams = "{offsetLeft: 30, autoHide: true, closeByEsc: true, angle: { position: 'top', offset: 10 }}";
					$menuItems = array_map('array_change_key_case', $menuItems);
					$menuItems = CUtil::PhpToJSObject($menuItems);
					$jsOnClick = "BX.Main.MenuManager.show('{$menuID}', this, {$menuItems}, {$menuParams});";
				}

				return '<span class="crm-activity-add-hint">'.htmlspecialcharsbx($hintText).'</span>
						<a 
							class="crm-activity-add" 
							onclick="'.$jsOnClick.' return false;"
						>'.htmlspecialcharsbx(GetMessage('CRM_ENTITY_ADD_ACTIVITY')).'
						</a>';
			}
			else
			{
				return '<span class="crm-activity-add-hint">'.htmlspecialcharsbx($hintText).'</span>
				<a class="crm-activity-add" onclick="BX.CrmInterfaceGridManager.showMenu(\''.htmlspecialcharsbx($menuID).'\', this); return false;">'.htmlspecialcharsbx(GetMessage('CRM_ENTITY_ADD_ACTIVITY')).'</a>
				<script>BX.CrmInterfaceGridManager.createMenu("'.$menuID.'", '.CUtil::PhpToJSObject($menuItems).');</script>';
			}
		}

		return '';
	}
	public static function RenderListMultiFields(&$arFields, $prefix = '', $arOptions = null)
	{
		$result = array();

		$arEntityTypes = CCrmFieldMulti::GetEntityTypes();

		$arInfos = CCrmFieldMulti::GetEntityTypeInfos();
		foreach($arInfos as $typeID => &$arInfo)
		{
			$result[$typeID] = self::RenderListMultiField($arFields, $typeID, $prefix, $arEntityTypes, $arOptions);
		}
		unset($arInfo);
		return $result;
	}
	public static function RenderListMultiField(&$arFields, $typeName, $prefix = '', $arEntityTypes = null, $arOptions = null)
	{
		$typeName = mb_strtoupper(strval($typeName));
		$prefix = strval($prefix);

		if(!is_array($arEntityTypes))
		{
			$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
		}

		$result = '';

		$arValueTypes = isset($arEntityTypes[$typeName]) ? $arEntityTypes[$typeName] : array();
		if(!empty($arValueTypes))
		{
			$values = self::PrepareListMultiFieldValues($arFields, $typeName, $arValueTypes);

			$result .= ($typeName === 'PHONE'
					? '<div class="crm-client-contacts-block crm-client-contacts-block-handset">'
					: '<div class="crm-client-contacts-block">')
				.self::RenderListMultiFieldValues("{$prefix}{$typeName}", $values, $typeName, $arValueTypes, $arOptions)
				.'</div>';
		}

		return $result;
	}
	public static function PrepareFormMultiField($arEntityFields, $typeName, $prefix = '', $arEntityTypes = null, $arOptions = null)
	{
		$typeName = mb_strtoupper(strval($typeName));
		$prefix = strval($prefix);

		if(!is_array($arEntityTypes))
		{
			$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
		}

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$result = '';
		$qty = 0;
		$enableSip = false;
		$valueTypes = isset($arEntityTypes[$typeName]) ? $arEntityTypes[$typeName] : array();
		if(!empty($valueTypes))
		{
			$values = array();
			$fields = isset($arEntityFields['FM']) && $arEntityFields['FM'][$typeName] ? $arEntityFields['FM'][$typeName] : null;

			$firstItemParams = null;
			if(is_array($fields))
			{
				foreach($fields as &$field)
				{
					$valueType = $field['VALUE_TYPE'];
					$value = $field['VALUE'];

					if($firstItemParams === null)
					{
						$firstItemParams = array('VALUE' => $value, 'VALUE_TYPE_ID' => $valueType);
						if(isset($valueTypes[$valueType]))
						{
							$firstItemParams['VALUE_TYPE'] = $valueTypes[$valueType];
						}
					}

					if(!isset($values[$valueType]))
					{
						$values[$valueType] = array();
					}
					$values[$valueType][] = $value;
					$qty++;
				}
				unset($field);
			}

			if($firstItemParams !== null)
			{
				$itemData = self::PrepareMultiFieldValueItemData($typeName, $firstItemParams, $arOptions);
				$result = $itemData['value'];
				if($typeName === 'PHONE' && isset($itemData['sipCallHtml']) && $itemData['sipCallHtml'] !== '')
				{
					$result .= $itemData['sipCallHtml'];
					$enableSip = true;
				}
			}

			if($qty > 1)
			{
				$anchorID = $prefix.'_'.mb_strtolower($typeName);
				$result .= '<span class="crm-client-contacts-block-text-list-icon" id="'.htmlspecialcharsbx($anchorID).'" onclick="'
					.CCrmViewHelper::PrepareMultiFieldValuesPopup($anchorID, $anchorID, $typeName, $values, $valueTypes, array_merge($arOptions, array('SKIP_FIRST' => true)))
					.'"><span>';
			}
		}

		$containerClassName = 'crm-client-contacts-block-text';
		if($qty > 1)
		{
			$containerClassName .= ' crm-client-contacts-block-text-list';
		}
		if($enableSip)
		{
			$containerClassName .= ' crm-client-contacts-block-handset';
		}

		return "<span class=\"{$containerClassName}\">{$result}</span>";
	}
	public static function PrepareMultiFieldCalltoLink($phone)
	{
		$linkAttrs = CCrmCallToUrl::PrepareLinkAttributes($phone);
		return '<a class="crm-fld-text" href="'
			.htmlspecialcharsbx($linkAttrs['HREF'])
			.'" onclick="'.htmlspecialcharsbx($linkAttrs['ONCLICK']).'">'
			.htmlspecialcharsbx($phone).'</a>';
	}
	public static function PrepareMultiFieldHtml($typeName, $arParams, $arOptions = array())
	{
		$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';
		$valueUrl = $value;

		if($typeName === 'PHONE')
		{
			if($value === '')
			{
				return isset($arOptions['STUB']) ? $arOptions['STUB'] : '';
			}

			$valueUrl = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($value)->format();
			$additionalHtml = '';
			$enableSip = is_array($arOptions) && isset($arOptions['ENABLE_SIP']) && (bool)$arOptions['ENABLE_SIP'];
			if($enableSip)
			{
				$sipParams =  isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : null;
				$additionalHtml = self::PrepareSipCallHtml($value, $sipParams);
			}

			$linkAttrs = CCrmCallToUrl::PrepareLinkAttributes($value, isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : array());
			$className = isset($arParams['CLASS_NAME']) ? $arParams['CLASS_NAME'] : 'crm-client-contacts-block-text-tel';

			return '<a'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '')
				.' title="'.htmlspecialcharsbx($valueUrl).'"'
				.' href="'.htmlspecialcharsbx($linkAttrs['HREF']).'"'
				.' onclick="'.htmlspecialcharsbx($linkAttrs['ONCLICK'])
				.'">'
				.htmlspecialcharsbx($valueUrl).'</a>'.$additionalHtml;
		}
		elseif($typeName === 'EMAIL')
		{
			if($value === '')
			{
				return isset($arOptions['STUB']) ? $arOptions['STUB'] : '';
			}

			$crmEmail = mb_strtolower(trim(COption::GetOptionString('crm', 'mail', '')));
			if($crmEmail !== '')
			{
				$valueUrl = $valueUrl.'?cc='.urlencode($crmEmail);
			}

			$className = isset($arParams['CLASS_NAME']) ? $arParams['CLASS_NAME'] : 'crm-client-contacts-block-text-tel';
			return '<a'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '')
				.' title="'.htmlspecialcharsbx($value).'"'
				.' href="mailto:'.htmlspecialcharsbx($valueUrl).'">'
				.htmlspecialcharsbx($value).'</a>';
		}
		elseif($typeName === 'WEB')
		{
			if($value === '')
			{
				return isset($arOptions['STUB']) ? $arOptions['STUB'] : '';
			}

			$valueUrl = preg_replace('/^\s*http(s)?:\/\//i', '', $value);
		}
		$valueTypeID = isset($arParams['VALUE_TYPE_ID']) ? $arParams['VALUE_TYPE_ID'] : '';
		$valueType = isset($arParams['VALUE_TYPE']) ? $arParams['VALUE_TYPE'] : null;

		if($typeName === 'IM')
		{
			$linkAttrs = OpenLineManager::prepareMultiFieldLinkAttributes($typeName, $valueTypeID, $value);
			if(is_array($linkAttrs))
			{
				$className = isset($arParams['CLASS_NAME']) ? $arParams['CLASS_NAME'] : 'crm-client-contacts-block-text-tel';
				return '<a'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '')
					.' title="'.htmlspecialcharsbx($linkAttrs['TITLE']).'"'
					.' href="'.htmlspecialcharsbx($linkAttrs['HREF']).'"'
					.' onclick="'.htmlspecialcharsbx($linkAttrs['ONCLICK'])
					.'">'
					.htmlspecialcharsbx($linkAttrs['TEXT']).'</a>';
			}
		}

		if(!$valueType && $valueTypeID !== '')
		{
			$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
			$arValueTypes = isset($arEntityTypes[$typeName]) ? $arEntityTypes[$typeName] : array();
			$valueType = isset($arValueTypes[$valueTypeID]) ? $arValueTypes[$valueTypeID] : null;
		}

		if(!($valueType && !empty($valueType['TEMPLATE'])))
		{
			if($value === '')
			{
				return isset($arOptions['STUB']) ? $arOptions['STUB'] : '';
			}

			return htmlspecialcharsbx($value);
		}

		if($value === '')
		{
			return isset($arOptions['STUB']) ? $arOptions['STUB'] : '';
		}

		$template = $valueType['TEMPLATE'];
		//HACK: Crutch for https protocol support.
		if($typeName === 'WEB'
			&& ($valueTypeID === 'HOME' || $valueTypeID === 'WORK' || $valueTypeID = 'OTHER')
			&& preg_match('/^\s*https:\/\//i', $value) === 1)
		{
			$template = preg_replace('/http:\/\//i', 'https://', $template);
		}

		return str_replace(
			array(
				'#VALUE#',
				'#VALUE_URL#',
				'#VALUE_HTML#'
			),
			array(
				$value,
				htmlspecialcharsbx($valueUrl),
				htmlspecialcharsbx($value)
			),
			$template
		);
	}
	public static function PrepareListMultiFieldValues(&$arFields, $typeName, &$arValueTypes)
	{
		$typeName = mb_strtoupper(strval($typeName));

		$result = array();
		foreach($arValueTypes as $valueTypeID => &$arValueType)
		{
			$key1 = "~{$typeName}_{$valueTypeID}";
			$key2 = "{$typeName}_{$valueTypeID}";
			if(isset($arFields[$key1]))
			{
				$result[$valueTypeID] = $arFields[$key1];
			}
			elseif(isset($arFields[$key2]))
			{
				$result[$valueTypeID] = $arFields[$key2];
			}
		}
		unset($arValueType);

		return $result;
	}
	public static function PrepareSipCallHtml($phone, $params = null)
	{
		if(!CCrmSipHelper::checkPhoneNumber($phone))
		{
			return '';
		}

		$entityType = is_array($params) && isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
		$entityId = is_array($params) && isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;
		$activityId = is_array($params) && isset($params['SRC_ACTIVITY_ID']) ? intval($params['SRC_ACTIVITY_ID']) : 0;

		$phone = CUtil::JSEscape(htmlspecialcharsbx($phone));
		$entityType = CUtil::JSEscape(htmlspecialcharsbx($entityType));
		$entityId = CUtil::JSEscape(htmlspecialcharsbx($entityId));
		$activityId = CUtil::JSEscape(htmlspecialcharsbx($activityId));

		$onclick =
			'if(typeof(top.BXIM) === \'undefined\') { window.alert(\''.GetMessageJS('CRM_SIP_NO_SUPPORTED').'\'); return; } '
			.' BX.CrmSipManager.startCall('
			.' { number:\''.$phone.'\', enableInfoLoading: true },'
			.' { ENTITY_TYPE: \''.$entityType.'\', ENTITY_ID: \''.$entityId.'\', SRC_ACTIVITY_ID: \''.$activityId.'\' }, true, this);'
		;

		return '<span class="crm-client-contacts-block-text-tel-icon" onclick="' . $onclick . '"></span>';
	}
	private static function RenderListMultiFieldValues($ID, &$arValues, $typeName, &$arValueTypes, $arOptions = null)
	{
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

		$ID = strval($ID);
		if($ID === '')
		{
			$ID = uniqid('CRM_MULTI_FIELD_');
		}

		$typeName = mb_strtoupper(strval($typeName));
		$result = '';
		$arValueData = array();
		foreach($arValueTypes as $valueTypeID => &$arValueType)
		{
			if(!isset($arValues[$valueTypeID]) || empty($arValues[$valueTypeID]))
			{
				continue;
			}

			foreach($arValues[$valueTypeID] as $value)
			{
				$arValueData[] = array(
					'VALUE_TYPE_ID' => $valueTypeID,
					'VALUE' => $value
				);
			}
		}
		unset($arValueType);

		$qty = count($arValueData);
		if($qty === 0)
		{
			return '';
		}

		$enableSip = is_array($arOptions) && isset($arOptions['ENABLE_SIP']) && (bool)$arOptions['ENABLE_SIP'];
		$sipParams =  $enableSip && isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : null;

		$first = $arValueData[0];
		$firstValueType = isset($arValueTypes[$first['VALUE_TYPE_ID']]) ? $arValueTypes[$first['VALUE_TYPE_ID']] : null;
		if($firstValueType)
		{
			if($typeName === 'PHONE' && $enableSip)
			{
				$additionalHtml = self::PrepareSipCallHtml($first['VALUE'], $sipParams);
				$result .= '<div class="crm-client-contacts-block-text" style="white-space:nowrap;">'
					.self::PrepareMultiFieldHtml($typeName, array('VALUE_TYPE_ID' => $first['VALUE_TYPE_ID'], 'VALUE_TYPE' => $firstValueType, 'VALUE' => $first['VALUE']))
					.$additionalHtml.'</div>';
			}
			else
			{
				$result .= '<div class="crm-client-contacts-block-text">'
					.self::PrepareMultiFieldHtml($typeName, array('VALUE_TYPE_ID' => $first['VALUE_TYPE_ID'], 'VALUE_TYPE' => $firstValueType, 'VALUE' => $first['VALUE']))
					.'</div>';
			}
		}

		if($qty > 1)
		{
			$arPopupItems = array();
			for($i = 1; $i < $qty; $i++)
			{
				$current = $arValueData[$i];
				$valueType = isset($arValueTypes[$current['VALUE_TYPE_ID']]) ? $arValueTypes[$current['VALUE_TYPE_ID']] : null;
				if(!$valueType)
				{
					continue;
				}

				$popupItemData = array(
					'value' => htmlspecialcharsbx(
						self::PrepareMultiFieldHtml($typeName, array('VALUE_TYPE_ID' => $current['VALUE_TYPE_ID'], 'VALUE_TYPE' => $valueType, 'VALUE' => $current['VALUE']))
					),
					'type' => htmlspecialcharsbx(
						isset($valueType['SHORT'])? mb_strtolower($valueType['SHORT']) : ''
					)
				);

				if($typeName === 'PHONE' && $enableSip)
				{
					$popupItemData['sipCallHtml'] = htmlspecialcharsbx(self::PrepareSipCallHtml($current['VALUE'], $sipParams));
				}

				$arPopupItems[] = &$popupItemData;
				unset($popupItemData);
			}

			$buttonID = $ID.'_BTN';
			$result .= '<div class="crm-multi-field-popup-wrapper">';
			$result .= '<span id="'.htmlspecialcharsbx($buttonID)
				.'" class="crm-multi-field-popup-button" onclick="BX.CrmMultiFieldViewer.ensureCreated(\''
				.CUtil::JSEscape($ID).'\', { \'anchorId\':\''.CUtil::JSEscape($buttonID).'\', \'items\':'.CUtil::PhpToJSObject($arPopupItems).', \'typeName\':\''.CUtil::JSEscape($typeName).'\' }).show();">'.htmlspecialcharsbx(GetMessage('CRM_ENTITY_MULTI_FIELDS_MORE')).' '.($qty - 1).'</span>';
			$result .= '</div>';
		}

		return $result;
	}
	public static function PrepareFirstMultiFieldHtml($typeName, $arValues, $arValueTypes, $arParams = array(), $arOptions = array())
	{
		foreach($arValues as $valueTypeID => $values)
		{
			$valueType = isset($arValueTypes[$valueTypeID]) ? $arValueTypes[$valueTypeID] : null;

			foreach($values as $value)
			{
				if($value !== '')
				{
					if(!is_array($arParams))
					{
						$arParams = array();
					}
					$arParams['VALUE_TYPE'] = $valueType;
					$arParams['VALUE'] = $value;
					return self::PrepareMultiFieldHtml($typeName, $arParams, $arOptions);
				}
			}
		}
		return '';
	}
	public static function PrepareMultiFieldValuesPopup($popupID, $achorID, $typeName, $arValues, $arValueTypes, $arOptions = array())
	{
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

		$enableSip = is_array($arOptions) && isset($arOptions['ENABLE_SIP']) && (bool)$arOptions['ENABLE_SIP'];
		$sipParams =  $enableSip && isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : null;
		$skipFirst =  isset($arOptions['SKIP_FIRST']) ? $arOptions['SKIP_FIRST'] : false;
		$isSkipped = false;
		$arPopupItems = array();
		foreach($arValues as $valueTypeID => $values)
		{
			$valueType = isset($arValueTypes[$valueTypeID]) ? $arValueTypes[$valueTypeID] : null;

			foreach($values as $value)
			{
				if($skipFirst && !$isSkipped)
				{
					$isSkipped = true;
					continue;
				}

				$popupItemData = array(
					'value' => htmlspecialcharsbx(
						self::PrepareMultiFieldHtml(
							$typeName,
							array(
								'VALUE_TYPE_ID' => $valueTypeID,
								'VALUE_TYPE' => $valueType,
								'VALUE' => $value
							),
							$arOptions
						)
					),
					'type' => htmlspecialcharsbx(
						isset($valueType['SHORT'])? mb_strtolower($valueType['SHORT']) : ''
					)
				);

				if($enableSip)
				{
					$popupItemData['sipCallHtml'] = htmlspecialcharsbx(self::PrepareSipCallHtml($value, $sipParams));
				}

				$arPopupItems[] = &$popupItemData;
				unset($popupItemData);
			}
		}

		$topmost =  isset($arOptions['TOPMOST']) ? $arOptions['TOPMOST'] : false;
		return 'BX.CrmMultiFieldViewer.ensureCreated(\''
			.CUtil::JSEscape($popupID).'\', { \'anchorId\':\''
			.CUtil::JSEscape($achorID).'\', \'items\':'
			.CUtil::PhpToJSObject($arPopupItems)
			.', \'typeName\':\''.CUtil::JSEscape($typeName).'\''
			.', \'topmost\':'.($topmost ? 'true' : 'false')
			.' }).show();';
	}
	public static function PrepareMultiFieldValueItemData($typeName, $params, $arOptions = array())
	{
		$enableSip = is_array($arOptions) && isset($arOptions['ENABLE_SIP']) && (bool)$arOptions['ENABLE_SIP'];
		$sipParams =  $enableSip && isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : null;
		$value = isset($params['VALUE']) ? $params['VALUE'] : '';
		$valueTypeID = isset($params['VALUE_TYPE_ID']) ? $params['VALUE_TYPE_ID'] : '';
		$valueType = isset($params['VALUE_TYPE']) ? $params['VALUE_TYPE'] : null;
		if(!$valueType && $valueTypeID !== '')
		{
			$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
			$arValueTypes = isset($arEntityTypes[$typeName]) ? $arEntityTypes[$typeName] : array();
			$valueType = isset($arValueTypes[$valueTypeID]) ? $arValueTypes[$valueTypeID] : null;
		}

		$itemData = array(
			'value' =>
				self::PrepareMultiFieldHtml($typeName, $params, $arOptions),
			'type' => htmlspecialcharsbx(
				is_array($valueType) && isset($valueType['SHORT'])? mb_strtolower($valueType['SHORT']) : ''
			)
		);

		if($typeName === 'PHONE' && $enableSip && $value !== '')
		{
			$itemData['sipCallHtml'] = self::PrepareSipCallHtml($value, $sipParams);
		}

		return $itemData;
	}
	public static function PrepareFormResponsible($userID, $nameTemplate, $userProfileUrlTemplate)
	{
		$userID = (int)$userID;
		if($userID <= 0)
		{
			return '';
		}


		$dbUsers = CUser::GetList(
			'id', 'asc',
			array('ID' => $userID),
			array('FIELDS' =>  array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE', 'EMAIL', 'PERSONAL_PHOTO'))
		);

		$user = $dbUsers->Fetch();
		if(!is_array($user))
		{
			return '';
		}

		$name = CUser::FormatName(
			$nameTemplate,
			$user,
			true,
			true
		);

		$photoID = isset($user['PERSONAL_PHOTO']) ? intval($user['PERSONAL_PHOTO']) : 0;
		$photoUrl = '';
		if($photoID > 0)
		{
			$photoInfo = CFile::ResizeImageGet(
				$photoID,
				array('width' => 38, 'height' => 38),
				BX_RESIZE_IMAGE_EXACT
			);
			$photoUrl = is_array($photoInfo) ? $photoInfo['src'] : '';
		}

		$showUrl = $userID > 0 && $userProfileUrlTemplate !== '' ? str_replace('#user_id#', $userID, $userProfileUrlTemplate) : '#';

		return "<span class=\"crm-detail-info-resp\"><div class=\"crm-detail-info-resp-img\"><a href=\"{$showUrl}\" target=\"_blank\"><img alt=\"\" src=\"{$photoUrl}\" /></a></div><a class=\"crm-detail-info-resp-name\" target=\"_blank\">{$name}</a></span>";
	}
	public static function RenderResponsiblePanel($arParams)
	{
		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$editable = isset($arParams['EDITABLE']) ? $arParams['EDITABLE'] : false;
		$userProfileUrlTemplate = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
		$userID = isset($arParams['USER_ID']) ? $arParams['USER_ID'] : '';
		$showUrl = $userID > 0 && $userProfileUrlTemplate !== '' ? str_replace('#user_id#', $userID, $userProfileUrlTemplate) : '#';

		$caption = isset($arParams['CAPTION']) && is_string($arParams['CAPTION']) && $arParams['CAPTION'] !== ''
			? $arParams['CAPTION'] : GetMessage('CRM_ENTITY_INFO_RESPONSIBLE');

		echo '<div class="crm-detail-info-resp-block">';
		echo '<div class="crm-detail-info-resp-header">';
		echo '<span class="crm-detail-info-resp-text">', htmlspecialcharsbx($caption), '</span>';

		$editButtonID = '';
		if($editable)
		{
			$editButtonID = isset($arParams['EDIT_BUTTON_ID']) ? $arParams['EDIT_BUTTON_ID'] : '';
			if($editButtonID === '')
			{
				$editButtonID = $prefix !== '' ? "{$prefix}_responsible_edit" : 'responsible_edit';
			}
			echo '<span class="crm-detail-info-resp-edit" id="', htmlspecialcharsbx($editButtonID), '">', htmlspecialcharsbx(GetMessage('CRM_ENTITY_INFO_RESPONSIBLE_CHANGE')), '</span>';
		}

		echo '</div>';

		$containerID = isset($arParams['CONTAINER_ID']) ? $arParams['CONTAINER_ID'] : '';
		if($containerID === '')
		{
			$containerID = $prefix !== '' ? "{$prefix}_responsible_container" : 'responsible_container';
		}
		echo '<a class="crm-detail-info-resp crm-detail-info-head-resp" id="', htmlspecialcharsbx($containerID), '" target="_blank" href="', htmlspecialcharsbx($showUrl), '">';

		echo '<div class="crm-detail-info-resp-img">';

		$photoUrl = isset($arParams['PHOTO_URL']) ? $arParams['PHOTO_URL'] : '';
		$photoID = isset($arParams['PHOTO']) ? intval($arParams['PHOTO']) : 0;
		if($photoUrl === '' && $photoID > 0)
		{
			$photoInfo = CFile::ResizeImageGet($photoID, array('width' => 100, 'height' => 100), BX_RESIZE_IMAGE_EXACT);
			if(is_array($photoInfo) && isset($photoInfo['src']))
			{
				$photoUrl = $photoInfo['src'];
			}
		}
		if($photoUrl !== '')
		{
			echo '<img alt="" width="38" height="38" src="', htmlspecialcharsbx($photoUrl), '"/>';
		}
		echo '</div>';

		echo '<span class="crm-detail-info-resp-name">', (isset($arParams['NAME']) ? htmlspecialcharsbx($arParams['NAME']) : ''), '</span>';

		echo '<span class="crm-detail-info-resp-descr">', (isset($arParams['WORK_POSITION']) ? htmlspecialcharsbx($arParams['WORK_POSITION']) : ''), '</span>';
		echo '</a>';

		$serviceUrl = isset($arParams['SERVICE_URL']) ? $arParams['SERVICE_URL'] : '';
		$userInfoProviderID = isset($arParams['USER_INFO_PROVIDER_ID']) ? $arParams['USER_INFO_PROVIDER_ID'] : '';
		if($userInfoProviderID === '')
		{
			$userInfoProviderID = $serviceUrl !== '' ? md5(mb_strtolower($serviceUrl)) : '';
		}

		if($userInfoProviderID !== '')
		{
			if(!self::$USER_INFO_PROVIDER_MESSAGES_REGISTRED)
			{
				echo '<script>',
				'BX.ready(function(){',
				'BX.CrmUserInfoProvider.messages = ',
				'{ "generalError":"', GetMessageJS('CRM_GET_USER_INFO_GENERAL_ERROR'), '" }',
				'});',
				'</script>';

				self::$USER_INFO_PROVIDER_MESSAGES_REGISTRED = true;
			}

			echo '<script>',
			'BX.ready(function(){',
			'BX.CrmUserInfoProvider.createIfNotExists(',
			'"', CUtil::JSEscape($userInfoProviderID), '",',
			'{ "serviceUrl":"', CUtil::JSEscape($serviceUrl), '", "userProfileUrlTemplate":"', CUtil::JSEscape($userProfileUrlTemplate) , '" }',
			');',
			'});',
			'</script>';
		}

		$instantEditorID = isset($arParams['INSTANT_EDITOR_ID']) ? $arParams['INSTANT_EDITOR_ID'] : '';
		$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';

		if(!$editable)
		{
			echo '<script>',
			'BX.ready(function(){',
			'BX.CrmUserLinkField.create(',
			'{',
			'"containerId":"', CUtil::JSEscape($containerID), '"',
			', "userInfoProviderId":"', CUtil::JSEscape($userInfoProviderID), '"',
			', "editorId":"', CUtil::JSEscape($instantEditorID), '"',
			', "fieldId":"', CUtil::JSEscape($fieldID), '"',
			'}',
			');',
			'});',
			'</script>';
		}
		else
		{
			$userSelectorName = isset($arParams['USER_SELECTOR_NAME']) ? $arParams['USER_SELECTOR_NAME'] : '';
			if($userSelectorName === '')
			{
				$userSelectorName = $prefix !== '' ? "{$prefix}_responsible_selector" : 'responsible_selector';
			}

			$enableLazyLoad = isset($arParams['ENABLE_LAZY_LOAD']) ? $arParams['ENABLE_LAZY_LOAD'] : false;
			if($enableLazyLoad)
			{
				$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js');
				$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css');
			}
			else
			{
				$GLOBALS['APPLICATION']->IncludeComponent(
					'bitrix:intranet.user.selector.new',
					'.default',
					array(
						'MULTIPLE' => 'N',
						'NAME' => $userSelectorName,
						'POPUP' => 'Y',
						'SITE_ID' => SITE_ID
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);
			}

			echo '<script>';
			echo 'BX.ready(function(){';
			echo 'BX.CrmSidebarUserSelector.create(',
			'"', $userSelectorName, '", ',
			'BX("', CUtil::JSEscape($editButtonID), '"), ',
			'BX("', CUtil::JSEscape($containerID), '"), ',
			'"', CUtil::JSEscape($userSelectorName), '", ',
			'{',
			'"userInfoProviderId":"', CUtil::JSEscape($userInfoProviderID), '"',
			', "editorId":"', CUtil::JSEscape($instantEditorID),'"',
			', "fieldId":"', CUtil::JSEscape($fieldID), '"',
			', "enableLazyLoad":', $enableLazyLoad ? 'true' : 'false',
			', "serviceUrl":"', CUtil::JSEscape($serviceUrl), '"',
			'}',
			');';
			echo '});';
			echo '</script>';
		}

		echo '</div>';

	}
	public static function RenderInstantEditorField($arParams)
	{
		$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';
		$type = isset($arParams['TYPE']) ? $arParams['TYPE'] : '';

		if($type === 'TEXT')
		{
			$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';
			$suffixHtml = isset($arParams['SUFFIX_HTML']) ? $arParams['SUFFIX_HTML'] : '';
			if($suffixHtml === '')
			{
				$suffix = isset($arParams['SUFFIX']) ? $arParams['SUFFIX'] : '';
				if($suffix !== '')
				{
					$suffixHtml = htmlspecialcharsbx($suffix);
				}
			}
			$inputWidth = isset($arParams['INPUT_WIDTH']) ? intval($arParams['INPUT_WIDTH']) : 0;

			echo '<span class="crm-instant-editor-fld crm-instant-editor-fld-input">',
			'<span class="crm-instant-editor-fld-text">', htmlspecialcharsbx($value), '</span>';

			echo '<input class="crm-instant-editor-data-input" type="text" value="', htmlspecialcharsbx($value),
			'" style="display:none;', ($inputWidth > 0 ? "width:{$inputWidth}px;" : ''), '" />',
			'<input class="crm-instant-editor-data-name" type="hidden" value="', htmlspecialcharsbx($fieldID), '" />';

			if($suffixHtml !== '')
			{
				echo '<span class="crm-instant-editor-fld-suffix">', $suffixHtml, '</span>';
			}

			echo '</span><span class="crm-instant-editor-fld-btn crm-instant-editor-fld-btn-input"></span>';
		}
		elseif($type === 'LHE')
		{
			$editorID = isset($arParams['EDITOR_ID']) ? $arParams['EDITOR_ID'] : '';
			if($editorID ==='')
			{
				$editorID = uniqid('LHE_');
			}

			$editorJsName = isset($arParams['EDITOR_JS_NAME']) ? $arParams['EDITOR_JS_NAME'] : '';
			if($editorJsName ==='')
			{
				$editorJsName = $editorID;
			}


			$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';

			/*if($value === '<br />')
			{
				$value = '';
			}*/

			echo '<span class="crm-instant-editor-fld-text">';
			echo $value;
			echo '</span>';
			echo '<div class="crm-instant-editor-fld-btn crm-instant-editor-fld-btn-lhe"></div>';
			echo '<input class="crm-instant-editor-data-name" type="hidden" value="', htmlspecialcharsbx($fieldID), '" />';
			echo '<input class="crm-instant-editor-data-value" type="hidden" value="', htmlspecialcharsbx($value), '" />';

			$wrapperID = isset($arParams['WRAPPER_ID']) ? $arParams['WRAPPER_ID'] : '';
			if($wrapperID ==='')
			{
				$wrapperID = $editorID.'_WRAPPER';
			}

			$toolbarConfig = is_array($arParams['TOOLBAR_CONFIG']) ? $arParams['TOOLBAR_CONFIG'] : '';
			if ($toolbarConfig === '')
			{
				$toolbarConfig = array(
					'Bold', 'Italic', 'Underline', 'Strike',
					'BackColor', 'ForeColor',
					'CreateLink', 'DeleteLink',
					'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
				);
			}

			echo '<input class="crm-instant-editor-lhe-data" type="hidden" value="',
			htmlspecialcharsbx('{ "id":"'.CUtil::JSEscape($editorID).'", "wrapperId":"'.CUtil::JSEscape($wrapperID).'", "jsName":"'.CUtil::JSEscape($editorJsName).'" }'),
			'" />';

			echo '<div id="', htmlspecialcharsbx($wrapperID),'" style="display:none;">';

			CModule::IncludeModule('fileman');
			$editor = new CLightHTMLEditor;
			$editor->Show(
				array(
					'id' => $editorID,
					'width' => '600',
					'height' => '200',
					'bUseFileDialogs' => false,
					'bFloatingToolbar' => false,
					'bArisingToolbar' => false,
					'bResizable' => false,
					'jsObjName' => $editorJsName,
					'bInitByJS' => false, // TODO: Lazy initialization
					'bSaveOnBlur' => true,
					'bHandleOnPaste'=> false,
					'toolbarConfig' => $toolbarConfig
				)
			);
			echo '</div>';
		}
	}
	public static function RenderSelector($arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';
		//Items must be html encoded
		$items = isset($arParams['ITEMS']) ? $arParams['ITEMS'] : array();
		$encodeItems = isset($arParams['ENCODE_ITEMS']) ? (bool)$arParams['ENCODE_ITEMS'] : true;
		$resultItems = array();
		foreach($items as $id => $caption)
		{
			$resultItems[] = array(
				'id' => $id,
				'caption' => !$encodeItems ? $caption : htmlspecialcharsbx($caption)
			);
		}

		$text =  $value !== '' && isset($items[$value]) ? $items[$value] : '';

		if($text === '')
		{
			$text = isset($arParams['UNDEFINED']) ? htmlspecialcharsbx($arParams['UNDEFINED']) : '';
		}

		$editable = isset($arParams['EDITABLE']) ? $arParams['EDITABLE'] : false;
		if($editable)
		{
			$selectorName = isset($arParams['SELECTOR_ID']) ? $arParams['SELECTOR_ID'] : 'selector';
			$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';
			//$containerID = isset($arParams['CONTAINER_ID']) ? $arParams['CONTAINER_ID'] : 'sidebar';

			$containerClassName = isset($arParams['CONTAINER_CLASS']) ? $arParams['CONTAINER_CLASS'] : '';
			echo '<span',
			($containerClassName !== '' ? ' class="'.htmlspecialcharsbx($containerClassName).'"' : ''),
			'>';

			$uniqueID = uniqid();

			$itemID = "{$selectorName}_{$uniqueID}";
			$textClassName = isset($arParams['TEXT_CLASS']) ? $arParams['TEXT_CLASS'] : '';
			echo '<span id="', htmlspecialcharsbx($itemID), '"';
			if($textClassName !== '')
			{
				echo ' class="', htmlspecialcharsbx($textClassName), '"';
			}

			echo '>', $text, '</span>';

			$buttonID = '';
			$arrowClassName = isset($arParams['ARROW_CLASS']) ? $arParams['ARROW_CLASS'] : '';
			if($arrowClassName !== '')
			{
				$buttonID = "{$selectorName}_btn_{$uniqueID}";
				echo '<span id="', htmlspecialcharsbx($buttonID),'" class="', htmlspecialcharsbx($arrowClassName), '"></span>';
			}

			echo '<script>';
			echo 'BX.ready(function(){',
			'BX.CmrSidebarFieldSelector.create(',
			'"', CUtil::JSEscape($selectorName), '",',
			'"', CUtil::JSEscape($fieldID), '",',
			'BX("', CUtil::JSEscape($itemID) ,'"),',
			'{
					"options": ', CUtil::PhpToJSObject($resultItems), ',
					"buttonId":', CUtil::JSEscape($buttonID) ,'
				});});';
			echo '</script>';

			echo '</span>';
		}
		else
		{
			echo htmlspecialcharsbx($text);
		}
	}
	public static function PrepareHtml(&$arData)
	{
		if(!is_array($arData))
		{
			return '';
		}

		if(isset($arData['HTML']))
		{
			return $arData['HTML'];
		}
		elseif(isset($arData['TEXT']))
		{
			return htmlspecialcharsbx($arData['TEXT']);
		}

		return '';
	}

	public static function RenderWidgetFilterPeriod(array $arParams)
	{
		$editorID = isset($arParams['EDITOR_ID']) ? strval($arParams['EDITOR_ID']) : '';
		$paramID = isset($arParams['PARAM_ID']) ? strval($arParams['PARAM_ID']) : '';
		$paramName = isset($arParams['PARAM_NAME']) ? strval($arParams['PARAM_NAME']) : $paramID;
		$config = isset($arParams['CONFIG']) ? $arParams['CONFIG'] : array();

		$periodType = isset($config['periodType'])
			? $config['periodType']
			: Bitrix\Crm\Widget\FilterPeriodType::UNDEFINED;

		$prefix = mb_strtolower($editorID);
		$controls = array(
			'period' => "{$prefix}_type",
			'year' => "{$prefix}_year",
			'quarter' => "{$prefix}_quarter",
			'month' => "{$prefix}_month",
			'yearWrap' => "{$prefix}_year_wrap",
			'quarterWrap' => "{$prefix}_quarter_wrap",
			'monthWrap' => "{$prefix}_month_wrap"
		);

		echo '<input type="hidden" id="', $paramID, '" name="', $paramName, '" />';

		echo '<span class="bx-select-wrap">',
		'<select id="', $controls['period'], '" class="bx-select"></select>',
		'</span>';

		echo '<span id="', $controls['quarterWrap'] ,'" class="bx-select-wrap" style="margin: 0 0 5px;',
		($periodType !== Bitrix\Crm\Widget\FilterPeriodType::QUARTER ? ' display:none;' : ''),'">',
		'<select id="', $controls['quarter'], '" class="bx-select"></select>',
		'</span>';

		echo '<span id="', $controls['monthWrap'] ,'" class="bx-select-wrap" style="margin: 0 0 5px;',
		($periodType !== Bitrix\Crm\Widget\FilterPeriodType::MONTH ? ' display:none;' : ''),' ">',
		'<select id="', $controls['month'], '" class="bx-select"></select>',
		'</span>';

		echo '<span id="', $controls['yearWrap'] ,'" class="bx-select-wrap" style="margin: 0 0 5px;',
		($periodType !== Bitrix\Crm\Widget\FilterPeriodType::YEAR
		&& $periodType !== Bitrix\Crm\Widget\FilterPeriodType::MONTH
		&& $periodType !== Bitrix\Crm\Widget\FilterPeriodType::QUARTER ? ' display:none;' : ''),'">',
		'<select id="', $controls['year'], '" class="bx-select"></select>',
		'</span>';

		echo '<script>',
		'BX.ready(function(){',
		'BX.addCustomEvent(window, "CrmWidgetPanelCreated", ',
		'function(){ BX.CrmWidgetConfigPeriodEditor.create("', $editorID, '", { isNested: false, config: ', CUtil::PhpToJSObject($config), ', controls: ', CUtil::PhpToJSObject($controls) ,' }); }',
		');',
		'}); </script>';
	}
	public static function RenderUserCustomSearch($arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

		$editorID = isset($arParams['ID']) ? strval($arParams['ID']) : '';
		$searchInputID = isset($arParams['SEARCH_INPUT_ID']) ? strval($arParams['SEARCH_INPUT_ID']) : '';
		$searchInputName = isset($arParams['SEARCH_INPUT_NAME']) ? strval($arParams['SEARCH_INPUT_NAME']) : '';
		if($searchInputName === '')
		{
			$searchInputName = $searchInputID;
		}

		$dataInputID = isset($arParams['DATA_INPUT_ID']) ? strval($arParams['DATA_INPUT_ID']) : '';
		$dataInputName = isset($arParams['DATA_INPUT_NAME']) ? strval($arParams['DATA_INPUT_NAME']) : '';
		if($dataInputName === '')
		{
			$dataInputName = $dataInputID;
		}

		$componentName = isset($arParams['COMPONENT_NAME']) ? strval($arParams['COMPONENT_NAME']) : '';

		$siteID = isset($arParams['SITE_ID']) ? strval($arParams['SITE_ID']) : '';
		if($siteID === '')
		{
			$siteID = SITE_ID;
		}

		$nameFormat = isset($arParams['NAME_FORMAT']) ? strval($arParams['NAME_FORMAT']) : '';
		if($nameFormat === '')
		{
			$nameFormat = CSite::GetNameFormat(false);
		}

		$user = isset($arParams['USER']) && is_array($arParams['USER']) ? $arParams['USER'] : array();
		$zIndex = isset($arParams['ZINDEX']) ? intval($arParams['ZINDEX']) : 0;

		/*
		//new style with user clear support
		echo '<span class="webform-field webform-field-textbox webform-field-textbox-empty webform-field-textbox-clearable">',
			'<span class="webform-field-textbox-inner">',
			'<input type="text" class="webform-field-textbox" id="', htmlspecialcharsbx($searchInputID) ,'" name="', htmlspecialcharsbx($searchInputName), '">',
			'<a class="webform-field-textbox-clear" href="#"></a>',
			'</span></span>',
			'<input type="hidden" id="', htmlspecialcharsbx($dataInputID),'" name="', htmlspecialcharsbx($dataInputName), '" value="">';
		*/
		$searchInputHint = isset($arParams['SEARCH_INPUT_HINT']) ? strval($arParams['SEARCH_INPUT_HINT']) : '';
		if($searchInputHint !== '')
		{
			$searchInputHint = 'BX.hint(this, \''.CUtil::JSEscape($searchInputHint).'\');';
		}
		echo '<span class="crm-filter-name-container"><span class="crm-filter-name-clean"></span><input class="crm-filter-name-item" type="text" id="', htmlspecialcharsbx($searchInputID) ,'" name="', htmlspecialcharsbx($searchInputName), '" style="width:200px;" autocomplete="off"',
		$searchInputHint !== '' ? ' onmouseover="'.$searchInputHint.'">' : '>','</span>',
		'<input type="hidden" id="', htmlspecialcharsbx($dataInputID),'" name="', htmlspecialcharsbx($dataInputName), '" value="">';

		$delay = isset($arParams['DELAY']) ? intval($arParams['DELAY']) : 0;

		echo '<script>',
		'BX.ready(function(){',
		'BX.CrmUserSearchPopup.deletePopup("', $editorID, '");',
		'BX.CrmUserSearchPopup.create("', $editorID, '", { searchInput: BX("', CUtil::JSEscape($searchInputID), '"), dataInput: BX("', CUtil::JSEscape($dataInputID),'"), componentName: "', CUtil::JSEscape($componentName),'", user: ', CUtil::PhpToJSObject(array_change_key_case($user, CASE_LOWER)) ,', zIndex: ', $zIndex,' }, ', $delay,');',
		'}); </script>';

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => $componentName,
				'INPUT_NAME' => $searchInputID,
				'SHOW_EXTRANET_USERS' => 'NONE',
				'POPUP' => 'Y',
				'SITE_ID' => $siteID,
				'NAME_TEMPLATE' => $nameFormat
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	public static function RenderUserSearch($ID, $searchInputID, $dataInputID, $componentName, $siteID = '', $nameFormat = '', $delay = 0, array $options = null)
	{
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

		if(!is_array($options))
		{
			$options = array();
		}

		$ID = strval($ID);
		$searchInputID = strval($searchInputID);
		$dataInputID = strval($dataInputID);
		$componentName = strval($componentName);

		$siteID = strval($siteID);
		if($siteID === '')
		{
			$siteID = SITE_ID;
		}

		$nameFormat = strval($nameFormat);
		if($nameFormat === '')
		{
			$nameFormat = CSite::GetNameFormat(false);
		}

		$delay = intval($delay);
		if($delay < 0)
		{
			$delay = 0;
		}

		if(!isset($options['RENDER_SEARCH_INPUT']) || $options['RENDER_SEARCH_INPUT'])
		{
			echo '<input type="text" id="', htmlspecialcharsbx($searchInputID) ,'" style="width:200px;">';
		}

		if(!isset($options['RENDER_DATA_INPUT']) || $options['RENDER_DATA_INPUT'])
		{
			echo '<input type="hidden" id="', htmlspecialcharsbx($dataInputID),'" name="', htmlspecialcharsbx($dataInputID),'">';
		}

		echo '<script>',
		'BX.ready(function(){',
		'BX.CrmUserSearchPopup.deletePopup("', $ID, '");',
		'BX.CrmUserSearchPopup.create("', $ID, '", { searchInput: BX("', CUtil::JSEscape($searchInputID), '"), dataInput: BX("', CUtil::JSEscape($dataInputID),'"), componentName: "', CUtil::JSEscape($componentName),'", user: {} }, ', $delay,');',
		'});</script>';

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => $componentName,
				'INPUT_NAME' => $searchInputID,
				'SHOW_EXTRANET_USERS' => 'NONE',
				'POPUP' => 'Y',
				'SITE_ID' => $siteID,
				'NAME_TEMPLATE' => $nameFormat
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	public static function RenderFiles($fileIDs, $fileUrlTemplate = '', $fileMaxWidth = 0, $fileMaxHeight = 0)
	{
		if(!is_array($fileIDs))
		{
			return 0;
		}
		$fileUrlTemplate = strval($fileUrlTemplate);
		$fileMaxWidth = intval($fileMaxWidth);
		if($fileMaxWidth <= 0)
		{
			$fileMaxWidth = 350;
		}
		$fileMaxHeight = intval($fileMaxHeight);
		if($fileMaxHeight <= 350)
		{
			$fileMaxHeight = 350;
		}

		$file = new CFile();
		$processed = 0;
		foreach($fileIDs as $fileID)
		{
			$fileInfo = $file->GetFileArray($fileID);
			if (!is_array($fileInfo))
			{
				continue;
			}

			if($processed > 0)
			{
				echo '<span class="bx-br-separator"><br/></span>';
			}

			echo '<span class="fields files">';

			$fileInfo['name'] = $fileInfo['ORIGINAL_NAME'];

			if ($file->IsImage($fileInfo['ORIGINAL_NAME'], $fileInfo['CONTENT_TYPE']))
			{
				echo $file->ShowImage($fileInfo, $fileMaxWidth, $fileMaxHeight, '', '', true, false, 0, 0, $fileUrlTemplate);
			}
			else
			{
				echo '<span class="crm-entity-file-info"><a target="_blank" class="crm-entity-file-link" href="',
				htmlspecialcharsbx(
					CComponentEngine::MakePathFromTemplate(
						$fileUrlTemplate,
						array('file_id' => $fileInfo['ID'])
					)
				), '">',
					htmlspecialcharsbx($fileInfo['ORIGINAL_NAME']).'</a><span class="crm-entity-file-size">',
					CFile::FormatSize($fileInfo['FILE_SIZE']).'</span></span>';
			}

			echo '</span>';
			$processed++;
		}

		return $processed;
	}

	public static function GetDealStageInfos($categoryID = 0)
	{
		return self::PrepareDealStages($categoryID);
	}

	protected static function PrepareDealStages($categoryID = 0)
	{
		if(!is_int($categoryID))
		{
			$categoryID = (int)$categoryID;
		}
		$categoryID = max($categoryID, 0);

		if(!is_array(self::$DEAL_STAGES))
		{
			self::$DEAL_STAGES = array();
		}

		if(isset(self::$DEAL_STAGES[$categoryID]))
		{
			return self::$DEAL_STAGES[$categoryID];
		}

		self::$DEAL_STAGES[$categoryID] = CCrmDeal::GetStages($categoryID);

		return self::$DEAL_STAGES[$categoryID];
	}
	public static function AreDealStageColorsEnabled($categoryID = 0)
	{
		return true;
	}
	public static function PrepareDealStageExtraParams(array &$infos, $categoryID = -1)
	{
		foreach(array_keys($infos) as $statusID)
		{
			$semanticID = CCrmDeal::GetSemanticID($statusID, $categoryID);
			$infos[$statusID]['SEMANTICS'] = $semanticID;
			if(!isset($infos[$statusID]['COLOR']))
			{
				if($semanticID === Bitrix\Crm\PhaseSemantics::SUCCESS)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::SUCCESS_COLOR;
				}
				elseif($semanticID === Bitrix\Crm\PhaseSemantics::FAILURE)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::FAILURE_COLOR;
				}
				else
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::PROCESS_COLOR;
				}
			}
		}
	}
	private static function PrepareDealStagesByCategoryId($categoryId): array
	{
		$result = array();

		$isTresholdPassed = false;

		$canWriteConfig = Container::getInstance()->getUserPermissions()->canWriteConfig();
		$successStageID = CCrmDeal::GetSuccessStageID($categoryId);
		$failureStageID = CCrmDeal::GetFailureStageID($categoryId);
		$preparedDealStages = self::PrepareDealStages($categoryId);
		(new StagePermissions(CCrmOwnerType::Deal, $categoryId))
			->fill($preparedDealStages)
		;

		foreach($preparedDealStages as $stage)
		{
			$info = array(
				'id' => $stage['STATUS_ID'],
				'name' => $stage['NAME'],
				'sort' => intval($stage['SORT']),
				'color' => isset($stage['COLOR']) ? $stage['COLOR'] : '',
				'stagesToMove' => $stage['STAGES_TO_MOVE'] ?? [],
				'allowMoveToAnyStage' => $canWriteConfig,
			);

			if($stage['STATUS_ID'] === $successStageID)
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_DEAL_STAGE_MANAGER_WON_STEP_HINT');
			}
			elseif($stage['STATUS_ID'] ===  $failureStageID)
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}
		return $result;
	}
	public static function RenderDealStageSettings($categoryId = -1): string
	{
		$result = array();
		if ($categoryId === -1 || $categoryId === null)
		{
			foreach(DealCategory::getAllIDs() as $categoryID)
			{
				$typeID = "category_{$categoryID}";
				$result[$typeID] = self::PrepareDealStagesByCategoryId($categoryID);
			}
		}
		else
		{
			$typeID = "category_{$categoryId}";
			$result[$typeID] = self::PrepareDealStagesByCategoryId($categoryId);
		}

		$messages = array(
			'dialogTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_DLG_TTL'),
			//'apologyTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_APOLOGY_TTL'),
			'failureTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_SELECTOR_TTL'),
			'checkErrorTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_CHECK_ERROR_TTL'),
			'checkErrorHelp' => GetMessage('CRM_STAGE_MANAGER_CHECK_ERROR_HELP'),
			'checkErrorHelpArticleCode' => '8233923'
		);

		return '<script>'
			.'BX.ready(function(){ if(typeof(BX.CrmDealStageManager) === "undefined") return; BX.CrmDealStageManager.infos = '.CUtil::PhpToJSObject($result).'; BX.CrmDealStageManager.messages = '.CUtil::PhpToJSObject($messages).'; });'
			.'</script>';
	}

	public static function GetLeadStatusInfos()
	{
		return self::PrepareLeadStatuses();
	}
	public static function AreLeadStatusColorsEnabled()
	{
		return true;
	}
	public static function PrepareLeadStatusInfoExtraParams(array &$infos)
	{
		foreach(array_keys($infos) as $statusID)
		{
			$semanticID = CCrmLead::GetSemanticID($statusID);
			$infos[$statusID]['SEMANTICS'] = $semanticID;
			if(!isset($infos[$statusID]['COLOR']))
			{
				if($semanticID === Bitrix\Crm\PhaseSemantics::SUCCESS)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::SUCCESS_COLOR;
				}
				elseif($semanticID === Bitrix\Crm\PhaseSemantics::FAILURE)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::FAILURE_COLOR;
				}
				else
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::PROCESS_COLOR;
				}
			}
		}
	}
	protected static function PrepareLeadStatuses()
	{
		if(self::$LEAD_STATUSES !== null)
		{
			return self::$LEAD_STATUSES;
		}

		self::$LEAD_STATUSES = CCrmLead::GetStatuses();

		return self::$LEAD_STATUSES;
	}
	public static function RenderLeadStatusSettings()
	{
		$result = array();
		$isThresholdPassed = false;
		$canWriteConfig = Container::getInstance()->getUserPermissions()->canWriteConfig();

		$preparedLeadStatuses = self::PrepareLeadStatuses();
		(new StagePermissions(CCrmOwnerType::Lead, null))
			->fill($preparedLeadStatuses)
		;

		foreach($preparedLeadStatuses as $status)
		{
			$info = array(
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT']),
				'color' => isset($status['COLOR']) ? $status['COLOR'] : '',
				'stagesToMove' => $status['STAGES_TO_MOVE'] ?? [],
				'allowMoveToAnyStage' => $canWriteConfig,
			);

			if($status['STATUS_ID'] === 'CONVERTED')
			{
				$isThresholdPassed = true;
				$info['semantics'] = 'success';
				$info['name'] = $status['NAME'];
				$info['hint'] = GetMessage('CRM_LEAD_STATUS_MANAGER_CONVERTED_STEP_HINT');
			}
			elseif($status['STATUS_ID'] === 'JUNK')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isThresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}

		$messages = array(
			'dialogTitle' => GetMessage('CRM_LEAD_STATUS_MANAGER_DLG_TTL'),
			//'apologyTitle' => Get?Message('CRM_LEAD_STATUS_MANAGER_APOLOGY_TTL'),
			'failureTitle' => GetMessage('CRM_LEAD_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_LEAD_STATUS_MANAGER_SELECTOR_TTL'),
			'checkErrorTitle' => GetMessage('CRM_LEAD_STAGE_MANAGER_CHECK_ERROR_TTL_MSGVER_1'),
			'checkErrorHelp' => GetMessage('CRM_STAGE_MANAGER_CHECK_ERROR_HELP'),
			'checkErrorHelpArticleCode' => '8233923',
			'conversionCancellationTitle' => GetMessage('CRM_CONFIRMATION_DLG_TTL_MSGVER_1'),
			'conversionCancellationContent' => GetMessage('CRM_LEAD_STATUS_MANAGER_CONVERSION_CANCEL_CNT_MSGVER_1')
		);

		return '<script>'
			.'BX.ready(function(){ if(typeof(BX.CrmLeadStatusManager) === "undefined") return; BX.CrmLeadStatusManager.infos = '.CUtil::PhpToJSObject($result).'; BX.CrmLeadStatusManager.messages = '.CUtil::PhpToJSObject($messages).'; });'
			.'</script>';
	}

	public static function GetInvoiceStatusInfos()
	{
		return self::PrepareInvoiceStatuses();
	}
	protected static function PrepareInvoiceStatuses()
	{
		if(self::$INVOICE_STATUSES !== null)
		{
			return self::$INVOICE_STATUSES;
		}

		self::$INVOICE_STATUSES = CCrmStatus::GetStatus('INVOICE_STATUS');

		return self::$INVOICE_STATUSES;
	}
	public static function RenderInvoiceStatusSettings()
	{
		$result = array();
		$isTresholdPassed = false;
		$canWriteConfig = Container::getInstance()->getUserPermissions()->canWriteConfig();

		foreach(self::PrepareInvoiceStatuses() as $status)
		{
			$info = array(
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT']),
				'color' => isset($status['COLOR']) ? $status['COLOR'] : ''
			);

			if($status['STATUS_ID'] === 'P')
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_INVOICE_STATUS_MANAGER_F_STEP_HINT');
				$info['hasParams'] = true;
			}
			elseif($status['STATUS_ID'] === 'D')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}

			$info['stagesToMove'] = ($status['STAGES_TO_MOVE'] ?? []);
			$info['allowMoveToAnyStage'] = $canWriteConfig;

			$result[] = $info;
		}

		$settings = array(
			'imagePath' => '/bitrix/js/crm/images/',
			'serverTime' => time() + CTimeZone::GetOffset()
		);

		$messages = array(
			'dialogTitle' => GetMessage('CRM_INVOICE_STATUS_MANAGER_DLG_TTL'),
			'failureTitle' => GetMessage('CRM_INVOICE_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_INVOICE_STATUS_MANAGER_SELECTOR_TTL'),
			'setDate' =>  GetMessage('CRM_INVOICE_STATUS_MANAGER_SET_DATE'),
			'dateLabelText' => GetMessage('CRM_INVOICE_STATUS_MANAGER_DATE_LABEL'),
			'payVoucherNumLabelText' => GetMessage('CRM_INVOICE_STATUS_MANAGER_PAY_VOUCHER_NUM_LABEL_1'),
			'commentLabelText' => GetMessage('CRM_INVOICE_STATUS_MANAGER_COMMENT_LABEL'),
			'notSpecified' => GetMessage('CRM_INVOICE_STATUS_MANAGER_NOT_SPECIFIED')
		);

		return '<script>'
			.'BX.ready(function(){ if(typeof(BX.CrmInvoiceStatusManager) === "undefined") return;'
			.'BX.CrmInvoiceStatusManager.infos = '.CUtil::PhpToJSObject($result).';'.PHP_EOL
			.'BX.CrmInvoiceStatusManager.messages = '.CUtil::PhpToJSObject($messages).';'.PHP_EOL
			.'BX.CrmInvoiceStatusManager.settings = '.CUtil::PhpToJSObject($settings).';'.PHP_EOL
			.'BX.CrmInvoiceStatusManager.failureDialogEventsBind();});'.PHP_EOL
			.'</script>';
	}
	public static function RenderInvoiceStatusInfo($params)
	{
		$html = '<div id="'.$params['id'].'">'.PHP_EOL;
		foreach ($params['items'] as $k => $item)
		{
			$style = '';
			if (empty($item['value'])
				|| (!$params['statusFailed'] && !$params['statusSuccess'])
				|| ($params['statusSuccess'] && $item['status'] === 'failed')
				|| ($params['statusFailed'] && $item['status'] === 'success'))
			{
				$style = ' style="display: none;"';
			}

			$html .= "\t".'<div id="INVOICE_STATUS_INFO_'.$k.'_block" class="crm-detail-info-item"'.$style.'>'.PHP_EOL;
			$html .= "\t\t".'<span class="crm-detail-info-item-name">'.htmlspecialcharsbx(GetMessage('CRM_INVOICE_FIELD_'.$k)).':</span>'.PHP_EOL;
			$html .= "\t\t".'<span id="INVOICE_STATUS_INFO_'.$k.'_value" class="crm-client-contacts-block-text">'.htmlspecialcharsbx($item['value']).'</span>'.PHP_EOL;
			$html .= "\t".'</div>'.PHP_EOL;
		}
		$html .= '</div>'.PHP_EOL;

		return $html;
	}

	public static function RenderDealPaidSumField($params)
	{
		if ($params['id'] != '')
			$html = '<div id="'.$params['id'].'">'.PHP_EOL;
		else
			$html = '<div>'.PHP_EOL;
		$html .= "\t".'<div class="crm-detail-info-item">'.PHP_EOL;
		$html .= "\t\t".'<span class="crm-detail-info-item-name">'.htmlspecialcharsbx(GetMessage('CRM_DEAL_SUM_PAID_FIELD')).':</span>'.PHP_EOL;
		$html .= "\t\t".'<span class="crm-client-contacts-block-text crm-sum-paid">'.htmlspecialcharsbx($params['value']).'</span>'.PHP_EOL;
		$html .= "\t".'</div>'.PHP_EOL;
		$html .= '</div>'.PHP_EOL;

		return $html;
	}

	/**
	 * @deprecated see \Bitrix\Crm\Service\Display\Field\PaymentStatusField
	 *
	 * @param string $stage
	 * @param string $cssPrefix
	 * @return string
	 */
	public static function RenderDealPaymentStageControl(string $stage, string $cssPrefix = 'crm-list-item-status'): string
	{
		if (!PaymentStage::isValid($stage))
		{
			return '';
		}

		$classMap = [
			PaymentStage::NOT_PAID => 'not-paid',
			PaymentStage::PAID => 'paid',
			PaymentStage::SENT_NO_VIEWED => 'send',
			PaymentStage::VIEWED_NO_PAID => 'seen',
			PaymentStage::CANCEL => 'cancel',
			PaymentStage::REFUND => 'refund',
		];

		$cssPostfix = $classMap[$stage] ?? 'default';
		$text = PaymentStage::getMessage($stage);

		return "<div class=\"$cssPrefix $cssPrefix-$cssPostfix\">$text</div>";
	}

	/**
	 * @deprecated see \Bitrix\Crm\Service\Display\Field\DeliveryStatusField
	 *
	 * @param string $stage
	 * @param string $cssPrefix
	 * @return string
	 */
	public static function RenderDealDeliveryStageControl($stage, string $cssPrefix = 'crm-list-item-status')
	{
		static $stages;

		if ($stages === null)
		{
			$stages = Order\DeliveryStage::getList();
		}

		if (!isset($stages[$stage]))
		{
			return '';
		}

		$cssPostfix = ($stage === Order\DeliveryStage::SHIPPED)
			? 'shipped'
			: 'no-shipped';

		$text = $stages[$stage];

		return "<div class=\"$cssPrefix $cssPrefix-$cssPostfix\">$text</div>";
	}

	public static function RenderDealStageControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		$arParams['INFOS'] = self::PrepareDealStages(isset($arParams['CATEGORY_ID']) ? $arParams['CATEGORY_ID'] : 0);
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Deal);
		return self::RenderProgressControl($arParams);
	}
	public static function RenderOrderStatusControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		$arParams['INFOS'] = self::PrepareOrderStatuses();
		$arParams['FINAL_ID'] = \Bitrix\Crm\Order\OrderStatus::getFinalStatus();
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Order);
		return self::RenderProgressControl($arParams);
	}
	public static function RenderOrderShipmentStatusControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		$arParams['INFOS'] = self::PrepareOrderShipmentStatuses();
		$arParams['FINAL_ID'] = \Bitrix\Crm\Order\DeliveryStatus::getFinalStatus();
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::OrderShipment);
		return self::RenderProgressControl($arParams);
	}

	public static function RenderLeadStatusControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		$arParams['INFOS'] = self::PrepareLeadStatuses();
		$arParams['FINAL_ID'] = 'CONVERTED';
		$arParams['FINAL_URL'] = isset($arParams['LEAD_CONVERT_URL']) ? $arParams['LEAD_CONVERT_URL'] : '';
		$arParams['VERBOSE_MODE'] = true;
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Lead);

		return self::RenderProgressControl($arParams);
	}
	public static function RenderProgressControl($arParams)
	{
		if(!is_array($arParams))
		{
			return '';
		}

		Extension::load('crm.stage.permission-checker');
		\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
		\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/partial_entity_editor.js');
		\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');

		$entityTypeName = isset($arParams['ENTITY_TYPE_NAME']) ? $arParams['ENTITY_TYPE_NAME'] : '';
		$leadTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Lead);
		$dealTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Deal);
		$invoiceTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Invoice);
		$quoteTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Quote);
		$orderTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Order);
		$orderShipmentTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::OrderShipment);

		$categoryID = isset($arParams['CATEGORY_ID']) ? $arParams['CATEGORY_ID'] : 0;
		$infos = isset($arParams['INFOS']) ? $arParams['INFOS'] : null;
		if(!is_array($infos) || empty($infos))
		{
			if($entityTypeName === $leadTypeName)
			{
				$infos = self::PrepareLeadStatuses();
			}
			elseif($entityTypeName === $dealTypeName)
			{
				$infos = self::PrepareDealStages($categoryID);
			}
			elseif($entityTypeName === $quoteTypeName)
			{
				$infos = self::PrepareQuoteStatuses();
			}
			elseif($entityTypeName === $invoiceTypeName)
			{
				$infos = self::PrepareInvoiceStatuses();
			}
			elseif($entityTypeName === $orderTypeName)
			{
				$infos = self::PrepareOrderStatuses();
			}
			elseif($entityTypeName === $orderShipmentTypeName)
			{
				$infos = self::PrepareOrderShipmentStatuses();
			}
			elseif (\CCrmOwnerType::isUseFactoryBasedApproach((int)$arParams['ENTITY_TYPE_ID']))
			{
				$infos = self::PrepareItemsStatuses((int)$arParams['ENTITY_TYPE_ID'], (int)$arParams['CATEGORY_ID']);
			}
		}

		$enableCustomColors = true;
		if(!is_array($infos) || empty($infos))
		{
			return '';
		}

		$registerSettings = isset($arParams['REGISTER_SETTINGS']) && is_bool($arParams['REGISTER_SETTINGS'])
			? $arParams['REGISTER_SETTINGS'] : false;

		$registrationScript = '';
		if($registerSettings)
		{
			if($entityTypeName === $leadTypeName)
			{
				$registrationScript = self::RenderLeadStatusSettings();
			}
			elseif($entityTypeName === $dealTypeName)
			{
				$registrationScript = self::RenderDealStageSettings();
			}
			elseif($entityTypeName === $quoteTypeName)
			{
				$registrationScript = self::RenderQuoteStatusSettings();
			}
			elseif($entityTypeName === $invoiceTypeName)
			{
				$registrationScript = self::RenderInvoiceStatusSettings();
			}
			elseif($entityTypeName === $orderTypeName)
			{
				$registrationScript = self::RenderOrderStatusSettings();
			}
			elseif($entityTypeName === $orderShipmentTypeName)
			{
				$registrationScript = self::RenderOrderShipmentStatusSettings();
			}
		}

		$finalID = isset($arParams['FINAL_ID']) ? $arParams['FINAL_ID'] : '';
		if($finalID === '')
		{
			if($entityTypeName === $leadTypeName)
			{
				$finalID = 'CONVERTED';
			}
			elseif($entityTypeName === $dealTypeName)
			{
				$finalID = DealCategory::prepareStageID($categoryID, 'WON');
			}
			elseif($entityTypeName === $quoteTypeName)
			{
				$finalID = 'APPROVED';
			}
			elseif($entityTypeName === $invoiceTypeName)
			{
				$finalID = 'P';
			}
			elseif($entityTypeName === $orderTypeName)
			{
				$finalID = \Bitrix\Crm\Order\OrderStatus::getFinalStatus();
			}
			elseif($entityTypeName === $orderShipmentTypeName)
			{
				$finalID = \Bitrix\Crm\Order\DeliveryStatus::getFinalStatus();
			}
			elseif ($infos)
			{
				foreach ($infos as $stageInfo)
				{
					if (\Bitrix\Crm\PhaseSemantics::isFinal($stageInfo['SEMANTICS']))
					{
						$finalID = $stageInfo['STATUS_ID'];
						break;
					}
				}
			}
		}

		$finalUrl = isset($arParams['FINAL_URL']) ? $arParams['FINAL_URL'] : '';
		if($finalUrl === '' && $entityTypeName === $leadTypeName)
		{
			$arParams['FINAL_URL'] = isset($arParams['LEAD_CONVERT_URL']) ? $arParams['LEAD_CONVERT_URL'] : '';
		}

		$currentInfo = null;
		$currentID = isset($arParams['CURRENT_ID']) ? $arParams['CURRENT_ID'] : '';
		if($currentID !== '' && isset($infos[$currentID]))
		{
			$currentInfo = $infos[$currentID];
		}
		$currentSort = is_array($currentInfo) && isset($currentInfo['SORT']) ? intval($currentInfo['SORT']) : -1;

		$finalInfo = null;
		if($finalID !== '' && isset($infos[$finalID]))
		{
			$finalInfo = $infos[$finalID];
		}

		$finalSort = is_array($finalInfo) && isset($finalInfo['SORT']) ? intval($finalInfo['SORT']) : -1;

		$isSuccessful = $currentSort === $finalSort;
		$isFailed = $currentSort > $finalSort;

		$defaultProcessColor = self::PROCESS_COLOR;
		$defaultSuccessColor = self::SUCCESS_COLOR;
		$defaultFailureColor = self::FAILURE_COLOR;

		$stepHtml = '';
		$color = isset($currentInfo['COLOR']) ? $currentInfo['COLOR'] : '';
		if($color === '')
		{
			$color = $defaultProcessColor;
			if($isSuccessful)
			{
				$color = $defaultSuccessColor;
			}
			elseif($isFailed)
			{
				$color = $defaultFailureColor;
			}
		}

		$finalColor = isset($finalInfo['COLOR']) ? $finalInfo['COLOR'] : '';
		if($finalColor === '')
		{
			$finalColor = $isSuccessful ? $defaultSuccessColor : $defaultFailureColor;
		}

		foreach($infos as $info)
		{
			$ID = isset($info['STATUS_ID']) ? $info['STATUS_ID'] : '';
			$sort = isset($info['SORT']) ? (int)$info['SORT'] : 0;

			if($sort > $finalSort)
			{
				break;
			}

			if($enableCustomColors)
			{
				$stepHtml .= '<td class="crm-list-stage-bar-part"';
				if($sort <= $currentSort)
				{
					$stepHtml .= ' style="background:'.$color.'"';
				}
				$stepHtml .= '>';
			}
			else
			{
				$stepHtml .= '<td class="crm-list-stage-bar-part';
				if($sort <= $currentSort)
				{
					$stepHtml .= ' crm-list-stage-passed';
				}
				$stepHtml .= '">';
			}

			$stepHtml .= '<div class="crm-list-stage-bar-block  crm-stage-'.htmlspecialcharsbx(mb_strtolower($ID)).'"><div class="crm-list-stage-bar-btn"></div></div></td>';
		}

		$wrapperStyle = '';
		$wrapperClass = '';
		if($enableCustomColors)
		{
			if($isSuccessful || $isFailed)
			{
				// $wrapperStyle = 'style="background:'.$finalColor.'"';
			}
		}
		else
		{
			if($isSuccessful)
			{
				$wrapperClass = ' crm-list-stage-end-good';
			}
			elseif($isFailed)
			{
				$wrapperClass =' crm-list-stage-end-bad';
			}
		}

		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$entityID = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
		$controlID = isset($arParams['CONTROL_ID']) ? $arParams['CONTROL_ID'] : '';

		if($controlID === '')
		{
			$controlID = $entityTypeName !== '' && $entityID > 0 ? "{$prefix}{$entityTypeName}_{$entityID}" : uniqid($prefix);
		}

		$isReadOnly = isset($arParams['READ_ONLY']) ? (bool)$arParams['READ_ONLY'] : false;
		$legendContainerID = isset($arParams['LEGEND_CONTAINER_ID']) ? $arParams['LEGEND_CONTAINER_ID'] : '';
		$displayLegend = $legendContainerID === '' && (!isset($arParams['DISPLAY_LEGEND']) || $arParams['DISPLAY_LEGEND']);
		$legendHtml = '';
		if($displayLegend)
		{
			$legendHtml = '<div class="crm-list-stage-bar-title">'.htmlspecialcharsbx(isset($infos[$currentID]) && isset($infos[$currentID]['NAME']) ? $infos[$currentID]['NAME'] : $currentID).'</div>';
		}

		$conversionScheme = null;
		if(isset($arParams['CONVERSION_SCHEME']) && is_array($arParams['CONVERSION_SCHEME']))
		{
			$conversionScheme = array();
			if(isset($arParams['CONVERSION_SCHEME']['ORIGIN_URL']))
			{
				$conversionScheme['originUrl'] = $arParams['CONVERSION_SCHEME']['ORIGIN_URL'];
			}
			if(isset($arParams['CONVERSION_SCHEME']['SCHEME_NAME']))
			{
				$conversionScheme['schemeName'] =  $arParams['CONVERSION_SCHEME']['SCHEME_NAME'];
			}
			if(isset($arParams['CONVERSION_SCHEME']['SCHEME_CAPTION']))
			{
				$conversionScheme['schemeCaption'] =  $arParams['CONVERSION_SCHEME']['SCHEME_CAPTION'];
			}
			if(isset($arParams['CONVERSION_SCHEME']['SCHEME_DESCRIPTION']))
			{
				$conversionScheme['schemeDescription'] =  $arParams['CONVERSION_SCHEME']['SCHEME_DESCRIPTION'];
			}
		}
		$conversionTypeID = isset($arParams['CONVERSION_TYPE_ID']) ? (int)$arParams['CONVERSION_TYPE_ID'] : LeadConversionType::GENERAL;
		$canConvert = !isset($arParams['CAN_CONVERT']) || $arParams['CAN_CONVERT'];

		$converterId = '';
		if (!empty($arParams['CONVERTER_ID']) && is_string($arParams['CONVERTER_ID']))
		{
			$converterId = $arParams['CONVERTER_ID'];
		}

		return $registrationScript.'<div class="crm-list-stage-bar'.$wrapperClass.'" '.$wrapperStyle.' id="'.htmlspecialcharsbx($controlID).'"><table class="crm-list-stage-bar-table"><tr>'
			.$stepHtml
			.'</tr></table>'
			.'<script>BX.ready(function(){'
			.'BX.loadExt("crm.stage.permission-checker").then(() => {'
			.'BX.CrmProgressControl.create("'
			.CUtil::JSEscape($controlID).'"'
			.', BX.CrmParamBag.create({"containerId": "'.CUtil::JSEscape($controlID).'"'
			.', "entityType":"'.CUtil::JSEscape($entityTypeName).'"'
			.', "entityId":"'.CUtil::JSEscape($entityID).'"'
			.', "legendContainerId":"'.CUtil::JSEscape($legendContainerID).'"'
			.', "serviceUrl":"'.(isset($arParams['SERVICE_URL']) ? CUtil::JSEscape($arParams['SERVICE_URL']) : '').'"'
			.', "finalUrl":"'.(isset($arParams['FINAL_URL']) ? CUtil::JSEscape($arParams['FINAL_URL']) : '').'"'
			.', "verboseMode":'.(isset($arParams['VERBOSE_MODE']) && $arParams['VERBOSE_MODE'] ? 'true' : 'false')
			.', "conversionScheme":'.($conversionScheme !== null ? CUtil::PhpToJSObject($conversionScheme) : 'null')
			.', "conversionTypeId":'.CUtil::JSEscape($conversionTypeID)
			.', "canConvert":'.($canConvert ? 'true' : 'false')
			.', "currentStepId":"'.CUtil::JSEscape($currentID).'"'
			.', "infoTypeId":"'.CUtil::JSEscape("category_{$categoryID}").'"'
			.', "readOnly":'.($isReadOnly ? 'true' : 'false')
			.', "enableCustomColors":'.($enableCustomColors ? 'true' : 'false')
			.', "converterId":"' . CUtil::JSEscape($converterId) . '"'
			.' }));})})</script>'
			.'</div>'.$legendHtml;
	}
	public static function RenderQuoteStatusControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		$arParams['INFOS'] = self::PrepareQuoteStatuses();
		$arParams['FINAL_ID'] = 'APPROVED';
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Quote);
		return self::RenderProgressControl($arParams);
	}
	public static function RenderInvoiceStatusControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		$arParams['INFOS'] = self::PrepareInvoiceStatuses();
		$arParams['FINAL_ID'] = 'P';
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Invoice);
		return self::RenderProgressControl($arParams);
	}
	public static function RenderItemStageControl($arParams): string
	{
		if (!is_array($arParams))
		{
			$arParams = [];
		}
		else
		{
			$arParams['PREFIX'] = \CCrmStatus::getDynamicEntityStatusPrefix((int)$arParams['ENTITY_TYPE_ID'], (int)$arParams['CATEGORY_ID']);
			$factory = Container::getInstance()->getFactory((int)$arParams['ENTITY_TYPE_ID']);
			if ($factory)
			{
				$stages = $factory->getStages((int)$arParams['CATEGORY_ID']);
				foreach ($stages as $stage)
				{
					if ($stage->getSemantics() === \Bitrix\Crm\PhaseSemantics::SUCCESS)
					{
						$arParams['FINAL_ID'] = $stage->getStatusId();
					}
				}
			}
			$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName($arParams['ENTITY_TYPE_ID']);
		}

		return self::RenderProgressControl($arParams);
	}
	public static function PrepareFormTabFields($tabID, &$arSrcFields, &$arFormOptions, $ignoredFieldIDs = array(), $arFieldOptions = array())
	{
		$arTabFields = isset($arSrcFields[$tabID]) ? $arSrcFields[$tabID] : array();
		$arResult = array();

		$enableFormSettings = !(isset($arFormOptions['settings_disabled']) && $arFormOptions['settings_disabled'] === 'Y');
		if($enableFormSettings && isset($arFormOptions['tabs']) && !empty($arFormOptions['tabs']))
		{
			$arFields = array();
			foreach($arSrcFields as &$tabFields)
			{
				foreach($tabFields as &$field)
				{
					if($field['type'] === 'section')
					{
						continue;
					}

					$fieldID = isset($field['id']) ? $field['id'] : '';
					if($fieldID !== '')
					{
						$arFields[$fieldID] = $field;
					}
				}
				unset($tabFields);
			}
			unset($field);

			if(isset($arFormOptions['tabs']) && is_array($arFormOptions['tabs']))
			{
				foreach($arFormOptions['tabs'] as &$formTab)
				{
					if($formTab['id'] !== $tabID
						|| !isset($formTab['fields'])
						|| !is_array($formTab['fields']))
					{
						continue;
					}

					foreach($formTab['fields'] as &$formField)
					{
						if($formField['type'] === 'section')
						{
							continue;
						}

						$fieldID = isset($formField['id']) ? $formField['id'] : '';

						if(in_array($fieldID, $ignoredFieldIDs, true))
						{
							continue;
						}

						$field = isset($arFields[$fieldID]) ? $arFields[$fieldID] : null;
						if(!$field)
						{
							continue;
						}

						$item = array(
							'ID' => $fieldID,
							'TITLE' => isset($field['name']) ? $field['name'] : $fieldID,
							'VALUE' => isset($field['value']) ? $field['value'] : ''
						);

						if(isset($arFieldOptions[$fieldID]))
						{
							foreach($arFieldOptions[$fieldID] as $k => $v)
							{
								$item[$k] = $v;
							}
						}

						$arResult[] = &$item;
						unset($item);
					}
					unset($formField);
				}
				unset($formTab);
			}
		}
		else
		{
			foreach($arTabFields as &$field)
			{
				if($field['type'] === 'section')
				{
					continue;
				}

				$fieldID = isset($field['id']) ? $field['id'] : '';

				if(in_array($fieldID, $ignoredFieldIDs, true))
				{
					continue;
				}

				$item = array(
					'ID' => $fieldID,
					'TITLE' => isset($field['name']) ? $field['name'] : $fieldID,
					'VALUE' => isset($field['value']) ? $field['value'] : ''
				);

				if(isset($arFieldOptions[$fieldID]))
				{
					foreach($arFieldOptions[$fieldID] as $k => $v)
					{
						$item[$k] = $v;
					}
				}

				$arResult[] = &$item;
				unset($item);
			}
			unset($field);
		}
		return $arResult;
	}
	public static function GetGridOptionalColumns($gridID)
	{
		$aOptions = CUserOptions::GetOption('main.interface.grid', $gridID, array());
		if(!(isset($aOptions['views']) && is_array($aOptions['views'])))
		{
			$aOptions['views'] = [];
		}
		if(!array_key_exists('default', $aOptions['views']))
		{
			$aOptions['views']['default'] = ['columns'=>''];
		}
		if(($aOptions['current_view'] ?? '') == '' || !array_key_exists($aOptions['current_view'], $aOptions['views']))
		{
			$aOptions['current_view'] = 'default';
		}
		$aCurView = $aOptions['views'][$aOptions['current_view']];
		$aColsTmp = explode(',', $aCurView['columns']);
		$aCols = array();
		foreach($aColsTmp as $col)
			if(trim($col)<>'')
				$aCols[] = trim($col);

		return $aCols;
	}
	public static function PrepareSelectItemsForJS($items)
	{
		$result = array();
		if (is_array($items))
			foreach ($items as $id => $name)
				$result[] = array('id' => $id, 'title' => $name);

		return $result;
	}

	public static function GetQuoteStatusInfos()
	{
		return self::PrepareQuoteStatuses();
	}

	public static function GetOrderStatusInfos()
	{
		return self::PrepareOrderStatuses();
	}

	public static function GetOrderShipmentStatusInfos()
	{
		return self::PrepareOrderShipmentStatuses();
	}

	protected static function PrepareQuoteStatuses()
	{
		if(self::$QUOTE_STATUSES !== null)
		{
			return self::$QUOTE_STATUSES;
		}

		self::$QUOTE_STATUSES = CCrmQuote::GetStatuses();

		return self::$QUOTE_STATUSES;
	}
	protected static function PrepareOrderStatuses()
	{
		if(self::$ORDER_STATUSES !== null)
		{
			return self::$ORDER_STATUSES;
		}

		self::$ORDER_STATUSES = \Bitrix\Crm\Order\OrderStatus::getListInCrmFormat();

		$scheme = Bitrix\Crm\Color\OrderStatusColorScheme::getCurrent();
		foreach(self::$ORDER_STATUSES as $ID => &$item)
		{
			$element = $scheme->getElementByName($ID);
			if($element !== null && !isset($item['COLOR']))
			{
				$item['COLOR'] = $element->getColor();
			}
		}
		unset($item);

		return self::$ORDER_STATUSES;
	}
	protected static function PrepareOrderShipmentStatuses()
	{
		if(self::$ORDER_SHIPMENT_STATUSES !== null)
		{
			return self::$ORDER_SHIPMENT_STATUSES;
		}

		self::$ORDER_SHIPMENT_STATUSES = \Bitrix\Crm\Order\DeliveryStatus::getListInCrmFormat();

		$scheme = Bitrix\Crm\Color\OrderShipmentStatusColorScheme::getCurrent();
		foreach(self::$ORDER_SHIPMENT_STATUSES as $ID => &$item)
		{
			$element = $scheme->getElementByName($ID);
			if($element !== null && !isset($item['COLOR']))
			{
				$item['COLOR'] = $element->getColor();
			}
		}
		unset($item);
		return self::$ORDER_SHIPMENT_STATUSES;
	}

	protected static function PrepareItemsStatuses($entityTypeId, $categoryId): array
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory->isStagesSupported())
		{
			$stages = $factory->getStages($categoryId);
			$statuses = [];
			foreach ($stages->getAll() as $stage)
			{
				$statuses[$stage->getStatusId()] = $stage->collectValues();
			}

			return $statuses;
		}

		return [];
	}

	public static function RenderOrderShipmentStatusSettings()
	{
		$result = array();
		$isTresholdPassed = false;
		$canWriteConfig = Container::getInstance()->getUserPermissions()->canWriteConfig();

		$preparedOrderShipmentStatuses = self::PrepareOrderShipmentStatuses();
		StagePermissions::fillAllPermissionsByStages($preparedOrderShipmentStatuses);

		foreach($preparedOrderShipmentStatuses as $status)
		{
			$info = array(
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT']),
				'color' => isset($status['COLOR']) ? $status['COLOR'] : '',
				'stagesToMove' => $status['STAGES_TO_MOVE'] ?? [],
				'allowMoveToAnyStage' => $canWriteConfig,
			);

			if($status['STATUS_ID'] === 'DF')
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_ORDER_SHIPMENT_STATUS_MANAGER_APPROVED_STEP_HINT');
			}
			elseif($status['STATUS_ID'] === 'DD')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}

		$messages = array(
			'dialogTitle' => GetMessage('CRM_ORDER_SHIPMENT_STATUS_MANAGER_DLG_TTL'),
			'failureTitle' => GetMessage('CRM_ORDER_SHIPMENT_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_ORDER_SHIPMENT_STATUS_MANAGER_SELECTOR_TTL'),
		);


		return '<script>'
			.'BX.ready(function(){ if(typeof(BX.CrmOrderShipmentStatusManager) === "undefined") return;'
			.'BX.CrmOrderShipmentStatusManager.messages = '.CUtil::PhpToJSObject($messages).';'.PHP_EOL
			.'BX.CrmOrderShipmentStatusManager.infos = '.CUtil::PhpToJSObject($result).';});'
			.'</script>';
	}

	public static function RenderOrderStatusSettings()
	{
		$result = array();
		$isTresholdPassed = false;
		$canWriteConfig = Container::getInstance()->getUserPermissions()->canWriteConfig();

		$preparedOrderStatuses = self::PrepareOrderStatuses();
		StagePermissions::fillAllPermissionsByStages($preparedOrderStatuses);

		foreach($preparedOrderStatuses as $status)
		{
			$info = array(
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT']),
				'color' => isset($status['COLOR']) ? $status['COLOR'] : '',
				'stagesToMove' => $status['STAGES_TO_MOVE'] ?? [],
				'allowMoveToAnyStage' => $canWriteConfig,
			);

			if($status['STATUS_ID'] === 'F')
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_ORDER_STATUS_MANAGER_F_STEP_HINT');
			}
			elseif($status['STATUS_ID'] === 'D')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}

		$settings = array(
			'imagePath' => '/bitrix/js/crm/images/',
			'serverTime' => time() + CTimeZone::GetOffset()
		);

		$messages = array(
			'dialogTitle' => GetMessage('CRM_ORDER_STATUS_MANAGER_DLG_TTL'),
			'failureTitle' => GetMessage('CRM_ORDER_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_ORDER_STATUS_MANAGER_SELECTOR_TTL'),
			'setDate' =>  GetMessage('CRM_ORDER_STATUS_MANAGER_SET_DATE'),
			'dateLabelText' => GetMessage('CRM_ORDER_STATUS_MANAGER_DATE_LABEL'),
			'payVoucherNumLabelText' => GetMessage('CRM_ORDER_STATUS_MANAGER_PAY_VOUCHER_NUM_LABEL'),
			'commentLabelText' => GetMessage('CRM_ORDER_STATUS_MANAGER_COMMENT_LABEL'),
			'notSpecified' => GetMessage('CRM_ORDER_STATUS_MANAGER_NOT_SPECIFIED')
		);

		return '<script>'
			.'BX.ready(function(){ if(typeof(BX.CrmOrderStatusManager) === "undefined") return;'
			.'BX.CrmOrderStatusManager.infos = '.CUtil::PhpToJSObject($result).';'.PHP_EOL
			.'BX.CrmOrderStatusManager.messages = '.CUtil::PhpToJSObject($messages).';'.PHP_EOL
			.'BX.CrmOrderStatusManager.settings = '.CUtil::PhpToJSObject($settings).';'.PHP_EOL
			.'BX.CrmOrderStatusManager.failureDialogEventsBind();});'.PHP_EOL
			.'</script>';
	}

	public static function RenderQuoteStatusSettings()
	{
		$result = array();
		$isTresholdPassed = false;
		$canWriteConfig = Container::getInstance()->getUserPermissions()->canWriteConfig();

		$preparedQuoteStatuses = self::PrepareQuoteStatuses();
		(new StagePermissions(CCrmOwnerType::Quote, null))
			->fill($preparedQuoteStatuses)
		;

		foreach($preparedQuoteStatuses as $status)
		{
			$info = array(
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT']),
				'color' => isset($status['COLOR']) ? $status['COLOR'] : '',
				'stagesToMove' => $status['STAGES_TO_MOVE'] ?? [],
				'allowMoveToAnyStage' => $canWriteConfig,
			);

			if($status['STATUS_ID'] === 'APPROVED')
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_QUOTE_STATUS_MANAGER_APPROVED_STEP_HINT_MSGVER_1');
			}
			elseif($status['STATUS_ID'] === 'DECLAINED')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}

		$messages = array(
			'dialogTitle' => GetMessage('CRM_QUOTE_STATUS_MANAGER_DLG_TTL_MSGVER_1'),
			'failureTitle' => GetMessage('CRM_QUOTE_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_QUOTE_STATUS_MANAGER_SELECTOR_TTL_MSGVER_1'),
			'checkErrorTitle' => GetMessage('CRM_QUOTE_STATUS_MANAGER_CHECK_ERROR_TTL_MSGVER_2'),
			'checkErrorHelp' => GetMessage('CRM_STAGE_MANAGER_CHECK_ERROR_HELP'),
			'checkErrorHelpArticleCode' => '8233923'
		);

		return '<script>'
			.'BX.ready(function(){ if(typeof(BX.CrmQuoteStatusManager) === "undefined") return;  BX.CrmQuoteStatusManager.infos = '.CUtil::PhpToJSObject($result).'; BX.CrmQuoteStatusManager.messages = '.CUtil::PhpToJSObject($messages).'; });'
			.'</script>';
	}

	private static function PrepareItemStatusesByCategoryId($entityTypeId, $categoryId): array
	{
		$result = [];

		$isFinalFailurePassed = false;
		$canWriteConfig = Container::getInstance()->getUserPermissions()->canWriteConfig();

		$preparedItemsStatuses = self::PrepareItemsStatuses($entityTypeId, $categoryId);
		(new StagePermissions($entityTypeId, $categoryId))
			->fill($preparedItemsStatuses)
		;

		foreach ($preparedItemsStatuses as $status)
		{
			$info = [
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT']),
				'color' => $status['COLOR'] ?? '',
				'semantics' => $status['SEMANTICS'],
				'stagesToMove' => $status['STAGES_TO_MOVE'] ?? [],
				'allowMoveToAnyStage' => $canWriteConfig || UserPermissions::isAlwaysAllowedEntity($entityTypeId),
			];

			if ($status['SEMANTICS'] === 'F')
			{
				$info['semantics'] = $isFinalFailurePassed ? 'apology' : 'failure';
				$isFinalFailurePassed = true;
			}

			if ($status['SEMANTICS'] === 'S')
			{
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_ITEM_STATUS_MANAGER_SELECTOR_TTL');
			}

			$result[] = $info;
		}
		return $result;
	}
	public static function RenderItemStatusSettings($entityTypeId, $categoryId): string
	{
		if (!isset($categoryId))
		{
			$categoryId = '0';
		}

		$result = [];
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($categoryId === '0')
		{
			foreach ($factory->getCategories() as $category)
			{
				$categoryId = $category->getId();
				$typeId = 'category_'.$categoryId;
				$result[$typeId] = self::PrepareItemStatusesByCategoryId($entityTypeId, $categoryId);
			}
		}
		else
		{
			$typeId = 'category_'.$categoryId;
			$result[$typeId] = self::PrepareItemStatusesByCategoryId($entityTypeId, $categoryId);
		}

		$messages = [
			'dialogTitle' => GetMessage('CRM_ITEM_STATUS_MANAGER_DLG_TTL'),
			'failureTitle' => GetMessage('CRM_ITEM_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_ITEM_STATUS_MANAGER_SELECTOR_TTL'),
			'checkErrorTitle' => GetMessage('CRM_ITEM_STATUS_MANAGER_CHECK_ERROR_TTL'),
			'checkErrorHelp' => GetMessage('CRM_STAGE_MANAGER_CHECK_ERROR_HELP'),
			'checkErrorHelpArticleCode' => '8233923',
		];

		return '<script>'
			.'BX.ready(function(){ if(typeof(BX.CrmItemStatusManager) === "undefined") return;'
			.'BX.CrmItemStatusManager.infos = '.CUtil::PhpToJSObject($result).';'.PHP_EOL
			.'BX.CrmItemStatusManager.messages = '.CUtil::PhpToJSObject($messages).'; });'.PHP_EOL
			.'</script>';
	}

	public static function GetFormFieldNames($formID)
	{
		if($formID === '')
		{
			return array();
		}

		$formOptions = CUserOptions::GetOption('main.interface.form', $formID, array());
		if(!is_array($formOptions) || empty($formOptions))
		{
			return array();
		}

		$formFieldNames = array();
		if(!(isset($formOptions['settings_disabled']) && $formOptions['settings_disabled'] === 'Y') && is_array($formOptions['tabs']))
		{
			foreach($formOptions['tabs'] as $tab)
			{
				$tabID = isset($tab['id']) ? $tab['id'] : '';
				if($tabID !== 'tab_1')
				{
					continue;
				}

				$fields = isset($tab['fields']) ? $tab['fields'] : null;
				if(!is_array($fields))
				{
					continue;
				}

				foreach($fields as $field)
				{
					$type = isset($field['type']) ? $field['type'] : '';
					if($type === 'section')
					{
						continue;
					}

					$fieldID = isset($field['id']) ? $field['id'] : '';
					if($fieldID === '')
					{
						continue;
					}

					$fieldName = isset($field['name']) ? $field['name'] : '';
					if($fieldName !== '')
					{
						$formFieldNames[$fieldID] = $fieldName;
					}
				}
			}
		}
		return $formFieldNames;
	}
	public static function PrepareQuoteStatusInfoExtraParams(array &$infos)
	{
		foreach(array_keys($infos) as $statusID)
		{
			$semanticID = CCrmQuote::GetSemanticID($statusID);
			$infos[$statusID]['SEMANTICS'] = $semanticID;
			if(!isset($infos[$statusID]['COLOR']))
			{
				if($semanticID === Bitrix\Crm\PhaseSemantics::SUCCESS)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::SUCCESS_COLOR;
				}
				elseif($semanticID === Bitrix\Crm\PhaseSemantics::FAILURE)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::FAILURE_COLOR;
				}
				else
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::PROCESS_COLOR;
				}
			}
		}
	}

	public static function PrepareOrderShipmentStatusInfoExtraParams(array &$infos)
	{
		foreach($infos as $statusID => $status)
		{
			$semanticID = Bitrix\Crm\Order\DeliveryStatus::getSemanticId($status['STATUS_ID']);
			$infos[$statusID]['SEMANTICS'] = $semanticID;

			if(!isset($infos[$statusID]['COLOR']))
			{
				if($semanticID === Bitrix\Crm\PhaseSemantics::SUCCESS)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::SUCCESS_COLOR;
				}
				elseif($semanticID === Bitrix\Crm\PhaseSemantics::FAILURE)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::FAILURE_COLOR;
				}
				else
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::PROCESS_COLOR;
				}
			}
		}
	}

	public static function PrepareOrderStatusInfoExtraParams(array &$infos)
	{
		foreach($infos as $statusID => $status)
		{
			$semanticID = Bitrix\Crm\Order\OrderStatus::getSemanticID($status['STATUS_ID']);
			$infos[$statusID]['SEMANTICS'] = $semanticID;
			if(!isset($infos[$statusID]['COLOR']))
			{
				if($semanticID === Bitrix\Crm\PhaseSemantics::SUCCESS)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::SUCCESS_COLOR;
				}
				elseif($semanticID === Bitrix\Crm\PhaseSemantics::FAILURE)
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::FAILURE_COLOR;
				}
				else
				{
					$infos[$statusID]['COLOR'] = \CCrmViewHelper::PROCESS_COLOR;
				}
			}
		}
	}

	public static function GetCurrencyText($currencyID)
	{
		return CCrmCurrency::GetCurrencyText($currencyID);
	}

	public static function RenderSipContext()
	{
		echo '<script>',
		'BX.ready(function(){', "\n",
		'var mgr = BX.CrmSipManager.getCurrent();', "\n",
		'mgr.setServiceUrl(',
		'"CRM_', CCrmOwnerType::LeadName, '", ',
		'"/bitrix/components/bitrix/crm.lead.show/ajax.php?', bitrix_sessid_get(), '"',
		');', "\n",
		'mgr.setServiceUrl(',
		'"CRM_', CCrmOwnerType::ContactName, '", ',
		'"/bitrix/components/bitrix/crm.contact.show/ajax.php?', bitrix_sessid_get(), '"',
		');', "\n",
		'mgr.setServiceUrl(',
		'"CRM_', CCrmOwnerType::CompanyName, '", ',
		'"/bitrix/components/bitrix/crm.company.show/ajax.php?', bitrix_sessid_get(), '"',
		');', "\n";

		echo 'if(typeof(BX.CrmSipManager.messages) === "undefined"){', "\n",
		'BX.CrmSipManager.messages = {', "\n",
		'unknownRecipient: "', GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT'), '",', "\n",
		'makeCall: "', GetMessageJS('CRM_SIP_MGR_MAKE_CALL'), '"', "\n",
		'};', "\n",
		'}', "\n";

		echo '}); </script>';
	}

	public static function getDetailFrameWrapperScript(
		int $entityTypeId,
		int $entityId,
		int $entityCategoryId = null,
		int $viewCategoryId = null
	): string
	{
		Extension::load(['crm_common', 'sidepanel']);

		$router = Container::getInstance()->getRouter();
		$url = $router->getItemDetailUrl($entityTypeId, $entityId, $entityCategoryId);
		if (is_array($_GET))
		{
			$url->addParams($_GET);
		}
		$url = CUtil::JSEscape($url->getUri());

		$viewUrl = $router->getItemListUrlInCurrentView($entityTypeId, $viewCategoryId);

		if (!$viewUrl)
		{
			return '';
		}

		$viewUrl = CUtil::JSEscape($viewUrl);

		return (
			'<script>' . PHP_EOL
			. 'BX.ready(' . PHP_EOL
			. '    function ()' . PHP_EOL
			. '    {' . PHP_EOL
			. '       const detectCrmSliderWidth = function () {' . PHP_EOL
			. '           if (window.innerWidth < 1500) {'. PHP_EOL
			. '               return null;' . PHP_EOL
			. '           }' . PHP_EOL
			. '           return 1500 + Math.floor((window.innerWidth - 1500) / 3);' . PHP_EOL
			. '        };' . PHP_EOL
			. '        BX.Crm.Page.initialize();' . PHP_EOL
			. '        BX.SidePanel.Instance.open(' . PHP_EOL
			. "            \"$url\"," . PHP_EOL
			. '            {' . PHP_EOL
			. '                cacheable: false,' . PHP_EOL
			. '                loader: "crm-entity-details-loader",' . PHP_EOL
			. '                width: detectCrmSliderWidth(),' . PHP_EOL
			. '                events: {' . PHP_EOL
			. '                    onCloseComplete: function () {' . PHP_EOL
			. '                        let themePicker = '. PHP_EOL
			. '                            BX.getClass("BX.Intranet.Bitrix24.ThemePicker.Singleton")' . PHP_EOL
			. '                        ;' . PHP_EOL
			. '                        if (themePicker)' . PHP_EOL
			. '                        {' . PHP_EOL
			. '                            themePicker.showLoader(BX("workarea-content"));' . PHP_EOL
			. '                        }' . PHP_EOL
			. "                        window.location = \"$viewUrl\";" . PHP_EOL
			. '                    }' . PHP_EOL
			. '                }' . PHP_EOL
			. '            }' . PHP_EOL
			. '        );' . PHP_EOL
			. '    }' . PHP_EOL
			. ');' . PHP_EOL
			. '</script>'
		);
	}

	public static function getUserInfo(bool $isTitleProtect = false, bool $isImageUrlEncode = true): array
	{
		$userInfo = Timeline\Layout\User::current()->toArray();
		$userInfo['userId'] = Container::getInstance()->getContext()->getUserId();

		if ($isTitleProtect)
		{
			$userInfo['title'] = htmlspecialcharsbx($userInfo['title']);
		}

		if ($isImageUrlEncode)
		{
			$userInfo['imageUrl'] = \Bitrix\Main\Web\Uri::urnEncode($userInfo['imageUrl']);
		}

		return $userInfo;
	}

	public static function renderObservers(int $entityTypeId, int $entityId, ?array $input): string
	{
		if (empty($input))
		{
			return '';
		}

		$entityName = CCrmOwnerType::ResolveName($entityTypeId);

		$result = [];
		foreach ($input as $index => $row)
		{
			$result[] = static::PrepareUserBaloonHtml([
				'PREFIX' => "{$entityName}_{$entityId}_OBSERVER_{$index}",
				'USER_ID' => $row['OBSERVER_USER_ID'],
				'USER_NAME'=> $row['OBSERVER_USER_FORMATTED_NAME'] ?? '',
				'USER_PROFILE_URL' => $row['OBSERVER_USER_SHOW_URL'] ?? '',
			]);
		}

		return implode(',<br>'."\n", $result);
	}

	final public static function initGridSettings(
		string $gridId,
		\Bitrix\Main\Grid\Options $gridOptions,
		array $headers,
		bool $isInExportMode,
		?int $categoryId = null,
		?bool $isAllItemsCategory = null,
		array $columnNameToEditableFieldNameMap = [],
		?bool $isRecurring = null,
		?bool $isMyCompany = null,
	): \Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings
	{
		$visibleColumns = $gridOptions->getUsedColumns(array_column($headers, 'id'));
		$editableVisibleColumns = array_filter(
			$headers,
			fn(array $column) => isset($column['editable']) && in_array($column['id'], $visibleColumns, true)
		);

		$editableFieldsWhitelist = array_column($editableVisibleColumns, 'id');

		return new \Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings([
			'ID' => $gridId,
			/**
			 * Could be rewritten in the future to
			 * @see \Bitrix\Main\Grid\Export\ExcelExporter::isExportRequest()
			 */
			'MODE' => $isInExportMode ? \Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings::MODE_EXCEL
				: \Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings::MODE_HTML,

			'EDITABLE_FIELDS_WHITELIST' => array_map(
				fn(string $columnName) => $columnNameToEditableFieldNameMap[$columnName] ?? $columnName,
				$editableFieldsWhitelist,
			),

			'COLUMN_NAME_TO_EDITABLE_FIELD_NAME_MAP' => $columnNameToEditableFieldNameMap,

			'CATEGORY_ID' => $categoryId,
			'IS_ALL_ITEMS_CATEGORY' => $isAllItemsCategory,

			'IS_RECURRING' => $isRecurring,

			'IS_MY_COMPANY' => $isMyCompany,
		]);
	}

	/**
	 * @internal
	 */
	final public static function initGridPanel(
		int $entityTypeId,
		\Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings $gridSettings,
		?\Bitrix\Main\HttpRequest $request = null
	): \Bitrix\Main\Grid\Panel\Panel
	{
		if (self::isCallListUpdateMode($entityTypeId, $request))
		{
			[$callListId, $callListContext] = self::getCallListIdAndContextFromRequest($request);

			return new \Bitrix\Main\Grid\Panel\Panel(
				new \Bitrix\Crm\Component\EntityList\Grid\Panel\Action\CallListUpdateModeItemDataProvider(
					$entityTypeId,
					$callListId,
					$callListContext,
					$gridSettings,
				)
			);
		}

		return new \Bitrix\Main\Grid\Panel\Panel(
			new \Bitrix\Crm\Component\EntityList\Grid\Panel\Action\ItemDataProvider(
				\Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId),
				\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions(),
				\Bitrix\Crm\Service\Container::getInstance()->getContext(),
				$gridSettings,
			),
		);
	}

	final public static function isCallListUpdateMode(int $entityTypeId, ?\Bitrix\Main\HttpRequest $request = null): bool
	{
		if (
			!\Bitrix\Main\ModuleManager::isModuleInstalled('voximplant')
			|| !\Bitrix\Crm\CallList\CallList::isEntityTypeSupported($entityTypeId)
		)
		{
			return false;
		}

		[$callListId, $context] = self::getCallListIdAndContextFromRequest($request);

		return $callListId > 0 && !empty($context);
	}

	final public static function getCallListIdAndContextFromRequest(?\Bitrix\Main\HttpRequest $request = null): array
	{
		$request ??= \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

		$id = $request->get('call_list_id');
		if ($id !== null)
		{
			$id = (int)$id;
		}

		$context = $request->get('call_list_context');
		if ($context !== null)
		{
			$context = (string)$context;
		}

		return [$id, $context];
	}

	final public static function processGridRequest(
		int $entityTypeId,
		string $gridId,
		\Bitrix\Main\Grid\Panel\Panel $panel,
		?\Bitrix\Main\HttpRequest $request = null
	): void
	{
		if (!check_bitrix_sessid())
		{
			return;
		}

		$request ??= \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

		$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter());
		$gridRequest = (new \Bitrix\Main\Grid\UI\Request\GridRequestFactory())->createFromRequest($request);

		$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
			\Bitrix\Crm\Filter\Factory::getSettingsByGridId($entityTypeId, $gridId)
		);

		$gridResponse = $panel->processRequest($gridRequest, $filter);
		if ($gridResponse?->isSendable())
		{
			$gridResponse?->send();
		}
	}
}
