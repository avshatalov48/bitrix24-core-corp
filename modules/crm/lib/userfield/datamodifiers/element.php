<?php

namespace Bitrix\Crm\UserField\DataModifiers;

use Bitrix\Crm\Security\EntityAuthorization;
use CComponentEngine;
use CCrmCompany;
use CCrmContact;
use CCrmDeal;
use CCrmLead;
use CCrmOwnerType;
use CCrmProduct;
use CCrmQuote;
use CCrmStatus;
use CFile;
use COption;
use CDBResult;
use Bitrix\Crm\Order\Permissions\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service;

/**
 * Class Element
 * @package Bitrix\Crm\UserField\DataModifiers
 */
class Element
{
	private const TYPES = [
		CCrmOwnerType::LeadName,
		CCrmOwnerType::ContactName,
		CCrmOwnerType::CompanyName,
		CCrmOwnerType::DealName,
		CCrmOwnerType::QuoteName,
		CCrmOwnerType::OrderName,
		'PRODUCT'
	];
	private const ELEMENTS_LIMIT = 50;

	public static function removeItemFromResultList(&$result, $params, $item): void
	{
		if(isset($result['SELECTED'][$item['SID']]))
		{
			unset($result['SELECTED'][$item['SID']]);
		}
		elseif(!empty($params['usePrefix']) && isset($result['SELECTED'][$item['ID']]))
		{
			unset($result['SELECTED'][$item['ID']]);
		}
	}

	public static function getIsSelectedValue($result, $params, $item): string
	{
		if(isset($result['SELECTED'][$item['SID']]))
		{
			unset($result['SELECTED'][$item['SID']]);
			$isSelected = 'Y';
		}
		elseif(!empty($params['usePrefix']) && isset($result['SELECTED'][$item['ID']]))
		{
			unset($result['SELECTED'][$item['ID']]);
			$isSelected = 'Y';
		}
		else
		{
			$isSelected = 'N';
		}
		return $isSelected;
	}

