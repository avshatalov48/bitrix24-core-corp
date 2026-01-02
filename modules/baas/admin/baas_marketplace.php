<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
/**
 * @var \CMain $APPLICATION
 */
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Baas;

$APPLICATION->setTitle(Loc::getMessage('BAAS_TITLE'));

if (Main\Loader::includeModule('baas') !== true)
{
	throw new Main\SystemException('Module baas is not installed');
}

$baas = Baas\Baas::getInstance();
$request = Main\Application::getInstance()->getContext()->getRequest();
$client = new Baas\Config\Client();
\Bitrix\Main\UI\Extension::load([
	'ui.dialogs.messagebox',
	'ui.notification',
]);
if (!$baas->isAvailable())
{
	ShowError(Loc::getMessage('BAAS_IS_NOT_AVAILABLE'));
	?><div data-bx-role="baas-disability-error"></div><?php
}
else if ($baas->isRegistered() !== true)
{
	echo BeginNote();
	?><?=Loc::getMessage('BAAS_IS_NOT_REGISTERED', ['#LINK#' => '/bitrix/admin/settings.php?mid=baas']);
	echo EndNote();
}
else if ($client->getLastSyncTime() <= 0)
{
	echo BeginNote();
	?><?=Loc::getMessage('BAAS_IS_NOT_SYNCHRONIZED');
	?><input type="button" name="refresh-portal" value="<?=Loc::getMessage('BAAS_SYNC')?>" class="adm-btn-save" /><?php
	echo EndNote();
}
else
{
	$sTableID = 'b_baas_packages';
	$lAdmin = new CAdminUiList($sTableID);
	$packageProvider = Baas\Public\Provider\PackageProvider::create();

	foreach ($packageProvider->getDistributedByBaas() as $package)
	{
		$row = $lAdmin->addRow($package->getCode(), [
			'CODE' => $package->getCode(),
			'TITLE' => $package->getTitle(),
			'DESCRIPTION' => $package->getDescription(),
			'PRICE' => $package->getPrice(),
			'BUY' => '',
			'PURCHASES' => ''
		], '');

		if ($package->isActive())
		{
			$row->AddViewField(
			'PRICE',
			'<nobr>'. $package->getPrice() . '</nobr> ' . $package->getPriceDescription() . '<br>'
			. '<button  type="button" data-bx-purchase-ulr="' . \CUtil::JSUrlEscape($package->getPurchaseUrl()) . '"> ' . Loc::getMessage('BAAS_PACKAGE_HEADER_BUY') . '</button>'
			);
		}
		else
		{
			$row->AddViewField('PRICE',
				'<button  type="button" disabled> ' . Loc::getMessage('BAAS_PACKAGE_HEADER_BUY') . '</button>'
			);
		}
		$purchaseInfo = $package->getPurchaseInfo(includeDepleted: true);

		$row->AddViewField(
			'PURCHASES',
			$purchaseInfo->getCount() <= 0 ?
				'0' :
				'<a href="/bitrix/admin/baas_marketplace_package.php?packageCode='. htmlspecialcharsbx($package->getCode()) .'">'
				. $purchaseInfo->getCount()
				. ' (' . $purchaseInfo->getBalance(). '%)'
				. '</a> '
		);

		$row->setConfig([
			'editable' => false,
		]);
	}
	$lAdmin->bShowActions = false;
	$lAdmin->addHeaders([
		['id' => 'CODE', 'content' => 'Code', 'sort' => 'CODE', 'default' => false],
		['id' => 'TITLE', 'content' => Loc::getMessage('BAAS_PACKAGE_HEADER_TITLE'), 'default' => true],
		['id' => 'DESCRIPTION', 'content' => Loc::getMessage('BAAS_PACKAGE_HEADER_DESCRIPTION'), 'default' => true],
		['id' => 'PRICE', 'content' => Loc::getMessage('BAAS_PACKAGE_HEADER_PRICE'), 'default' => true],
		['id' => 'PURCHASES', 'content' => Loc::getMessage('BAAS_PACKAGE_HEADER_BOOSTS'), 'default' => true],
	]);
	// $lAdmin->AddAdminContextMenu([
	// 	[
	// 		'TEXT' => 'Refresh client',
	// 		'ONCLICK' => 'BX.refreshClient(this);',
	// 		'ICON' => 'btn_new'
	// 	],
	// ], false, false);
	$lAdmin->CheckListMode();
	$lAdmin->DisplayList([
		'SHOW_COUNT_HTML' => false,
		'ACTION_PANEL' => false,
		'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => false,
		'SHOW_TOTAL_COUNTER' => false,
	]);
}

