<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var CMain $APPLICATION
 */

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	return;
}

Loader::includeModule('ui');
Extension::load([
	'ui.layout-form',
]);

Toolbar::deleteFavoriteStar();
$APPLICATION->setTitle($arResult['TITLE']);

?>

<div class="dashboard-url-param-container">
	<div class="ui-form">
		<div class="ui-form-row ui-form-row-line biconnector-url-list-header ui-ctl-w100">
			<?php
			foreach ($arResult['COLUMNS'] as $value)
			{
				?>
				<div class="ui-form-label ui-ctl-w33">
					<div class="biconnector-url-list-text">
						<div class="ui-ctl-label-text"><?=$value['title']?></div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
		foreach ($arResult['SECTIONS'] as $section)
		{
			?>
			<div class="ui-form-row ui-form-row-line biconnector-url-list-scope-name ui-ctl-w100">
				<div class="ui-form-label ui-ctl-w100">
					<div class="biconnector-url-list-text">
						<div class="ui-ctl-label-text"><?=mb_strtoupper($section['title'])?></div>
					</div>
				</div>
			</div>
			<?php
			foreach ($section['rows'] as $row)
			{
				?>
				<div class="ui-form-row ui-form-row-line ui-ctl-w100">
					<?php
					foreach ($row as $columnValue)
					{
						?>
						<div class="ui-form-label ui-ctl-w33">
							<div class="biconnector-url-list-text">
								<div class="ui-ctl-label-text"><?=$columnValue?></div>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
		}
		?>
	</div>
</div>

