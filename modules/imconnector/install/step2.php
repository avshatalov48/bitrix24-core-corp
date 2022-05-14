<?
use \Bitrix\Main\Localization\Loc;

global $APPLICATION;

Loc::loadMessages(__FILE__);

if (\check_bitrix_sessid())
{
	if ($ex = $APPLICATION->GetException())
	{
		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
			'DETAILS' => $ex->GetString(),
			'HTML' => true,
		]);
	}
	else
	{
		\CAdminMessage::ShowNote(Loc::getMessage('MOD_INST_OK'));
	}
	?>
<form action="<?=$APPLICATION->GetCurPage(); ?>">
	<input type="hidden" name="lang" value="<?=LANGUAGE_ID ?>">
	<input type="submit" name="" value="<?=Loc::getMessage('MOD_BACK'); ?>">
	<form>
	<?
}