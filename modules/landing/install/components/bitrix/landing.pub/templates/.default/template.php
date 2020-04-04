<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Landing\Config;
use Bitrix\Landing\Hook;
use \Bitrix\Landing\Manager;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Landing\Assets;

Loc::loadMessages(__FILE__);

$this->setFrameMode(true);

Manager::setPageTitle(
	Loc::getMessage('LANDING_TPL_TITLE')
);

if ($arResult['ERRORS'])
{
	\showError(implode("\n", $arResult['ERRORS']));
	return;
}

$arResult['LANDING']->view([
	'check_permissions' => false
]);

$enableHook = Manager::checkFeature(
	Manager::FEATURE_ENABLE_ALL_HOOKS,
	['hook' => 'copyright']
);
if ($enableHook)
{
	$hooksSite = Hook::getForSite($arResult['LANDING']->getSiteId());
}

// set meta og:image
$metaOG = Manager::getPageView('MetaOG');
if (strpos($metaOG, '"og:image"') === false)
{
	Manager::setPageView('MetaOG',
		'<meta property="og:image" content="' . $arResult['LANDING']->getPreview() . '" />'
	);
}

$assets = Assets\Manager::getInstance();
$assets->addAsset(
		'landing_public',
		Assets\Location::LOCATION_AFTER_TEMPLATE
);
$assets->addAsset(
	Config::get('js_core_public'),
	Assets\Location::LOCATION_KERNEL
);
$assets->addAsset('landing_critical_grid', Assets\Location::LOCATION_BEFORE_ALL);
?>

<?ob_start(); ?>
<?if (!$enableHook || isset($hooksSite['COPYRIGHT']) && $hooksSite['COPYRIGHT']->enabled()):?>
<div class="bitrix-footer">
	<?if (Manager::isB24()):?>
		<span class="bitrix-footer-text">
			<?
			$zone = Manager::getZone();
			$fullCopy = in_array($zone, array('ru', 'by'))
						? Loc::getMessage('LANDING_TPL_COPY_FULL')
						: Loc::getMessage('LANDING_TPL_COPY_FULL2');
			$logo = '<img src="' .
						$this->getFolder() . '/images/' .
						(in_array($zone, array('ru', 'ua', 'en')) ? $zone : 'en') .
						'.svg?1" alt="' . Loc::getMessage('LANDING_TPL_COPY_NAME') . '">';
			if ($fullCopy)
			{
				echo str_replace(
					[
						'#LOGO#',
						'<linklogo>', '</linklogo>',
						'<linksite>', '</linksite>',
						'<linkcrm>', '</linkcrm>',
						'<linkcreate>', '</linkcreate>'
					],
					[
						$logo,
						'<a target="_blank" href="' . $this->getComponent()->getRefLink('bitrix24_logo') . '">', '</a>',
						'<a class="bitrix-footer-link" target="_blank" href="' . $this->getComponent()->getRefLink('websites') . '">', '</a>',
						'<a class="bitrix-footer-link" target="_blank" href="' . $this->getComponent()->getRefLink('crm') . '">', '</a>',
						'<a class="bitrix-footer-link" target="_blank" href="' . $this->getComponent()->getRefLink('create', false) . '">', '</a>'
					],
					$fullCopy
				);
			}
			else
			{
				echo Loc::getMessage('LANDING_TPL_COPY_NAME_0') . ' ';
				echo $logo;
				echo ' &mdash; ';
				echo Loc::getMessage('LANDING_TPL_COPY_REVIEW');
			}
			?>
		</span>
		<?if (!$fullCopy):?>
		<a class="bitrix-footer-link" target="_blank" href="<?= $this->getComponent()->getRefLink('create', false);?>">
			<?= Loc::getMessage('LANDING_TPL_COPY_LINK');?>
		</a>
		<?endif;?>
	<?else:?>
		<span class="bitrix-footer-text"><?= Loc::getMessage('LANDING_TPL_COPY_NAME_SMN_0');?></span>
		<a href="https://www.1c-bitrix.ru/?<?= $arResult['ADV_CODE'];?>" target="_blank" class="bitrix-footer-link"><?= Loc::getMessage('LANDING_TPL_COPY_NAME_SMN_1');?></a>
	<?endif;?>
</div>
<?endif;?>
<?
$footer = ob_get_contents();
ob_end_clean();
Manager::setPageView('BeforeBodyClose', $footer);
?>