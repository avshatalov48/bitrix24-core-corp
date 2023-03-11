<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global string $mid
 */

if (!$USER->canDoOperation('edit_other_settings'))
{
	$APPLICATION->authForm(Loc::getMessage("ACCESS_DENIED"));
}

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Main\HttpApplication,
	Bitrix\Main\Config\Option;

$module_id = 'imconnector';

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);

Loader::includeModule($module_id);

\Bitrix\ImConnector\Library::loadMessages();

$request = HttpApplication::getInstance()->getContext()->getRequest();

$listConnector = \Bitrix\ImConnector\Connector::getListConnectorReal(true, false);

/**
 * Description of options
 *
 * Stored in options:
 * 1. the server address
 * 2. a list of available connectors
 * 3. debug mode
 * 4. allow search in bitrix24.network by text
 */
$aTabs = array(
	'DIV' => 'edit1',
	'TAB' => Loc::getMessage('IMCONNECTOR_TAB_SETTINGS'),
	'OPTIONS' => array(
		array(
			'debug',
			Loc::getMessage('IMCONNECTOR_FIELD_DEBUG_TITLE'),
			'',
			array('checkbox'),
		),
		array(
			'allow_search_network',
			Loc::getMessage('IMCONNECTOR_FIELD_ALLOW_SEARCH_NETWORK_TITLE'),
			'Y',
			array('checkbox'),
		),
		array(
			'list_connector',
			Loc::getMessage('IMCONNECTOR_FIELD_LIST_CONNECTOR_TITLE'),
			'',
			array('multiselectbox', $listConnector),
		),
	),
);

if (defined('BX24_HOST_NAME'))
{
	$aTabs['OPTIONS'][] = array(
		'uri_server',
		Loc::getMessage('IMCONNECTOR_FIELD_URI_SERVER_TITLE'),
		'',
		array('text', 50),
	);
}

$tabControl = new \CAdminTabControl('tabControl', [$aTabs]);

$publicUrl = '';

if ($request->isPost() && \check_bitrix_sessid())
{
	if ($request['RestoreDefaults'] != '')
	{
		Option::delete($module_id);
	}
	elseif (($request['Update'] != '') || ($request['Apply'] != ''))
	{
		$publicUrl = trim($request['uri_client'] ?? '');
		$checkResult = new \Bitrix\ImConnector\Result;
		if (!empty($publicUrl))
		{
			$checkResult = \Bitrix\ImConnector\Connector::checkPublicUrl($publicUrl);
			if ($checkResult->isSuccess())
			{
				$saveResult = \Bitrix\ImConnector\Output::saveDomainSite($publicUrl);
				if ($saveResult->isSuccess())
				{
					Option::set(\Bitrix\ImConnector\Library::MODULE_ID, 'uri_client', $publicUrl);
				}
				else
				{
					/** @var \Bitrix\ImConnector\Error $error */
					foreach ($saveResult->getErrors() as $error)
					{
						$errorCode =
							strpos($error->getCode(), 'IM_CONNECTOR_SERVER_ERROR_') === 0
								? str_replace('IM_CONNECTOR_SERVER_ERROR_', 'IMCONNECTOR_ERROR_', $error->getCode())
								: 'IMCONNECTOR_ERROR_'.$error->getCode();

						$message = Loc::getMessage($errorCode) ?: $error->getCode();
						$checkResult->addError(new \Bitrix\Main\Error($message, $error->getCode()));
					}
				}
			}
		}
		elseif (isset($request['uri_client']) && $publicUrl === '')
		{
			// gonna use domain value from 'main:server_name' option
			Option::delete(\Bitrix\ImConnector\Library::MODULE_ID, ['name' => 'uri_client']);
			$defaultPublicUrl = \Bitrix\ImConnector\Connector::getDomainDefault();
			$checkResult = \Bitrix\ImConnector\Connector::checkPublicUrl($defaultPublicUrl);
			if ($checkResult->isSuccess())
			{
				$saveResult = \Bitrix\ImConnector\Output::saveDomainSite($defaultPublicUrl);
				if (!$saveResult->isSuccess())
				{
					/** @var \Bitrix\ImConnector\Error $error */
					foreach ($saveResult->getErrors() as $error)
					{
						$errorCode =
							strpos($error->getCode(), 'IM_CONNECTOR_SERVER_ERROR_') === 0
								? str_replace('IM_CONNECTOR_SERVER_ERROR_', 'IMCONNECTOR_ERROR_', $error->getCode())
								: 'IMCONNECTOR_ERROR_'.$error->getCode();

						$message = Loc::getMessage($errorCode) ?: $error->getCode();
						$checkResult->addError(new \Bitrix\Main\Error($message, $error->getCode()));
					}
				}
			}
		}
		if (!$checkResult->isSuccess())
		{
			$errors = $checkResult->getErrorMessages();
			if ($errors)
			{
				$message = Loc::getMessage(
					'IMCONNECTOR_ERROR_PUBLIC_CHECK',
					['#ERROR#' => '<br>- '. implode('<br>- ', $errors)]
				);
			}
			else
			{
				$message = Loc::getMessage('IMCONNECTOR_ERROR_PUBLIC');
			}
			$APPLICATION->throwException($message);
		}

		\__AdmSettingsSaveOptions($module_id, $aTabs['OPTIONS']);
	}

	if ($exception = $APPLICATION->getException())
	{
		\CAdminMessage::showMessage([
			'DETAILS' => $exception->getString(),
			'TYPE' => 'ERROR',
			'HTML' => true,
		]);
	}
	elseif($request['back_url_settings'] <> '')
	{
		\LocalRedirect($request['back_url_settings']);
	}
	else
	{
		\LocalRedirect(
			$APPLICATION->getCurPage()
			. '?mid='. urlencode($mid)
			. '&mid_menu=1'
			. '&lang='. urlencode(\LANGUAGE_ID)
			. '&'. $tabControl->activeTabParam()
		);
	}
}


