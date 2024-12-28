<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Application;
use Bitrix\Main\Grid\Cell\Label\Size;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

global $APPLICATION;

$APPLICATION->SetTitle($arResult['TITLE']);

const COMPONENT_TYPE_SAFE = 'safe';
const COMPONENT_TYPE_CURRENT = 'current';

Extension::load([
	'ui.icons.disk',
	'ui.label',
	'ui.icon-set.actions',
	'ui.design-tokens',
]);

$getValidatedUrl = static function(string $validatedUrl, bool $needValidateOpenRedirect = true): string
{
	$urlParseResult = parse_url($validatedUrl);

	if (!$urlParseResult)
	{
		return "";
	}

	if (!isset($urlParseResult['host']) || !$needValidateOpenRedirect)
	{
		return htmlspecialcharsbx($validatedUrl);
	}

	$server = Application::getInstance()->getContext()->getServer();
	$portalHost = $server->getHttpHost();

	if ($portalHost !== $urlParseResult['host'])
	{
		return '';
	}

	return htmlspecialcharsbx($validatedUrl);
};

$getDataForLabels = static function(string $status, string $color, ?string $identifier = null)
{
	ob_start();
	?>
	<div class="ui-label ui-label-fill sign-grid-member-text-wrapper <?= htmlspecialcharsbx($color) ?> <?= htmlspecialcharsbx($identifier) ?>">
		<div class="ui-label-inner sign-grid-member-status-inner">
			<div class="sign-grid-member-status-text">
				<?= htmlspecialcharsbx($status) ?>
			</div>
		</div>
	</div>
	<?php
	return (string)ob_get_clean();
};

$getTextWithHoverTemplate = static function(string $text, string $hoverText): string
{
	ob_start();
	?>
	<span title="<?= htmlspecialcharsbx($hoverText) ?>">
		<?= htmlspecialcharsbx($text) ?>
	</span>
	<?php
	return (string)ob_get_clean();
};

$getText = static function(string $text): string
{
	ob_start();
	?>
	<div class="sign-grid-member-text-wrapper">
		<span>
			<?= htmlspecialcharsbx($text) ?>
		</span>
	</div>
	<?php
	return (string)ob_get_clean();
};

$getOpenSliderLinkTemplate = static function(string $templateText, ?string $link = null) use ($getValidatedUrl): string
{
	if ($link !== null)
	{
		$validatedLink = $getValidatedUrl($link);
		ob_start();
		?>
		<div class="sign-grid-document-title">
			<div class="sign-grid-document-title__icon_wrapper">
				<span
					class="ui-icon-set --file-2 sign-grid-document-title__icon"
					style="--ui-icon-set__icon-size: 30px;"
				></span>
			</div>
			<a class="sign-grid-document-title_text" target="_top"  href="<?= $validatedLink ?>">
				<?= htmlspecialcharsbx($templateText) ?>
			</a>
		</div>
		<?php
	}
	else
	{
		ob_start();
		?>
		<div class="sign-grid-document-title">
			<div class="sign-grid-document-title__icon_wrapper">
				<span
					class="ui-icon-set --file-2 sign-grid-document-title__icon"
					style="--ui-icon-set__icon-size: 30px;"
				></span>
			</div>
			<span class="sign-grid-document-title_text">
					<?= htmlspecialcharsbx($templateText) ?>
				</span>
		</div>
		<?php
	}

	return ob_get_clean();
};

$getUserInfoTemplate = static function (
	?string $fullName,
	?string $linkPath,
	?string $iconPath,
	?string $companyName = null
) use (
	$getValidatedUrl
): string {
	$userLinkPath = $getValidatedUrl((string)$linkPath);
	$userImagePath = $getValidatedUrl((string)$iconPath, false);
	$userFullName = htmlspecialcharsbx($fullName);

	ob_start();
	?>
	<div>
	<?php if ($companyName !== null): ?>
		<div class="sign-personal-grid-company">
			<?= htmlspecialcharsbx($companyName) ?>
		</div>
	<?php endif; ?>
	<a
		class="sign-personal-grid-user"
		target="_top"
		onclick="event.stopPropagation();"
		href="<?= $userLinkPath ?>">
		<span class="ui-icon ui-icon-common-user">
			<i style=" <?= ($iconPath !== null ? "background-image: url('". Uri::urnEncode($userImagePath) . "');" : '') ?>">
			</i>
		</span>
		<span class="sign-personal-grid-user-name">
				<?= $userFullName ?>
		</span>
	</a>
	</div>
	<?php
	return (string)ob_get_clean();
};

