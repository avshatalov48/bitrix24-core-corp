<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;

if (Loader::includeModule('extranet') && \CExtranet::IsExtranetSite())
{
	return;
}

$frame = $this->createFrame()->begin('');
$notification = (new Bitrix\Bitrix24\Service\InvitationPushNotificationService())->createInvitationNotification();
if (!is_null($notification))
{
	\Bitrix\Main\UI\Extension::load([
		'intranet.invitation-notification',
	]);
	?>
	<script>
		BX.ready(() => {
			setTimeout(() => {
				(new BX.Intranet.InvitationNotification(<?= \Bitrix\Main\Web\Json::encode($notification) ?>)).show();
			}, 0);
		});
	</script>
	<?php
}
$frame->end();
