<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$service = $arParams['SERVICES'][$arParams['MAILBOX']['SERVICE_ID']];

?>

<div class="mail-set-wrap">
	<div class="post-dialog-success-compl">
		<table class="post-dialog-title">
			<tr>
				<td class="post-dialog-title-text align-center">
					<? if ($service['icon']) { ?>
					<img src="<?=$service['icon']; ?>" alt="<?=$service['name']; ?>">
					<? } else {?>
					<span class="post-dialog-success-cell"><?=(strpos($arParams['MAILBOX']['LOGIN'], '@') === false ? $service['name'] : $arParams['MAILBOX']['LOGIN']); ?></span>
					<? } ?>
				</td>
				<td class="post-dialog-title-img"></td>
			</tr>
		</table>
		<table class="post-dialog-success-table">
			<tr>
				<td class="post-dialog-success-cell">
					<?=GetMessage('INTR_MAIL_SUCCESS_MESSAGE_TITLE'); ?>
					<div class="post-dialog-info-text">
						<?=GetMessage('INTR_MAIL_SUCCESS_MESSAGE_TEXT'); ?>
					</div>
					<a href="?mailbox" target="_blank" class="webform-button webform-button-create">
						<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_SUCCESS_MAIL_GO'); ?></span><span class="webform-button-right"></span>
					</a>
					<a href="?page=home" class="webform-button webform-button">
						<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_SUCCESS_MAIL_HOME'); ?></span><span class="webform-button-right"></span>
					</a>
				</td>
			</tr>
		</table>
	</div>
</div>