$tabControl->Begin();
?>
<form method="post" action="<?= $APPLICATION->getCurPage() ?>?mid=<?= \htmlspecialcharsbx($module_id) ?>&lang=<?= \LANGUAGE_ID ?>">
	<?= \bitrix_sessid_post() ?>
	<?

	$tabControl->BeginNextTab();
	?>
	<tr>
		<td width="40%"><?= Loc::getMessage("IMCONNECTOR_FIELD_URI_CLIENT_TITLE") ?>:</td>
		<td width="60%">
			<input type="text"
				name="uri_client"
				value="<?= \htmlspecialcharsbx($publicUrl ?: Option::getRealValue(\Bitrix\ImConnector\Library::MODULE_ID, 'uri_client', '')) ?>"
				placeholder="<?= \htmlspecialcharsbx(\Bitrix\ImConnector\Library::getCurrentServerUrl()) ?>" /></td>
	</tr>
	<?

	\__AdmSettingsDrawList($module_id, $aTabs['OPTIONS']);

	$tabControl->Buttons();
	if ($request["back_url_settings"] <> '')
	{
		?>
		<input type="submit" name="Update" value="<?=Loc::getMessage("MAIN_SAVE")?>" title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE")?>">
		<?
	}
	?>
	<input type="submit" name="Apply" value="<?=Loc::getMessage("MAIN_OPT_APPLY")?>" title="<?=Loc::getMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?
	if ($request["back_url_settings"] <> '')
	{
		?>
		<input type="button"
			name="Cancel"
			value="<?=Loc::getMessage("MAIN_OPT_CANCEL")?>"
			title="<?=Loc::getMessage("MAIN_OPT_CANCEL_TITLE")?>"
			onclick="window.location='<?= \htmlspecialcharsbx(\CUtil::addslashes($request["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=\htmlspecialcharsbx($request["back_url_settings"])?>">
		<?
	}
	?>
	<input type="submit"
		name="RestoreDefaults"
		title="<?= Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS")?>"
		onclick="return confirm('<?= AddSlashes(Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')"
		value="<?= Loc::getMessage("MAIN_RESTORE_DEFAULTS")?>">
</form>
<?
$tabControl->End();
