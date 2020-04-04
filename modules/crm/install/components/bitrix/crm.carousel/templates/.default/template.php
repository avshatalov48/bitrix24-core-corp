<?php
if(!(defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true)) die();

use Bitrix\Main\Localization\Loc;

/** @var \CCrmCarouselComponent $component */
CJSCore::Init(array('popup', 'ajax'));

$guid = $component->getGuid();

$prefix = strtolower($guid);
$wrapperID = "{$prefix}_wrapper";
$containerID = "{$prefix}_container";
$forwardButtonID = "{$prefix}_fwd_btn";
$backwardButtonID = "{$prefix}_bwd_btn";
$closeButtonID = "{$prefix}_close_btn";
$bulletContainerID = "{$prefix}_bullet_container";
$bulletNodeID = "{$prefix}_bullet_#pagenum#";

$pages = array_chunk($component->getItems(), 3);
$pageCount = count($pages);

$autorewind = $pageCount > 1 && $component->isAutoRewindEnabled();
$defaultButtonText = $component->getDefaultButtonText();

?><div id="<?=htmlspecialcharsbx($wrapperID)?>" class="crm-carousel-wrapper">
	<?if($component->isCloseButtonEnabled())
	{
		?><div id="<?=htmlspecialcharsbx($closeButtonID)?>" class="crm-carousel-close">
			<span class="crm-carousel-close-item"></span>
		</div><?
	}?>
	<?if($pageCount > 1)
	{
		?><div class="crm-carousel-arrow">
			<span id="<?=htmlspecialcharsbx($forwardButtonID)?>" class="crm-carousel-arrow-item"></span>
		</div>
		<div class="crm-carousel-arrow-previous">
			<span id="<?=htmlspecialcharsbx($backwardButtonID)?>" class="crm-carousel-arrow-item-previous"></span>
		</div><?
	}?>
	<div class="crm-carousel-container">
		<div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-carousel-inner-container">
			<?foreach($pages as $page)
			{
				?><div class="crm-carousel-item"><?
					foreach($page as $item)
					{
						/** @var CCrmCarouselItem $item */
						$className = $item->getCaptionClassName();
						if($className !== '')
						{
							$className = htmlspecialcharsbx($className);
						}

						$buttonText = $item->getButtonText();
						if($buttonText === '')
						{
							$buttonText = $defaultButtonText !== ''
								? $defaultButtonText
								: Loc::getMessage('ITEM_BUTTON_TEXT');
						}

						?><div class="crm-carousel-item-element">
							<div class="crm-carousel-item-title">
								<span class="crm-carousel-item-title-icon<?=$className !== '' ? " {$className}" : ''?>"></span>
								<span class="crm-carousel-item-title-element"><?=htmlspecialcharsbx($item->getCaption())?></span>
							</div>
							<div class="crm-carousel-item-description">
								<span class="crm-carousel-item-description-element"><?=htmlspecialcharsbx($item->getLegend())?></span>
							</div>
							<div class="crm-carousel-item-button">
								<a href="<?=htmlspecialcharsbx($item->getUrl())?>" class="webform-small-button webform-small-button-transparent crm-carousel-item-button-element">
									<?=htmlspecialcharsbx($buttonText)?>
								</a>
							</div>
						</div><?
					}
				?></div><?
			}?>
		</div>
	</div>
	<?if($pageCount > 1)
	{
		?><div class="crm-carousel-bullet-container">
			<div id="<?=htmlspecialcharsbx($bulletContainerID)?>" class="crm-carousel-bullet-inner">
				<?for($pageNum = 0; $pageNum < $pageCount; $pageNum++){
					?><span class="crm-carousel-bullet-item"></span><?
				}?>
			</div>
		</div><?
	}?>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmCarouselCloseDialog.messages =
			{
				title: "<?=CUtil::JSEscape($component->getCloseTitle())?>",
				confirm: "<?=CUtil::JSEscape($component->getCloseConfirm())?>",
				closeButton: "<?=GetMessageJS('CLOSE_BUTTON_TEXT')?>"
			};

			BX.CrmCarousel.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					pageCount: <?=$pageCount?>,
					pageNum: 1,
					autorewind: <?=$autorewind ? 'true' : 'false'?>,
					wrapperId: "<?=CUtil::JSEscape($wrapperID)?>",
					containerId: "<?=CUtil::JSEscape($containerID)?>",
					bulletContainerId: "<?=CUtil::JSEscape($bulletContainerID)?>",
					bulletNodeId: "<?=CUtil::JSEscape($bulletNodeID)?>",
					forwardButtonId: "<?=CUtil::JSEscape($forwardButtonID)?>",
					backwardButtonId: "<?=CUtil::JSEscape($backwardButtonID)?>",
					closeButtonId: "<?=CUtil::JSEscape($closeButtonID)?>"
				}
			);
		}
	);
</script>
