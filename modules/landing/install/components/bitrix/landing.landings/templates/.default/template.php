<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Page\Asset;
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__FILE__);

if ($arResult['ERRORS'])
{
	\showError(implode("\n", $arResult['ERRORS']));
}

if ($arResult['FATAL'])
{
	return;
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$folderId = $request->get($arParams['ACTION_FOLDER']);

// title
if (isset($arResult['SITES'][$arParams['SITE_ID']]))
{
	\Bitrix\Landing\Manager::setPageTitle(
		htmlspecialcharsbx($arResult['SITES'][$arParams['SITE_ID']]['TITLE'])
	);
}

\CJSCore::init(array(
	'landing_master', 'action_dialog', 'clipboard', 'sidepanel', 'ui.icons.disk'
));

// assets
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass.' ' : '') .
	'no-all-paddings no-background landing-tile landing-tile-pages'
);
Asset::getInstance()->addJS(
	'/bitrix/components/bitrix/landing.sites/templates/.default/script.js'
);
Asset::getInstance()->addCSS(
	'/bitrix/components/bitrix/landing.sites/templates/.default/style.css'
);

// get site selector
$siteSelector = '<input id="landing-site-selector-value" type="hidden" value="' . $arParams['SITE_ID'] . '_0" />';
$siteSelector .= '<ul id="landing-site-selector" style="display: none;" class="landing-site-selector-list">';
foreach ($arResult['TREE'] as $siteItem)
{
	$selected = false;
	$value = $siteItem['SITE_ID'];
	if ($siteItem['FOLDER_ID'])
	{
		$value .= '_' . $siteItem['FOLDER_ID'];
	}
	else if ($siteItem['SITE_ID'] == $arParams['SITE_ID'])
	{
		$selected = true;
	}
	$siteSelector .= '<li class="landing-site-selector-item' .
						($siteItem['DEPTH'] ? ' landing-site-selector-item-lower' : '') .
						($selected ? ' landing-site-selector-item-selected' : '') . '" 
						data-value="' . $value . '_' . ($siteItem['FOLDER_ID'] ? $siteItem['FOLDER_ID'] : 0) . '" 
						onclick="onClickSelectorItem(this);"
						>
							<span class="ui-icon ui-icon-file-folder">
								<i></i>
							</span>
							<span class="landing-site-selector-item-value">' .
								htmlspecialcharsbx($siteItem['TITLE']) .
							'</span>
					</li>';
}
$siteSelector .= '</ul>';
echo $siteSelector;

// prepare urls
$arParams['PAGE_URL_LANDING_ADD'] = str_replace('#landing_edit#', 0, $arParams['PAGE_URL_LANDING_EDIT']);
if ($folderId)
{
	$arParams['PAGE_URL_LANDING_ADD'] = new Uri(
		$arParams['PAGE_URL_LANDING_ADD']
	);
	$arParams['PAGE_URL_LANDING_ADD']->addParams(array(
		$arParams['ACTION_FOLDER'] => $folderId
	));
	$arParams['PAGE_URL_LANDING_ADD'] = $arParams['PAGE_URL_LANDING_ADD']->getUri();
}

$sliderConditions = [
	str_replace(
		array(
			'#landing_edit#', '?'
		),
		array(
			'(\d+)', '\?'
		),
		CUtil::jsEscape($arParams['PAGE_URL_LANDING_EDIT'])
	),
	str_replace(
		array(
			'#landing_edit#', '?'
		),
		array(
			'(\d+)', '\?'
		),
		CUtil::jsEscape($arParams['PAGE_URL_LANDING_ADD'])
	),
	str_replace(
		array(
			'#landing_edit#', '?'
		),
		array(
			'(\d+)', '\?'
		),
		CUtil::jsEscape($arParams['PAGE_URL_LANDING_DESIGN'])
	),
];

if ($arParams['TILE_MODE'] === 'view')
{
	$sliderConditions[] = str_replace(
		array(
			'#landing_edit#', '?'
		),
		array(
			'(\d+)', '\?'
		),
		CUtil::jsEscape($arParams['PAGE_URL_LANDING_VIEW'])
	);
}
?>

