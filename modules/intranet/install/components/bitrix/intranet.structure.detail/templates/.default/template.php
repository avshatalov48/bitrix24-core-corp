<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?
if (!is_array($arResult['USER'])):
?>
Пользователь не найден
<?
else:
?>
<div class="bx-user-name">
<h2><?=$arResult['USER']['NAME']?> <?=$arResult['USER']['SECOND_NAME']?> <?=$arResult['USER']['LAST_NAME']?></h2>
<small><?=$arResult['USER']['PERSONAL_PROFESSION']?></small>
</div>

<?=(strlen($arResult['USER']['PERSONAL_PHOTO']) > 0) ? '<div class="bx-user-photo">'.$arResult['USER']['PERSONAL_PHOTO'].'</div>' : ''?>

<h4>Подразделения</h4>

<div class="bx-user-departments">
	<ul>
<?
	foreach ($arResult['DEPARTMENTS'] as $ID => $NAME):
?>
		<li><a href="<?=$arParams['DETAIL_URL']?>?set_filter_<?=$arParams['FILTER_NAME']?>=Y&<?=$arParams['FILTER_NAME']?>_UF_DEPARTMENT=<?=$ID?>"><?=$NAME;?></a></li>
<?
	endforeach;
?>
	</ul>
</div>


<div class="bx-user-fields">
	<ul>
		<li><b>E-mail: </b><a href="mailto:<?=$arResult['USER']['EMAIL']?>"><?=$arResult['USER']['EMAIL']?></a></li>
		<li><b>Тел. внутренний: </b><?=$arResult['USER']['UF_PHONE_INNER']?></li>
		<li><b>Тел. мобильный: </b><?=$arResult['USER']['PERSONAL_MOBILE']?></li>
		<li><b>Адрес: </b><?=$arResult['USER']['PERSONAL_STREET']?></li>
	</ul>
</div>

<div class="bx-user-state-history">

</div>
<?/*
<pre>
<?
	print_r($arResult['USER']);
?>
</pre>
*/?>
<?
endif;
?>
<?
if ($arParams['LIST_URL']):
?>
<div style="bx-back-url"><a href="<?=$arParams['LIST_URL']?>">К списку пользователей</a></div>
<?
endif;
?>