$getActionButton = static function (string $role, int $memberId): string {
	$buttonText = match ($role)
	{
		\Bitrix\Sign\Type\Member\Role::SIGNER,
		\Bitrix\Sign\Type\Member\Role::ASSIGNEE => Loc::getMessage('SIGN_DOCUMENT_GRID_COLUMN_ACTION_BUTTON_TEXT_ROLE_SIGNER'),
		\Bitrix\Sign\Type\Member\Role::EDITOR => Loc::getMessage('SIGN_DOCUMENT_GRID_COLUMN_ACTION_BUTTON_TEXT_ROLE_EDITOR'),
		\Bitrix\Sign\Type\Member\Role::REVIEWER => Loc::getMessage('SIGN_DOCUMENT_GRID_COLUMN_ACTION_BUTTON_TEXT_ROLE_REVIEWER'),
	};

	$button = new Button([
		'text' => $buttonText,
	]);

	$button->setColor(\Bitrix\UI\Buttons\Color::SUCCESS);
	$button->setSize(\Bitrix\UI\Buttons\Size::SMALL);
	$button->setStyles(['width' => '160px']);
	$button->addDataAttribute('member-id', $memberId);

	return $button->render();
};

$getDownloadResultFileTemplate = static function (
	?string $extensionName,
	?string $downloadUrl,
	?string $name = null,
	?string $entityId = null,
	?string $downloadUrlForPrinted = null
) use (
	$arParams
): string {

	ob_start();
	?>

	<?php
	$downloadUrlObject = new \Bitrix\Main\Web\Uri($downloadUrl);
	$downloadUrlForPrintedObject = new \Bitrix\Main\Web\Uri($downloadUrlForPrinted);

	$isValidDownloadUrl = str_starts_with($downloadUrlObject->getPath(), '/bitrix/services/main/ajax.php');
	$isValidDownloadUrlForPrinted = str_starts_with($downloadUrlForPrintedObject->getPath(), '/bitrix/services/main/ajax.php') ?? null;

	if (mb_strtolower($arParams['COMPONENT_TYPE']) === COMPONENT_TYPE_SAFE): ?>
			<?php if ($isValidDownloadUrl || $isValidDownloadUrlForPrinted): ?>
				<?php if ($downloadUrlForPrinted !== null): ?>
					<div class="ui-btn-split ui-btn-light-border" id="menu_<?= htmlspecialcharsbx($entityId) ?>">
						<a href="<?= htmlspecialcharsbx($downloadUrlObject) ?>" class="ui-btn-main sign-personal-download-btn">
							<?= Loc::getMessage('SIGN_DOCUMENT_GRID_COLUMN_DOWNLOAD_BUTTON') ?>
						</a>
						<button class="ui-btn-menu" id="menu_btn_<?= htmlspecialcharsbx($entityId) ?>"></button>
					</div>
				<?php else: ?>
					<button class="ui-btn ui-btn-light-border" id="menu_<?= htmlspecialcharsbx($entityId) ?>">
						<a href="<?= htmlspecialcharsbx($downloadUrlObject) ?>" class="sign-personal-download-btn">
							<?= Loc::getMessage('SIGN_DOCUMENT_GRID_COLUMN_DOWNLOAD_BUTTON') ?>
						</a>
					</button>
				<?php endif; ?>
			<?php endif; ?>
		<script>
			BX.ready(function() {
				const menuButton = BX("menu_btn_<?= CUtil::JSEscape($entityId) ?>");

				const menu = new BX.PopupMenuWindow({
					bindElement: menuButton,
					offsetLeft: -160,
					closeByEsc: true,
					className: 'sign-personal-download-dropdown-menu-btn',
					items: [
						<?php if ($downloadUrlForPrinted !== null && $isValidDownloadUrlForPrinted): ?>
							{
								text: "<?= CUtil::JSEscape(Loc::getMessage('SIGN_DOCUMENT_DOWNLOAD_PRINTED'))?>",
								href: "<?= CUtil::JSEscape($downloadUrlForPrintedObject)?>"
							},
						<? endif; ?>
						<?php if ($downloadUrl !== null && $isValidDownloadUrl): ?>
							{
								text: "<?= CUtil::JSEscape(Loc::getMessage('SIGN_DOCUMENT_DOWNLOAD_ARCHIVE'))?>",
								href: "<?= CUtil::JSEscape($downloadUrlObject)?>"
							},
						<? endif; ?>
					]
				});

				BX.bind(menuButton, 'click', (event) => {
					event.preventDefault();
					event.stopPropagation();
					menu.popupWindow.show();
				});

				BX.bind(document, 'click', () => {
					if (menu.popupWindow.isShown())
					{
						menu.popupWindow.close();
					}
				});

				BX.bind(document.querySelector('.main-grid-container'), 'scroll', () => {
					if (menu.popupWindow.isShown())
					{
						menu.popupWindow.close();
					}
				})
			});
		</script>
	<?php else: ?>
		<?php if ($isValidDownloadUrl): ?>
			<a href="<?= htmlspecialcharsbx($downloadUrlObject) ?>" class="ui-label ui-label-light sign-personal-download-result-file-wrapper">
				<div class="ui-label-inner sign-personal-download-result-file-inner">
					<div class="ui-icon ui-icon-file-<?= htmlspecialcharsbx($extensionName) ?> sign-personal-download-result-icon"><i></i></div>
					<div class="sign-personal-download-result-file-text">
						<?= htmlspecialcharsbx($name ?? $extensionName) ?>
					</div>
				</div>
			</a>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	return (string)ob_get_clean();
};

