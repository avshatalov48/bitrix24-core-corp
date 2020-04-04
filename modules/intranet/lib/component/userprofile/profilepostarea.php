<?php
namespace Bitrix\Intranet\Component\UserProfile;

use Bitrix\Blog\PostSocnetRightsTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\UI\Extension;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ProfilePostArea implements \Bitrix\Main\Engine\Response\ContentArea\ContentAreaInterface
{
	private $postFields = [];
	private $pathToUser = '';

	public function __construct(array $params = [])
	{
		$this->postFields = (!empty($params['postFields']) && is_array($params['postFields']) ? $params['postFields'] : []);
		$this->pathToUser = (!empty($params['pathToUser']) ? $params['pathToUser'] : '');
	}

	/**
	 * @return string
	 */
	public function getHtml()
	{
		Extension::load('viewer');

		// TODO: Implement getHtml() method.
		$pathToSmile = Option::get("socialnetwork", "smile_page", false, SITE_ID);
		$pathToSmile = ($pathToSmile ? $pathToSmile : "/bitrix/images/socialnetwork/smile/");

		$p = new \blogTextParser(false, $pathToSmile);

		$parserParams = [
			"imageWidth" => 600,
			"imageHeight" => 1000,
			"pathToUser" => $this->pathToUser,
		];

		$allow = [
			"HTML" => "N",
			"ANCHOR" => "Y",
			"BIU" => "Y",
			"IMG" => "Y",
			"QUOTE" => "Y",
			"CODE" => "Y",
			"FONT" => "Y",
			"LIST" => "Y",
			"SMILES" => "Y",
			"NL2BR" => "N",
			"VIDEO" => "Y",
			"USER" => "Y",
			"TAG" => "Y",
			"SHORT_ANCHOR" => "Y"
		];
		if(Option::get("blog","allow_video", "Y") != "Y")
		{
			$allow["VIDEO"] = "N";
		}

		if (is_array($this->postFields['UF']['UF_BLOG_POST_FILE']))
		{
			$p->arUserfields = [
				"UF_BLOG_POST_FILE" => array_merge($this->postFields['UF']['UF_BLOG_POST_FILE'], [ "TAG" => "DOCUMENT ID" ])
			];
		}
		return $p->convert($this->postFields['DETAIL_TEXT'], false, [], $allow, $parserParams);
	}
}