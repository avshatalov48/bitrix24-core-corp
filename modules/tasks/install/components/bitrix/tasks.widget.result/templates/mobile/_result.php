<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

/**
 * @var $result \Bitrix\Tasks\Internals\Task\Result\Result
 * @var $arResult array
 * @var $arParams array
 */

use Bitrix\Main\Web\Uri;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$author = $arResult['USERS'][$result->getCreatedBy()] ? $arResult['USERS'][$result->getCreatedBy()] : null;
?>

<div class="mobile-tasks-widget-result__item" data-role="mobile-tasks-widget--result-item" >
	<div class="mobile-tasks-widget-result__item--header-title"><?= \Bitrix\Main\Localization\Loc::getMessage('TASKS_RESULT_HEADER'); ?></div>
	<div class="mobile-tasks-widget-result__item--content">
		<div class="mobile-tasks-widget-result__item-header">
			<span class="ui-icon ui-icon-common-user user-img mobile-tasks-widget-result__item-header--avatar">
				<i style="<?= ($author && !empty($author['AVATAR'])) ? 'background-image: url(\''. Uri::urnEncode($author['AVATAR']).'\');' : '';  ?>"></i>
			</span>
			<div class="mobile-tasks-widget-result__item-header--info">
				<a href="/company/personal/user/<?= $result->getCreatedBy(); ?>/" class="mobile-tasks-widget-result__item-header--name ui-link"><?= $author ? \htmlspecialcharsbx(\Bitrix\Tasks\Util\User::formatName($author)) : ''; ?></a>
			</div>
		</div>
		<div class="mobile-tasks-widget-result__item-content">
			<?= $result->getFormattedText(true); ?>

			<?php
			$files = $result->get(\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_FILE_NAME);
			if($files)
			{
				$uf = $arResult['UF'][\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_FILE_NAME];
				$uf['VALUE'] = $files;
				$uf['ENTITY_VALUE_ID'] = $result->getId();
				?>
					<div class="mobile-tasks-widget-result__item--file-block">
						<?php
						$APPLICATION->IncludeComponent(
							'bitrix:system.field.view',
							$uf['USER_TYPE_ID'],
							[
								'arUserField' => $uf,
								'MOBILE' => 'Y',
							]
						);
						?>
					</div>
				<?php
			}
			?>

			<?php
			$preview = $result->get(\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_PREVIEW_NAME);
			if($preview)
			{
				$uf = $arResult['UF'][\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_PREVIEW_NAME];
				$uf['VALUE'] = $preview;
				?>
					<div class="mobile-tasks-widget-result__item--link-block">
						<?php
							$APPLICATION->IncludeComponent(
								'bitrix:system.field.view',
								$uf['USER_TYPE_ID'],
								[
									'arUserField' => $uf,
									'MOBILE' => 'Y',
								]
							);
						?>
					</div>
				<?php
			}
			?>
		</div>
	</div>
</div>