<div class="grid-tile-wrap landing-pages-wrap" id="grid-tile-wrap">
	<div class="grid-tile-inner" id="grid-tile-inner">

	<?if ($folderId):
		$curUrlWoFolder = new Uri($arResult['CUR_URI']);
		$curUrlWoFolder->deleteParams(array(
			$arParams['ACTION_FOLDER']
		));
		?>
		<div class="landing-item landing-item-add-new" style="display: <?=$arResult['IS_DELETED'] ? 'none' : 'block';?>;">
			<a class="landing-item-inner" href="<?= htmlspecialcharsbx($curUrlWoFolder->getUri());?>">
			<span class="landing-item-add-new-inner">
				<span class="landing-item-add-icon landing-item-add-icon-up"></span>
				<span class="landing-item-text"><?= Loc::getMessage('LANDING_TPL_ACTION_FOLDER_UP');?></span>
			</span>
			</a>
		</div>
	<?endif;?>

	<?if ($arResult['ACCESS_SITE']['EDIT'] == 'Y'):?>
	<div class="landing-item landing-item-add-new" style="display: <?=$arResult['IS_DELETED'] ? 'none' : 'block';?>;">
		<span class="landing-item-inner" data-href="<?= $arParams['PAGE_URL_LANDING_ADD']?>">
			<span class="landing-item-add-new-inner">
				<span class="landing-item-add-icon"></span>
				<span class="landing-item-text"><?= Loc::getMessage('LANDING_TPL_ACTION_ADD')?></span>
			</span>
		</span>
	</div>
	<?endif;?>

