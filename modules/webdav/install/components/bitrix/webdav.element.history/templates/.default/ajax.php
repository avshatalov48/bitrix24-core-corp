<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/** @var $webdav CWebDavIblock */
/** @var $APPLICATION CMain */
/** @var $arResult array */
$webdav = $arResult['webdav'];
?>
<script type="text/javascript">
	function historyShowMoreEditUsers(elem)
	{
		if(BX('hidden-feed-com-editing-more'))
		{
			BX.show(BX('hidden-feed-com-editing-more'), 'inline');
			BX.hide(BX(elem));
		}
	}
</script>
<div id="feed-file-history-cont" class="feed-file-history-cont">
	<div class="feed-file-history-top">
		<span class="feed-file-history-icon feed-file-history-icon-<?=htmlspecialcharsbx(toLower(GetFileExtension($webdav->arParams['file_name'])))?>"></span>
		<div class="feed-file-hist-name">
			<div class="feed-com-file-wrap">
				<span class="feed-con-file-name-wrap">
					<span class="feed-com-file-name"><a class="feed-com-file-name-text" href="#" onclick="if(wdufCurrentIdDocument){BX.fireEvent(BX(wdufCurrentIdDocument), 'click');}return false;"><?= $webdav->arParams['file_name'] ?></a><span class="feed-com-file-size"><?= CFile::FormatSize(intval($webdav->arParams["file_size"])) ?></span></span>
				</span>
				<? if(!empty($arResult['editService'])){ ?>
				<?
					$onlineUsers = array();
					foreach ($arResult['editUsers'] as $user)
					{
						$onlineUsers[] = CUser::FormatName(CSite::GetNameFormat(false), array(
							"NAME" => $user["USER_NAME"],
							"LAST_NAME" => $user["USER_LAST_NAME"],
							"SECOND_NAME" => $user["USER_SECOND_NAME"],
							"LOGIN" => $user["USER_LOGIN"]
						), true);
					}
					unset($user);
					$showOnlineUsers = array_splice($onlineUsers, 0, 2);
					$notShowOnlineUsers = $onlineUsers;
					$notShownCount = count($notShowOnlineUsers);
					$showOnlineUsers = implode(', ', $showOnlineUsers);
					$notShowOnlineUsers = implode(', ', $notShowOnlineUsers);
					$countLangMessage = CWebDavElementHistoryComponent::getNumericCase(
						$notShownCount,
						GetMessage('WD_ELEMENT_HISTORY_AND_EDITOR_COUNT_1', array('#COUNT#' => $notShownCount)),
						GetMessage('WD_ELEMENT_HISTORY_AND_EDITOR_COUNT_21', array('#COUNT#' => $notShownCount)),
						GetMessage('WD_ELEMENT_HISTORY_AND_EDITOR_COUNT_2_4', array('#COUNT#' => $notShownCount)),
						GetMessage('WD_ELEMENT_HISTORY_AND_EDITOR_COUNT_5_20', array('#COUNT#' => $notShownCount))
					);
				?>
					<span class="feed-con-file-revision-history"><span class="feed-con-file-rev-hist-status"><?= GetMessage('WD_ELEMENT_HISTORY_ONLINE_SERVICE_EDIT', array('#SERVICE#' => '<span class="status-' . ($arResult['editService'] == CWebDavLogOnlineEditBase::GOOGLE_SERVICE_NAME? 'google': 'sky-drive') . '"></span>')) ?> <?= $showOnlineUsers ?><? if($notShownCount > 0){ ?><span class="feed-com-editing-more" onclick="historyShowMoreEditUsers(this)"><?= $countLangMessage ?></span><span id="hidden-feed-com-editing-more" style="display: none;">, <?= $notShowOnlineUsers ?></span><? } ?></span>
				<? } ?>
				</span>
			</div>
		</div>
		<div class="feed-file-hist-description">

			<?
				ob_start();
				$APPLICATION->IncludeComponent("bitrix:main.user.link",
					'',
					array(
						"ID" => $arResult['modifier']["ID"],
						"NAME" => $arResult['modifier']["NAME"],
						"LAST_NAME" => $arResult['modifier']["LAST_NAME"],
						"SECOND_NAME" => $arResult['modifier']["SECOND_NAME"],
						"LOGIN" => $arResult['modifier']["LOGIN"],
						"USE_THUMBNAIL_LIST" => "N",
						"INLINE" => "Y",
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$modifierUserLink = ob_get_clean();
				ob_start();
				$APPLICATION->IncludeComponent("bitrix:main.user.link",
					'',
					array(
						"ID" => $arResult['creator']["ID"],
						"NAME" => $arResult['creator']["NAME"],
						"LAST_NAME" => $arResult['creator']["LAST_NAME"],
						"SECOND_NAME" => $arResult['creator']["SECOND_NAME"],
						"LOGIN" => $arResult['creator']["LOGIN"],
						"USE_THUMBNAIL_LIST" => "N",
						"INLINE" => "Y",
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$creatorUserLink = ob_get_clean();
			?>

			<?= GetMessage('WD_ELEMENT_HISTORY_LAST_EDIT_' . CWebDavElementHistoryComponent::getUserGender($arResult['modifier']['PERSONAL_GENDER']), array('#USER#' => $modifierUserLink)) ?> <?= $arResult['date_modify'] ?><br>
			<?= GetMessage('WD_ELEMENT_HISTORY_FIRST_UPLOAD_' . CWebDavElementHistoryComponent::getUserGender($arResult['creator']['PERSONAL_GENDER']), array('#USER#' => $creatorUserLink)) ?> <?= $arResult['date_create'] ?> <br>
		</div>
	</div>
	<div id="feed-file-history-list" class="feed-file-history-list">
		<div class="feed-file-history-list-title"><?= GetMessage('WD_ELEMENT_HISTORY_LIST_TITLE') ?></div>

		<?
			$detailPage = $historyPage = '';
			$i = 0;
			foreach ($arResult['history'] as $document)
			{
				$i++;
				//last element
				$detailPage = $document['DETAIL_PAGE_URL'];
				if($i == count($arResult['history']))
				{
					$historyPage = $document['HISTORY_PAGE_URL'];
				}
				$dateFormat = FormatDate('x', MakeTimeStamp($document['MODIFIED']));
				$dateFormat = ToUpper(substr($dateFormat, 0, 1)).substr($dateFormat, 1, 1+strlen($dateFormat));
				ob_start();
				$APPLICATION->IncludeComponent("bitrix:main.user.link",
					'',
					array(
						"ID" => $document["USER_ID"],
						"NAME" => $document["USER_NAME"],
						"LAST_NAME" => $document["USER_LAST_NAME"],
						"SECOND_NAME" => $document["USER_SECOND_NAME"],
						"LOGIN" => $document["USER_LOGIN"],
						"USE_THUMBNAIL_LIST" => "N",
						"INLINE" => "Y",
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$userLink = ob_get_clean();
				//todo bad-bad
				$userLink = str_replace('<a ', '<a class="feed-file-history-list-name"', $userLink);

		?>
		<div class="feed-file-history-list-item">
			<span class="feed-file-history-list-num"><?= $i ?>.</span>
			<a target="_blank" href="<?=htmlspecialcharsbx($document['URL_DOWNLOAD'])?>"<?
				?> id="hist-wdif-doc-<?=$document['ID']?>"<?
				?> class="feed-file-history-list-link" <?
				?> data-bx-viewer="iframe"<?
				?> data-bx-title="<?=htmlspecialcharsbx($webdav->arParams['file_name'])?>"<?
				?> data-bx-src="<?=$detailPage . '?showInViewer=1&v=' . $document['ID']?>"<?
				?> data-bx-download="<?=htmlspecialcharsbx($document['URL_DOWNLOAD'])?>"<?
			?>><?= $dateFormat ?></a>
			<span class="feed-com-file-size"><?= $document['FILE_SIZE'] ?></span><span class="feed-file-history-list-text">
				&nbsp;&nbsp;&ndash;&nbsp;
				<?= $userLink ?></span>
		</div>
		<?
			}
			unset($document);
			if($arResult['count_history_items'] > $i)
			{
				$countLangMessage = CWebDavElementHistoryComponent::getNumericCase(
					($arResult['count_history_items']-$i),
					GetMessage('WD_ELEMENT_HISTORY_AND_REVISION_COUNT_1', array('#COUNT#' => ($arResult['count_history_items']-$i))),
					GetMessage('WD_ELEMENT_HISTORY_AND_REVISION_COUNT_21', array('#COUNT#' => ($arResult['count_history_items']-$i))),
					GetMessage('WD_ELEMENT_HISTORY_AND_REVISION_COUNT_2_4', array('#COUNT#' => ($arResult['count_history_items']-$i))),
					GetMessage('WD_ELEMENT_HISTORY_AND_REVISION_COUNT_5_20', array('#COUNT#' => ($arResult['count_history_items']-$i)))
				);
				
				?>
				<div class="feed-com-files-more"><a href="<?= $historyPage ?>" class="feed-com-files-more-link"><?= $countLangMessage ?></a></div>
				<?
			}
		?>
	</div>
</div>

<script type="text/javascript">

	BX.ready(function(){
		BX.viewElementBind('feed-file-history-list', {}, {attribute: 'data-bx-viewer'});
	});
</script>
