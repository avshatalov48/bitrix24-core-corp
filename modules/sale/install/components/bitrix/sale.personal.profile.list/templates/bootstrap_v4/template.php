<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

if (!empty($arResult['ERRORS']))
{
	$component = $this->__component;
	foreach($arResult['ERRORS'] as $code => $error)
	{
		if ($code !== $component::E_NOT_AUTHORIZED)
			ShowError($error);
	}

	if ($arParams['AUTH_FORM_IN_TEMPLATE'] && isset($arResult['ERRORS'][$component::E_NOT_AUTHORIZED]))
	{
		?>
		<div class="row">
			<div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
				<div class="alert alert-danger"><?=$arResult['ERRORS'][$component::E_NOT_AUTHORIZED]?></div>
			</div>
			<? $authListGetParams = array(); ?>
			<div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
				<?$APPLICATION->AuthForm('', false, false, 'N', false);?>
			</div>
		</div>
		<?

		return;
	}
}
if($arResult["NAV_STRING"] <> '')
{
	?>
	<div class="row mb-3">
		<div class="col"><?=$arResult["NAV_STRING"]?></div>
	</div>
	<?
}

if (count($arResult["PROFILES"]))
{
	?>
	<div class="row mb-3">
		<div class="col">
			<div class="table-responsive">
				<table class="table table-hover">
					<thead>
						<tr>
							<?
							$dataColumns = array(
								"ID", "DATE_UPDATE", "NAME", "PERSON_TYPE_ID"
							);
							foreach ($dataColumns as $column)
							{
								?>
								<th scope="col">
									<?=Loc::getMessage("P_".$column)?>
									<a class="sale-personal-profile-list-arrow-up" href="<?=$arResult['URL']?>by=<?=$column?>&order=asc#nav_start"><i class="fa fa-chevron-up"></i></a>
									<a class="sale-personal-profile-list-arrow-down" href="<?=$arResult['URL']?>by=<?=$column?>&order=desc#nav_start"><i class="fa fa-chevron-down"></i></a>
								</th>
								<?
							}
							?>
							<th class="text-right"><?=Loc::getMessage("SALE_ACTION")?></th>
						</tr>
					</thead>
					<tbody>
						<?foreach($arResult["PROFILES"] as $val)
					{
						?>
						<tr>
							<th scope="row"><?= $val["ID"] ?></th>
							<td><?= $val["DATE_UPDATE"] ?></td>
							<td><?= $val["NAME"] ?></td>
							<td><?= $val["PERSON_TYPE"]["NAME"] ?></td>
							<td class="text-right">
								<a title="<?= Loc::getMessage("SALE_DETAIL_DESCR") ?>" href="<?= $val["URL_TO_DETAIL"] ?>"><?= GetMessage("SALE_DETAIL") ?></a>
								<span class="sale-personal-profile-list-border"></span>
								<a class="sale-personal-profile-list-close-button" title="<?= Loc::getMessage("SALE_DELETE_DESCR") ?>"
									href="javascript:if(confirm('<?= Loc::getMessage("STPPL_DELETE_CONFIRM") ?>')) window.location='<?= $val["URL_TO_DETELE"] ?>'">
									<?= Loc::getMessage("SALE_DELETE") ?>
								</a>
							</td>
						</tr>
						<?
					}?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?
	if($arResult["NAV_STRING"] <> '')
	{
		?>
		<div class="row">
			<div class="col"><?=$arResult["NAV_STRING"]?></div>
		</div>
		<?
	}
}
else
{
	?>
	<h3><?=Loc::getMessage("STPPL_EMPTY_PROFILE_LIST") ?></h3>
	<?
}
?>
