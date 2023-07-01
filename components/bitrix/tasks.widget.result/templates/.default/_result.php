<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

/**
 * @global CMain $APPLICATION
 * @var $result Result
 * @var $arResult array
 * @var $arParams array
 */

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\Internals\Task\Result\Result;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use \Bitrix\Tasks\Util\User;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Extension::load('ui.fonts.opensans');

$author = $arResult['USERS'][$result->getCreatedBy()] ? $arResult['USERS'][$result->getCreatedBy()] : null;
$userId = (int)CurrentUser::get()->getId();
$userModel = UserModel::createFromId($userId);
if (
	$result->getCreatedBy() === $userId
	|| $userModel->isAdmin()
)
{
	$canRemove = true;
}
else
{
	$canRemove = false;
}
?>

<div
	class="tasks-widget-result__item"
	data-id="<?= $result->getId() ?>"
	data-role="tasks-widget--result-item"
>
	<div class="tasks-widget-result__item--header">
		<div class="tasks-widget-result__item--header-title"><?= Loc::getMessage('TASKS_RESULT_HEADER'); ?></div>
			<?php if($canRemove): ?>
			<div class="tasks-widget-result-remove" onclick="BX.Tasks.ResultAction.getInstance().deleteFromComment('<?= $result->getCommentId() ?>')"><?= Loc::getMessage('TASKS_RESULTS_REMOVE_RESULT') ?></div>
			<?php endif;?>
		</div>
	<div class="tasks-widget-result__item--content">
		<div class="tasks-widget-result__item-header">
			<span class="ui-icon ui-icon-common-user user-img tasks-widget-result__item-header--avatar">
				<i style="<?= ($author && !empty($author['AVATAR'])) ? 'background-image: url(\''. Uri::urnEncode($author['AVATAR']).'\');' : '';  ?>"></i>
			</span>
			<div class="tasks-widget-result__item-header--info">
				<a href="/company/personal/user/<?= $result->getCreatedBy(); ?>/" class="tasks-widget-result__item-header--name ui-link" bx-tooltip-user-id="<?= $result->getCreatedBy(); ?>" bx-tooltip-params="[]"><?= $author ? \htmlspecialcharsbx(User::formatName($author)) : ''; ?></a>
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
					$files = $result->get(ResultTable::UF_FILE_NAME);
					if($files):
						$uf = $arResult['UF'][ResultTable::UF_FILE_NAME];
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
					$preview = $result->get(ResultTable::UF_PREVIEW_NAME);
					if($preview):
						$uf = $arResult['UF'][ResultTable::UF_PREVIEW_NAME];
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