echo BeginNote();
?><?=Loc::getMessage('BAAS_DATA_DATE', ['#DATETIME#' => Main\Type\DateTime::createFromTimestamp($client->getLastSyncTime())->toString()]) ?>
	<input type="button" name="refresh-portal" value="<?=Loc::getMessage('BAAS_SYNC')?>" />
<?php
echo EndNote();

$exceptionHandling = Main\Config\Configuration::getInstance()->get('exception_handling');
?><script>
	BX.ready(() => {
		BX.message(<?=Main\Web\Json::encode(Loc::loadLanguageFile(__FILE__))?>);
		const debugMode = <?=(($exceptionHandling['debug'] ?? false) ? 'true' : 'false')?>;

		const showNotification = (response) => {
			if (debugMode)
			{
				BX.UI.Notification.Center.notify({
					content: JSON.stringify(response.data),
					category: 'baas',
					autoHideDelay: 5000,
				});
			}
			else
			{
				window.location.reload();
			}
		};

		const showResponse = (title, answer, response) => {
			const {status, body} = response.data.httpResponse;

			BX.UI.Dialogs.MessageBox.show({
				title: title || `Response with a status: ${status}`,
				message: answer || `<p>${body}</p>`,
				modal: true,
				buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
				maxWidth: 1000
			});
		};

		document
			.querySelector('input[type=button][name="refresh-portal"]')
			.addEventListener('click', async () => {
				BX.ajax.runAction('baas.Host.refresh', {data: {}})
					.then((response) => { showNotification(response); })
					.catch((response) => { showResponse(response); });
			})
		;
		const node = document.querySelector('input[type=button][name="show-widget"]');
		const cb = function() {
			BX.loadExt('baas.store').then(function(exports) {
				BX.Baas.Store.Widget.getInstance().bind(node, exports.Analytics.CONTEXT_LICENSE_WIDGET).show();
			});
		};
		BX.bind(node, 'click', cb);

		document
			.querySelectorAll('button[type=button][data-bx-purchase-ulr]')
			.forEach((node) => {
				node
					.addEventListener('click', async () => {
						const purchaseUrl = node.getAttribute('data-bx-purchase-ulr');
						if (purchaseUrl.indexOf('http') === 0)
						{
							window.open(purchaseUrl);
						}
						else
						{
							BX.SidePanel.Instance.open(
								purchaseUrl,
								{
									width: 1250,
									cacheable: false,
								},
							);
						}
					})
				;
			})
		;

		const availabilityErrorNode = document.querySelector('[data-bx-role="baas-disability-error"]');
		if (availabilityErrorNode) {
			BX.ajax.runAction('baas.Host.getBaasStatus', { data: {} })
				.then((response) => {
					const status = response.data;
					status.code = BX.util.htmlspecialchars(status.code);
					status.description = BX.util.htmlspecialchars(status.description);
					let description = '';
					switch (status.code)
					{
						case 'ok':
							description = BX.message('BAAS_STATUS_STOPPED');
							break;
						case 'stop':
							description = BX.message('BAAS_STATUS_STOPPED');
							break;
						case 'no':
							description = BX.message('BAAS_STATUS_STOPPED');
							break;
					}

					availabilityErrorNode.innerHTML = debugMode ?
						`<dl><dt>${status.code}</dt><dd>${status.description}</dd><dd>${description}</dd></dl>`
							:
						`<h2>${description ?? status.description}</h2>`
					;
				})
				.catch(() => {
				})
			;
		}
	});
</script><?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
