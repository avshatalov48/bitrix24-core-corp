<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Disk\Security\ParameterSigner;

/**
 * @var $arParams array
 * @var $arResult array
 */
if (strpos($this->__page, "show") === 0 && !($arParams["RECIPIENT_ID"] > 0 && !empty($arParams["SITE_ID"]) && CModule::IncludeModule('mail')))
	$arResult["FILES"] = array();
else if (strpos($this->__page, "show") === 0)
{
	$arSize = array_change_key_case((is_array($arParams["SIZE"][ $file["ID"]]) ? $arParams["SIZE"][ $file["ID"]] : array("width" => 0, "height" => 0)), CASE_LOWER);
	$arParams["MAX_SIZE"] = array_change_key_case((is_array($arParams["MAX_SIZE"]) ? $arParams["MAX_SIZE"] : array("width" => 600, "height" => 600)), CASE_LOWER);

	foreach ($arResult['FILES'] as &$file)
	{
		if ($this->__page == "show_inline" && array_key_exists("IMAGE", $file) &&
			/** @var \Bitrix\Disk\File $fileModel */
			($fileModel = \Bitrix\Disk\File::loadById($file["FILE_ID"])) && $fileModel
		)
		{
			$extLinks = $fileModel->getExternalLinks(array(
				'filter' => array(
					'OBJECT_ID' => $file["FILE_ID"],
					'CREATED_BY' => $arParams["RECIPIENT_ID"],
					'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
					'IS_EXPIRED' => false,
				),
				'limit' => 1,
			));

			if (empty($extLinks))
			{
				$externalLink = $fileModel->addExternalLink(array(
					'CREATED_BY' => $arParams["RECIPIENT_ID"],
					'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
				));
			}
			else
			{
				/** @var \Bitrix\Disk\ExternalLink $externalLink */
				$externalLink = reset($extLinks);
			}

			if ($externalLink)
			{
				$file["INLINE"] = array(
					"src" => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(
						array(
							'hash' => $externalLink->getHash(),
							'action' => 'showFile'
						),
						true
					),
					"width" => $file["IMAGE"]["WIDTH"],
					"height" => $file["IMAGE"]["HEIGHT"]
				);

				$bExactly = ($arSize["width"] > 0 && $arSize["height"] > 0);

				if ($arParams["MAX_SIZE"]["width"] > 0 || $arParams["MAX_SIZE"]["height"] > 0)
				{
					$circumscribed = array(
						"width" => ($arParams["MAX_SIZE"]["width"] ?: $file["IMAGE"]["WIDTH"]),
						"height" => ($arParams["MAX_SIZE"]["height"] ?: $file["IMAGE"]["HEIGHT"]));
					CFile::ScaleImage(
						$file["IMAGE"]["WIDTH"], $file["IMAGE"]["HEIGHT"],
						$circumscribed, BX_RESIZE_IMAGE_PROPORTIONAL,
						$bNeedCreatePicture,
						$arSourceSize, $arDestinationSize);

					if ($bExactly && $circumscribed) {
						CFile::ScaleImage(
							$arSize["width"], $arSize["height"],
							$circumscribed, BX_RESIZE_IMAGE_PROPORTIONAL,
							$bNeedCreatePicture,
							$arSourceSize, $arSize);
					}

					$file["INLINE"]["width"] = ($bExactly ? $arSize["width"] : $arDestinationSize["width"]);
					$file["INLINE"]["height"] = ($bExactly ? $arSize["height"] : $arDestinationSize["height"]);
				}
				else if ($bExactly)
				{
					$file["INLINE"]["width"] = $arSize["width"];
					$file["INLINE"]["height"] = $arSize["height"];
				}
				continue;
			}
		}

		if ((list($replyTo, $url) = \Bitrix\Mail\User::getReplyTo(
				$arParams["SITE_ID"],
				$arParams["RECIPIENT_ID"],
				"ATTACHED_OBJECT",
				$file["ID"],
				$file["DOWNLOAD_URL"])) && $url)
		{
			$file["VIEW_URL"] = $url;
		}
	}
}