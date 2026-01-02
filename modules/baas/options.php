<?php

/** @var CMain $APPLICATION */

use Bitrix\Main;
use Bitrix\Baas;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$moduleId = 'baas';

Main\Loader::includeModule($moduleId);

$baas = Baas\Baas::getInstance();

if (!$baas->isAvailable())
{
	?><h1>Baas is not available</h1><?php
}

\Bitrix\Main\UI\Extension::load([
	'ui.dialogs.messagebox',
	'ui.notification',
	'baas.utility.registration',
]);

$request = Main\Application::getInstance()->getContext()->getRequest();
$client = new Baas\Config\Client();
$server =  Main\Loader::includeModule('bitrix24')
	? new Baas\UseCase\External\Entity\Bitrix24Server() : new Baas\UseCase\External\Entity\BusServer()
;
$historyBaasServers= json_decode(Main\Config\Option::get('baas', 'servers', ''), true) ?? [];

$tabs = [
	'server' => [
		'DIV' => 'server',
		'TAB' => Loc::getMessage('BAAS_OPTIONS_TAB_SERVER'),
		'TITLE' => Loc::getMessage('BAAS_OPTIONS_TAB_SERVER_TITLE'),
	],
];

if (
	$request->isPost()
	&& check_bitrix_sessid()
	&& $request->getPost('SaveBaasServerSettings') === 'Y'
)
{
	$action = 'none';
	if ($request->getPost('reset_migration'))
	{
		$action = 'reset_migration';
	}
	else if ($request->getPost('save'))
	{
		$action = 'save';
	}
	else if ($request->getPost('saveAndRegister'))
	{
		$action = 'saveAndRegister';
	}
	try
	{
		switch ($action)
		{
			case 'reset_domain_url':
				$server->getConfigs()->resetUrl();
				break;
			case 'save':
			case 'saveAndRegister':
					$baasServerUrl = $request->getPost('baas_server_url');
					$server->getConfigs()->setUrl($baasServerUrl);
					$historyBaasServers[] = $baasServerUrl;
					$historyBaasServersJson = json_encode(array_unique($historyBaasServers));
					Main\Config\Option::set('baas', 'servers', $historyBaasServersJson);
					if ($action === 'saveAndRegister' && Baas\Service\BillingService::getInstance()->register(true)->isSuccess())
					{
						Baas\Service\BillingService::getInstance()->synchronizeWithBilling();
					}
				break;
		}

		LocalRedirect($APPLICATION->GetCurPageParam());
	}
	catch (Main\SystemException $exception)
	{
		ShowError($exception->getMessage());
	}
}

$tabControl = new CAdminTabControl('tabControl', array_values($tabs));
?>
<form method='post' action='<?=$APPLICATION->getCurPage()?>?mid=<?=urlencode($moduleId)?>&lang=<?=LANGUAGE_ID?>'>
	<?=bitrix_sessid_post()?>
	<?php
	$tabControl->begin();
	$tabControl->beginNextTab();
		?>
		<input type="hidden" name="SaveBaasServerSettings" value="Y">
		<tr>
			<td style="width: 40%;"><?=Loc::getMessage('BAAS_OPTIONS_TITLE_ACTUAL_BAAS_SERVER_URL')?></td>
			<td style="width: 60%;">
				<input style="min-width: 300px;" type="text" readonly value="<?=htmlspecialcharsbx($server->getConfigs()->getUrl())?>">
				<input type="button" id="chane-baas" value="<?=Loc::getMessage('MAIN_EDIT')?>">
			</td>
		</tr>
		<tr>
			<td><?=Loc::getMessage('BAAS_OPTIONS_TITLE_ACTUAL_STATUS')?></td>
			<td>
				<?=$baas->isRegistered() ?
					Loc::getMessage('BAAS_OPTIONS_TITLE_ACTUAL_STATUS_REGISTERED') :
					Loc::getMessage('BAAS_OPTIONS_TITLE_ACTUAL_STATUS_IS_NOT_REGISTERED')
				?>
			</td>
		</tr>
		<tr id="new-baas" style="display: none;">
			<td><?=Loc::getMessage('BAAS_OPTIONS_TITLE_NEW_BAAS_SERVER_URL')?>:</td>
			<td>
				<input style="min-width: 300px;" type="text" name="baas_server_url" value="<?=htmlspecialcharsbx($server->getConfigs()->getUrl())?>">
			</td>
		</tr>
		<?php

		if (!empty($historyBaasServers))
		{
		?>
		<tr id="saved-baas" style="display: none;">
			<td><?=Loc::getMessage('BAAS_OPTIONS_TITLE_PREVIOUS_BAAS_SERVER_URL')?>:</td>
			<td>
				<select style="min-width: 315px;" name="baas_server_urls_repo">
					<?php
					foreach ($historyBaasServers as $url)
					{
						?><option><?=htmlspecialcharsbx($url)?></option><?php
					}
					?>
				</select>
				<input type="button" value="<?=Loc::getMessage('BAAS_OPTIONS_INSERT')?>" onclick="this.form.querySelector('[name=baas_server_url]').value=this.form.querySelector('[name=baas_server_urls_repo]').value;">
			</td>
		</tr>
		<?php
	}

	$tabControl->endTab();
	$tabControl->Buttons();
	?>
	<input type="submit" name="saveAndRegister" style="display: none;" value="<?=Loc::getMessage("BAAS_OPTIONS_SAVE_AND_REGISTER") ?? 'Save and Register'?>" class="adm-btn-save">
	<input type="submit" name="save" style="display: none;" value="<?=Loc::getMessage("admin_lib_edit_save") ?? 'Save'?>" ><?php
	if ($baas->isRegistered()):
		?><input type="button" name="register-portal" value="<?=Loc::getMessage("BAAS_OPTIONS_REREGISTER") ?? 'Reregister'?>"><?php
	else:
		?><input type="button" name="register-portal" value="<?=Loc::getMessage("BAAS_OPTIONS_REGISTER") ?? 'Register'?>" class="adm-btn-save"><?php
	endif;
	if (IsModuleInstalled('bitrix24')):
		?><input type="submit" name="reset_migration" value="Reset migrations"><?php
	endif;
	$tabControl->end();
?>
</form>
<script>
	BX.ready(() => {
		BX.message(<?=Main\Web\Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		const changeBaasButton = document.querySelector('input[id="chane-baas"]');
		changeBaasButton.addEventListener('click', function() {
			changeBaasButton.style.display = 'none';
			document.querySelector('tr[id="new-baas"]').style.display = 'table-row';
			document.querySelector('input[name="saveAndRegister"]').style.display = 'inline-block'
			document.querySelector('input[name="save"]').style.display = 'inline-block';
			const node = document.querySelector('tr[id="saved-baas"]');
			if (node)
			{
				node.style.display = 'table-row';
			}
		});
		const registerNode = document.querySelector('input[type=button][name="register-portal"]');
		const registerCb = function() {
			registerNode.disabled = true;
			BX.loadExt('baas.utility.registration').then(function() {
				BX.Baas.Utility.Registration.getInstance().bind(registerNode).send().finally(() => {
					registerNode.disabled = false;
				});
			});
		};
		BX.bind(registerNode, 'click', registerCb);
	});
</script>

