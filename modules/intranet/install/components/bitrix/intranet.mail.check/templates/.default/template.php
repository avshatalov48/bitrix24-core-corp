<?
use Bitrix\Main\Config\Option;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$frame = $this->createFrame()->begin('');

if ($arResult['SETTED_UP'] !== false): ?>
	<script type="text/javascript">

		var ExternalMail = {
			interval: <?= intval($arResult['CHECK_INTERVAL']) ?>
		};

		ExternalMail.check = function (force)
		{
			BX.ajax({
				url: "/bitrix/tools/check_mail.php?SITE_ID=<?=SITE_ID; ?>",
				dataType: "json",
				lsId: "sync-mailbox",
				lsTimeout: ExternalMail.interval / 100 * 80,
				lsForce: force ? true : false,
				onsuccess: function (json)
				{
					if (!BX.Type.isPlainObject(json))
					{
						return;
					}

					if (json.hasSuccessSync)
					{
						ExternalMail.scheduleSync(ExternalMail.interval);
					}

					ExternalMail.warning(
						json.failedToSyncMailboxId > 0 ? json.failedToSyncMailboxId : 0
					);

					if (typeof BXIM == "object" && typeof BXIM.notify == "object" && typeof BXIM.notify.counters == "object")
					{
						BXIM.notify.counters.mail_unseen = json.unseen;
					}

					var b24MenuItem = BX('bx_left_menu_menu_external_mail') || BX('menu_external_mail');
					if (b24MenuItem)
					{
						if (typeof B24 == "object" && typeof B24.updateCounters == "function")
						{
							B24.updateCounters({mail_unseen: json.unseen});
						}
					}
					else if (BX("menu_extmail_counter"))
					{
						var link = BX("menu_extmail_counter");
						var counter = BX.findChild(link, {"class": "user-indicator-text"}, true);
						var warning = BX("menu_extmail_warning");

						if (typeof counter == "object")
						{
							BX.adjust(counter, {text: json.unseen});
						}

						if (typeof link == "object")
						{
							BX.adjust(link, {style: {display: "inline-block"}});
						}
						if (typeof warning == "object")
						{
							BX.adjust(warning, {style: {display: "none"}});
						}
					}

					if (typeof BXIM == "object" && typeof BXIM.notify == "object" && typeof BXIM.notify.updateNotifyMailCount == "function")
					{
						BXIM.notify.updateNotifyMailCount(json.unseen);
					}
				}
			});
		};

		ExternalMail.scheduleSync = function (timeout)
		{
			ExternalMail.syncTimeout = clearTimeout(ExternalMail.syncTimeout);
			ExternalMail.syncTimeout = setTimeout(ExternalMail.check, timeout * 1000);
		};

		ExternalMail.scheduleStop = function ()
		{
			ExternalMail.stopTimeout = clearTimeout(ExternalMail.stopTimeout);
			ExternalMail.stopTimeout = setTimeout(function ()
			{
				ExternalMail.syncTimeout = clearTimeout(ExternalMail.syncTimeout);
			}, 15 * 60000);
		};

		ExternalMail.warning = function (id)
		{
			var menu = BX.getClass("BX.Intranet.LeftMenu");
			if (!menu)
			{
				return;
			}

			if (id > 0)
			{
				var url = "<?=\CUtil::jsEscape(Option::get("intranet", "path_mail_config", "/mail/")) ?>".replace("#id#", id);

				BX.Intranet.LeftMenu.showItemWarning({
					itemId: "menu_external_mail",
					events: {
						click: function (event) {
							BX.SidePanel.Instance.open(url, {width: 760, allowChangeHistory: false});
							event.preventDefault();
						}
					},
					title: "<?=GetMessageJS("MAIL_CHECK_MAIL_CONNECTION_ERROR")?>"
				});
			}
			else
			{
				BX.Intranet.LeftMenu.removeItemWarning("menu_external_mail");
			}
		};

		<? if ($arResult['HAS_SUCCESS_SYNC']): ?>

		BX.ready(function ()
		{
			var b24MenuItem = BX('bx_left_menu_menu_external_mail') || BX('menu_external_mail');
			if (b24MenuItem)
			{
				var link = BX.findChild(b24MenuItem, {'class': 'menu-item-link'}, true);
			} else if (BX("menu_extmail_counter"))
			{
				var link = BX("menu_extmail_counter");
			}

			BX.bind(link, "click", function ()
			{
				window.onfocus = function ()
				{
					window.onfocus = null;
					ExternalMail.check(true);
				};
				return true;
			});

			<? if ($arResult['IS_TIME_TO_MAIL_CHECK']): ?>

			ExternalMail.check(true);

			<? elseif ($arResult['NEXT_TIME_TO_CHECK'] >= 0): ?>

			ExternalMail.scheduleSync(<?= (int)$arResult['NEXT_TIME_TO_CHECK'] ?>);

			<? endif ?>
		});

		<? endif ?>

		<? if ($arResult['FAILED_SYNC_MAILBOX_ID']): ?>

		ExternalMail.warning(<?=intval($arResult['FAILED_SYNC_MAILBOX_ID']) ?>);

		<? endif; ?>

	</script>

<? endif ?>
<? $frame->end(); ?>