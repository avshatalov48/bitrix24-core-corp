<?php
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Internals\FieldTable;
use Bitrix\Crm\WebForm;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if ($arParams['VIEW_TYPE'] !== 'frame' && $arResult['IS_EMBEDDING_AVAILABLE'])
{
	$this->addExternalCss($this->GetFolder() . '/mobile.css');
	echo WebForm\Script::getListContext($arResult['FORM'], [])['INLINE']['text'];
	return;
}

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

if (Loader::includeModule('calendar'))
{
	\Bitrix\Crm\Integration\Calendar::loadResourcebookingExtention();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

$this->addExternalCss('/bitrix/css/main/bootstrap.css');
$this->addExternalCss('/bitrix/css/main/font-awesome.css');
$this->addExternalJs($this->GetFolder() . '/form_checker.js');

if($arResult['FORM']['CSS_PATH'])
{
	$this->addExternalCss(htmlspecialcharsbx($arResult['FORM']['CSS_PATH']));
}

if($arResult['HAS_PHONE_FIELD'])
{
	$this->addExternalJs($this->GetFolder() . '/masked.js');
	$this->addExternalCss($this->GetFolder() . '/flag.css');
}

if(!$arResult['FORM']['BUTTON_CAPTION'] || !trim($arResult['FORM']['BUTTON_CAPTION']))
{
	$arResult['FORM']['BUTTON_CAPTION'] = Loc::getMessage('CRM_WEBFORM_FILL_BUTTON_DEFAULT');
}

$classesIconMap = array(
	'email' => 'fa-envelope-o',
	'phone' => 'fa-phone',
	'date' => 'fa-calendar',
	'datetime' => 'fa-calendar',
	'file' => 'fa-file-pdf-o',
	'list' => 'fa-level-down',
	'product' => 'fa-level-down',
);

if(isset($arResult['ERRORS']) && count($arResult['ERRORS']) > 0)
{
	foreach($arResult['ERRORS'] as $errorMessage)
	{
		echo "<br>" . $errorMessage;
	}
}

$frame = $this->createFrame('')->begin('');
?>

<div class="crm-webform-wrapper">
	<div id="crm-web-form-id-<?=htmlspecialcharsbx($arResult['FORM']['ID'])?>" class="container crm-webform-main-container">
		<div class="row">
			<div class="col-md-12 col-sm-12">
				<div class="crm-webform-block crm-webform-default">
					<?if($arResult['FORM']['CAPTION'] || $arResult['FORM']['DESCRIPTION_DISPLAY']):?>
					<div class="crm-webform-header-container">
						<?if($arResult['FORM']['CAPTION']):?>
						<h2 class="crm-webform-header"><?=htmlspecialcharsbx($arResult['FORM']['CAPTION'])?></h2>
						<?endif;?>
						<?if($arResult['FORM']['DESCRIPTION_DISPLAY']):?>
						<div><?=($arResult['FORM']['DESCRIPTION_DISPLAY'])?></div>
						<?endif;?>
					</div>
					<?endif;?>
					<div class="crm-webform-body">
						<form novalidate class="crm-webform-form-container" id="bxform" method="POST" enctype="multipart/form-data" action="<?=$APPLICATION->GetCurPageParam()?>">
							<?
							$isFirstFieldSection = $arResult['FIELDS'][0]['type'] == 'section';
							if(!$isFirstFieldSection):
							?>
							<fieldset class="crm-webform-fieldset">
							<?
							endif;

							$hasProduct = false;
							foreach($arResult['FIELDS'] as $fieldNum => $field)
							{
								$inputCaption = htmlspecialcharsbx($field['caption']);
								$inputValue = htmlspecialcharsbx($field['value']);
								$inputId = htmlspecialcharsbx($field['name']);
								$inputName = htmlspecialcharsbx($field['name']) . ($field['multiple'] ? '[]' : '');
								$inputPlaceholder = $field['placeholder'] ? htmlspecialcharsbx($field['placeholder']) : '';

								$fieldAttributeId = 'field_' . htmlspecialcharsbx($field['name']);
								$fieldClassHidden = $field['hidden'] ? 'crm-webform-hide' : '';
								$requiredClassName = ($field['required'] ? 'crm-webform-label-required-field' : '');

								if($field['type'] == 'page')
								{
									continue;
								}

								if($field['type'] == 'product')
								{
									$hasProduct = true;
									$field['multiple'] = false;
								}

								if(in_array($field['type'], array('br', 'hr')))
								{
									?>
									<div class="row">
										<div class="col-md-12 col-sm-12">
											<div class="crm-webform-fill-<?=$field['type']?>"></div>
										</div>
									</div><!--row-->
									<?
									continue;
								}

								if($field['type'] == 'section')
								{
									if($fieldNum !== 0)
									{
										echo '</fieldset>';
									}

									echo '<fieldset class="crm-webform-fieldset ' . $fieldClassHidden . '" id="' . $fieldAttributeId . '">' .
										'<div class="crm-webform-inner-sub-header-container">' .
										'<h2 class="crm-webform-inner-header">' . $inputCaption . '</h2>' .
									'</div>';
									continue;
								}
								?>
								<div class="row">
									<div class="col-md-12 col-sm-12 crm-webform-field-<?=$field['type']?> <?=$fieldClassHidden?>" id="<?=$fieldAttributeId?>">

										<?if($field['type'] != 'checkbox' || $field['multiple']):?>
											<div class="crm-webform-label-title-container">
												<div class="crm-webform-label-title">
													<label for="<?=$inputId?>" class="crm-webform-label <?=$requiredClassName?>"><?=$inputCaption?>:</label>
												</div>
											</div>
										<?endif;?>

										<?
										$input = '';
										$isCustomInput = false;
										$isMultipleInput = false;
										switch($field['type'])
										{
											case 'list':
											case 'product':
												$input = '<select class="crm-webform-input" '. ($field['multiple'] ? 'multiple' : '') .' name="' . $inputName . '" id="' . $inputId . '">';
												//if($field['type'] == 'product')
												{
													$input .= '<option value="">' . Loc::getMessage('CRM_WEBFORM_FILL_NOT_SELECTED') . '</option>';
												}

												$listItemCounter = 0;
												foreach($field['items'] as $item)
												{
													$selected = ($listItemCounter == 0 && $field['type'] == 'product' && count($field['items']) == 1);

													$input .= '<option value="' . htmlspecialcharsbx($item['value']) . '"'
														. ($selected ? 'selected' : '')
														. '>'
														. htmlspecialcharsbx($item['title'])
														. '</option>';

													$listItemCounter++;
												}
												$input .= '</select>';
												break;


											case 'checkbox':
											case 'radio':
												$i = 0;
												$input = '';
												$isCustomInput = true;

												if($field['type'] == 'checkbox' && !$field['multiple'])
												{
													$value = $field['value'] ? htmlspecialcharsbx($field['value']) : 'Y';
													$input .= '<label class="crm-webform-checkbox-container crm-webform-checkbox-products">';
													$input .= '<input class="crm-webform-checkbox crm-webform-input" type="' . $field['type'] . '" name="' . $inputName . '" id="' . $inputId . '" value="' . $value .'"> ';
													$input .= '<i></i><span class="crm-webform-checkbox-name ' . $requiredClassName . '">' .
														htmlspecialcharsbx($field['caption']) .
														'</span>';
													$input .= '</label>';
													break;
												}

												foreach($field['items'] as $item)
												{
													$inputItemId = $inputId . '_' . ($i++);

													$typeClass = $field['type'] == 'radio' ? 'crm-webform-input-radio' : 'crm-webform-input-checkbox';
													$input .= '<label class="crm-webform-checkbox-container crm-webform-checkbox-' . $field['type'] . '">';
													$input .= '<input class="crm-webform-checkbox ' . $typeClass . ' crm-webform-input" type="' . $field['type'] . '" name="' . $inputName . '" id="' . $inputItemId . '" value="' . htmlspecialcharsbx($item['value']) .'"> ';
													$input .= '<i ' . $classInput . '></i><span class="crm-webform-checkbox-name">' . htmlspecialcharsbx($item['title']) .'</span>';
													$input .= '</label>';
												}
												break;

											case 'date':
											case 'datetime':
												$isMultipleInput = true;
												//' . $field['type'] . '
												$inputClass = 'crm-webform-input crm-webform-input-' . $field['type'];
												$input = '<input class="' . $inputClass . ' crm-webform-input-desktop" onfocus="this.blur()" type="text" name="' . $inputName . '" id="' . $inputId . '" placeholder="' . $inputPlaceholder . '">';
												$mobileType = $field['type'] == "date" ? "date" : "datetime-local";
												$input .= '<input type="' . $mobileType . '" class="' . $inputClass . ' crm-webform-input-mobile" id="' . $inputId . '_mobile" placeholder="' . $inputPlaceholder . '">';

												break;

											case 'text':
												$isMultipleInput = true;
												$input = '<textarea class="crm-webform-input crm-webform-textarea" name="' . $inputName . '" id="' . $inputId . '" placeholder="' . $inputPlaceholder . '"></textarea>';
												break;

											case 'file':
												$isMultipleInput = true;
												$isCustomInput = true;
												$input .= '<label class="crm-webform-input-label crm-webform-file-upload">';
												$input .= '<span class="crm-webform-file-button">' . Loc::getMessage('CRM_WEBFORM_FILL_FILE_SELECT') . '</span>';
												$input .= '<mark class="crm-webform-file-text-field">';
												$input .= '<span>' . Loc::getMessage('CRM_WEBFORM_FILL_FILE_NOT_SELECTED') . '</span><span></span>';
												$input .= '</mark>';
												$inputAccept = in_array($inputName, ['CONTACT_PHOTO', 'COMPANY_LOGO'])
													? 'image/*'
													: '';
												$input .= '<input class="crm-webform-input"'
													. ' type="file"'
													. ($inputAccept ? " accept=\"$inputAccept\"" : '')
													. ' name="' . $inputName . '" id="' . $inputId . '">';
												$input .= '<b class="tooltip crm-webform-tooltip-bottom-right">' . Loc::getMessage('CRM_WEBFORM_FILL_ERROR_FIELD_EMPTY') . '</b>';
												$input .= '</label>';
												break;

											case 'phone':
												$isMultipleInput = true;
												if(in_array(LANGUAGE_ID, array('ru', 'ua')))
												{
													$input .= '<span class="crm-webform-input-phone-flag"></span>';
													$input .= '<input value="' . $inputValue . '" class="crm-webform-input crm-webform-input-phone crm-webform-input-phone-padding" autocomplete="off" type="tel" placeholder="' . $inputPlaceholder . '">';
													$input .= '<input type="hidden" name="' . $inputName . '" id="' . $inputId . '">';
												}
												else
												{
													$input .= '<input value="' . $inputValue . '" name="' . $inputName . '" id="' . $inputId . '" class="crm-webform-input crm-webform-input-phone" type="text" placeholder="' . $inputPlaceholder . '">';
												}
												break;

											case FieldTable::TYPE_ENUM_INT:
											case FieldTable::TYPE_ENUM_FLOAT:
												$inputStep = $field['type'] == FieldTable::TYPE_ENUM_FLOAT ? '0.00001' : '1';
												$input = '<input value="' . $inputValue . '" class="crm-webform-input" type="number" name="' . $inputName . '" id="' . $inputId . '" placeholder="' . $inputPlaceholder . '" ' . ($inputStep ? 'step="' . $inputStep . '"' : '') . '>';
												break;

											case 'resourcebooking':
												$input = '';
												$isCustomInput = true;
												if (\Bitrix\Main\Loader::includeModule('calendar'))
												{
													$input = '<div id="'.$inputId.'" class="crm-webform-resourcebooking-wrap-live"></div>';
												}
												break;

											default:
												$isMultipleInput = true;
												$pattern = null;
												$inputType = 'text';
												switch ($field['type'])
												{
													case FieldTable::TYPE_ENUM_INT:
														$inputType = 'number';
														break;
													case FieldTable::TYPE_ENUM_FLOAT:
														$inputType = 'number';
														break;
												}

												$input = '<input value="' . $inputValue . '" class="crm-webform-input" type="text" name="' . $inputName . '" id="' . $inputId . '" placeholder="' . $inputPlaceholder . '">';
										}
										?>


										<div class="crm-webform-group" id="field_<?=$inputId?>_CONT">
											<div class="crm-webform-label-content">
												<?if($isCustomInput):?>
													<?=$input?>
												<?else:?>
													<label class="crm-webform-input-label">
														<?if(!($field['multiple'] && in_array($field['type'], array('list', 'product'))) && isset($classesIconMap[$field['type']])):?>
															<i class="crm-webform-icon fa <?=($classesIconMap[$field['type']])?>"></i>
														<?endif;?>
														<?=$input?>
														<b class="tooltip crm-webform-tooltip-bottom-right"><?=Loc::getMessage('CRM_WEBFORM_FILL_ERROR_FIELD_EMPTY')?></b>
													</label>
												<?endif;?>
											</div>
										</div>


										<?if($field['multiple'] && $isMultipleInput):?>
											<script type="text/html" id="tmpl_<?=$inputId?>">
												<div class="crm-webform-label-content">
													<label class="crm-webform-input-label">
														<?=$input?>
													</label>
												</div>
											</script>

											<div class="crm-webform-add-input-container">
												<a href="javascript: BX.CrmWebForm.createFormField('<?=$inputId?>', 'tmpl_<?=$inputId?>');" class="crm-webform-add-input"><?=Loc::getMessage('CRM_WEBFORM_FILL_FIELD_ADD_OTHER')?> &#10010;</a>
											</div>
										<?endif?>

									</div>
								</div>

							<?
							}
							?>

							</fieldset>

							<?if($hasProduct):?>
								<div data-bx-webform-cart="mini" class="crm-webform-mini-cart-container">
									<div class="crm-webform-mini-cart-title-container">
										<h4 class="crm-webform-mini-cart-title"><?=Loc::getMessage('CRM_WEBFORM_FILL_PRODUCT_TITLE')?>:</h4>
									</div>
									<div class="crm-webform-mini-cart-inner">
										<div data-bx-webform-cart-items=""></div>
										<div class="crm-webform-mini-cart-services-container">
											<span class="crm-webform-mini-cart-services-name"><?=Loc::getMessage('CRM_WEBFORM_FILL_PRODUCT_SUMMARY')?>:</span>
											<span data-bx-webform-cart-total="" class="crm-webform-mini-cart-services-cost"></span>
										</div>
									</div>
								</div>
							<?endif;?>

							<fieldset class="crm-webform-fieldset-footer">
								<div class="row">

									<?if($arResult['USER_CONSENT']['IS_USED']):?>
									<div class="col-md-12 col-sm-12 crm-webform-field-checkbox">
										<div class="crm-webform-group crm-webform-agreement-modifier">
											<div class="crm-webform-label-content">
												<label id="licence_show_button" class="crm-webform-checkbox-container crm-webform-checkbox-products crm-webform-desktop-font-style">
													<span class="crm-webform-checkbox-icon-container">
														<input id="licence_accept" type="checkbox" value="Y" <?=($arResult['USER_CONSENT']['IS_CHECKED'] ? 'checked' : '')?> class="crm-webform-checkbox crm-webform-input">
														<i></i>
													</span>
														<a class="crm-webform-checkbox-name"><?=htmlspecialcharsbx($arResult['USER_CONSENT']['BUTTON_CAPTION'])?></a>
												</label>
											</div>
										</div>
									</div>
									<?endif;?>


									<?if($arResult['CAPTCHA']['USE']):?>
										<div class="col-md-12 col-sm-12">
											<div class="crm-webform-group crm-webform-captcha">
												<div id="recaptcha-error" class="crm-webform-captcha-error"></div>
												<div id="recaptcha-cont" class="g-recaptcha" data-sitekey="<?=htmlspecialcharsbx($arResult['CAPTCHA']['KEY'])?>"></div>
											</div>
										</div>
									<?endif;?>

									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group crm-webform-button-container">
											<button data-bx-webform-submit-btn="" id="SUBMIT_BUTTON" class="crm-webform-submit-button" type="submit">
												<?=htmlspecialcharsbx($arResult['FORM']['BUTTON_CAPTION'])?>
											</button>

											<?if($arResult['FORM']['IS_CALLBACK_FORM'] == 'Y'):?>
												<div class="crm-webform-callback-free"><?=Loc::getMessage('CRM_WEBFORM_FILL_CALLBACK_FREE')?></div>
											<?endif;?>
										</div>
									</div>
								</div>
							</fieldset>


						</form>
					</div>
				</div>
			</div>
		</div><!--row-->

		<?if($arResult['FORM']['COPYRIGHT_REMOVED'] != 'Y'):?>
		<div class="row">
			<div class="col-md-12 col-sm-12 crm-webform-bottom-logo-container">
				<a class="crm-webform-bottom-link" href="<?=$arResult['CUSTOMIZATION']['REF_LINK']?>" target="_blank">
					<span class="crm-webform-bottom-text"><?=Loc::getMessage('CRM_WEBFORM_FILL_COPYRIGHT_CHARGED_BY')?></span>
					<span class="crm-webform-bottom-logo-bx"><?=Loc::getMessage('CRM_WEBFORM_FILL_COPYRIGHT_BITRIX')?></span>
					<span class="crm-webform-bottom-logo-24">24</span>
					<?if(!in_array(LANGUAGE_ID, array('ru', 'ua', 'kz', 'by'))):?>
						<span class="crm-webform-bottom-text">, #1 Free CRM</span>
					<?endif;?>
				</a>
			</div>
		</div>
		<?endif;?>

	</div><!--container-->
</div>

<?if($hasProduct):?>
<div class="crm-webform-fixed-right-sidebar">
	<div data-bx-webform-cart="" class="crm-webform-cart-container">
		<div class="crm-webform-cart-title-container">
			<h4 class="crm-webform-cart-title"><?=Loc::getMessage('CRM_WEBFORM_FILL_PRODUCT_TITLE')?>:</h4>
		</div>
		<div class="crm-webform-cart-inner">
			<div data-bx-webform-cart-items="" class="crm-webform-cart-inner-box"></div>
			<div class="crm-webform-cart-goods-total-price-container">
				<span class="crm-webform-cart-goods-total-price-name"><?=Loc::getMessage('CRM_WEBFORM_FILL_PRODUCT_SUMMARY')?>:</span>
				<span data-bx-webform-cart-total="" class="crm-webform-cart-goods-total-price-cost"></span>
			</div><!--crm-webform-cart-goods-total-price-container-->
		</div>
		<!--
		<div class="crm-webform-cart-button-container">
			<button data-bx-webform-submit-btn="" id="summary_product_submit" class="crm-webform-submit-button" type="submit">
				<?=htmlspecialcharsbx($arResult['FORM']['BUTTON_CAPTION'])?>
			</button>
		</div>
		-->
	</div><!--crm-webform-cart-container-->
</div><!--crm-webform-fixed-right-sidebar-->
<?endif;?>

<script type="text/html" id="product_price_item">
	<div class="crm-webform-cart-services-container">
		<span class="crm-webform-cart-services-name">%name%</span>
		<span class="crm-webform-cart-services-cost">%price%</span>
	</div>
</script>
<script type="text/html" id="product_price_mini_item">
	<div class="crm-webform-mini-cart-services-container">
		<span class="crm-webform-mini-cart-services-name">%name%</span>
		<span class="crm-webform-mini-cart-services-cost">%price%</span>
	</div>
</script>

<script type="text/html" id="tmpl_result_message">
	<div class="crm-webform-popup-mask" id="RESULT_MESSAGE_CONTAINER">
		<div class="crm-webform-popup-container">
			<div class="crm-webform-popup-content" id="RESULT_MESSAGE_CONTENT_LOADER">
				<div class="crm-webform-popup-text crm-webform-popup-content crm-webform-popup-content-loader"></div>
			</div>
			<div class="crm-webform-popup-content" id="RESULT_MESSAGE_CONTENT">
				<div class="crm-webform-popup-warning"><?=Loc::getMessage('CRM_WEBFORM_FILL_ERROR_TITLE')?></div>
				<div class="crm-webform-popup-text">%text%</div>
				<div class="crm-webform-popup-licence">
					<textarea disabled class="crm-webform-popup-licence-text"><?=htmlspecialcharsbx($arResult['USER_CONSENT']['IS_USED'] ? $arResult['USER_CONSENT']['TEXT'] : '')?></textarea>
				</div>
			</div>
			<div style="display: none;">
				<div class="crm-webform-popup-button" id="RESULT_BUTTON_CONTAINER">
					<button id="RESULT_BUTTON_BNT" class="crm-webform-submit-button"><?=Loc::getMessage('CRM_WEBFORM_FILL_FILL_AGAIN')?></button>
				</div>
			</div>
			<div class="crm-webform-popup-button" id="RESULT_BUTTON_LICENCE_CONTAINER">
				<button id="RESULT_BUTTON_BNT_ACCEPT" class="crm-webform-submit-button" href="#"><?=Loc::getMessage('CRM_WEBFORM_FILL_LICENCE_ACCEPT')?></button>
				<button id="RESULT_BUTTON_BNT_CANCEL" class="crm-webform-submit-button crm-webform-submit-button-cancel" href="#"><?=Loc::getMessage('CRM_WEBFORM_FILL_LICENCE_DECLINE')?></button>
			</div>
			<div class="crm-webform-popup-button" id="RESULT_BUTTON_REDIRECT_CONTAINER">
				<?=Loc::getMessage('CRM_WEBFORM_FILL_REDIRECT_DESC')?>
				<br>
				<span id="RESULT_BUTTON_REDIRECT_COUNTER"></span> <?=Loc::getMessage('CRM_WEBFORM_FILL_REDIRECT_SECONDS')?>
				<br>
				<br>
				<button id="RESULT_BUTTON_REDIRECT_BNT" class="crm-webform-submit-button" href="#"><?=Loc::getMessage('CRM_WEBFORM_FILL_REDIRECT_GO_NOW')?></button>
			</div>
		</div>
	</div>
</script>

<script>
	BX.ready(function(){
		BX.CrmWebForm = new CrmWebForm(<?=CUtil::PhpToJSObject(
			array(
				'id' => $arResult['FORM']['ID'],
				'currency' => $arResult['CURRENCY'],
				'lang' => Context::getCurrent()->getLanguage(),
				'date_format' => '',
				'form' => 'bxform',
				'isCallBackForm' => $arResult['FORM']['IS_CALLBACK_FORM'] == 'Y',
				'useReCaptcha' => $arResult['CAPTCHA']['USE'],
				'linkReCaptcha' => $arResult['CAPTCHA']['JS_LINK'],
				'showOnlyFirstError' => false,
				'canRemoveCopyright' => $arResult['CAN_REMOVE_COPYRIGHT'],
				'phoneFormatDataUrl' => $this->GetFolder() . '/base',
				'phoneCountryCode' => $arResult['PHONE_COUNTRY_CODE'],
				'postAjax' => ($arParams['AJAX_POST'] == 'Y'),
				'fields' => $arResult['FIELDS'],
				'guestLoader' => $arResult['TRACKING_GUEST_LOADER'],
				'tracking' => array(
					'ga' => ($arResult['FORM']['GOOGLE_ANALYTICS_ID'] ? $arResult['FORM']['GOOGLE_ANALYTICS_ID'] : ''),
					'gaPageView' => true,//(bool) $arResult['FORM']['GOOGLE_ANALYTICS_PV'],
					'gaPageViewEvent' => $arResult['GOOGLE_ANALYTICS_PAGE_VIEW'],
					'ya' => ($arResult['FORM']['YANDEX_METRIC_ID'] ? $arResult['FORM']['YANDEX_METRIC_ID'] : ''),
					'data' => $arResult['EXTERNAL_ANALYTICS_DATA'],
				),
				'mess' => array(
					'sentSuccess' => Loc::getMessage('CRM_WEBFORM_FILL_RESULT_SENT'),
					'sentError' => Loc::getMessage('CRM_WEBFORM_FILL_RESULT_ERROR'),
					'licencePre' => Loc::getMessage('CRM_WEBFORM_FILL_LICENCE_PROMPT1')
				)
			)
		)?>);
	});
</script>

<?
if($arResult['FORM']['GOOGLE_ANALYTICS_ID']):
	$gAnalyticsId = HtmlFilter::encode($arResult['FORM']['GOOGLE_ANALYTICS_ID']);
?>
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', '<?=CUtil::JSEscape($gAnalyticsId)?>', 'auto');
		ga('send', 'pageview');

	</script>
<?endif;?>
<?
if($arResult['FORM']['YANDEX_METRIC_ID']):
	$yaMetricId = (int) $arResult['FORM']['YANDEX_METRIC_ID'];
?>
	<!-- Yandex.Metrika counter -->
	<script type="text/javascript">
		(function (d, w, c) {
			(w[c] = w[c] || []).push(function() {
				try {
					w.yaCounter<?=$yaMetricId?> = new Ya.Metrika({
						id:'<?=$yaMetricId?>',
						clickmap:true,
						trackLinks:true,
						accurateTrackBounce:true,
						webvisor:true,
						trackHash:true
					});
				} catch(e) { }
			});

			var n = d.getElementsByTagName("script")[0],
				s = d.createElement("script"),
				f = function () { n.parentNode.insertBefore(s, n); };
			s.type = "text/javascript";
			s.async = true;
			s.src = "https://mc.yandex.ru/metrika/watch.js";

			if (w.opera == "[object Opera]") {
				d.addEventListener("DOMContentLoaded", f, false);
			} else { f(); }
		})(document, window, "yandex_metrika_callbacks");
	</script>
	<noscript><div><img src="https://mc.yandex.ru/watch/<?=$yaMetricId?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
	<!-- /Yandex.Metrika counter -->
<?endif;?>

<?
$frame->end();