$prepareGridData = static function ($documentData) use (
	$getTextWithHoverTemplate,
	$getOpenSliderLinkTemplate,
	$getUserInfoTemplate,
	$getDownloadResultFileTemplate,
	$getActionButton,
	$getDataForLabels,
	$getText
)
{
	$data = [];

	$data['ID'] = htmlspecialcharsbx($documentData['ID']);

	if (isset($documentData['DATE_SIGN_INFO']))
	{
		$dateSignInfo = $documentData['DATE_SIGN_INFO'];
		$data['DATE_SIGN'] = $getTextWithHoverTemplate(
			(string)$dateSignInfo['TEXT'],
			(string)$dateSignInfo['DETAIL']
		);
	}

	if (isset($documentData['DATE_CREATE_INFO']))
	{
		$dateCreateInfo = $documentData['DATE_CREATE_INFO'];
		$data['DATE_CREATE'] = $getTextWithHoverTemplate(
			(string)$dateCreateInfo['TEXT'],
			(string)$dateCreateInfo['DETAIL']
		);
	}

	if (isset($documentData['TITLE_INFO']))
	{
		$titleInfo = $documentData['TITLE_INFO'];
		if (isset($titleInfo['DOCUMENT_LINK']))
		{
			$data['TITLE'] = $getOpenSliderLinkTemplate((string)$titleInfo['TEXT'], (string)$titleInfo['DOCUMENT_LINK']);
		}
		else
		{
			$data['TITLE'] = $getOpenSliderLinkTemplate((string)$titleInfo['TEXT']);
		}
	}

	if (isset($documentData['INITIATOR']))
	{
		$signWithInfo = $documentData['INITIATOR'];
		$data['INITIATOR'] = $getUserInfoTemplate(
			$signWithInfo['FULL_NAME'],
			$signWithInfo['LINK'],
			$signWithInfo['ICON'],
			$signWithInfo['COMPANY_NAME'],
		);
	}

	if (isset($documentData['MEMBER_INFO']))
	{
		$memberInfo = $documentData['MEMBER_INFO'];
		$data['MEMBER'] = $getUserInfoTemplate(
			$memberInfo['FULL_NAME'],
			$memberInfo['LINK'],
			$memberInfo['ICON']
		);
	}

	if (isset($documentData['CREATED_BY']))
	{
		$creatorUserInfo = $documentData['CREATED_BY'];
		$data['CREATED_BY'] = $getUserInfoTemplate(
			$creatorUserInfo['FULL_NAME'],
			$creatorUserInfo['LINK'],
			$creatorUserInfo['ICON']
		);
	}

	if (isset($documentData['RESULT_FILE_INFO']))
	{
		$downloadResultFileInfo = $documentData['RESULT_FILE_INFO'];
		$data['DOWNLOAD_DOCUMENT'] = !$downloadResultFileInfo['DOWNLOAD_URL']
			? ''
			: $getDownloadResultFileTemplate(
				extensionName: $downloadResultFileInfo['EXTENSION'],
				downloadUrl: $downloadResultFileInfo['DOWNLOAD_URL'],
				entityId: $data['ID'],
				downloadUrlForPrinted: $downloadResultFileInfo['DOWNLOAD_URL_PRINTED']
			);
	}

	if (isset($documentData['ACTION']))
	{
		$type = $documentData['ACTION']['TYPE'];
		$actionData = $documentData['ACTION']['DATA'];
		if ($type === 'link')
		{
			$data['ACTION'] = $getActionButton($actionData['role'], $actionData['memberId']);
		}
		elseif ($type === 'file')
		{
			$data['ACTION'] = $actionData === null
				? ''
				: $getDownloadResultFileTemplate(
					$actionData['EXTENSION'],
					$actionData['DOWNLOAD_URL'],
					Loc::getMessage('SIGN_DOCUMENT_GRID_COLUMN_ACTION_DOWNLOAD')
				);
		}
	}

	if (isset($documentData['MEMBER_STATUS']))
	{
		$memberStatus = $documentData['MEMBER_STATUS'];
		$data['MEMBER_STATUS'] = $getDataForLabels(
			(string)$memberStatus['TEXT'],
			(string)$memberStatus['COLOR'],
			(string)$memberStatus['IDENTIFIER'],
		);
	}

	if (isset($documentData['ROLE']))
	{
		$data['ROLE'] = $getText($documentData['ROLE']);
	}

	return $data;
};

