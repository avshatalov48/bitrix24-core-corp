<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<meta http-equiv="Content-type" content="text/html;charset=<?echo LANG_CHARSET?>" />
<table border="1">
<?

/** @var CBitrixComponentTemplate $this */
$this->IncludeLangFile('template.php');

$arParams['USER_PROPERTY'] =
	is_array($arParams['USER_PROPERTY_EXCEL']) && count($arParams['USER_PROPERTY_EXCEL']) > 0
	? $arParams['USER_PROPERTY_EXCEL']
	: $arParams['USER_PROPERTY'];

TrimArr($arParams['USER_PROPERTY']);

if (!is_array($arResult['USERS']) || !($USERS_CNT = count($arResult['USERS']))):
	?><tbody><tr><td><?
	echo(GetMessage('INTR_ISL_TPL_NOTE_NULL'));
	?></td></tr></tbody><?
else:
	if (!is_array($arParams['USER_PROPERTY']) || count($arParams['USER_PROPERTY']) <= 0)
		$arParams['USER_PROPERTY'] = array('FULL_NAME', 'PERSONAL_PHONE', 'EMAIL', 'PERSONAL_PROFESSION', 'UF_DEPARTMENT');

?>
	<thead>
		<tr>
<?
foreach ($arParams['USER_PROPERTY'] as $key):
	//if ($key == 'PERSONAL_PHOTO') continue;
?>
			<th><?=$arResult['USER_PROP'][$key]['LIST_COLUMN_LABEL'] ? $arResult['USER_PROP'][$key]['LIST_COLUMN_LABEL'] : GetMessage('ISL_'.$key)?></th>
<?
endforeach;
?>
		</tr>
	</thead>
	<tbody>
<?
	foreach ($arResult['USERS'] as $i => $arUser):
?>
		<tr>
<?
		foreach ($arParams['USER_PROPERTY'] as $key):
			//if ($key == 'PERSONAL_PHOTO') continue;

			$cell_height = 0;
			$cell_width = 0;
			switch($key)
			{
				case 'UF_DEPARTMENT':
					$arResult['USERS'][$i][$key] = is_array($arResult['USERS'][$i][$key]) ? implode('<br />', $arResult['USERS'][$i][$key]) : '';
				break;

				case 'FULL_NAME':
					$arResult['USERS'][$i][$key] = htmlspecialcharsbx($arResult['USERS'][$i]['LAST_NAME'].' '.$arResult['USERS'][$i]['NAME'].' '.$arResult['USERS'][$i]['SECOND_NAME']);
				break;

				case 'EMAIL':
					$arResult['USERS'][$i][$key] = '<a href="mailto:'.urlencode($arResult['USERS'][$i][$key]).'">'.htmlspecialcharsbx($arResult['USERS'][$i][$key]).'</a>';
				break;

				case 'PERSONAL_WWW':
					$arResult['USERS'][$i][$key] = '<a href="http://'.urlencode($arResult['USERS'][$i][$key]).'" target="_blank">'.htmlspecialcharsbx($arResult['USERS'][$i][$key]).'</a>';
				break;

				case 'PERSONAL_GENDER':
					$arResult['USERS'][$i][$key] = $arResult['USERS'][$i][$key] == 'F' ? GetMessage('INTR_ISL_TPL_GENDER_F') : ($arResult['USERS'][$i][$key] == 'M' ? GetMessage('INTR_ISL_TPL_GENDER_M') : '');
				break;

				case 'PERSONAL_PAGER':
				case 'WORK_FAX':
				case 'WORK_PHONE':
				case 'PERSONAL_MOBILE':
				case 'PERSONAL_FAX':
				case 'PERSONAL_PHONE':
					$arResult['USERS'][$i][$key] = ($arResult['USERS'][$i][$key]{0} == '+' ? '&nbsp;' : '').$arResult['USERS'][$i][$key];
				break;

				default: $arResult['USERS'][$i][$key] = htmlspecialcharsbx($arResult['USERS'][$i][$key]); break;
			}

			echo '<td'.($cell_height ? ' height="'.$cell_height.'"' : '').($cell_width ? ' width="'.$cell_width.'"' : '').'>'.$arResult['USERS'][$i][$key].'</td>';
?>
<?
		endforeach;
?>
		</tr>
<?
	endforeach;
?>
	</tbody>
<?
endif;
?>
</table>