<?foreach (array_values($arResult['LANDINGS']) as $i => $item):

	$uriFolder = null;
	$areaCode = '';
	$areaTitle = '';
	$accessSite = $arResult['ACCESS_SITE'];
	$urlEdit = str_replace('#landing_edit#', $item['ID'], $arParams['~PAGE_URL_LANDING_EDIT']);
	$urlEditDesign = str_replace('#landing_edit#', $item['ID'], $arParams['~PAGE_URL_LANDING_DESIGN']);
	$urlView = str_replace('#landing_edit#', $item['ID'], $arParams['~PAGE_URL_LANDING_VIEW']);

	$uriCopy = new Uri($arResult['CUR_URI']);
	$uriCopy->addParams(array(
		'action' => 'copy',
		'param' => $item['ID'],
		'sessid' => bitrix_sessid()
	));

	$uriMove = new Uri($arResult['CUR_URI']);
	$uriMove->addParams(array(
		'action' => 'move',
		'param' => $item['ID'],
		'sessid' => bitrix_sessid()
	));

	if ($item['FOLDER'] === 'Y' && $item['ID'] !== $folderId)
	{
		$uriFolder = new Uri($arResult['CUR_URI']);
		$uriFolder->addParams(array(
			$arParams['ACTION_FOLDER'] => $item['ID']
		));
	}
	if ($arParams['DRAFT_MODE'] === 'Y' && $item['DELETED'] !== 'Y')
	{
		$item['ACTIVE'] = 'Y';
	}

	if ($item['IS_AREA'])
	{
		$areaCode = $item['AREA_CODE'];
		$areaTitle = Loc::getMessage('LANDING_TPL_AREA_'.mb_strtoupper($item['AREA_CODE']));
	}
	else if ($item['IS_HOMEPAGE'])
	{
		$areaCode = 'main_page';
		$areaTitle = Loc::getMessage('LANDING_TPL_AREA_MAIN_PAGE');
		if ($arParams['TYPE'] === 'GROUP')
		{
			$accessSite['DELETE'] = 'N';
		}
	}

	if (in_array($item['ID'], $arResult['DELETE_LOCKED']))
	{
		$accessSite['DELETE'] = 'N';
	}
	?>
	<?if ($uriFolder):?>
		<div class="landing-item landing-item-folder<?
			?><?= $item['ACTIVE'] != 'Y' || $item['DELETED'] != 'N' ? ' landing-item-unactive' : '';?><?
			?><?= $item['DELETED'] == 'Y' ? ' landing-item-deleted' : '';?>">
			<div class="landing-title">
				<div class="landing-title-wrap">
					<div class="landing-title-overflow"><?= htmlspecialcharsbx($item['TITLE']);?></div>
				</div>
			</div>
			<div class="landing-item-cover">
				<div class="landing-item-preview">
					<?foreach ($item['FOLDER_PREVIEW'] as $picture):?>
					<div class="landing-item-preview-item" style="background-image: url(<?= $picture;?>);"></div>
					<?endforeach;?>
				</div>
				<div class="landing-item-folder-corner">
					<div class="landing-item-folder-dropdown"
						 onclick="showTileMenu(this,{
									viewSite: '<?= htmlspecialcharsbx(CUtil::jsEscape($urlView));?>',
									ID: '<?= $item['ID']?>',
									publicUrl: '<?= htmlspecialcharsbx(CUtil::jsEscape($item['PUBLIC_URL'])) ?>',
									copyPage: '<?= htmlspecialcharsbx(CUtil::jsEscape($uriCopy->getUri())) ?>',
									deletePage: '#',
									editPage: '<?= htmlspecialcharsbx(CUtil::jsEscape($urlEdit)) ?>',
									editPageDesign: '<?= htmlspecialcharsbx(CUtil::jsEscape($urlEditDesign)) ?>',
							 		folderIndex: false,
							 		isFolder: <?= ($item['FOLDER'] === 'Y') ? 'true' : 'false' ?>,
							 		isActive: <?= ($item['ACTIVE'] === 'Y') ? 'true' : 'false' ?>,
							 		isDeleted: <?= ($item['DELETED'] === 'Y') ? 'true' : 'false' ?>,
							 		wasModified: <?= ($item['WAS_MODIFIED'] === 'Y') ? 'true' : 'false' ?>,
									isEditDisabled: <?= ($accessSite['EDIT'] !== 'Y') ? 'true' : 'false' ?>,
									isSettingsDisabled: <?= ($accessSite['SETTINGS'] !== 'Y') ? 'true' : 'false' ?>,
									isPublicationDisabled: <?= ($accessSite['PUBLICATION'] !== 'Y') ? 'true' : 'false' ?>,
									isDeleteDisabled: <?= ($accessSite['DELETE'] !== 'Y') ? 'true' : 'false' ?>
								})">
						<span class="landing-item-folder-dropdown-inner"></span>
					</div>
				</div>
			</div>
			<?if ($item['DELETED'] == 'Y'):?>
			<span class="landing-item-link"></span>
			<?else:?>
			<a href="<?= $uriFolder->getUri();?>" class="landing-item-link" target="_top"></a>
			<?endif;?>
		</div>
	<?else:?>
		<div class="landing-item<?php
			?><?= $item['ACTIVE'] !== 'Y' || $item['DELETED'] !== 'N' ? ' landing-item-unactive' : '' ?><?php
			?><?= $item['DELETED'] === 'Y' ? ' landing-item-deleted' : '' ?>">
			<div class="landing-item-inner">
				<div class="landing-title">
					<div class="landing-title-btn"
						 onclick="showTileMenu(this,{
									viewSite: '<?= htmlspecialcharsbx(CUtil::jsEscape($urlView)) ?>',
									ID: '<?= $item['ID'] ?>',
									isArea: <?= $item['IS_AREA'] ? 'true' : 'false' ?>,
							 		isMainPage: <?= $item['IS_HOMEPAGE'] ? 'true' : 'false' ?>,
									publicUrl: '<?= htmlspecialcharsbx(CUtil::jsEscape($item['PUBLIC_URL'])) ?>',
									copyPage: '<?= htmlspecialcharsbx(CUtil::jsEscape($uriCopy->getUri())) ?>',
									movePage: '<?= htmlspecialcharsbx(CUtil::jsEscape($uriMove->getUri())) ?>',
									deletePage: '#',
									editPage: '<?= htmlspecialcharsbx(CUtil::jsEscape($urlEdit)) ?>',
									editPageDesign: '<?= htmlspecialcharsbx(CUtil::jsEscape($urlEditDesign))?>',
						 			folderIndex: <?= ($item['FOLDER'] === 'Y') ? 'true' : 'false' ?>,
							 		isFolder: <?= ($item['FOLDER'] === 'Y') ? 'true' : 'false' ?>,
							 		isActive: <?= ($item['ACTIVE'] === 'Y') ? 'true' : 'false' ?>,
							 		isDeleted: <?= ($item['DELETED'] === 'Y') ? 'true' : 'false' ?>,
									wasModified: <?= ($item['WAS_MODIFIED'] === 'Y') ? 'true' : 'false' ?>,
									isEditDisabled: <?= ($accessSite['EDIT'] !== 'Y') ? 'true' : 'false' ?>,
									isSettingsDisabled: <?= ($accessSite['SETTINGS'] !== 'Y') ? 'true' : 'false' ?>,
									isPublicationDisabled: <?= ($accessSite['PUBLICATION'] !== 'Y') ? 'true' : 'false' ?>,
									isDeleteDisabled: <?= ($accessSite['DELETE'] !== 'Y') ? 'true' : 'false' ?>
								})">
						<span class="landing-title-btn-inner"><?= Loc::getMessage('LANDING_TPL_ACTIONS') ?></span>
					</div>
					<div class="landing-title-wrap">
						<div class="landing-title-overflow"><?= htmlspecialcharsbx($item['TITLE']) ?></div>
					</div>
				</div>
				<?if ($item['IS_HOMEPAGE']):?>
					<div class="landing-item-desc">
						<span class="landing-item-desc-text"><?= htmlspecialcharsbx($areaTitle) ?></span>
					</div>
				<?php endif;?>
				<div class="landing-item-cover<?= $item['IS_AREA'] ? ' landing-item-cover-area' : '' ?>"
					<?if ($item['PREVIEW'] && !$item['IS_AREA']) {?> style="background-image: url(<?=
					htmlspecialcharsbx($item['PREVIEW'])?>);"<?}?>>
					<?if ($item['IS_HOMEPAGE'] || $item['IS_AREA']):?>
					<div class="landing-item-area">
						<div class="landing-item-area-icon<?=' landing-item-area-icon-' . htmlspecialcharsbx($areaCode) ?>"></div>
						<?if ($item['IS_AREA']):?>
							<span class="landing-item-area-text"><?= htmlspecialcharsbx($areaTitle);?></span>
						<?php endif;?>
					</div>
					<?php endif;?>
				</div>
			</div>
			<?if ($item['DELETED'] == 'Y'):?>
				<span class="landing-item-link"></span>
			<?elseif ($arParams['TILE_MODE'] == 'view' && $item['PUBLIC_URL']):?>
				<a href="<?= htmlspecialcharsbx($item['PUBLIC_URL']);?>" class="landing-item-link" target="_top"></a>
			<?elseif ($urlView):?>
				<a href="<?= htmlspecialcharsbx($urlView);?>" class="landing-item-link" target="_top"></a>
			<?else:?>
				<span class="landing-item-link"></span>
			<?endif;?>
			<?if ($arParams['DRAFT_MODE'] != 'Y' || $item['DELETED'] == 'Y'):?>
			<div class="landing-item-status-block">
				<div class="landing-item-status-inner">
					<?if ($item['DELETED'] == 'Y'):?>
						<span class="landing-item-status landing-item-status-unpublished"><?= Loc::getMessage('LANDING_TPL_DELETED');?></span>
					<?elseif ($item['ACTIVE'] != 'Y'):?>
						<span class="landing-item-status landing-item-status-unpublished"><?= Loc::getMessage('LANDING_TPL_UNPUBLIC');?></span>
					<?else:?>
						<span class="landing-item-status landing-item-status-published"><?= Loc::getMessage('LANDING_TPL_PUBLIC');?></span>
					<?endif;?>
					<?if ($item['DELETED'] == 'Y'):?>
						<span class="landing-item-status landing-item-status-changed">
							<?= Loc::getMessage('LANDING_TPL_TTL_DELETE');?>:
							<?= $item['DATE_DELETED_DAYS'];?>
							<?= Loc::getMessage('LANDING_TPL_TTL_DELETE_D');?>
						</span>
					<?elseif ($item['DATE_MODIFY_UNIX'] > $item['DATE_PUBLIC_UNIX']):?>
						<span class="landing-item-status landing-item-status-changed">
							<?= Loc::getMessage('LANDING_TPL_MODIF');?>
						</span>
					<?endif;?>

				</div>
			</div>
			<?endif;?>
		</div>
	<?endif;?>
