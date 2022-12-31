<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (empty($arResult['WORKFLOW_ID'])):?>
<p style="color:red"><?=GetMessage('BPWLFC_WORKFLOW_NOT_FOUND')?></p>
<?else:
$defaultAvatar = '/bitrix/templates/mobile_app/images/bizproc/bp-default-icon.jpg';
?>
<div class="bp-short-process">
	<?if ($arResult['STATE_TITLE']):?>
		<span class="bp-short-process-finished <?if ($arResult['LAST_USER_STATUS'] == CBPTaskUserStatus::Yes || $arResult['LAST_USER_STATUS'] == CBPTaskUserStatus::Ok):?>process-finished-ready<?endif?>">
			<span>
				<span title="<?=htmlspecialcharsbx($arResult['STATE_TITLE'])?>"><?=htmlspecialcharsbx($arResult['STATE_TITLE'])?></span>
			</span>
		</span>
	<?endif?>
	<div class="bp-short-process-steps <?if (empty($arResult['TASKS']['COMPLETED']) && !$arResult['STATE_TITLE']) echo 'alone';?>">
		<div class="bp-short-process-step-wrapper">
			<a href="<?=empty($arResult['STARTED_BY'])? 'javascript:void(0)' : SITE_DIR.'mobile/users/?user_id='.(int)$arResult['STARTED_BY']['ID']?>" class="bp-short-process-step bp-short-process-step-firs">
				<span class="bp-short-process-step-inner">
					<?if (!empty($arResult['STARTED_BY']) && is_array($arResult['STARTED_BY'])):
						$startedPhoto = CBPViewHelper::getUserPhotoSrc($arResult['STARTED_BY']);
						if (!$startedPhoto)
							$startedPhoto = $defaultAvatar;
						?>
						<img src="<?=$startedPhoto?>" border="0"/>
					<?elseif (!empty($arResult['DOCUMENT_ID']) && in_array($arResult['DOCUMENT_ID'][0], array('crm', 'disk', 'lists', 'tasks'))):?>
					<img src="/bitrix/templates/mobile_app/images/bizproc/bp-<?=$arResult['DOCUMENT_ID'][0]?>-icon.png"  border="0"/>
					<?else:?>
					<img src="/bitrix/templates/mobile_app/images/bizproc/bp-other-icon.png" border="0"/>
					<?endif;?>
				</span>
			</a>
		</div>
		<?if (!empty($arResult['TASKS']['COMPLETED'][0])):
				$task = $arResult['TASKS']['COMPLETED'][0];
				$face = $task['USERS'][0];
				$photoSrc = $face['PHOTO_SRC'];
				if (!$photoSrc)
					$photoSrc = $defaultAvatar
			?>
			<span class="bp-short-prosess-steps-arrow bp-short-prosess-steps-arrow-ready">
				<?if ( $arResult['TASKS']['COMPLETED_CNT'] >= 2):?>
						<a href="javascript:void(0)" class="process-step-more"><?=GetMessage('BPWLFC_MORE')?> <?=
							($arResult['TASKS']['COMPLETED_CNT'] == 2? $arResult['TASKS']['COMPLETED'][1]['USERS_CNT'] : $arResult['TASKS']['COMPLETED_CNT']-1)?></a>
				<?endif?>
			</span>

			<div class="bp-short-process-step-wrapper <?if ($task['USERS_CNT'] > 1) echo 'bp-short-process-step-wrapper-more'?>">
				<a href="<?=SITE_DIR.'mobile/users/?user_id='.(int)$face['USER_ID']?>" class="bp-short-process-step <?if ($face['STATUS'] == CBPTaskUserStatus::Ok || $face['STATUS'] == CBPTaskUserStatus::Yes) echo 'bp-short-process-step-ready'?>
			<?if ($face['STATUS'] == CBPTaskUserStatus::No || $face['STATUS'] == 4) echo 'bp-short-process-step-cancel'?> <?if ($task['USERS_CNT'] > 1) echo 'bp-short-process-step-more'?>">
					<span class="bp-short-process-step-inner"><img src="<?=$photoSrc?>" border="0"/></span>
				</a>
				<?if ($task['USERS_CNT'] > 1):?>
				<a href="javascript:void(0)" class="process-step-more process-step-more-complete">
					<span class=""><?=GetMessage('BPWLFC_TOTAL')?> <?=$task['USERS_CNT']?></span>
				</a>
				<?endif?>
			</div>
		<?endif?>
	<?if (!$arResult['STATE_TITLE']):?>
	</div>
	<?endif?>
	<?if (!empty($arResult['TASKS']['RUNNING'][0])):
			$task = $arResult['TASKS']['RUNNING'][0];
			$face = $task['USERS'][0];
			$allFaces = sizeof($arResult['TASKS']['RUNNING_ALL_USERS']);
			$photoSrc = $face['PHOTO_SRC'];
			if (!$photoSrc)
				$photoSrc = $defaultAvatar
		?>
		<span class="bp-short-prosess-steps-arrow <?if ($arResult['TASKS']['RUNNING_CNT'] > 1) echo 'steps-arrow-right-right'?>"></span>
		<span class="bp-short-process-step-wrapper <?if ($task['USERS_CNT'] > 1) echo 'bp-short-process-step-wrapper-more'?>">
			<a href="<?=SITE_DIR.'mobile/users/?user_id='.(int)$face['USER_ID']?>" class="bp-short-process-step  <?if ($face['STATUS'] == CBPTaskUserStatus::Ok || $face['STATUS'] == CBPTaskUserStatus::Yes) echo 'bp-short-process-step-ready'?>
			<?if ($face['STATUS'] == CBPTaskUserStatus::No || $face['STATUS'] == 4) echo 'bp-short-process-step-cancel'?> <?if ($task['USERS_CNT'] > 1) echo 'bp-short-process-step-more'?>">
				<span class="bp-short-process-step-inner"><img src="<?=$photoSrc?>" border="0" data-users="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($arResult['TASKS']['RUNNING_ALL_USERS']))?>" onload="return BX.BizProcMobile.renderFacePhoto(this, JSON.parse(this.getAttribute('data-users')));"/></span>
			</a>
			<? if ($allFaces >= 2):?>
			<a href="javascript:void(0)" class="process-step-more process-step-more-running"><span><?=GetMessage('BPWLFC_TOTAL')?> <?=$allFaces?></span></a>
			<?endif?>
		</span>
	<?endif?>
	<?if ($arResult['STATE_TITLE']):?>
	</div>
	<?endif?>
</div>
<?endif?>