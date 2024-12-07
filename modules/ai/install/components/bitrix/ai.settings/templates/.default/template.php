<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\AI\Tuning\Manager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load([
	'ui.design-tokens',
	'ui.layout-form',
	'ui.forms',
]);

/** @var \CMain $APPLICATION */
/** @var array $arResult */

/** @var bool $isSaved */
$isSaved = $arResult['SAVE'];

/** @var Bitrix\AI\Tuning\GroupCollection $list */
$list = $arResult['LIST'];
?>

<?php if ($isSaved):?>
<script>
	BX.ready(function()
	{
		top.BX.SidePanel.Instance.close();
	});
</script>
<?php endif?>

<form class="ui-form ai-settings_form" method="post" action="<?= POST_FORM_ACTION_URI?>">
	<?= bitrix_sessid_post()?>
	<div class="">
		<?php foreach ($list as $code => $group):?>
			<?php if (!$group->getItems()->isEmpty()): ?>
			<div class="ai-settings-section">
				<div class="ui-form-section">
					<div class="ai-settings_table-wrapper">
						<h3 class="ai-settings_group-title"><?= $group->getTitle() ?></h3>
						<?php if ($group->getDescription()): ?>
							<h4 class="ai-settings_group-description"> <?= $group->getDescription() ?></h4>
						<?php endif; ?>
						<table class="ai-settings_table">
							<tbody>
							<?php foreach ($group->getItems() as $item):?>
								<tr class="ai-settings_option">
									<td class="ai-settings_option-title-wrapper">
										<span class="ui-form-label ai-settings_option-title">
											<?= $item->getHeader()?>
										</span>
									</td>
									<td>
										<?php if ($item->isBoolean()):?>
											<label class="ui-ctl ui-ctl-checkbox ai-settings_option-input">
												<input
													type="hidden"
													name="items[<?= $item->getCode()?>]"
													value="0"
												/>
												<span class="ai__settings_checkbox">
													<input
														class="ui-ctl-element"
														type="checkbox"
														name="items[<?= $item->getCode()?>]"
														value="1"<?= $item->getValue() ? ' checked="checked"' : ''?>
													/>
												</span>
												<span class="ui-ctl-label-text">
													<?= $item->getTitle()?>
												</span>
											</label>
										<?php elseif ($item->isList()): ?>
										<div class="ai-settings_option-input-wrapper">
											<input
													type="hidden"

													value="0"
											/>
											<span class="ui-ctl-label-text">
												<?= $item->getTitle()?>
											</span>

											<div class="ai__settings_list ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
												<div class="ui-ctl-after ui-ctl-icon-angle"></div>
												<select
													class="ui-ctl-element"
													name="items[<?= $item->getCode()?>]"
												>
													<?php foreach ($item->getOptions() as $value => $name): ?>
														<option
																value="<?= $value ?>"
															<?= $item->getValue() === $value ? ' selected' : '' ?>
														>
															<?= $name ?>
														</option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>
										<?php endif?>
									</td>
								</tr>
								<?php endforeach?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<?php endif; ?>
		<?php endforeach?>
	</div>

	<?php $APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
		'BUTTONS' => [
			[
				'type' => 'custom',
				'layout' => '<button name="save" value="Y" class="ui-btn ui-btn-success ui-btn-round">'.Loc::getMessage('AI_CMP_SETTINGS_SAVE').'</button>'
			],
			'cancel'
		]
	]);?>
</form>