<?endforeach;?>

	</div>
</div>

<?if ($arResult['NAVIGATION']->getPageCount() > 1):?>
	<div class="<?= (defined('ADMIN_SECTION') && ADMIN_SECTION === true) ? '' : 'landing-navigation';?>">
		<?$APPLICATION->IncludeComponent(
			'bitrix:main.pagenavigation',
			'',//grid
			array(
				'NAV_OBJECT' => $arResult['NAVIGATION'],
				'SEF_MODE' => 'N',
				'BASE_LINK' => $arResult['CUR_URI']
			),
			false
		);?>
	</div>
<?endif;?>


<script type="text/javascript">
	BX.SidePanel.Instance.bindAnchors(
		top.BX.clone({
			rules: [
				{
					condition: <?= CUtil::phpToJSObject($sliderConditions);?>,
					stopParameters: [
						'action',
						'fields%5Bdelete%5D',
						'nav'
					],
					options: {
						allowChangeHistory: false,
						events: {
							onOpen: function(event)
							{
								if (BX.hasClass(BX('landing-create-element'), 'ui-btn-disabled'))
								{
									event.denyAction();
								}
							}
						}
					}
				}]
		})
    );

	BX.bind(document.querySelector('.landing-item-add-new span.landing-item-inner'), 'click', function(event) {
		BX.SidePanel.Instance.open(event.currentTarget.dataset.href, {
			allowChangeHistory: false
		});
	});

	var tileGrid;
	var isMenuShown = false;
	var menu;

	BX.ready(function ()
	{
		var wrapper = BX('grid-tile-wrap');
		var title_list = Array.prototype.slice.call(wrapper.getElementsByClassName('landing-item'));

		tileGrid = new BX.Landing.TileGrid({
			wrapper: wrapper,
			siteType: '<?= $arParams['TYPE'];?>',
			inner: BX('grid-tile-inner'),
			tiles: title_list,
			sizeSettings : {
				minWidth : 280,
				maxWidth: 330
			}
		});

		// disable some buttons for deleted
		var createFolderEl = BX('landing-create-folder');
		var createElement = BX('landing-create-element');

		<?if ($arResult['IS_DELETED']):?>
		if (createFolderEl)
		{
			BX.addClass(createFolderEl, 'ui-btn-disabled');
		}
		if (createElement)
		{
			BX.addClass(createElement, 'ui-btn-disabled');
		}
		<?else:?>
		if (createFolderEl)
		{
			BX.removeClass(createFolderEl, 'ui-btn-disabled');
		}
		if (createElement)
		{
			BX.removeClass(createElement, 'ui-btn-disabled');
		}
		<?endif;?>
	});

	if (typeof showTileMenu === 'undefined')
	{
		function copyPage(params, isMoving)
		{
			isMoving === !!isMoving;
			var url = isMoving ? params.movePage : params.copyPage;
			BX.Landing.UI.Tool.ActionDialog.getInstance()
				.show({
					title: isMoving
							? '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_MOVE_TITLE'));?>'
							:  '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_COPY_TITLE'));?>',
					content: BX('landing-site-selector')
				})
				.then(
					function() {
						url += '&additional[siteId]=';
						url += BX('landing-site-selector-value').value;
						<?if ($folderId):?>
						url += '&additional[folderId]=';
						url += <?= (int)$folderId;?>;
						<?endif;?>
						var loaderContainer = BX.create('div',{
							attrs:{className:'landing-filter-loading-container'}
						});
						document.body.appendChild(loaderContainer);
						var loader = new BX.Loader({size: 130, color: '#bfc3c8'});
						loader.show(loaderContainer);
						if (top.window !== window)
						{
							// we are in slider
							window.location.href = url;
						}
						else
						{
							top.window.location.href = url;
						}
					},
					function() {
						//
					}
				);

			var selectedItemPos = document.querySelector('.landing-site-selector-item-selected').getBoundingClientRect();

			if (!isSelectedItemVisible(selectedItemPos))
			{
				scrollToSelectedItem(selectedItemPos);
			}
		}

		function showTileMenu(node, params)
		{
			if (typeof showTileMenuCustom === 'function')
			{
				showTileMenuCustom(node, params);
				return;
			}

			var menuItems = [
				{
					text: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_VIEW'));?>',
					disabled: params.isDeleted || params.isEditDisabled,
					<?if ($arParams['TILE_MODE'] == 'view'):?>
					href: params.viewSite,
					<?else:?>
					onclick: function(e, item)
					{
						window.top.location.href = params.viewSite;
					}
					<?endif;?>
				},
				{
					text: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_COPYLINK'));?>',
					className: 'landing-popup-menu-item-icon',
					disabled: params.isArea || params.isDeleted,
					onclick: function(e, item)
					{
						if (BX.clipboard.isCopySupported())
						{
							BX.clipboard.copy(params.publicUrl);
						}
						var menuItem = item.layout.item;
						menuItem.classList.add('landing-link-copied');

						BX.bind(menuItem.childNodes[0], 'transitionend', function ()
						{
							setTimeout(function()
							{
								this.popupWindow.close();
								menuItem.classList.remove('landing-link-copied');
								menu.destroy();
								isMenuShown = false;
							}.bind(this),250);

						}.bind(this));
					}
				},
				{
					text: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_GOTO'));?>',
					className: 'landing-popup-menu-item-icon',
					href: params.publicUrl,
					target: '_blank',
					disabled: params.isArea || params.isDeleted || !params.isActive,
					onclick: function(event)
					{
						if (top.window !== window)
						{
							event.preventDefault();
							top.window.location.href = params.publicUrl;
						}
					}
				},
				{
					text: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_EDIT'));?>',
					href: params.editPage,
					disabled: params.isDeleted || params.isSettingsDisabled,
					onclick: function()
					{
						this.popupWindow.close();
					}
				},
				{
					text: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_EDIT_DESIGN'));?>',
					href: params.editPageDesign,
					disabled: params.isDeleted || params.isSettingsDisabled,
					onclick: function()
					{
						this.popupWindow.close();
					}
				},
				{
					text: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_COPY'));?>',
					disabled: params.isDeleted || (params.isFolder && <?= !$folderId ? 'true' : 'false';?>) || params.isEditDisabled,
					onclick: function(event)
					{
						event.preventDefault();
						copyPage(params);
						this.popupWindow.close();
					}
				},
				{
					text: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_MOVE'));?>',
					disabled: params.isDeleted || params.isFolder || params.isEditDisabled || params.isDeleteDisabled || params.isMainPage,
					onclick: function(event)
					{
						event.preventDefault();
						copyPage(params, true);
						this.popupWindow.close();
					}
				},
				<?if ($arParams['DRAFT_MODE'] != 'Y'):?>
				{
					text: params.wasModified && params.isActive
							? '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_PUBLIC_CHANGED'));?>'
							: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_PUBLIC'));?>',
					disabled: params.isDeleted || params.isPublicationDisabled || (!params.wasModified && params.isActive),
					onclick: function(event)
					{
						event.preventDefault();

						var successFunction = function()
						{
							tileGrid.action('Landing::publication',
								{
									lid: params.ID
								},
								null,
								'<?= CUtil::jsEscape($this->getComponent()->getName());?>'
							);
						};

						if (!params.isActive && <?= $arResult['AGREEMENT'] ? 'true' : 'false';?>)
						{
							landingAgreementPopup({
								success: successFunction
							});
							return;
						}
						else
						{
							successFunction();
							this.popupWindow.close();
						}
						menu.destroy();
					}
				},
				{
					text: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_UNPUBLIC'));?>',
					disabled: params.isDeleted || params.isPublicationDisabled || !params.isActive,
					onclick: function(event)
					{
						event.preventDefault();

						var successFunction = function()
						{
							tileGrid.action(
								'Landing::unpublic',
								{
									lid: params.ID
								},
								null,
								'<?= CUtil::jsEscape($this->getComponent()->getName());?>'
							);
						};

						successFunction();
						this.popupWindow.close();
						menu.destroy();
					}
				},
				<?endif;?>
				{
					text: params.isDeleted
						? (
							(params.isFolder && <?= !$folderId ? 'true' : 'false';?>)
							? '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_UNDELETE_FOLDER'));?>'
							: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_UNDELETE'));?>'
						)
						: (
							(params.isFolder && <?= !$folderId ? 'true' : 'false';?>)
							? '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_DELETE_FOLDER'));?>'
							: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_DELETE'));?>'
						),
					href: params.deletePage,
					disabled: params.folderIndex || params.isDeleteDisabled,
					onclick: function(event)
					{
						event.preventDefault();

						this.popupWindow.close();
						menu.destroy();

						if (params.isDeleted)
						{
							tileGrid.action(
								'Landing::markUndelete',
								{
									lid: params.ID
								}
							);
						}
						else
						{
							BX.Landing.UI.Tool.ActionDialog.getInstance()
								.show({
									content: '<?= CUtil::jsEscape(Loc::getMessage('LANDING_TPL_ACTION_REC_CONFIRM'));?>'
								})
								.then(
									function() {
										//BX.Landing.History.getInstance().removePageHistory(params.ID);
										tileGrid.action(
											'Landing::markDelete',
											{
												lid: params.ID
											}
										);
									},
									function() {

									}
								);
						}
					}
				}
			];

			if (!isMenuShown) {
				menu = new BX.PopupMenuWindow(
					'landing-popup-menu' + params.ID,
					node,
					menuItems,
					{
						autoHide : true,
						offsetTop: -2,
						offsetLeft: -55,
						className: 'landing-popup-menu',
						events: {
							onPopupClose: function onPopupClose() {
								menu.destroy();
								isMenuShown = false;
							},
						},
					}
				);
				menu.show();

				isMenuShown = true;
			}
			else
			{
				menu.destroy();
				isMenuShown = false;
			}
		}
	}

	if (window.location.hash === '#createPage')
	{
		window.location.hash = '';
		var addButton = document.querySelector('.landing-item-add-new .landing-item-inner');

		if (BX.type.isDomNode(addButton))
		{
			addButton.click();
		}
	}

	function isSelectedItemVisible(node)
	{
		var parentNodePos = document.querySelector('.landing-site-selector-list').getBoundingClientRect();
		return (
			node.bottom > parentNodePos.top &&
			node.top < parentNodePos.bottom &&
			node.right > parentNodePos.left &&
			node.left < parentNodePos.right
		);
	}

	function scrollToSelectedItem(node)
	{
		document.querySelector('.landing-site-selector-list').scrollTo(0, node.y);
	}

	function onClickSelectorItem(node)
	{
		var items = document.querySelectorAll('.landing-site-selector-item');
		items.forEach(function (item)
		{
			item.classList.remove('landing-site-selector-item-selected');
		}, this);

		BX('landing-site-selector-value').value = BX.data(node, 'value');
		node.classList.add('landing-site-selector-item-selected');
	}

</script>



<?php
if ($arResult['AGREEMENT'])
{
	include \Bitrix\Landing\Manager::getDocRoot() .
			'/bitrix/components/bitrix/landing.start/templates/.default/popups/agreement.php';
}
