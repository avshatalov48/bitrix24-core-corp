<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arResult
 */
?>

<span class="fields employee field-wrap">
	<?php
	foreach($arResult['value'] as $item)
	{
		$style = null;
		if($item['personalPhoto'])
		{
			$style = 'style="background-image:url(\'' . htmlspecialcharsbx($item['personalPhoto']) . '\'); background-size: 30px;"';
		}
		?>
		<span class="fields employee field-item">
			<?php
			if (empty($item['disabled']))
			{
				?>
				<span class="crm-widget-employee-change">
					<?= Loc::getMessage('INTRANET_FIELD_EMPLOYEE_CHANGE') ?>
				</span>
				<?php
			}
			?>
			<a
				class="uf-employee-wrap"
				href="<?= $item['href'] ?>"
				target="_blank"
			>
				<span
					class="uf-employee-image"
					<?= ($style ?? '') ?>
				>
				</span>
				<span class="uf-employee-data">
					<span class="uf-employee-name">
						<?= $item['name'] ?>
					</span>
					<span class="uf-employee-position">
						<?= $item['workPosition'] ?>
					</span>
				</span>
			</a>
		</span>
	<?php } ?>
</span>
