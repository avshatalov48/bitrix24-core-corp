<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blank\Block;
use Bitrix\Sign\Document;
use Bitrix\Sign\Error;

/** @var array $arResult */
/** @var Document $document */
/** @var \SignEditorComponent $component */

$document = $arResult['DOCUMENT'];
$layout = $arResult['LAYOUT'];
$blank = $document ? $document->getBlank() : null;
$documentBlocks = $arResult['DOCUMENT_BLOCKS'];
$crmEntityFields = $arResult['CRM_ENTITY_FIELDS'];
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass . ' ' : '') . 'sign-editor__body'
);

if (!$document || !$blank || !Error::getInstance()->isEmpty())
{
	$component->includeError('ui.error');
	return;
}

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'sign.document',
	'sign.error',
	'sign.documentmasterloader',
	'ui.hint',
	'ui.icons',
]);

$blankDocumentCount = $blank->getDocumentCount();
$isBlankInEditState = $blankDocumentCount <= 1;
?>

<div class="sign-editor__wrapper sign-editor__scope">
	<div class="sign-editor__header" data-role="document-header">
		<div class="sign-editor__header-title" data-role="document-title">
			<?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_TITLE')?>
		</div>
		<button id="sign-editor-help-btn" class="ui-icon ui-icon-common-question ui-icon-xs">
			<i></i>
		</button>
		<div class="sign-editor__header-buttons">
			<?php if (!$isBlankInEditState):?>
			<form method="post" action="<?php echo POST_FORM_ACTION_URI?>" class="sign-editor__inline-block">
				<?php echo bitrix_sessid_post()?>
				<input type="hidden" name="actionName" value="copyBlank" />
				<button id="sign-editor-btn-edit" type="submit" class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-round" data-role="sign-editor__btm-edit">
					<?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_BUTTON_EDIT')?>
				</button>
			</form>
			<?php endif?>
			<span class="ui-btn ui-btn-sm ui-btn-success ui-btn-round sign-editor-save" data-role="sign-editor__btm-save">
				<?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_BUTTON_SAVE')?>
			</span>
		</div>
	</div>
	<div class="sign-editor__container">
		<div class="sign-editor__content" data-role="sign-editor__content">
			<div class="sign-editor__content-document" data-role="document-container">
				<div class="sign-editor__content-document--wrapper" data-type="document-layout">
					<div class="sign-editor-content-document-error"></div>
					<?php foreach ($layout as $page):?>
						<div class="sign-editor__content-document--page" data-page="<?php echo $page['page']?>">
							<img class="sign-editor__content-document--file" data-role="layout" src="<?php echo \htmlspecialcharsbx($page['path'])?>" alt="<?php echo \htmlspecialcharsbx($page['name'])?>"/>
							<?/*<div class="sign-editor__content-document--page-info">
								<div class="sign-editor__content-document--page-info-value --id"><?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_DOC_ID')?> 4320Ц4WWOЦ80JK-5500</div>
								<div class="sign-editor__content-document--page-info-value --nav">страница 1 из 1</div>
							</div>*/?>
						</div>
					<?php endforeach?>
				</div>
			</div>
		</div>
		<div class="sign-editor__bar" data-role="sidebar">
			<div id="sign-editor-bar-content">
			<?php if ($arResult['SHOW_EDITOR_DEMO']):?>
			<div class="sign-editor__bar-demo" data-role="sign-editor__bar-demo">
				<div class="sign-editor__bar-demo-close" data-role="sign-editor__bar-demo-close"><i></i></div>
				<div class="sign-editor__bar-demo-header">
					<div class="sign-editor__bar-demo-header-title"><?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_DEMO_TITLE')?></div>
					<div class="sign-editor__bar-demo-header-sub-title"><?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_DEMO_TEXT')?></div>
				</div>
				<div class="sign-editor__bar-demo-video-wrapper">
					<video class="sign-editor__bar-demo-video" poster="/bitrix/components/bitrix/sign.editor/templates/.default/res/sign-editor-move.jpg" autoplay="true" loop="true" muted="true" playsinline="true">
						<source type="video/webm" src="/bitrix/components/bitrix/sign.editor/templates/.default/res/sign-editor-move.webm">
						<source type="video/mp4" src="/bitrix/components/bitrix/sign.editor/templates/.default/res/sign-editor-move.mp4">
					</video>
				</div>
			</div>
			<?php endif?>
			<div id="sign-editor-content-blocks-container" class="sign-editor__content-blocks--container" data-role="sign-editor__content-blocks--container">
				<?php foreach (\Bitrix\Sign\Blank\Block::getSections() as $section):?>
					<div class="sign-editor__content-blocks">
						<div class="sign-editor__content-block--header" data-role="repository-header">
							<?php echo $section['title']?>
						</div>
						<?php foreach ($arResult['BLOCKS'] as $block):
							/** @var \Bitrix\Sign\Blank\Block $block */
							if ($block->getSection() !== $section['code'])
							{
								continue;
							}
							?>
							<div class="sign-editor__content-block--item" data-type="repository-block" data-part="<?php echo $block->isThirdParty() ? 2 : 1?>" data-code="<?php echo $block->getCode()?>">
								<div class="sign-editor__content-block--item-name">
									<span class="sign-editor__content-block--item-name-value">
										<span class="sign-editor__content-block--item-name-value-text" data-role="hint"><?php echo $block->getTitle()?></span>
										<?php if ($hint = $block->getHint()):?>
											<div class="sign-editor__content-block--item-name-hint" data-hint="<?php echo $hint?>"></div>
										<?php endif ?>
									</span>
								</div>
								<div class="sign-editor__content-block--item-block-btn">
									<span class="sign-editor__content-blocks--btn-dashed" data-action="add">
										<?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_BLOCKS_ACTION_ADD')?>
									</span>
								</div>
							</div>
						<?php endforeach?>
					</div>
				<?php endforeach?>
			</div>
			</div>
		</div>
	</div>
