<?
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\UI\Extension::load("ui.icons.b24");
?>

<div class="timeman-grid-user">
	<div class="ui-icon ui-icon-common-user timeman-grid-user-avatar">
<!--		<img class="timeman-grid-user-avatar-value"-->
<!--				src="--><?//= $data['PHOTO_SRC'] ?: 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20viewBox%3D%220%200%2089%2089%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Ccircle%20fill%3D%22%23535C69%22%20cx%3D%2244.5%22%20cy%3D%2244.5%22%20r%3D%2244.5%22/%3E%3Cpath%20d%3D%22M68.18%2071.062c0-3.217-3.61-16.826-3.61-16.826%200-1.99-2.6-4.26-7.72-5.585a17.363%2017.363%200%200%201-4.887-2.223c-.33-.188-.28-1.925-.28-1.925l-1.648-.25c0-.142-.14-2.225-.14-2.225%201.972-.663%201.77-4.574%201.77-4.574%201.252.695%202.068-2.4%202.068-2.4%201.482-4.3-.738-4.04-.738-4.04a27.076%2027.076%200%200%200%200-7.918c-.987-8.708-15.847-6.344-14.085-3.5-4.343-.8-3.352%209.082-3.352%209.082l.942%202.56c-1.85%201.2-.564%202.65-.5%204.32.09%202.466%201.6%201.955%201.6%201.955.093%204.07%202.1%204.6%202.1%204.6.377%202.556.142%202.12.142%202.12l-1.786.217a7.147%207.147%200%200%201-.14%201.732c-2.1.936-2.553%201.485-4.64%202.4-4.032%201.767-8.414%204.065-9.193%207.16-.78%203.093-3.095%2015.32-3.095%2015.32H68.18z%22%20fill%3D%22%23FFF%22/%3E%3C/g%3E%3C/svg%3E' ?><!--"-->
<!--				alt="--><?//= htmlspecialcharsbx($data['FORMATTED_NAME']) ?: '' ?><!--"-->
<!--		>-->
		<i
			class="timeman-grid-user-avatar-value"
			style="<?= (!empty($data['PHOTO_SRC']) ? 'background-image: url(\'' . Uri::urnEncode($data['PHOTO_SRC']) . '\')' : '')?>"
		></i>
	</div>

	<div class="timeman-grid-user-info">
		<a href="<?= $data['USER_PROFILE_PATH'] ?>"
				target="_blank"
				class="timeman-grid-user-name">
			<?= htmlspecialcharsbx($data['FORMATTED_NAME']) ?: '' ?>
		</a>
		<span class="timeman-grid-user-company">
			<?= htmlspecialcharsbx($data['WORK_POSITION']) ?: '' ?>
		</span>
	</div>
	<? if ($arResult['canReadSettings'] && $arResult['showUserWorktimeSettings']): ?>
		<span class="timeman-grid-settings-icon timeman-grid-settings-icon-time"
				data-role="timeman-settings-toggle"
				data-entity-code="<?php echo \Bitrix\Timeman\Helper\EntityCodesHelper::buildUserCode($data['USER_ID']); ?>"
				data-id="<?php echo htmlspecialcharsbx($data['USER_ID']); ?>"
				data-type="user"></span>
	<? endif; ?>
	<? if ($data['SHOW_DELETE_USER_BTN']): ?>
		<span class="timeman-grid-user-delete"
				data-user-id="<?= htmlspecialcharsbx($data['USER_ID']) ?>"
				data-role="delete-user-btn"
				data-user-name="<?= htmlspecialcharsbx($data['FORMATTED_NAME']); ?>"
		></span>
	<? endif; ?>
</div>
