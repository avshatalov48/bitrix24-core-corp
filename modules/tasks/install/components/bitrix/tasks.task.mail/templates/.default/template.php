<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if(!empty($arResult['ERROR']))
{
	ShowError(Loc::getMessage('TASKS_FORMAT_ERROR'));
	return;
}
?>

<?$data = $arResult['DATA']['TASK'];?>
<?$sender = $arResult['DATA']['MEMBERS']['SENDER'];?>
<?$receiver = $arResult['DATA']['MEMBERS']['RECEIVER'];?>
<?$path = $arResult['AUX_DATA']["ENTITY_URL"];?>

<table cellpadding="0" cellspacing="0" border="0" align="left" style="border-collapse: collapse;mso-table-lspace: 0pt;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: 14px;width: 100%;max-width: 600px;">

	<?//header?>
	<tr>
		<td align="left" valign="top" style="border-collapse: collapse;border-spacing: 0;padding: 3px 15px 8px 0;text-align: left;">
			<table cellpadding="0" cellspacing="0" border="0" align="left" style="border-collapse: collapse;mso-table-lspace: 0pt;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: 14px;width: 100%;">
				<tr>
					<td width="50" style="border-collapse: collapse;border-spacing: 0;padding: 0 17px 0 0;width: 50px;">
						<img height="50" width="50" src="<?=$sender['AVATAR']?>" alt="user" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;border-radius: 50%;display: block;">
					</td>
					<td width="" style="border-collapse: collapse;border-spacing: 0;padding: 0 17px 0 0;">
						<a href="<?=$path?>" target="_blank" style="color: #2067b0;font-size: 14px;font-weight: bold;vertical-align: top;"><?=htmlspecialcharsbx($sender['NAME_FORMATTED'])?></a>
						<img height="12" width="20" src="<?=$arResult['TEMPLATE_FOLDER']?>/img/arrow.gif" alt="&rarr;" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;display: inline;font-size: 19px;vertical-align: top;line-height: 15px;">
						<span style="color: #7f7f7f;font-size: 14px;vertical-align: top;">
							<?=htmlspecialcharsbx($receiver['NAME_FORMATTED'])?>
						</span>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<table cellspacing="0" cellpadding="0" border="0" align="left" style="border-collapse: collapse;mso-table-lspace: 0pt;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;width: 100%;margin-bottom:10px;">
				<tr>
					<td style="padding-left:10px;">
						<img src="<?=$arResult['TEMPLATE_FOLDER']?>/img/icon.png" alt="">
					</td>
					<td style="color: #55606f;font-size: 14px;">
						<?$action = Loc::getMessage('TASKS_'.$arParams['ENTITY_ACTION'].'_'.$arParams['ENTITY'].'_'.$sender['PERSONAL_GENDER']);?>
						<?=($action != '' ? $action : Loc::getMessage('TASKS_TASK'))?>: <span style="color: #0067a3;font-size: 14px;line-height: 16px;"><?=htmlspecialcharsbx($data['TITLE'])?></span>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<?//body?>

	<?//task add?>
	<?if($arParams['ENTITY'] == 'TASK'):?>
		<tr>
			<td valign="top" style="background: #fffae3; border-collapse: collapse;border-spacing: 0;color: #000000;font-size: 14px;vertical-align: top;padding: 18px 15px 10px;">

				<?if($arParams['ENTITY_ACTION'] == 'ADD'):?>

					<b style="font-size:14px;"><?=htmlspecialcharsbx($data['TITLE'])?></b>
					<br />
					<br />
					<?if((string) $data['DEADLINE'] != ''):?>
						<div style="color:#4c4b44;font-size:13px;">
							<?=Loc::getMessage('TASKS_FIELD_DEADLINE')?>: <b style="font-weight:normal;color:#000;margin-right:10px"><?=htmlspecialcharsbx($data['DEADLINE'])?></b>
						</div>
						<br />
					<?endif?>
					<?if((string) $data['START_DATE_PLAN'] != ''):?>
						<div style="color:#4c4b44;font-size:13px;">
							<?=Loc::getMessage('TASKS_FIELD_START_DATE_PLAN')?>: <b style="font-weight:normal;color:#000;margin-right:10px"><?=htmlspecialcharsbx($data['START_DATE_PLAN'])?></b>
						</div>
						<br />
					<?endif?>
					<?if((string) $data['END_DATE_PLAN'] != ''):?>
						<div style="color:#4c4b44;font-size:13px;">
							<?=Loc::getMessage('TASKS_FIELD_END_DATE_PLAN')?>: <b style="font-weight:normal;color:#000;margin-right:10px"><?=htmlspecialcharsbx($data['END_DATE_PLAN'])?></b>
						</div>
						<br />
					<?endif?>
					<?if($data['PRIORITY'] == CTasks::PRIORITY_HIGH):?>
						<div style="color:#4c4b44;font-size:13px;">
							<?=Loc::getMessage('TASKS_FIELD_PRIORITY')?>: <b style="font-weight:normal;color:red;margin-right:10px"><?=Loc::getMessage('TASKS_IMPORTANT')?></b>
						</div>
						<br />
					<?endif?>
					<?if($data['STATUS'] == CTasks::METASTATE_EXPIRED):?>
						<div style="border-radius:2px;font-size:13px;display: inline-block;background: #fcbe9e;padding:10px 15px;color:#000;"><?=Loc::getMessage('TASKS_EXPIRED')?></div>
					<?else:?>
						<div style="border-radius:2px;font-size:13px;display: inline-block;background: #<?if($data["REAL_STATUS"] == CTasks::STATE_DEFERRED):?>fee178<?else:?>e3f1b8<?endif?>;padding:10px 15px;color:#000;"><?=Loc::getMessage("TASKS_STATUS_".$data["REAL_STATUS"])?><?if((string) $data["STATUS_CHANGED_DATE"] != ''):?><?if($arResult['S_NEEDED']):?> <?=Loc::getMessage('TASKS_SIDEBAR_START_DATE')?><?endif?> <b><?=htmlspecialcharsbx($data["STATUS_CHANGED_DATE"])?></b><?endif?></div>
					<?endif?>
					<br />
					<br />
					<?if((string) $data['DESCRIPTION'] != ''):?>
						<b><?=Loc::getMessage('TASKS_FIELD_DESCRIPTION')?>:</b>
						<p>
							<?=$data['DESCRIPTION']?>
						</p>
					<?endif?>
					<?if(!empty($data['SE_CHECKLIST'])):?>
						<b><?=Loc::getMessage('TASKS_CHECKLIST')?>:</b>
						<p style="font-size:14px;">

							<?$i = 1;?>
							<?foreach($data['SE_CHECKLIST'] as $item):?>
								<?if(\Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue($item['TITLE'])):?>
									<hr style="height: 1px;border: none;background:#d9d5c1">
								<?else:?>
									- <?=$item['TITLE_HTML']?> <br />
								<?endif?>
								<?$i++;?>

								<?if($i > $arResult['CHECKLIST_LIMIT']):?>
									<?break;?>
								<?endif?>

							<?endforeach?>
						</p>
						<?if($arResult['CHECKLIST_MORE']):?>
							<a href="<?=$path?>" style="border-bottom: 1px dashed #969999;text-decoration:none; color:#969999; font-size:11px;"><?=Loc::getMessage('TASKS_MORE')?> <?=intval($arResult['CHECKLIST_MORE'])?></a>
						<?endif?>
						<br />
						<br />
						<br />
					<?endif?>

					<?if(!empty($arResult['DATA']['ATTACHMENT'])):?>
						<b><?=Loc::getMessage('TASKS_FILES')?>:</b>
						<p style="font-size:14px;">
						<?foreach($arResult['DATA']['ATTACHMENT'] as $file):?>
							<a href="<?=$file['URL']?>" target="_blank" style="color: #146cc5;font-size:12px;"><?=htmlspecialcharsbx($file['NAME'])?></a><br />
						<?endforeach?>
						<p>
					<?endif?>

				<?else:?>

					<?//task update?>
					<div>

						<?foreach($arResult['AUX_DATA']['CHANGES'] as $key => $change):?>
							<?$title = Loc::getMessage('TASKS_FIELD_'.$key);?>
							<?if($title == ''):?>
								<?continue;?>
							<?endif?>

							<?
							$value = false;

							switch ($key)
							{
								case 'TIME_ESTIMATE':
									if(intval($change['TO_VALUE']))
									{
										$value = \Bitrix\Tasks\UI::formatTimeAmount($change['TO_VALUE']);
									}
									break;

								case "TITLE":
									$value = htmlspecialcharsbx($change["TO_VALUE"]);
									break;

								case "CREATED_BY":
									$value = htmlspecialcharsbx(\Bitrix\Tasks\Util\User::formatName($data['SE_ORIGINATOR']));
									break;
								case "RESPONSIBLE_ID":
									$value = htmlspecialcharsbx(\Bitrix\Tasks\Util\User::formatName(array_shift($data['SE_RESPONSIBLE'])));
									break;

								case "ACCOMPLICES":
								case "AUDITORS":

									$value = '<nobr>'.implode('</nobr>, <nobr>', array_map('htmlspecialcharsbx', $change['TO_VALUE'])).'</nobr>';
									break;

								case "DESCRIPTION":
									$value = $data['DESCRIPTION'];
									break;

								case "TAGS":
									$value = htmlspecialcharsbx(\Bitrix\Tasks\Ui\Task\Tag::formatTagString($data['SE_TAG']));
									break;

								case "PRIORITY":

									if($change["FROM_VALUE"] == CTasks::PRIORITY_HIGH || $change["TO_VALUE"] == CTasks::PRIORITY_HIGH)
									{
										$value = $change['TO_VALUE'] == CTasks::PRIORITY_HIGH ? Loc::getMessage('TASKS_IMPORTANT') : Loc::getMessage('TASKS_NORMAL');
									}

									break;

								case "STATUS":
									$value = Loc::getMessage('TASKS_STATUS_'.$data["REAL_STATUS"]);
									break;

								case "GROUP_ID":

									if(($change['TO_VALUE'] = intval($change['TO_VALUE'])) && $arResult['DATA']['GROUP'][$change['TO_VALUE']])
									{
										$value = htmlspecialcharsbx($arResult['DATA']['GROUP'][$change['TO_VALUE']]['NAME']);
									}

									break;

								case "PARENT_ID":

									if($data['SE_PARENTTASK']['ID'])
									{
										$value = htmlspecialcharsbx($data['SE_PARENTTASK']['TITLE']);
									}

									break;

								case "MARK":
									$value = ($data['MARK'] != 'P' && $data['MARK'] != 'N') ? Loc::getMessage('TASKS_FIELD_MARK_NONE') : Loc::getMessage('TASKS_FIELD_MARK_'.$data['MARK']);
									break;

								default:
									$value = htmlspecialcharsbx($data[$key]);
									break;
							}
							?>

							<div style="padding-bottom: 1px;line-height:20px;vertical-align:top;font-size:14px;color:#aa966a;"><?=$title?>: </div>
							<div style="padding-bottom:10px;line-height:20px;vertical-align:top;font-size:14px;color:#000;"><?=($value === false ? Loc::getMessage('TASKS_FIELD_NO_VALUE') : $value)?></div>
						<?endforeach?>

					</div>

				<?endif?>
			</td>
		</tr>

	<?elseif($arParams['ENTITY'] == 'COMMENT'):?>
		<?
		$APPLICATION->IncludeComponent(
			"bitrix:forum.comments",
			"mail",
			Array(
				"FORUM_ID" => $data["FORUM_ID"],
				"ENTITY_TYPE" => 'TK',
				"ENTITY_ID" => $data['ID'],
				"ENTITY_XML_ID" => 'TASK_'.$data['ID'],
				"ENTITY_URL" => $path,
				"RECIPIENT_ID" => $arParams["RECIPIENT_ID"],
				"CHECK_ACTIONS" => "N"
			),
			false
		);
		?>
	<?endif?>

	<?//footer?>
	<tr>
		<td valign="top" align="center" style="border-collapse: collapse;border-spacing: 0;border-top: 1px solid #edeef0;padding: 33px 0 20px;">
			<table cellspacing="0" cellpadding="0" border="0" align="center" style="border-collapse: collapse;mso-table-lspace: 0pt;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
				<tr>
					<td style="border-collapse: collapse;border-spacing: 0;background-color: #44c8f2;padding: 0;">
						<a href="<?=$path?>" target="_blank" style="color: #ffffff;background-color: #44c8f2;border: 8px solid #44c8f2;border-radius: 2px;display: block;font-family: Helvetica, Arial, sans-serif;font-size: 12px;font-weight: bold;padding: 4px;text-transform: uppercase;text-decoration: none;"><?=Loc::getMessage('TASKS_GOTO_TASK')?></a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="center" style="border-collapse: collapse;border-spacing: 0;color: #8b959d;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: 11px;text-align: center;padding: 14px 0 0;">
			<?=Loc::getMessage('TASKS_FOOTER_HINT', array('#BR#' => '<br />'))?>
		</td>
	</tr>
</table>