</div>

<?php /*
<div class="sign-editor__content-wrapper">
	<div class="sign-editor__content-document">
		<div class="sign-editor__content-document--wrapper" data-type="document-layout">
			<div class="sign-editor-content-document-error"></div>
			<?php foreach ($layout as $page):?>
			<div class="sign-editor__content-document--page" data-page="<?php echo $page['page']?>">
				<img class="sign-editor__content-document--file" onload="signImageLoaded();" src="<?php echo \htmlspecialcharsbx($page['path'])?>" alt="<?php echo \htmlspecialcharsbx($page['name'])?>"/>
			</div>
			<?php endforeach?>
		</div>
	</div>
	<div class="sign-editor__content-blocks--wrapper">
		<div class="sign-editor__content-blocks--container">
		<?php foreach ([1, 2] as $code):?>
		<div class="sign-editor__content-blocks">
			<div class="sign-editor__content-block--header">
				<?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_BLOCKS_HEADER_' . $code)?>
			</div>
			<?php foreach ($arResult['BLOCKS_' . $code] as $block):?>
			<div class="sign-editor__content-block--item" data-type="repository-block" data-part="<?php echo $code?>" data-code="<?php echo $block->getCode()?>">
				<div class="sign-editor__content-block--item-name">
					<?php echo $block->getTitle()?>
				</div>
				<div class="sign-editor__content-block--item-block-btn">
					<span class="sign-editor__content-blocks--btn-dashed" data-action="add">
						<?php echo Loc::getMessage('SIGN_CMP_EDITOR_TPL_BLOCKS_ACTION_ADD')?>
					</span>
				</div>
			</div>
			<?php endforeach?>
		</div>
		<?php endforeach?>
		</div>
	</div>
</div>
*/?>
<?php if ($blankDocumentCount > 1):?>
<style>
	.sign-editor__container {
		opacity: 0.4;
		pointer-events: none;
	}
</style>
<?php endif?>