$gridRows = [];
foreach ($arResult["DOCUMENTS"] as $documentData)
{
	$gridRows[] = [
		'data' => $prepareGridData($documentData),
	];
}

$stub = null;
if (!$arResult['USE_DEFAULT_STUB'] && count($gridRows) === 0)
{
	$stub = $arResult['STUB'] ?? null;
}


if (!empty($arResult['IS_SHOW_RESULT_STATUS_BUTTON']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:sign.document.counter.panel',
		'',
		[
			'TYPE' => $arResult['GRID_TYPE'],
			'ITEMS' => $arResult['COUNTER_ITEMS'],
			'TITLE' => Loc::getMessage('SIGN_DOCUMENT_COUNTER_ITEMS_TITLE_MSG_1'),
			'FILTER_ID' => $arResult['FILTER_ID']
		]
	);
}

if ($arResult['IS_SHOW_B2E_GRID_BANNER']):
$this->SetViewTarget('below_pagetitle', 0);
?>

<div class="sign-document-list__banner">
	<div class="sign-document-list__banner-icon"></div>
	<div class="sign-document-list__banner-content">
		<div class="sign-document-list__banner-title">
			<?= htmlspecialcharsbx(Loc::getMessage('SIGN_DOCUMENT_GRID_BANNER_TITLE'))?>
		</div>
		<div class="sign-document-list__banner-desc">
			<?php
			$placeholders = [
				'[helpdesklink]' => '<a href="javascript:top.BX.Helper.show(\'redirect=detail&code=20617048\');">',
				'[/helpdesklink]' => '</a>'
			];
			$replacedText = str_replace(
				array_keys($placeholders),
				array_values($placeholders),
				$arResult['BANNER_TEXT']
			);
			?>

			<?= nl2br($replacedText)?>
		</div>
	</div>
	<div class="ui-icon-set --cross-20 sign-document-list__banner-btn_close"></div>
</div>

<?php
$this->EndViewTarget();
endif;

if ($arResult['IS_SHOW_TOOLBAR_FILTER'])
{
	Toolbar::addFilter([
		'GRID_ID' => $arResult['GRID_ID'],
		'FILTER_ID' => $arResult['FILTER_ID'],
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'DISABLE_SEARCH' => true,
		'ENABLE_LIVE_SEARCH' => true,
		'ENABLE_LABEL' => true,
		'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
	]);
}

?>

<div class="sign-document-list-wrapper">
	<?php
		$APPLICATION->IncludeComponent('bitrix:main.ui.grid',
			"",
			[
				'GRID_ID' => $arResult['GRID_ID'],
				'COLUMNS' => $arResult['COLUMNS'],
				'ROWS' => $gridRows,
				'NAV_OBJECT' => $arResult['NAVIGATION_OBJECT'],
				'SHOW_ROW_CHECKBOXES' => false,
				'SHOW_TOTAL_COUNTER' => $arResult['SHOW_TOTAL_COUNTER'] ?? true,
				'TOTAL_ROWS_COUNT' => $arResult['TOTAL_COUNT'],
				'ALLOW_COLUMNS_SORT' => true,
				'ALLOW_SORT' => true,
				'ALLOW_COLUMNS_RESIZE' => true,
				'AJAX_MODE' => 'Y',
				'AJAX_OPTION_HISTORY' => 'N',
				'STUB' => $stub,
		]);
	?>
