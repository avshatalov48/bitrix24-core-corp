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

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$author = $arResult['USERS'][$result->getCreatedBy()] ? $arResult['USERS'][$result->getCreatedBy()] : null;
?>

<div class="tasks-widget-result__item" data-role="tasks-widget--result-item">
	<div class="tasks-widget-result__item--header">
		<div class="tasks-widget-result__item--header-title"><?= \Bitrix\Main\Localization\Loc::getMessage('TASKS_RESULT_HEADER'); ?></div>
	</div>
	<div class="tasks-widget-result__item--content">
		<div class="tasks-widget-result__item-header">
			<span class="ui-icon ui-icon-common-user user-img tasks-widget-result__item-header--avatar">
				<i style="<?= ($author && !empty($author['AVATAR'])) ? 'background-image: url(\''.$author['AVATAR'].'\');' : '';  ?>"></i>
			</span>
			<div class="tasks-widget-result__item-header--info">
				<a href="/company/personal/user/<?= $result->getCreatedBy(); ?>/" class="tasks-widget-result__item-header--name ui-link" bx-tooltip-user-id="<?= $result->getCreatedBy(); ?>" bx-tooltip-params="[]"><?= $author ? \htmlspecialcharsbx(\Bitrix\Tasks\Util\User::formatName($author)) : ''; ?></a>
				<div class="tasks-widget-result__item-header--time-block">
					<i class="tasks-widget-result__item-header--time-img"></i>
					<a class="tasks-widget-result__item-header--time ui-link ui-link-secondary" rel="nofollow">
						<?= \CComponentUtil::GetDateTimeFormatted(\Bitrix\Tasks\UI::parseDateTime($result->getCreatedAt())); ?>
					</a>
				</div>
			</div>
		</div>
		<div class="tasks-widget-result__item-content">
			<?= $result->getFormattedText(); ?>


				<?php
					$files = $result->get(\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_FILE_NAME);
					if($files):
						$uf = $arResult['UF'][\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_FILE_NAME];
						$uf['VALUE'] = $files;
						$uf['ENTITY_VALUE_ID'] = $result->getId();
					?>
						<div class="tasks-widget-result__item--file-block">
							<?php
								$APPLICATION->IncludeComponent(
									'bitrix:system.field.view',
									$uf['USER_TYPE_ID'],
									[
										'arUserField' => $uf,
									]
								);
							?>
						</div>
				<?php endif; ?>


				<?php
					$preview = $result->get(\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_PREVIEW_NAME);
					if($preview):
						$uf = $arResult['UF'][\Bitrix\Tasks\Internals\Task\Result\ResultTable::UF_PREVIEW_NAME];
						$uf['VALUE'] = $preview;
					?>
						<div class="tasks-widget-result__item--link-block">
							<?php
								$APPLICATION->IncludeComponent(
									'bitrix:system.field.view',
									$uf['USER_TYPE_ID'],
									[
										'arUserField' => $uf,
									]
								);
							?>
						</div>
				<?php endif; ?>


		</div>
	</div>

</div>