	public static function setProductElements(array &$result, array $params, array $settings, array $selected): void
	{
		if(
			isset($settings['PRODUCT'])
			&&
			$settings['PRODUCT'] === 'Y'
			&&
			!empty($selected['PRODUCT'])
		)
		{
			$ar = [];
			$selectFields = ['ID', 'NAME', 'PRICE', 'CURRENCY_ID'];
			$pricesSelect = $vatsSelect = [];
			$select = CCrmProduct::DistributeProductSelect(
				$selectFields,
				$pricesSelect,
				$vatsSelect
			);
			$products = CCrmProduct::GetList(
				['ID' => 'DESC'],
				['ID' => $selected['PRODUCT']],
				$select
			);

			$productsList = $productsId = [];

			while($product = $products->Fetch())
			{
				foreach($pricesSelect as $fieldName)
				{
					$product[$fieldName] = null;
				}
				foreach($vatsSelect as $fieldName)
				{
					$product[$fieldName] = null;
				}
				$productsId[] = $product['ID'];
				$productsList[$product['ID']] = $product;
			}

			CCrmProduct::ObtainPricesVats(
				$productsList,
				$productsId,
				$pricesSelect,
				$vatsSelect
			);
			unset($productsId, $pricesSelect, $vatsSelect);

			foreach($productsList as $product)
			{
				$product['SID'] = ($result['PREFIX'] === 'Y' ? 'D_' . $product['ID'] : $product['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $product);
				self::removeItemFromResultList($result, $params, $product);

				$ar[] = [
					'title' => $product['NAME'],
					'desc' => CCrmProduct::FormatPrice($product),
					'id' => $product['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_product_show'),
						['product_id' => $product['ID']]
					),
					'type' => 'product',
					'selected' => $isSelected
				];
			}
			unset($productsList);
			$result['ELEMENT'] = array_merge($ar, $result['ELEMENT']);
		}
	}

	public static function setResultElements(array &$result, array $params, array $settings, array $selected): void
	{
		if(
			$settings['LEAD'] === 'Y'
			&&
			!empty($selected['LEAD'])
		)
		{
			$hasNameFormatter = method_exists('CCrmLead', 'PrepareFormattedName');
			$leads = CCrmLead::GetListEx(
				['ID' => 'DESC'],
				['=ID' => $selected['LEAD']],
				false,
				false,
				$hasNameFormatter
					? ['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME']
					: ['ID', 'TITLE', 'FULL_NAME']
			);

			$ar = [];
			while($lead = $leads->Fetch())
			{
				$lead['SID'] = ($result['PREFIX'] === 'Y' ? 'L_' . $lead['ID'] : $lead['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $lead);
				self::removeItemFromResultList($result, $params, $lead);

				if($hasNameFormatter)
				{
					$description = CCrmLead::PrepareFormattedName(
						[
							'HONORIFIC' => ($lead['HONORIFIC'] ?? ''),
							'NAME' => ($lead['NAME'] ?? ''),
							'SECOND_NAME' => ($lead['SECOND_NAME'] ?? ''),
							'LAST_NAME' => ($lead['LAST_NAME'] ?? '')
						]
					);
				}
				else
				{
					$description = ($lead['FULL_NAME'] ?? '');
				}

				$ar[] = [
					'title' => (str_replace([';', ','], ' ', $lead['TITLE'])),
					'desc' => $description,
					'id' => $lead['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_lead_show'),
						[
							'lead_id' => $lead['ID']
						]
					),
					'type' => 'lead',
					'selected' => $isSelected
				];
			}
			$result['ELEMENT'] = array_merge($ar, $result['ELEMENT']);
		}
	}

	public static function setContactElements(array &$result, array $params, array $settings, array $selected): void
	{
		if(
			$settings['CONTACT'] === 'Y'
			&&
			!empty($selected['CONTACT'])
		)
		{
			$hasNameFormatter = method_exists('CCrmContact', 'PrepareFormattedName');
			$contacts = CCrmContact::GetListEx(
				['ID' => 'DESC'],
				['=ID' => $selected['CONTACT']],
				false,
				false,
				$hasNameFormatter
					? ['ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO']
					: ['ID', 'FULL_NAME', 'COMPANY_TITLE', 'PHOTO']
			);

			$ar = [];
			while($contact = $contacts->Fetch())
			{
				$imageUrl = '';

				if(isset($contact['PHOTO']) && $contact['PHOTO'] > 0)
				{
					$image = CFile::ResizeImageGet(
						$contact['PHOTO'],
						['width' => 25, 'height' => 25],
						BX_RESIZE_IMAGE_EXACT
					);
					if(is_array($image) && isset($image['src']))
					{
						$imageUrl = $image['src'];
					}
				}

				$contact['SID'] = ($result['PREFIX'] === 'Y' ? 'C_' . $contact['ID'] : $contact['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $contact);
				self::removeItemFromResultList($result, $params, $contact);

				if($hasNameFormatter)
				{
					$title = CCrmContact::PrepareFormattedName(
						[
							'HONORIFIC' => ($contact['HONORIFIC'] ?? ''),
							'NAME' => ($contact['NAME'] ?? ''),
							'SECOND_NAME' => ($contact['SECOND_NAME'] ?? ''),
							'LAST_NAME' => ($contact['LAST_NAME'] ?? '')
						]
					);
				}
				else
				{
					$title = ($contact['FULL_NAME'] ?? '');
				}

				$ar[] = [
					'title' => $title,
					'desc' => (empty($contact['COMPANY_TITLE']) ? '' : $contact['COMPANY_TITLE']),
					'id' => $contact['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_contact_show'),
						['contact_id' => $contact['ID']]
					),
					'image' => $imageUrl,
					'type' => 'contact',
					'selected' => $isSelected
				];
			}
			$result['ELEMENT'] = array_merge($ar, $result['ELEMENT']);
		}
	}

	public static function setCompanyElements(array &$result, array $params, array $settings, array $selected): void
	{
		if(
			$settings['COMPANY'] === 'Y'
			&&
			!empty($selected['COMPANY'])
		)
		{
			$companyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
			$companyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
			$selectFields = ['ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO'];
			$companies = CCrmCompany::GetList(
				['ID' => 'DESC'],
				['ID' => $selected['COMPANY']],
				$selectFields
			);

			$ar = [];
			while($company = $companies->Fetch())
			{
				$imageUrl = '';
				if(isset($company['LOGO']) && $company['LOGO'] > 0)
				{
					$image = CFile::ResizeImageGet(
						$company['LOGO'],
						['width' => 25, 'height' => 25],
						BX_RESIZE_IMAGE_EXACT
					);

					if(is_array($image) && isset($image['src']))
					{
						$imageUrl = $image['src'];
					}
				}

				$company['SID'] = ($result['PREFIX'] === 'Y' ? 'CO_' . $company['ID'] : $company['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $company);
				self::removeItemFromResultList($result, $params, $company);

				$desc = [];
				if(isset($companyTypeList[$company['COMPANY_TYPE']]))
				{
					$desc[] = $companyTypeList[$company['COMPANY_TYPE']];
				}
				if(isset($companyIndustryList[$company['INDUSTRY']]))
				{
					$desc[] = $companyIndustryList[$company['INDUSTRY']];
				}

				$ar[] = [
					'title' => (str_replace([';', ','], ' ', $company['TITLE'])),
					'desc' => implode(', ', $desc),
					'id' => $company['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_company_show'),
						[
							'company_id' => $company['ID']
						]
					),
					'image' => $imageUrl,
					'type' => 'company',
					'selected' => $isSelected
				];

			}
			$result['ELEMENT'] = array_merge($ar, $result['ELEMENT']);
		}
	}

	public static function setDealElements(array &$result, array $params, array $settings, array $selected): void
	{
		if(
			$settings['DEAL'] === 'Y'
			&&
			!empty($selected['DEAL'])
		)
		{
			$selectFields = ['ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME'];
			$ar = [];
			$deals = CCrmDeal::GetList(
				['ID' => 'DESC'],
				['ID' => $selected['DEAL']],
				$selectFields
			);

			while($deal = $deals->Fetch())
			{
				$deal['SID'] = ($result['PREFIX'] === 'Y' ? 'D_' . $deal['ID'] : $deal['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $deal);
				self::removeItemFromResultList($result, $params, $deal);

				$clientTitle = (!empty($deal['COMPANY_TITLE']) ? $deal['COMPANY_TITLE'] : '');
				$isClientTitle = ($clientTitle !== '' && !empty($deal['CONTACT_FULL_NAME']));
				$clientTitle .= ($isClientTitle ? ', ' : '') . $deal['CONTACT_FULL_NAME'];

				$ar[] = [
					'title' => (str_replace([';', ','], ' ', $deal['TITLE'])),
					'desc' => $clientTitle,
					'id' => $deal['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_deal_show'),
						['deal_id' => $deal['ID']]
					),
					'type' => 'deal',
					'selected' => $isSelected
				];
			}
			$result['ELEMENT'] = array_merge($ar, $result['ELEMENT']);
		}
	}

	public static function setOrderElements(array &$result, array $params, array $settings, array $selected): void
	{
		if(
			$settings['ORDER'] === 'Y'
			&&
			!empty($selected['ORDER'])
		)
		{
			$ar = [];
			$orders = \Bitrix\Crm\Order\Order::getList([
				'filter' => ['=ID' => $selected['ORDER']],
				'select' => ['ID', 'ACCOUNT_NUMBER'],
				'order' => ['ID' => 'DESC']
			]);

			while($order = $orders->fetch())
			{
				$order['SID'] = ($result['PREFIX'] === 'Y' ? 'O_' . $order['ID'] : $order['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $order);
				self::removeItemFromResultList($result, $params, $order);

				$ar[] = [
					'title' => (str_replace([';', ','], ' ', $order['ACCOUNT_NUMBER'])),
					'desc' => $order['ACCOUNT_NUMBER'],
					'id' => $order['SID'],
					'url' => Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
						->getOrderDetailsLink($order['ID']),
					'type' => 'order',
					'selected' => $isSelected
				];
			}

			$result['ELEMENT'] = array_merge($ar, $result['ELEMENT']);
		}
	}

	public static function setQuoteElements(array &$result, array $params, array $settings, array $selected): void
	{
		if(
			$settings['QUOTE'] === 'Y'
			&&
			!empty($selected['QUOTE'])
		)
		{
			$selectFields = array('ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME');
			$ar = [];
			$quotes = CCrmQuote::GetList(
				['ID' => 'DESC'],
				['ID' => $selected['QUOTE']],
				false,
				false,
				$selectFields
			);

			while($quote = $quotes->Fetch())
			{
				$quote['SID'] = ($result['PREFIX'] === 'Y' ? 'Q_' . $quote['ID'] : $quote['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $quote);
				self::removeItemFromResultList($result, $params, $quote);

				$clientTitle = (!empty($quote['COMPANY_TITLE']) ? $quote['COMPANY_TITLE'] : '');
				$isClientTitle = ($clientTitle !== '' && !empty($quote['CONTACT_FULL_NAME']));
				$clientTitle .= ($isClientTitle ? ', ' : '') . $quote['CONTACT_FULL_NAME'];

				$ar[] = [
					'title' => (str_replace([';', ','], ' ', $quote['TITLE'])),
					'desc' => $clientTitle,
					'id' => $quote['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_quote_show'),
						['quote_id' => $quote['ID']]
					),
					'type' => 'quote',
					'selected' => $isSelected
				];
			}
			$result['ELEMENT'] = array_merge($ar, $result['ELEMENT']);
		}
	}

	public static function setLeads(array &$result, array $params, $userPermissions): void
	{
		if(in_array('LEAD', $params['ENTITY_TYPE'], true))
		{
			$hasNameFormatter = method_exists('CCrmLead', 'PrepareFormattedName');
			$result['ENTITY_TYPE'][] = 'lead';

			if(method_exists('CCrmLead', 'GetTopIDs'))
			{
				$topIdList = CCrmLead::GetTopIDs(
					self::ELEMENTS_LIMIT,
					'DESC',
					$userPermissions
				);

				if(empty($topIdList))
				{
					$leads = new CDBResult();
					$leads->InitFromArray([]);
				}
				else
				{
					$leads = CCrmLead::GetListEx(
						['ID' => 'DESC'],
						['@ID' => $topIdList, 'CHECK_PERMISSIONS' => 'N'],
						false,
						false,
						['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'FULL_NAME']
					);
				}
			}
			else
			{
				$leads = CCrmLead::GetListEx(
					['ID' => 'DESC'],
					[],
					false,
					['nTopCount' => self::ELEMENTS_LIMIT],
					(
					$hasNameFormatter
						? ['ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME']
						: ['ID', 'TITLE', 'FULL_NAME']
					)
				);
			}

			while($lead = $leads->Fetch())
			{
				$lead['SID'] = (
				$result['PREFIX'] === 'Y'
					? 'L_' . $lead['ID'] : $lead['ID']
				);

				$isSelected = self::getIsSelectedValue($result, $params, $lead);
				self::removeItemFromResultList($result, $params, $lead);

				if($hasNameFormatter)
				{
					$description = CCrmLead::PrepareFormattedName(
						[
							'HONORIFIC' => ($lead['HONORIFIC'] ?? ''),
							'NAME' => ($lead['NAME'] ?? ''),
							'SECOND_NAME' => ($lead['SECOND_NAME'] ?? ''),
							'LAST_NAME' => ($lead['LAST_NAME'] ?? '')
						]
					);
				}
				else
				{
					$description = $lead['FULL_NAME'] ?? '';
				}

				$result['ELEMENT'][] = [
					'title' => (str_replace([';', ','], ' ', $lead['TITLE'])),
					'desc' => $description,
					'id' => $lead['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_lead_show'),
						['lead_id' => $lead['ID']]
					),
					'type' => 'lead',
					'selected' => $isSelected
				];
			}
		}
	}

	public static function setContacts(array &$result, array $params, $userPermissions): void
	{
		if(in_array('CONTACT', $params['ENTITY_TYPE'], true))
		{
			$hasNameFormatter = method_exists('CCrmContact', 'PrepareFormattedName');
			$result['ENTITY_TYPE'][] = 'contact';

			$topIdList = CCrmContact::GetTopIDsInCategory(
				0,
				self::ELEMENTS_LIMIT,
				'DESC',
				$userPermissions
			);

			if(empty($topIdList))
			{
				$contacts = new CDBResult();
				$contacts->InitFromArray([]);
			}
			else
			{
				$contacts = CCrmContact::GetListEx(
					['ID' => 'DESC'],
					['@ID' => $topIdList, 'CHECK_PERMISSIONS' => 'N'],
					false,
					false,
					['ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'FULL_NAME', 'COMPANY_TITLE', 'PHOTO']
				);
			}

			while($contact = $contacts->Fetch())
			{
				$imageUrl = '';
				if(isset($contact['PHOTO']) && $contact['PHOTO'] > 0)
				{
					$image = CFile::ResizeImageGet(
						$contact['PHOTO'],
						['width' => 25, 'height' => 25],
						BX_RESIZE_IMAGE_EXACT
					);
					if(is_array($image) && isset($image['src']))
					{
						$imageUrl = $image['src'];
					}
				}

				$contact['SID'] = ($result['PREFIX'] === 'Y' ? 'C_' . $contact['ID'] : $contact['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $contact);
				self::removeItemFromResultList($result, $params, $contact);

				if($hasNameFormatter)
				{
					$title = CCrmContact::PrepareFormattedName(
						[
							'HONORIFIC' => ($contact['HONORIFIC'] ?? ''),
							'NAME' => ($contact['NAME'] ?? ''),
							'SECOND_NAME' => ($contact['SECOND_NAME'] ?? ''),
							'LAST_NAME' => ($contact['LAST_NAME'] ?? '')
						]
					);
				}
				else
				{
					$title = ($contact['FULL_NAME'] ?? '');
				}

				$result['ELEMENT'][] = [
					'title' => $title,
					'desc' => (empty($contact['COMPANY_TITLE']) ? '' : $contact['COMPANY_TITLE']),
					'id' => $contact['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_contact_show'),
						['contact_id' => $contact['ID']]
					),
					'image' => $imageUrl,
					'type' => 'contact',
					'selected' => $isSelected
				];
			}
		}
	}

	public static function setCompanies(array &$result, array $params, $userPermissions): void
	{
		if(in_array('COMPANY', $params['ENTITY_TYPE'], true))
		{
			$result['ENTITY_TYPE'][] = 'company';

			$topIdList = CCrmCompany::GetTopIDsInCategory(
				0,
				self::ELEMENTS_LIMIT,
				'DESC',
				$userPermissions
			);

			if(empty($topIdList))
			{
				$companies = new CDBResult();
				$companies->InitFromArray([]);
			}
			else
			{
				$companies = CCrmCompany::GetListEx(
					['ID' => 'DESC'],
					['@ID' => $topIdList, 'CHECK_PERMISSIONS' => 'N'],
					false,
					false,
					['ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO']
				);
			}

			$companyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
			$companyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');

			while($company = $companies->Fetch())
			{
				$imageUrl = '';
				if(isset($company['LOGO']) && $company['LOGO'] > 0)
				{
					$image = CFile::ResizeImageGet(
						$company['LOGO'],
						['width' => 25, 'height' => 25],
						BX_RESIZE_IMAGE_EXACT
					);
					if(is_array($image) && isset($image['src']))
					{
						$imageUrl = $image['src'];
					}
				}

				$company['SID'] = ($result['PREFIX'] === 'Y' ? 'CO_' . $company['ID'] : $company['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $company);
				self::removeItemFromResultList($result, $params, $company);

				$desc = [];

				if(isset($companyTypeList[$company['COMPANY_TYPE']]))
				{
					$desc[] = $companyTypeList[$company['COMPANY_TYPE']];
				}

				if(isset($companyIndustryList[$company['INDUSTRY']]))
				{
					$desc[] = $companyIndustryList[$company['INDUSTRY']];
				}

				$result['ELEMENT'][] = [
					'title' => (str_replace([';', ','], ' ', $company['TITLE'])),
					'desc' => implode(', ', $desc),
					'id' => $company['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_company_show'),
						['company_id' => $company['ID']]
					),
					'image' => $imageUrl,
					'type' => 'company',
					'selected' => $isSelected
				];
			}
		}
	}

	public static function setDeals(array &$result, array $params, $userPermissions): void
	{
		if(in_array('DEAL', $params['ENTITY_TYPE'], true))
		{
			$result['ENTITY_TYPE'][] = 'deal';

			if(method_exists('CCrmDeal', 'GetTopIDs'))
			{
				$topIdList = CCrmDeal::GetTopIDs(
					self::ELEMENTS_LIMIT,
					'DESC',
					$userPermissions
				);

				if(empty($topIdList))
				{
					$deals = new CDBResult();
					$deals->InitFromArray([]);
				}
				else
				{
					$deals = CCrmDeal::GetListEx(
						['ID' => 'DESC'],
						['@ID' => $topIdList, 'CHECK_PERMISSIONS' => 'N'],
						false,
						false,
						['ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME']
					);
				}
			}
			else
			{
				$deals = CCrmDeal::GetListEx(
					['ID' => 'DESC'],
					[],
					false,
					['nTopCount' => self::ELEMENTS_LIMIT],
					['ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME']
				);
			}

			while($deal = $deals->Fetch())
			{
				$deal['SID'] = ($result['PREFIX'] === 'Y' ? 'D_' . $deal['ID'] : $deal['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $deal);
				self::removeItemFromResultList($result, $params, $deal);

				$clientTitle = (!empty($deal['COMPANY_TITLE']) ? $deal['COMPANY_TITLE'] : '');
				$isClientTitle = ($clientTitle !== '' && !empty($deal['CONTACT_FULL_NAME']));
				$clientTitle .= ($isClientTitle ? ', ' : '') . $deal['CONTACT_FULL_NAME'];

				$result['ELEMENT'][] = [
					'title' => (str_replace([';', ','], ' ', $deal['TITLE'])),
					'desc' => $clientTitle,
					'id' => $deal['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_deal_show'),
						['deal_id' => $deal['ID']]
					),
					'type' => 'deal',
					'selected' => $isSelected
				];
			}
		}
	}

	public static function setQuotes(array &$result, array $params, $userPermissions): void
	{
		if(in_array('QUOTE', $params['ENTITY_TYPE'], true))
		{
			$result['ENTITY_TYPE'][] = 'quote';

			if(method_exists('CCrmQuote', 'GetTopIDs'))
			{
				$topIdList = CCrmQuote::GetTopIDs(
					self::ELEMENTS_LIMIT,
					'DESC',
					$userPermissions
				);

				if(empty($topIdList))
				{
					$quotes = new CDBResult();
					$quotes->InitFromArray([]);
				}
				else
				{
					$quotes = CCrmQuote::GetList(
						['ID' => 'DESC'],
						['@ID' => $topIdList, 'CHECK_PERMISSIONS' => 'N'],
						false,
						false,
						['ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME']
					);
				}
			}
			else
			{
				$quotes = CCrmQuote::GetList(
					['ID' => 'DESC'],
					[],
					false,
					['nTopCount' => self::ELEMENTS_LIMIT],
					['ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME']
				);
			}

			while($quote = $quotes->Fetch())
			{
				$quote['SID'] = ($result['PREFIX'] === 'Y' ? 'Q_' . $quote['ID'] : $quote['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $quote);
				self::removeItemFromResultList($result, $params, $quote);

				$clientTitle = (!empty($quote['COMPANY_TITLE']) ? $quote['COMPANY_TITLE'] : '');
				$isClientTitle = ($clientTitle !== '' && !empty($quote['CONTACT_FULL_NAME']));
				$clientTitle .= ($isClientTitle ? ', ' : '') . $quote['CONTACT_FULL_NAME'];

				$result['ELEMENT'][] = [
					'title' => (str_replace([';', ','], ' ', $quote['TITLE'])),
					'desc' => $clientTitle,
					'id' => $quote['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_quote_show'),
						['quote_id' => $quote['ID']]
					),
					'type' => 'quote',
					'selected' => $isSelected
				];
			}
		}
	}

	public static function setProducts(array &$result, array $params, $userPermissions): void
	{
		if(in_array('PRODUCT', $params['ENTITY_TYPE'], true))
		{
			$result['ENTITY_TYPE'][] = 'product';

			$selectFields = ['ID', 'NAME', 'PRICE', 'CURRENCY_ID'];
			$pricesSelect = $vatsSelect = [];
			$select = CCrmProduct::DistributeProductSelect(
				$selectFields,
				$pricesSelect,
				$vatsSelect
			);

			$products = CCrmProduct::GetList(
				['ID' => 'DESC'],
				[],
				$select,
				self::ELEMENTS_LIMIT
			);

			$productsList = $productsId = [];

			while($product = $products->Fetch())
			{
				foreach($pricesSelect as $fieldName)
				{
					$product[$fieldName] = null;
				}
				foreach($vatsSelect as $fieldName)
				{
					$product[$fieldName] = null;
				}
				$productsId[] = $product['ID'];
				$productsList[$product['ID']] = $product;
			}

			CCrmProduct::ObtainPricesVats(
				$productsList,
				$productsId,
				$pricesSelect,
				$vatsSelect
			);
			unset($productsId, $pricesSelect, $vatsSelect);

			foreach($productsList as $product)
			{
				$product['SID'] = ($result['PREFIX'] === 'Y' ? 'PROD_' . $product['ID'] : $product['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $product);
				self::removeItemFromResultList($result, $params, $product);

				$result['ELEMENT'][] = [
					'title' => $product['NAME'],
					'desc' => CCrmProduct::FormatPrice($product),
					'id' => $product['SID'],
					'url' => CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('crm', 'path_to_product_show'),
						['product_id' => $product['ID']]
					),
					'type' => 'product',
					'selected' => $isSelected
				];
			}
			unset($productsList);
		}
	}

	public static function setOrders(array &$result, array $params, $userPermissions): void
	{
		if(in_array('ORDER', $params['ENTITY_TYPE'], true))
		{
			$result['ENTITY_TYPE'][] = 'order';

			$orders = \Bitrix\Crm\Order\Order::getList([
				'select' => ['ID', 'ACCOUNT_NUMBER'],
				'limit' => self::ELEMENTS_LIMIT,
				'order' => ['ID' => 'DESC']
			]);

			while($order = $orders->fetch())
			{
				$order['SID'] = ($result['PREFIX'] === 'Y' ? 'O_' . $order['ID'] : $order['ID']);

				$isSelected = self::getIsSelectedValue($result, $params, $order);
				self::removeItemFromResultList($result, $params, $order);

				$result['ELEMENT'][] = [
					'title' => $order['ACCOUNT_NUMBER'],
					'desc' => $order['ACCOUNT_NUMBER'],
					'id' => $order['SID'],
					'url' => Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
						->getOrderDetailsLink($order['ID']),
					'type' => 'order',
					'selected' => $isSelected
				];
			}
		}
	}

	public static function setDynamics(array &$result, array $params, $userPermissions): void
	{
		foreach($params['ENTITY_TYPE'] as $entityTypeName)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

			if (($factory = Container::getInstance()->getFactory($entityTypeId)) === null)
			{
				continue;
			}

			if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
			{
				$result['ENTITY_TYPE'][$entityTypeId] = mb_strtolower($entityTypeName);

				$list = $factory->getItemsFilteredByPermissions([
					'order' => ['ID' => 'DESC'],
					'limit' => self::ELEMENTS_LIMIT
				]);

				foreach ($list as $item)
				{
					$sid = (
					$result['PREFIX'] === 'Y'
						? \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId) . '_' . $item->getId()
						: $item->getId()
					);

					$result['ELEMENT'][] = [
						'title' => (str_replace([';', ','], ' ', $item->getTitle())),
						'desc' => '',
						'id' => $sid,
						'url' => null,
						'type' => mb_strtolower($entityTypeName)
					];
				}
			}
		}
	}

	/**
	 * @param array|null $settings
	 * @return array
	 */
	public static function getSupportedTypes(?array $settings): array
	{
		$supportedTypes = [];

		foreach ($settings as $entityTypeName => $status)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
			if ($entityTypeId && $status === 'Y')
			{
				$supportedTypes[$entityTypeId] = \CCrmOwnerType::ResolveName($entityTypeId);
			}
		}

		return $supportedTypes;
	}

	/**
	 * @todo remove $userPermissions later, now only for compatibility with the Mobile module
	 * @param array $supportedTypes
	 * @param array $userPermissions
	 * @return array
	 */
	public static function getEntityTypes(array $supportedTypes, $userPermissions = null): array
	{
		$entityTypes = [];
		foreach ($supportedTypes as $typeId => $type)
		{
			if(EntityAuthorization::checkReadPermission($typeId, 0))
			{
				$entityTypes[$typeId] = $type;
			}
		}

		return $entityTypes;
	}
}
