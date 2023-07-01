<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
$this->IncludeLangFile("show.php");
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */

if (count($arResult['FILES']) <= 0)
{
	return;
}

$this->setFrameMode(true);

$jsIds = "";

foreach ($arResult['FILES'] as $id => $file)
{
	if ($file['IS_MARK_DELETED'])
	{
		?><span <?php
		?>title="<?= htmlspecialcharsbx($file["NAME"]) ?>" <?php
		?> class="feed-com-file-inline feed-com-file-wrap diskuf-files-entity"<?php
		?>><?php
		?><span class="feed-com-file-inline feed-com-file-icon feed-file-icon-<?= htmlspecialcharsbx($file["EXTENSION"]) ?>"></span><?php
		?><span class="feed-com-file-inline feed-com-file-deleted-name"><?= htmlspecialcharsbx($file["NAME"]) ?></span><?php
		?><?php
		?></span><?php
	}
	elseif (array_key_exists("IMAGE", $file))
	{
		?><div id="disk-attach-<?= $file['ID'] ?>" class="feed-com-file-inline feed-com-file-inline-image feed-com-file-wrap diskuf-files-entity"><?php
			?><span class="feed-com-file-inline feed-com-img-wrap feed-com-img-load" style="width:<?=$file["INLINE"]["width"]?>px;height:<?=$file["INLINE"]["height"]?>px;"><?php

				$id = "disk-inline-image-" . $file['ID'] . "-" . $this->getComponent()->randString(4);
				if (
					isset($arParams["LAZYLOAD"]) 
					&& $arParams["LAZYLOAD"] === "Y"
				)
				{
					$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
				}

				?><img id="<?=$id?>" <?php
				if (
					isset($arParams["LAZYLOAD"]) 
					&& $arParams["LAZYLOAD"] === "Y"
				)
				{
					?> src="<?= \Bitrix\Disk\Ui\LazyLoad::getBase64Stub() ?>" <?php
					?> data-thumb-src="<?= $file["INLINE"]["src"] ?>"<?php
				}
				else
				{
					?> src="<?=$file["INLINE"]["src"]?>" <?php
				}
				?> width="<?= $file["INLINE"]["width"] ?>"<?php
				?> height="<?= $file["INLINE"]["height"] ?>"<?php
				?> alt="<?= htmlspecialcharsbx($file["NAME"]) ?>"<?php
				?> <?= $file['ATTRIBUTES_FOR_VIEWER']
				?> bx-attach-file-id="<?= $file['FILE_ID'] ?>"<?php
				if (isset($file['XML_ID']))
				{
					?> bx-attach-xml-id="<?= $file['XML_ID'] ?>"<?php
				}
				?> data-bx-width="<?= $file["BASIC"]["width"] ?>"<?php
				?> data-bx-height="<?= $file["BASIC"]["height"] ?>"<?php
				if (!empty($file["ORIGINAL"])) {
					?> data-bx-full="<?= $file["ORIGINAL"]["src"] ?>"<?php
					?> data-bx-full-width="<?= $file["ORIGINAL"]["width"] ?>" <?php
					?> data-bx-full-height="<?= $file["ORIGINAL"]["height"] ?>"<?php
					?> data-bx-full-size="<?= $file["SIZE"] ?>"<?php
				}
				?> data-bx-onload="Y"<?php
				?> /><?php
			?></span><?php
		?></div><?php
	}
	elseif (array_key_exists("VIDEO", $file))
	{
		echo $file['VIDEO'];
	}
	else
	{
		$onClick = (
			SITE_TEMPLATE_ID === 'landing24'
				? ""
				: "WDInlineElementClickDispatcher(this, 'disk-attach-" . $file['ID'] . "'); return false;"
		);
		$href = $file["DOWNLOAD_URL"];

		?><a target="_blank" href="<?= $href ?>" <?php
			?>title="<?= htmlspecialcharsbx($file["NAME"]) ?>" <?php
			?>onclick="<?= $onClick ?>" <?php
			?> alt="<?= htmlspecialcharsbx($file["NAME"]) ?>" <?php
			?> class="feed-com-file-inline feed-com-file-wrap diskuf-files-entity"<?php
			?> bx-attach-file-id="<?= $file['FILE_ID'] ?>"<?php
			if ($file['XML_ID'])
			{
				?> bx-attach-xml-id="<?= $file['XML_ID'] ?>"<?php
			}
			?>><?php
			?><span class="feed-com-file-inline feed-com-file-icon feed-file-icon-<?= htmlspecialcharsbx($file["EXTENSION"]) ?>"></span><?php
			?><span class="feed-com-file-inline feed-com-file-name"><?= htmlspecialcharsbx($file["NAME"]) ?></span><?php
			?><?php
		?></a><?php
	}
}

if ($jsIds !== '')
{
	?><script>BX.LazyLoad.registerImages([<?=$jsIds?>], typeof oLF != 'undefined' ? oLF.LazyLoadCheckVisibility : false, {dataSrcName: "thumbSrc"});</script><?php
}