<script>
	let images = document.querySelectorAll('[data-role="layout"]');
	let imagesTotal = <?php echo count($layout)?>;
	let imagesLoad = 0;
	let documentReady = false;

	for (let i = 0; i < images.length; i++)
	{
		images[i].addEventListener('load', function() {
			if (!documentReady)
			{
				imagesLoad++
			}
		});
	}

	BX.ready(function() {

		//hint init
		BX.UI.Hint.init(document.querySelector('[data-role="sign-editor__content-blocks--container"]'));

		// close demo
		let nodeCross = document.querySelector('[data-role="sign-editor__bar-demo-close"]');
		let nodeDemo = document.querySelector('[data-role="sign-editor__bar-demo"]');

		if (nodeCross && nodeDemo)
		{
			nodeCross.addEventListener('click', function() {
				nodeDemo.classList.add('--hide');
				nodeDemo.addEventListener('transitionend', function() {
					nodeDemo.parentNode.removeChild(nodeDemo);
				});
			});
		}

		// set span wrapper for hint
		let nodeTitles = document.querySelectorAll('[data-role="hint"]');

		for (let i = 0; i < nodeTitles.length; i++)
		{
			let title = nodeTitles[i].innerText;
			let hint = nodeTitles[i].nextElementSibling;
			let arrTitle = title.split(' ');
			let lastWord = BX.create('span', {
				text: arrTitle[arrTitle.length - 1]
			});
			lastWord.appendChild(hint);
			arrTitle.splice(arrTitle.length - 1);
			title = arrTitle.join(' ') + ' ';
			nodeTitles[i].innerHTML = title;
			nodeTitles[i].appendChild(lastWord);
		}

		// close demo end
		documentReady = true;
		let masterLoader = new BX.Sign.DocumentMasterLoader({
			totalImages: imagesTotal
		});
		masterLoader.show();

		window.addEventListener('load', function() {
			masterLoader.close();
			BX.onCustomEvent(window, 'BX.Sign:signImageLoaded');
		});

		let pagesNode = [];

		for (let i = 0; i < images.length; i++)
		{
			images[i].addEventListener('load', function() {
				signImageLoaded(this);
			});
			images[i].addEventListener('error', function() {
				signImageLoadedFail(this);
			});
		}

		function signImageLoaded(pageNode)
		{
			if (pageNode)
			{
				pagesNode.push(pageNode);
			}

			imagesLoad++;
			masterLoader.updateLoadPage(imagesLoad);
			if (imagesLoad === imagesTotal)
			{
				setTimeout(function() {
					for (let i = 0; i < pagesNode.length; i++)
					{
						pagesNode[i].style.width = pagesNode[i].offsetWidth + 'px';
						pagesNode[i].style.height = pagesNode[i].offsetHeight + 'px';
					}
				});

				BX.onCustomEvent(window, 'BX.Sign:signImageLoaded');
				masterLoader.close();
			}
		}

		function signImageLoadedFail()
		{
			masterLoader.showError();
		}

		const buttonEdit = document.querySelector('[data-role="sign-editor__btm-edit"]');
		const buttonSave = document.querySelector('[data-role="sign-editor__btm-save"]');

		if (buttonEdit)
		{
			buttonEdit.addEventListener('click', function() {
				buttonEdit.classList.add('ui-btn-wait');
				if (buttonSave)
				{
					buttonSave.classList.add('ui-btn-disabled');
				}
			});
		}

		if (buttonSave)
		{
			buttonSave.addEventListener('click', function() {
				buttonSave.classList.add('ui-btn-wait');
				if (buttonEdit)
				{
					buttonEdit.classList.add('ui-btn-disabled');
				}
			});
		}

		function signLoadDocument()
		{
			new BX.Sign.Document({
				documentId: <?php echo $document->getId()?>,
				entityId: <?php echo $document->getEntityId()?>,
				blankId: <?php echo $document->getBlankId()?>,
				companyId: <?php echo $document->getCompanyId()?>,
				initiatorName: "<?php echo htmlspecialcharsbx($arResult['INITIATOR_NAME'])?>",
				disableEdit: <?php echo !$isBlankInEditState ? 'true' : 'false'?>,
				members: <?php echo \CUtil::phpToJSObject($document->getMembers(true), false, false, true)?>,
				repositoryItems: document.querySelectorAll('[data-type="repository-block"]'),
				documentLayout: document.querySelector('[data-type="document-layout"]'),
				saveButton: document.querySelector('.sign-editor-save'),
				closeDemoContent: document.querySelector('[data-role="sign-editor__bar-demo-close"]'),
				blocks: <?php echo \CUtil::phpToJSObject($documentBlocks, false, false, true)?>,
				config: {
					crmEntityFields: <?php echo \CUtil::phpToJSObject($crmEntityFields, false, false, true)?>,
					crmRequisiteContactPresetId: <?=
						isset($arResult['CRM_ENTITY_REQ_CONTACT_PRESET_ID'])
							? (int)$arResult['CRM_ENTITY_REQ_CONTACT_PRESET_ID']
							: 'null' ?>,
					crmRequisiteCompanyPresetId: <?=
						isset($arResult['CRM_ENTITY_REQ_COMPANY_PRESET_ID'])
							? (int)$arResult['CRM_ENTITY_REQ_COMPANY_PRESET_ID']
							: 'null' ?>,
					crmOwnerTypeContact: <?php echo $arResult['CRM_OWNER_TYPE_CONTACT']?>,
					crmOwnerTypeCompany: <?php echo $arResult['CRM_OWNER_TYPE_COMPANY']?>,
					crmNumeratorUrl: '<?php echo \CUtil::jsEscape($arResult['CRM_NUMERATOR_URL'])?>',
				},
				afterSaveCallback: function()
				{
					BX.SidePanel.Instance.close();
				},
				saveErrorCallback: function()
				{
					const buttonEdit = document.querySelector('[data-role="sign-editor__btm-edit"]');
					const buttonSave = document.querySelector('[data-role="sign-editor__btm-save"]');

					if (buttonSave)
					{
						buttonSave.classList.remove('ui-btn-wait');
						buttonSave.classList.remove('ui-btn-disabled');
					}

					if (buttonEdit)
					{
						buttonEdit.classList.remove('ui-btn-wait');
						buttonEdit.classList.remove('ui-btn-disabled');
					}
				}
			});
		}

		BX.addCustomEvent(window, 'BX.Sign:signImageLoaded', signLoadDocument);
	});

</script>
