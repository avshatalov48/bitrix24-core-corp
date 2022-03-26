<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

$component = $this->getComponent();
$selectorName = $arResult['selectorName'];

if($arResult['userField']['EDIT_IN_LIST'] === 'Y')
{
	?>

	<div
		id="cont_<?= $selectorName ?>"
		data-has-input="no"
	>
		<div
			id="field_<?= $selectorName ?>"
			class="main-ui-control-entity main-ui-control userfieldemployee-control"
			data-multiple="<?= ($arResult['userField']['MULTIPLE'] === 'Y' ? 'true' : 'false') ?>"
		>
			<a
				href="#"
				class="feed-add-destination-link"
				id="add_user_<?= $selectorName ?>"
				onclick="BX.Intranet.UserFieldEmployee.instance('<?= $arResult['selectorNameJs'] ?>').open(this.parentNode); return false;"
			>
				<?= Loc::getMessage('INTR_PROP_EMP_SU') ?>
			</a>
		</div>
		<div
			id="value_<?= $selectorName ?>"
			style="display: none;"
		>
			<input
				type="hidden"
				name="<?= HtmlFilter::encode($arResult['fieldName']) ?>"
				value=""
			>
		</div>
	</div>

	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.selector',
		'.default',
		[
			'API_VERSION' => 3,
			'ID' => $selectorName,
			'BIND_ID' => 'field_' . $selectorName,
			'TAG_ID' => 'add_user_' . $selectorName,
			'ITEMS_SELECTED' => $arResult['componentValue'],
			'CALLBACK' => [
				'select' => $arResult['jsObject'] . '.callback.select',
				'unSelect' => $arResult['jsObject'] . '.callback.unSelect',
			],
			'OPTIONS' => [
				'useContainer' => 'Y',
				'multiple' => ($arResult['isMultiple'] ? 'Y' : 'N'),
				'extranetContext' => false,
				'eventInit' => 'BX.UFEMP:' . $selectorName . ':init',
				'eventOpen' => 'BX.UFEMP:' . $selectorName . ':open',
				'context' => EmployeeUfComponent::SELECTOR_CONTEXT,
				'contextCode' => 'U',
				'useSearch' => 'Y',
				'userNameTemplate' => \CSite::GetNameFormat(),
				'useClientDatabase' => 'Y',
				'allowEmailInvitation' => 'N',
				'enableAll' => 'N',
				'enableDepartments' => 'Y',
				'enableSonetgroups' => 'N',
				'departmentSelectDisable' => 'Y',
				'allowAddUser' => 'N',
				'allowAddCrmContact' => 'N',
				'allowAddSocNetGroup' => 'N',
				'allowSearchEmailUsers' => 'N',
				'allowSearchCrmEmailUsers' => 'N',
				'allowSearchNetworkUsers' => 'N'
			]
		],
		false,
		['HIDE_ICONS' => 'Y']
	);
	?>

	<script>
		BX.ready(function ()
		{
			new BX.Default.Field.Employee(
				<?=CUtil::PhpToJSObject([
					'selectorName' => $selectorName,
					'isMultiple' => $arResult['isMultiple'],
					'fieldNameJs' => $arResult['fieldNameJs'],
				])?>
			);
		});
	</script>

	<?php
}
elseif($arResult['value'])
{
	foreach($arResult['value'] as $item)
	{
		$style = null;
		if($item['personalPhoto'])
		{
			$style = 'style="background-image:url(\'' . htmlspecialcharsbx($item['personalPhoto']) . '\'); background-size: 30px;"';
		}
		?>
		<span class="fields employee field-item" data-has-input="no">
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
		<?php
	}
}
else
{
	?>
	<span class="fields employee field-wrap" data-has-input="no">
	<?php
	if(is_array($arResult['value']))
	{
		foreach($arResult['value'] as $item)
		{
			$style = null;
			if($item['personalPhoto'])
			{
				$style = 'style="background-image:url(' . $item['personalPhoto'] . '); background-size: 30px;"';
			}
			?>
			<span class="fields employee field-item">
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
			<?php
		}
	}
	else
	{
		print Loc::getMessage('EMPLOYEE_FIELD_EMPTY');
	}
	?>
	</span>
	<?php
}