</div>

<script>
	BX.ready(function ()
	{
		const bannerNode = document.querySelector('.sign-document-list__banner');
		if (bannerNode)
		{
			const closeIconNode = bannerNode.querySelector('.sign-document-list__banner-btn_close');
			closeIconNode.addEventListener('click', () =>
			{
				BX.Dom.remove(bannerNode);
				BX.ajax.runComponentAction(
					'bitrix:sign.document.list',
					'setBannerOptionClose',
					{
						mode: 'class',
						data: {
							type: '<?= CUtil::JSEscape($arResult['GRID_TYPE']) ?>',
						},
					}
				)
			})
		}

		if (typeof BX.PULL !== 'undefined')
		{
			BX.PULL.subscribe({
				type: BX.PullClient.SubscriptionType.Server,
				moduleId: 'sign',
				command: 'memberStatusChanged',
				callback: async (params) =>
				{
					if (params.isMemberReadyStatus)
					{
						const grid = BX.Main.gridManager.getInstanceById('<?= CUtil::JSescape($arResult['GRID_ID']) ?>');
						if (grid)
						{
							grid.reloadTable();

							return;
						}
					}

					if (params.memberId)
					{
						const label = document.querySelector(`.${params.labelId}`);
						if (!label)
						{
							return;
						}

						const response = await BX.ajax.runAction('sign.api_v1.document.member.loadStage', {json: {memberId: params.memberId}});
						const data = response.data;

						label.classList.remove('ui-label-danger');
						label.classList.remove('ui-label-success');
						label.classList.remove('ui-label-light');
						label.classList.remove('ui-label-secondary');
						label.classList.remove('ui-label-default');
						label.classList.remove('ui-label-warning');

						label.classList.add(BX.Text.encode(data.color));
						const textNode = label.querySelector('.sign-grid-member-status-text');
						textNode.innerText = data.text;

						const buttonNode = document.querySelector(`[data-member-id="${params.memberId}"]`);
						if (buttonNode && BX.Dom.hasClass(buttonNode, 'ui-btn'))
						{
							buttonNode.style.display = 'none';
						}
					}
				}
			});

			BX.PULL.subscribe({
				moduleId: 'sign',
				command: 'changeB2eCurrentCounters',
				callback: function (params) {
					const grid = BX.Main.gridManager.getInstanceById('DOCUMENT_GRID_ID_CURRENT');
					if (grid)
					{
						grid.reloadTable();
					}
				}
			});
		}
	});
<?php if (isset($arResult['COLUMNS']['action'])): ?>
	BX.ready(function ()
	{
		const gridContainer = document.querySelector('#<?= CUtil::JSescape($arResult['GRID_ID']) ?>');
		if (!gridContainer)
		{
			return;
		}

		gridContainer.addEventListener('click', async (event) => {
			let target = event.target;
			if (BX.Dom.hasClass(target, 'ui-btn-text'))
			{
				target = target.parentNode;
			}

			if (
				!target.classList.contains('ui-btn')
				|| !target.dataset.memberId
			)
			{
				return;
			}

			BX.Dom.addClass(target, 'ui-btn-wait');

			const memberId = Number(target.dataset.memberId);
			BX.Runtime.loadExtension('sign.v2.b2e.sign-link')
				.then((exports) => {
					return new exports.SignLink({memberId}).openSlider({
						target,
						events: {
							onClose: function ()
							{
								BX.ajax.runAction('sign.api_v1.B2e.Document.Member.callStatus', {json: {memberId}})
									.then(() => {
										const grid = BX.Main.gridManager.getInstanceById('<?= CUtil::JSescape($arResult['GRID_ID']) ?>');
										if (grid)
										{
											grid.reloadTable();
										}
								});
							}
						},
					});
				})
				.finally(() => {
					BX.Dom.removeClass(target, 'ui-btn-wait');
				})
			;

			event.preventDefault();
		});

	});
<?php endif; ?>
</script>
