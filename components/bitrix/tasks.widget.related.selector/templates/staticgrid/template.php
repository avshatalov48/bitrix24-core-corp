<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arParams =& $this->__component->arParams;
?>

<div class="task-list">
	<table class="task-list-table" cellspacing="0" style="width:100%">
		<thead>
			<tr>
				<th></th>
				<?foreach($arResult['COLUMNS'] as $column):?>
					<th <?=($column['SOURCE'] == 'TITLE'? 'style="width: 35%"' : '')?>>
						<div class="task-head-cell">
							<span class="task-head-cell-title"><?=htmlspecialcharsbx($column['TITLE'])?></span>
						</div>
					</th>
				<?endforeach?>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?foreach($arParams['DATA'] as $item):?>
				<tr class="task-list-item task-depth-0 task-status-accepted">
					<td></td>
					<?foreach($arResult['COLUMNS'] as $column):?>
						<td>
							<?if($column['SOURCE'] == 'TITLE'):?>
								<div class="task-title-info">
									<a class="task-title-link<?=(($item['STATUS'] ?? null) == 5? ' task-title-complete' : '')?>"
									   href="<?=htmlspecialcharsbx($item["URL"])?>"><?=htmlspecialcharsbx($item["TITLE"])?></a>
								</div>
							<?elseif($column['SOURCE'] == 'RESPONSIBLE_ID'):?>
								<a href="<?=htmlspecialcharsbx($item["RESPONSIBLE_URL"])?>" class="task-responsible-link" target="_top"><?=htmlspecialcharsbx($item["RESPONSIBLE_FORMATTED_NAME"])?></a>
							<?else:?>
								<?=htmlspecialcharsbx($item[$column['SOURCE']])?>
							<?endif?>
						</td>
					<?endforeach?>
					<td></td>
				</tr>
			<?endforeach?>
		</tbody>
	</table>
</div>
