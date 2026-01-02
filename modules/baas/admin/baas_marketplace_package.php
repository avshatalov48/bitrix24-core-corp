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
$packageCode = $request->get('packageCode');
$client = new Baas\Config\Client();
\Bitrix\Main\UI\Extension::load([
	'ui.dialogs.messagebox',
	'ui.notification',
]);

if (!$baas->isAvailable())
{
	ShowError(Loc::getMessage('BAAS_IS_NOT_AVAILABLE'));
}
else
{
	$sTableID = 'b_baas_purchased_packages';
	$lAdmin = new CAdminUiList($sTableID);
	$package = Baas\Public\Provider\PackageProvider::create()->getByCode($packageCode);
	$purchaseInfo = $package->getPurchaseInfo(includeDepleted: true);
	$serviceManager = Baas\Baas::getInstance()->getServiceManager();
	if ($purchaseInfo->getCount() > 0)
	{
		foreach ($purchaseInfo->getPurchases() as $purchase)
		{
			/** @var Baas\Model\Dto\PurchasedPackage $purchasedPackage */
			foreach ($purchase->getPurchasedPackages() as $purchasedPackage)
			{
				$balance = [];
				/** @var Baas\Model\Dto\PurchasedServiceInPackage $purchasedService */
				foreach ($purchasedPackage->getPurchasedServices() as $purchasedService)
				{
					$serviceCode = $serviceManager->getByCode($purchasedService->getServiceCode())?->getTitle() ?? $purchasedService->getServiceCode();
					$balance[] = Loc::getMessage('BAAS_PACKAGE_HEADER_BALANCE_ROW', [
						'#serviceCode#' => $serviceCode,
						'#currentValue#' => $purchasedService->getCurrentValue(),
						'#initialValue#' => $purchasedService->getInitialValue(),
					]);
				}
				$row = $lAdmin->addRow($purchasedPackage->getCode(), [
					'START_DATE' => $purchasedPackage->getStartDate(),
					'EXPIRE_DATE' => $purchasedPackage->getExpirationDate(),
					'BALANCE' => '',
					'LOGS' => ''
				], '');

				$row->AddViewField(
					'BALANCE',
					implode('<br />', $balance)
				);
				if ($purchasedService->getCurrentValue() < $purchasedService->getInitialValue() && $purchasedService->getInitialValue() > 1)
				{
					$row->AddViewField(
					'LOGS',
					'<a href="javascript:void(0);" 
							data-action="download-logs" 
							data-package-code="' . $purchasedPackage->getPackageCode(). '"
							data-purchase-code="'. $purchasedPackage->getCode() . '"
							data-service-code="'. $purchasedService->getServiceCode() . '"
						>' . Loc::getMessage('BAAS_PACKAGE_LOGS_ROW_DOWNLOAD') . '</a>'
					);
				}

				$row->setConfig([
					'editable' => false,
				]);
			}
		}
	}

	$lAdmin->bShowActions = false;
	$lAdmin->addHeaders([
		['id' => 'START_DATE', 'content' => Loc::getMessage('BAAS_PACKAGE_HEADER_START_DATE'), 'default' => false],
		['id' => 'EXPIRE_DATE', 'content' => Loc::getMessage('BAAS_PACKAGE_HEADER_EXPIRE_DATE'), 'default' => true],
		['id' => 'BALANCE', 'content' => Loc::getMessage('BAAS_PACKAGE_HEADER_BALANCE'), 'default' => true],
		['id' => 'LOGS', 'content' => ' ', 'default' => true],
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


?><script>
	const showNotification = (response) => {
		BX.UI.Notification.Center.notify({
			content: JSON.stringify(response.data),
			category: 'baas',
			autoHideDelay: 5000,
		});
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

	document.querySelectorAll('a[data-action="download-logs"]').forEach((node) => {

		BX.bind(node, 'click', async () => {
			BX.ajax.runAction('baas.Host.getPurchaseReport', {data: {
				packageCode: node.dataset.packageCode,
				purchaseCode: node.dataset.purchaseCode,
				serviceCode: node.dataset.serviceCode,
			}})
			.then((response) => { downloadAsFile(response.data); })
			.catch((response) => { showResponse(response); });
		});
		return false;
	});
	const downloadAsFile = function downloadAsFile(data) {
		const fileData = ['<table>'];

		Array.from(data).forEach((item) => {
			fileData.push(['<tr><td>', item.datetime, '</td><td>', item.value, '</td></tr>'].join(''));
		});
		fileData.push(['</table>']);

		let a = document.createElement("a");
		let file = new Blob([fileData.join('')], {type: 'application/json'});
		a.href = URL.createObjectURL(file);
		a.download = "logs.xls";
		a.click();
	};
</script><?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
