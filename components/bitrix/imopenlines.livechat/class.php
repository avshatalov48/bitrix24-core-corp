<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc;

class ImOpenLinesComponentLines extends CBitrixComponent
{
	private $configId = null;
	private $config = null;

	protected function checkModules()
	{
		if (!Loader::includeModule('im'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_IM_NOT_INSTALLED'));
			return false;
		}
		if (!Loader::includeModule('imopenlines'))
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
			return false;
		}
		return true;
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if (!$this->checkModules())
		{
			return false;
		}

		$this->configId = intval($this->arParams['CONFIG_ID']);
		if (!$this->configId)
			return false;

		$configManager = new \Bitrix\ImOpenLines\Config();
		$this->config = $configManager->get($this->configId, true, false);

		$this->showContextPage();

		return true;
	}

	private function showContextPage()
	{
		$liveChatManager = new \Bitrix\ImOpenLines\LiveChatManager($this->configId);
		$config = $liveChatManager->get();

		$this->arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'] = htmlspecialcharsbx($config['BACKGROUND_IMAGE_LINK']);
		$this->arResult['CUSTOMIZATION']['TEMPLATE_ID'] = htmlspecialcharsbx($config['TEMPLATE_ID']);
		$this->arResult['CUSTOMIZATION']['CSS_ACTIVE'] = $config['CSS_ACTIVE'];
		$this->arResult['CUSTOMIZATION']['CSS_PATH'] = $config['CSS_ACTIVE'] == 'Y'? htmlspecialcharsbx($config['CSS_PATH']): '';
		$this->arResult['CUSTOMIZATION']['CSS_TEXT'] = $config['CSS_ACTIVE'] == 'Y'? htmlspecialcharsbx($config['CSS_TEXT']): '';

		$this->arResult['CUSTOMIZATION']['OG_CAPTION'] = $this->arResult['LINE_NAME'];
		$this->arResult['CUSTOMIZATION']['OG_DESCRIPTION'] = Loc::getMessage('OL_COMPONENT_LIVECHAT_DESCRIPTION');

		$siteName = '//' . \Bitrix\Main\Context::getCurrent()->getServer()->getHttpHost();
		$siteNameHttp = 'http:' . $siteName;
		$siteNameHttps = 'https:' . $siteName;
		$this->arResult['CUSTOMIZATION']['OG_IMAGE'] = array(
			array(
				'PATH' => $siteNameHttp . $this->getPath() . '/templates/.default/images/rich_link_form_150_150.png',
				'PATH_HTTPS' => $siteNameHttps . $this->getPath() . '/templates/.default/images/rich_link_form_150_150.png',
				'TYPE' => 'image/png', 'WIDTH' => '150', 'HEIGHT' => '150'
			),array(
				'PATH' => $siteNameHttp . $this->getPath() . '/templates/.default/images/rich_link_form_150_100.png',
				'PATH_HTTPS' => $siteNameHttps . $this->getPath() . '/templates/.default/images/rich_link_form_150_100.png',
				'TYPE' => 'image/png', 'WIDTH' => '150', 'HEIGHT' => '100'
			),
		);

		if($config['BACKGROUND_IMAGE'])
		{
			if(in_array($this->getLanguageId(), array('ru', 'ua', 'by')))
			{
				$sizeList = array(
					array('width' => 150, 'height' => 100),
				);
			}
			else
			{
				$sizeList = array(
					array('width' => 150, 'height' => 150),
				);
			}
			foreach($sizeList as $size)
			{
				$image = CFile::ResizeImageGet(
					$config['BACKGROUND_IMAGE'],
					$size, BX_RESIZE_IMAGE_PROPORTIONAL, false
				);
				if(!$image['src'])
				{
					continue;
				}
				$backgroundImageSrcHttp = $image['src'];
				$backgroundImageSrcHttps = $backgroundImageSrcHttp;
				if(mb_substr($backgroundImageSrcHttp, 0, 1) == '/')
				{
					$backgroundImageSrcHttp = $siteNameHttp . $backgroundImageSrcHttp;
					$backgroundImageSrcHttps = $siteNameHttps . $backgroundImageSrcHttps;
				}
				$this->arResult['CUSTOMIZATION']['OG_IMAGE'][] = array(
					'PATH' => $backgroundImageSrcHttp,
					'PATH_HTTPS' => $backgroundImageSrcHttps,
					'WIDTH' => $size['width'], 'HEIGHT' => $size['height']
				);
			}
		}

		$ogLang = mb_strtoupper(\Bitrix\Main\Context::getCurrent()->getLanguage());
		$ogLogo = $this->arResult['CUSTOMIZATION']['OG_IMAGE'];
		if(isset($this->arResult['CUSTOMIZATION']['OG_IMAGE_' . $ogLang]))
		{
			$ogLogo = $this->arResult['CUSTOMIZATION']['OG_IMAGE_' . $ogLang];
		}
		$this->arResult['CUSTOMIZATION']['OG_IMAGE_CURRENT'] = $ogLogo;
		if(Loader::includeModule('intranet'))
		{
			$this->arResult['CUSTOMIZATION']['REF_LINK'] = \CIntranetUtils::getB24Link('crm-form');
		}

		\Bitrix\Main\UI\Extension::load("imopenlines.component.widget");

		$this->arResult['WIDGET_CODE'] = $liveChatManager->getWidgetConfigForPublicPage(Array('CONFIG' => [
			'copyright' => $config['COPYRIGHT_REMOVED'] == 'N'
		]));

		$this->includeComponentTemplate();
	}
};