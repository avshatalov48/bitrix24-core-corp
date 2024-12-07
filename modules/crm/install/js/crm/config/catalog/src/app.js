import LocMixin from './loc';
import Reservation from './reservation';
import { Type, Loc, ajax, Tag, Extension, Event, Dom, Runtime, userOptions as UserOptions } from 'main.core';
import { Popup } from 'main.popup';
import { Button, ButtonColor } from 'ui.buttons';
import { StoreSlider } from 'catalog.store-use'
import { Vue } from 'ui.vue';
import ProductUpdater from './product-updater/template';
import { Const } from './const';
import 'ui.notification';
import 'ui.design-tokens';
import 'ui.alerts';
import '../css/app.css';

const HELP_ARTICLE_ID = 15_706_692;
const HELP_COST_CALCULATION_MODE_ARTICLE_ID = 17_858_278;

export default Vue.extend({
	components: {
		reservation: Reservation,
	},
	mixins: [LocMixin],
	props: {
		initData: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			/**
			 * State
			 */
			isSaving: false,
			isChanged: false,
			currentReservationEntityCode: null,
			/**
			 *
			 */
			isStoreControlUsed: null,
			isStoreBatchUsed: false,
			productsCnt: null,
			initCostPriceCalculationMethod: null,
			costPriceCalculationMethod: null,
			isEmptyCostPriceCalculationMethod: true,
			isHiddenCostPriceCalculationMethodChangeWarning: true,
			/**
			 * Reservation settings
			 */
			reservationEntities: [],
			/**
			 * Default products settings
			 */
			initDefaultQuantityTrace: null,
			initDefaultCanBuyZero: null,
			initDefaultSubscribe: null,
			initCheckRightsOnDecreaseStoreAmount: null,
			defaultQuantityTrace: null,
			defaultCanBuyZero: null,
			defaultSubscribe: null,
			checkRightsOnDecreaseStoreAmount: null,
			/**
			 * Product card
			 */
			productCardSliderEnabled: null,
			isCanEnableProductCardSlider: false,
			isBitrix24: false,
			busProductCardHelpLink: '',
			defaultProductVatIncluded: null,
			defaultProductVatId: null,
			vats: [],
		};
	},
	computed: {
		hasAccessToReservationSettings()
		{
			if (this.initData.hasAccessToReservationSettings !== undefined)
			{
				return this.initData.hasAccessToReservationSettings === true;
			}

			return true;
		},
		hasAccessToCatalogSettings()
		{
			if (this.initData.hasAccessToCatalogSettings !== undefined)
			{
				return this.initData.hasAccessToCatalogSettings === true;
			}

			return true;
		},
		isCanChangeOptionCanByZero()
		{
			return Extension.getSettings('crm.config.catalog')?.isCanChangeOptionCanByZero === true;
		},
		costPriceCalculationMethods(): []
		{
			return Extension.getSettings('crm.config.catalog')?.costPriceCalculationMethods ?? [];
		},
		showNegativeStoreAmountPopup(): boolean
		{
			return Extension.getSettings('crm.config.catalog')?.showNegativeStoreAmountPopup === true;
		},
		storeBalancePopupLink(): string
		{
			return Extension.getSettings('crm.config.catalog')?.storeBalancePopupLink;
		},
		shouldShowBatchMethodSpotlight(): boolean
		{
			return (
				Extension.getSettings('crm.config.catalog')?.shouldShowBatchMethodSpotlight === true
				&& this.isEmptyCostPriceCalculationMethod
			);
		},
		isReservationUsed()
		{
			return (
				this.isStoreControlUsed
				|| this.isReservationUsageViaQuantityTrace
			);
		},
		isCanBuyZeroInDocsVisible()
		{
			return this.isStoreControlUsed && this.isEmptyCostPriceCalculationMethod;
		},
		isDefaultQuantityTraceVisible()
		{
			return this.isReservationUsageViaQuantityTrace;
		},
		isReservationUsageViaQuantityTrace()
		{
			return (
				!this.isStoreControlUsed
				&& this.initDefaultQuantityTrace
			);
		},
		hasProductSettingsChanged()
		{
			return !(
				this.initDefaultQuantityTrace === this.defaultQuantityTrace
				&& this.initDefaultCanBuyZero === this.defaultCanBuyZero
				&& this.initDefaultSubscribe === this.defaultSubscribe
				&& this.initCheckRightsOnDecreaseStoreAmount === this.checkRightsOnDecreaseStoreAmount
				&& this.initCostPriceCalculationMethod === this.costPriceCalculationMethod
			);
		},
		needProgressBarOnProductsUpdating()
		{
			return this.productsCnt > 500;
		},
		saveButtonClasses()
		{
			return {
				'ui-btn': true,
				'ui-btn-success': true,
				'ui-btn-wait': this.isSaving,
			};
		},
		buttonsPanelClass()
		{
			return {
				'ui-button-panel-wrapper': true,
				'ui-pinner': true,
				'ui-pinner-bottom': true,
				'ui-pinner-full-width': true,
				'ui-button-panel-wrapper-hide': !this.isChanged,
			};
		},
		description()
		{
			return this.isStoreControlUsed
				? Loc.getMessage('CRM_CFG_C_SETTINGS_STORE_CONTROL_ACTIVE')
				: Loc.getMessage('CRM_CFG_C_SETTINGS_STORE_CONTROL_NOT_ACTIVE');
		},
	},
	watch: {
		defaultQuantityTrace(newVal, oldVal)
		{
			const showWarn = this.isDefaultQuantityTraceVisible && (newVal === false && oldVal === true);
			if (!showWarn)
			{
				return;
			}

			const warnPopup = new Popup(null, null, {
				events: {
					onPopupClose: () => warnPopup.destroy(),
				},
				content: Tag.render`
					<div class="catalog-settings-popup-content">
						<h3>
							${Loc.getMessage('CRM_CFG_C_SETTINGS_TURN_OFF_QUANTITY_TRACE_TITLE')}
						</h3>
						<div class="catalog-settings-popup-text">
							${Loc.getMessage('CRM_CFG_C_SETTINGS_TURN_OFF_QUANTITY_TRACE_TEXT')}
						</div>
					</div>
				`,
				maxWidth: 500,
				overlay: true,
				buttons: [
					new Button({
						text: Loc.getMessage('CRM_CFG_C_SETTINGS_CLOSE'),
						color: Button.Color.PRIMARY,
						onclick: () => warnPopup.close(),
					}),
				],
			});
			warnPopup.show();
		},
	},
	created()
	{
		this.initialize(this.initData);
		this.productUpdaterPopup = null;
		this.settingsMenu = null;

		let sliderUrl = Const.url;
		if (this.configCatalogSource)
		{
			sliderUrl += `?configCatalogSource=${this.configCatalogSource}`;
		}
		this.slider = BX.SidePanel.Instance.getSlider(sliderUrl);
	},
	methods: {
		markAsChanged()
		{
			this.isChanged = true;
		},
		onEnableProductCardCheckboxClick()
		{
			if (!this.productCardSliderEnabled)
			{
				this.askToEnableProductCardSlider();
			}

			this.markAsChanged();
		},
		askToEnableProductCardSlider()
		{
			const askPopup =				this.isBitrix24
				? this.createWarningProductCardPopupForBitrix24()
				: this.createWarningProductCardPopupForBUS();

			askPopup.show();
		},
		createWarningProductCardPopupForBitrix24()
		{
			const askPopup = this.createWarningProductCardPopup(
				Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_TEXT'),
				[
					new Button({
						text: Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_DISAGREE'),
						color: Button.Color.PRIMARY,
						onclick: () => {
							this.productCardSliderEnabled = false;
							askPopup.close();
						},
					}),
					new Button({
						text: Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_AGREE'),
						onclick: () => askPopup.close(),
					}),
				],
				{
					onPopupShow: () => {
						const helpdeskLink = document.getElementById('catalog-settings-new-productcard-popup-helpdesk');
						if (helpdeskLink)
						{
							Event.bind(helpdeskLink, 'click', () => top.BX.Helper.show('redirect=detail&code=11657084'));
						}
					},
				},
			);

			return askPopup;
		},
		createWarningProductCardPopupForBUS()
		{
			const askPopup = this.createWarningProductCardPopup(
				Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_BUS_TEXT').replace('#HELP_LINK#', this.busProductCardHelpLink),
				[
					new Button({
						text: Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_AGREE'),
						color: Button.Color.SUCCESS,
						onclick: () => askPopup.close(),
					}),
					new Button({
						text: Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_BUS_DISAGREE'),
						color: Button.Color.LINK,
						onclick: () => {
							this.productCardSliderEnabled = false;
							askPopup.close();
						},
					}),
				],
			);

			return askPopup;
		},
		createWarningProductCardPopup(contentText: string, buttons: Array, events = {})
		{
			const popupParams = {
				events: {
					onPopupClose: () => askPopup.destroy(),
					...events,
				},
				content: Tag.render`
					<div class="catalog-settings-new-productcard-popup-content">
						${contentText}
					</div>
				`,
				className: 'catalog-settings-new-productcard-popup',
				titleBar: Loc.getMessage('CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD_ASK_TITLE'),
				maxWidth: 800,
				overlay: true,
				buttons: buttons,
			};

			const askPopup = new Popup(null, null, popupParams);

			return askPopup;
		},
		openStoreControlMaster()
		{
			let sliderUrl = '/bitrix/components/bitrix/catalog.warehouse.master.clear/slider.php';
			if (this.configCatalogSource)
			{
				sliderUrl += `?inventoryManagementSource=${this.configCatalogSource}`;
			}
			new StoreSlider().open(
				sliderUrl,
				{},
			)
				.then((slider) => {
					ajax.runAction('catalog.config.isUsedInventoryManagement', {})
						.then((response) => {
							if (this.isStoreControlUsed !== response.data)
							{
								if (response.data === true)
								{
									this.close();
								}
								else
								{
									this.refresh();
								}
							}

							if (slider?.getData().get('isPresetApplied'))
							{
								this.showMessage(Loc.getMessage('CRM_CFG_C_SETTINGS_SAVED_SUCCESSFULLY'));
							}
						});
				});
		},
		refresh()
		{
			return new Promise((resolve, reject) => {
				ajax.runComponentAction('bitrix:crm.config.catalog.settings', 'initialize', {
					mode: 'class',
					json: {},
				}).then((response) => {
					this.initialize(response.data);
					resolve();
				}).catch((response) => {
					this.showResponseErrors(response);
					reject();
				});
			});
		},
		wait(ms)
		{
			return new Promise((resolve, reject) => {
				setTimeout(() => {
					resolve();
				}, ms);
			});
		},
		showResponseErrors(response)
		{
			this.showMessage(
				response.errors.map((error) => error.message).join(', '),
			);
		},
		showMessage(message)
		{
			top.BX.loadExt('ui.notification').then(() => {
				top.BX.UI.Notification.Center.notify({ content: message });
			});
		},
		initialize(data)
		{
			this.isStoreControlUsed = data.isStoreControlUsed;
			this.isStoreBatchUsed = data.isStoreControlUsed && data.isStoreBatchUsed;
			this.productsCnt = data.productsCnt;

			/**
			 * Reservation settings
			 */
			this.reservationEntities = data.reservationEntities;
			if (this.reservationEntities.length > 0)
			{
				this.currentReservationEntityCode = this.reservationEntities[0].code;
			}

			/**
			 * Product settings
			 */
			this.initDefaultQuantityTrace = this.defaultQuantityTrace = data.defaultQuantityTrace;
			this.initDefaultCanBuyZero = this.defaultCanBuyZero = data.defaultCanBuyZero;
			this.initDefaultSubscribe = this.defaultSubscribe = data.defaultSubscribe;
			this.initCheckRightsOnDecreaseStoreAmount = this.checkRightsOnDecreaseStoreAmount = data.checkRightsOnDecreaseStoreAmount;
			this.initCostPriceCalculationMethod = this.costPriceCalculationMethod = data.costPriceCalculationMethod;

			/**
			 * Other settings
			 */
			this.defaultProductVatIncluded = data.defaultProductVatIncluded;
			this.vats = data.vats;
			this.defaultProductVatId = data.defaultProductVatId;
			this.productCardSliderEnabled = data.productCardSliderEnabled;
			this.costPriceCalculationMethod = data.costPriceCalculationMethod;
			this.isEmptyCostPriceCalculationMethod = !Type.isStringFilled(this.costPriceCalculationMethod);
			this.isCanEnableProductCardSlider = data.isCanEnableProductCardSlider;
			this.isBitrix24 = data.isBitrix24;
			this.busProductCardHelpLink = data.busProductCardHelpLink;
			this.configCatalogSource = this.configCatalogSource ?? data.configCatalogSource;

			this.isChanged = false;
		},
		onReservationSettingsValuesChanged(values, index)
		{
			this.reservationEntities[index].settings.values = values;
			this.markAsChanged();
		},
		save()
		{
			if (this.isSaving)
			{
				return;
			}

			if (Type.isStringFilled(this.costPriceCalculationMethod) && this.showNegativeStoreAmountPopup)
			{
				const text = Loc.getMessage(
					'CRM_CFG_C_SETTINGS_NEGATIVE_STORE_BALANCE_POPUP_TEXT_MSGVER_1',
					{
						'#STORE_BALANCE_LIST_LINK#': '<help-link></help-link>',
					},
				);

				const content = Tag.render`
					<div class="catalog-settings-popup-content">
						<div class="catalog-settings-popup-text">
							${text}
						</div>
					</div>
				`;

				if (!Type.isUndefined(top.BX.SidePanel.Instance) && Type.isStringFilled(this.storeBalancePopupLink))
				{
					const balanceInfoLink = Tag.render`
						<a href="#" class="ui-form-link">
							${Loc.getMessage('CRM_CFG_C_SETTINGS_NEGATIVE_STORE_BALANCE_POPUP_LINK')}
						</a>
					`;

					Event.bind(balanceInfoLink, 'click', () => {
						top.BX.SidePanel.Instance.open(
							`${this.storeBalancePopupLink}`,
							{
								requestMethod: 'post',
								cacheable: false,
							},
						);
					});

					Dom.replace(content.querySelector('help-link'), balanceInfoLink);
				}

				const popup = new Popup({
					id: 'catalog_settings_document_negative_balance_popup',
					content,
					buttons: [
						new Button({
							text: Loc.getMessage('CRM_CFG_C_SETTINGS_RETURN'),
							color: ButtonColor.DANGER,
							onclick: (button, event) => {
								popup.destroy();
							},
						}),
					],
				});
				popup.show();

				return;
			}

			this.isSaving = true;

			this.saveProductSettings().then(() => {
				ajax.runComponentAction('bitrix:crm.config.catalog.settings', 'save', {
					mode: 'class',
					json: {
						values: {
							reservationSettings: this.makeReservationSettings(),
							productCardSliderEnabled: this.productCardSliderEnabled,
							defaultProductVatIncluded: this.defaultProductVatIncluded,
							defaultProductVatId: this.defaultProductVatId,
							checkRightsOnDecreaseStoreAmount: this.checkRightsOnDecreaseStoreAmount,
							costPriceCalculationMethod: this.costPriceCalculationMethod,
						},
					},
				}).then((response) => {
					this.isChanged = false;
					this.isSaving = false;
					this.showMessage(Loc.getMessage('CRM_CFG_C_SETTINGS_SAVED_SUCCESSFULLY'));
					this.refresh()
						.then(() => this.wait(700))
						.then(() => this.close());

					BX.SidePanel.Instance.postMessage(window, 'BX.Crm.Config.Catalog:onAfterSaveSettings');
				}).catch((response) => {
					this.isChanged = false;
					this.isSaving = false;
					this.showResponseErrors(response);
				});
			});
		},
		saveProductSettings()
		{
			if (!this.hasProductSettingsChanged)
			{
				return Promise.resolve();
			}

			const productUpdaterOptions = {
				propsData: {
					settings: {
						default_quantity_trace: this.defaultQuantityTrace ? 'Y' : 'N',
						default_can_buy_zero: this.defaultCanBuyZero ? 'Y' : 'N',
						default_subscribe: this.defaultSubscribe ? 'Y' : 'N',
					},
				},
			};

			return new Promise((resolve) => {
				const productUpdater = (new ProductUpdater(productUpdaterOptions))
					.$on('complete', () => {
						resolve();
						if (this.needProgressBarOnProductsUpdating)
						{
							this.productUpdaterPopup.destroy();
						}
					})
					.$mount();

				if (this.needProgressBarOnProductsUpdating)
				{
					this.productUpdaterPopup = new Popup({
						content: productUpdater.$el,
						width: 310,
						overlay: true,
						padding: 17,
						animation: 'fading-slide',
						angle: false,
					});
					this.productUpdaterPopup.show();
				}
			});
		},
		makeReservationSettings()
		{
			const result = {};

			for (const reservationEntity of this.reservationEntities)
			{
				result[reservationEntity.code] = reservationEntity.settings.values;
			}

			return result;
		},
		cancel()
		{
			this.close();
		},
		close()
		{
			this.slider.close();
		},
		getReservationSettingsHint()
		{
			return this.getHintContent(
				'CRM_CFG_C_SETTINGS_RESERVATION_SETTINGS_HINT_MSGVER_1',
				HELP_ARTICLE_ID,
				'reservation',
			);
		},
		getProductsSettingsHint()
		{
			return this.getHintContent(
				'CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_HINT_MSGVER_1',
				HELP_ARTICLE_ID,
				'products',
			);
		},
		getCostPriceCalculationHint()
		{
			return this.getHintContent(
				'CRM_CFG_C_SETTINGS_COST_PRICE_CALCULATION_MODE_HINT_MSGVER_1',
				HELP_COST_CALCULATION_MODE_ARTICLE_ID,
			);
		},
		getCanBuyZeroHint()
		{
			return this.getHintContent(
				'CRM_CFG_C_SETTINGS_CAN_BUY_ZERO_HINT_MSGVER_1',
				HELP_ARTICLE_ID,
				'products',
			);
		},
		getDefaultVatHint()
		{
			return this.getHintContent(
				'CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_DEFAULT_VAT_HINT_MSGVER_1',
				HELP_ARTICLE_ID,
				'products',
			);
		},
		getCanBuyZeroInDocsHint()
		{
			return this.getHintContent(
				'CRM_CFG_C_SETTINGS_CAN_BUY_ZERO_IN_DOCS_HINT_MSGVER_1',
				HELP_ARTICLE_ID,
				'products',
			);
		},
		getHintContent(contentPhraseKey, article, anchor)
		{
			const { linkStart, linkEnd } = this.getDocumentationLink(article, anchor);

			return `
				${Loc.getMessage(contentPhraseKey, {
					'#LINK_START#': linkStart,
					'#LINK_END#': linkEnd,
				})}
			`;
		},
		getDocumentationLink(article, anchor)
		{
			let link = `redirect=detail&code=${article}`;
			if (!Type.isNil(anchor))
			{
				link += `#${anchor}`;
			}

			return {
				linkStart: `<a href="javascript:void(0);" onclick="if (top.BX.Helper){top.BX.Helper.show('${link}');}" class="catalog-settings-helper-link">`,
				linkEnd: '</a>',
			};
		},
		getDocumentationProductBatchLink(phraseKey): string
		{
			return this.getHintContent(phraseKey, HELP_COST_CALCULATION_MODE_ARTICLE_ID);
		},
		changeCalculationMode()
		{
			if (!this.isEmptyCostPriceCalculationMethod)
			{
				return;
			}

			this.isHiddenCostPriceCalculationMethodChangeWarning = false;
			this.markAsChanged();
		},
	},
	mounted()
	{
		BX.UI.Hint.init(this.$el);

		if (this.shouldShowBatchMethodSpotlight)
		{
			const methodSelector = document.querySelector('.catalog-settings-cost-price-method-selector');
			Runtime.loadExtension('spotlight').then((exports) => {
				const spotlight = new BX.SpotLight(
					{
						id: 'batch-method-tour-spotlight',
						targetElement: methodSelector,
						autoSave: true,
						targetVertex: 'middle-center',
						zIndex: 200,
						left: -420,
					},
				);
				spotlight.show();
				spotlight.container.style.pointerEvents = "none";
				UserOptions.save('crm.catalog-settings', 'tour', 'batch_spotlight_shown', 'Y');
				Event.bind(methodSelector, 'click', () => {
					spotlight.close();
				});
			});
		}
	},
	template: `
		<div class="catalog-settings-wrapper">
			<form>
				<div class="ui-slider-section">
					<div class="ui-slider-content-box">
						<div
							style="display: flex; align-items: center"
							class="ui-slider-heading-4"
						>
							{{loc.CRM_CFG_C_SETTINGS_TITLE}}
						</div>
						<div class="ui-slider-inner-box">
							<p class="ui-slider-paragraph-2">
								{{description}}
							</p>
						</div>
						<div v-if="hasAccessToCatalogSettings" class="catalog-settings-button-container">
							<template v-if="isStoreControlUsed">
								<a
									@click="openStoreControlMaster()"
									class="ui-btn ui-btn-md ui-btn-light-border ui-btn-width"
								>
									{{loc.CRM_CFG_C_SETTINGS_INVENTORY_MANAGEMENT_DISABLE}}
								</a>
							</template>
							<template v-else>
								<a
									@click="openStoreControlMaster()"
									class="ui-btn ui-btn-success"
								>
									{{loc.CRM_CFG_C_SETTINGS_TURN_INVENTORY_CONTROL_ON}}
								</a>
							</template>
						</div>
					</div>
				</div>
				<div class="catalog-settings-main-settings">
					<div
						v-if="isReservationUsed && hasAccessToReservationSettings"
						class="ui-slider-section"
					>
						<div class="ui-slider-heading-4">
							{{loc.CRM_CFG_C_SETTINGS_RESERVATION_SETTINGS}}
							<span
								class="ui-hint"
								data-hint-html=""
								data-hint-interactivity=""
								:data-hint="getReservationSettingsHint()"
							>
								<span class="ui-hint-icon"></span>
							</span>
						</div>
						<div class="catalog-settings-editor-content-block">
							<div class="ui-ctl-label-text">
								<label>{{loc.CRM_CFG_C_SETTINGS_RESERVATION_ENTITY}}</label>
							</div>
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-disabled ui-ctl-w100">
								<!--<div class="ui-ctl-after ui-ctl-icon-angle"></div>-->
								<select
									v-model="currentReservationEntityCode"
									class="ui-ctl-element"
								>
									<option
										v-for="reservationEntity in reservationEntities"
										:value="reservationEntity.code"
										:disabled="reservationEntity.code !== 'deal'"
									>
										{{reservationEntity.name}}
									</option>
								</select>
							</div>
						</div>
						<reservation
							v-for="(reservationEntity, index) in reservationEntities"
							v-show="reservationEntity.code === currentReservationEntityCode"
							:key="reservationEntity.code"
							:settings="reservationEntity.settings"
							@change="onReservationSettingsValuesChanged($event, index)"
						></reservation>
					</div>
					<div v-if="isStoreBatchUsed && hasAccessToCatalogSettings" class="ui-slider-section">
						<div class="ui-slider-content-box">
							<div
								style="display: flex; align-items: center"
								class="ui-slider-heading-4"
							>
								{{loc.CRM_CFG_C_SETTINGS_COST_PRICE_TITLE_MSGVER_1}}
							</div>
							<div class="catalog-settings-editor-content-block">
								<div class="ui-ctl-label-text">
									<label>{{loc.CRM_CFG_C_SETTINGS_COST_PRICE_CALCULATION_MODE_MSGVER_1}}</label>
									<span
										class="ui-hint"
										data-hint-html=""
										data-hint-interactivity=""
										:data-hint="getCostPriceCalculationHint()"
									>
										<span class="ui-hint-icon"></span>
									</span>
								</div>
								<div v-if="!isEmptyCostPriceCalculationMethod" class="ui-alert ui-alert-primary ui-alert-icon-info">
									<span class="ui-alert-message">
										<span v-html='getDocumentationProductBatchLink("CRM_CFG_C_SETTINGS_COST_PRICE_CHANGE_MODE_INFO_MSGVER_2")'></span>
									</span>
								</div>
								<div v-if="!isHiddenCostPriceCalculationMethodChangeWarning" class="ui-alert ui-alert-warning ui-alert-icon-warning">
									<span class="ui-alert-message">
										<span v-html='getDocumentationProductBatchLink("CRM_CFG_C_SETTINGS_COST_PRICE_CHANGE_MODE_WARNING_MSGVER_2")'></span>
									</span>
								</div>
								<div 
									class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100" 
									:class='{"ui-ctl-disabled": !isEmptyCostPriceCalculationMethod}'
								>
									<div 
										v-if='isEmptyCostPriceCalculationMethod' 
										class="ui-ctl-after ui-ctl-icon-angle"
									>									
									</div>
									<select
										v-model="costPriceCalculationMethod"
										:disabled="!isEmptyCostPriceCalculationMethod"
										@change="changeCalculationMode"
										required
										class="ui-ctl-element catalog-settings-cost-price-method-selector"
									>
										<option 
											v-if="isEmptyCostPriceCalculationMethod" 
											value=''
											disabled 
											selected
											hidden
										>
											{{loc.CRM_CFG_C_SETTINGS_COST_PRICE_CHANGE_PLACEHOLDER}}
										</option>
										<option
											v-for="method in costPriceCalculationMethods"
											:value="method.code"
											:selected="method.code === costPriceCalculationMethod"
										>
											{{method.title}}
										</option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div v-if="hasAccessToCatalogSettings" class="ui-slider-section">
						<div class="ui-slider-heading-4">
							{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS}}
							<span
								class="ui-hint"
								data-hint-html=""
								data-hint-interactivity=""
								:data-hint="getProductsSettingsHint()"
							>
								<span class="ui-hint-icon"></span>
							</span>
						</div>
						<div
							v-if="isCanEnableProductCardSlider"
							class="catalog-settings-editor-checkbox-content-block"
						>
							<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
								<input
									@click="onEnableProductCardCheckboxClick"
									v-model="productCardSliderEnabled"
									id="product_card_slider_enabled"
									type="checkbox"
									class="ui-ctl-element"
								>
								<label for="product_card_slider_enabled" class="ui-ctl-label-text">
									{{loc.CRM_CFG_C_SETTINGS_PRODUCT_CARD_ENABLE_NEW_CARD}}
								</label>
							</div>
						</div>
						<div class="catalog-settings-editor-checkbox-content-block">
							<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
								<input
									v-model="defaultSubscribe"
									@click="markAsChanged"
									id="default_subscribe"
									type="checkbox"
									class="ui-ctl-element"
								>
								<label for="default_subscribe" class="ui-ctl-label-text">
									{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_DEFAULT_SUBSCRIBE}}
								</label>
							</div>
						</div>
						<div
							v-if="isDefaultQuantityTraceVisible"
							class="catalog-settings-editor-checkbox-content-block"
						>
							<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
								<input
									v-model="defaultQuantityTrace"
									@click="markAsChanged"
									id="default_quantity_trace"
									type="checkbox"
									class="ui-ctl-element"
								>
								<label for="default_quantity_trace" class="ui-ctl-label-text">
									{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_DEFAULT_QUANTITY_TRACE}}
								</label>
							</div>
						</div>
						<div v-if="isCanBuyZeroInDocsVisible" class="catalog-settings-editor-checkbox-content-block">
							<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
								<input
									v-model="checkRightsOnDecreaseStoreAmount"
									@click="markAsChanged"
									type="checkbox"
									class="ui-ctl-element"
								>
								<label class="ui-ctl-label-text">
									{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_DEFAULT_CAN_BUY_ZERO_IN_DOCS}}
								</label>
								<span
									class="ui-hint"
									data-hint-html=""
									data-hint-interactivity=""
									:data-hint="getCanBuyZeroInDocsHint()"
								>
									<span class="ui-hint-icon"></span>
								</span>
							</div>
						</div>
						<div
							v-if="isReservationUsed && isCanChangeOptionCanByZero"
							class="catalog-settings-editor-checkbox-content-block"
						>
							<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
								<input
									v-model="defaultCanBuyZero"
									@click="markAsChanged"
									id="default_can_buy_zero"
									type="checkbox"
									class="ui-ctl-element"
									:disabled="!isCanChangeOptionCanByZero"
								>
								<label for="default_can_buy_zero" class="ui-ctl-label-text">
									{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_DEFAULT_CAN_BUY_ZERO_V2}}
								</label>
								<span
									class="ui-hint"
									data-hint-html=""
									data-hint-interactivity=""
									:data-hint="getCanBuyZeroHint()"
								>
									<span class="ui-hint-icon"></span>
								</span>
							</div>
						</div>
						<div class="catalog-settings-editor-checkbox-content-block">
							<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
								<input
									v-model="defaultProductVatIncluded"
									@click="markAsChanged"
									id="default_product_vat_included"
									type="checkbox"
									class="ui-ctl-element"
								>
								<label for="default_product_vat_included" class="ui-ctl-label-text">
									{{loc.CRM_CFG_C_SETTINGS_PRODUCT_CARD_SET_VAT_IN_PRICE_FOR_NEW_PRODUCTS}}
								</label>
							</div>
						</div>
						<div class="catalog-settings-editor-content-block">
							<div class="ui-ctl-label-text">
								<label>{{loc.CRM_CFG_C_SETTINGS_PRODUCTS_SETTINGS_DEFAULT_VAT}}</label>
								<span
									class="ui-hint"
									data-hint-html=""
									data-hint-interactivity=""
									:data-hint="getDefaultVatHint()"
								>
									<span class="ui-hint-icon"></span>
								</span>
							</div>
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select
									v-model="defaultProductVatId"
									class="ui-ctl-element"
									@change="markAsChanged"
								>
									<option
										v-for="vat in vats"
										:value="vat.ID"
									>
										{{vat.NAME}}
									</option>
								</select>
							</div>
						</div>
					</div>
				</div>
			</form>
			<div
				:class="buttonsPanelClass"
			>
				<div class="ui-button-panel ui-button-panel-align-center ">
					<button
						@click="save"
						:class="saveButtonClasses"
					>
						{{loc.CRM_CFG_C_SETTINGS_SAVE_BUTTON}}
					</button>
					<a
						@click="cancel"
						class="ui-btn ui-btn-link"
					>
						{{loc.CRM_CFG_C_SETTINGS_CANCEL_BUTTON}}
					</a>
				</div>
			</div>
			<div style="height: 65px;"></div>
		</div>
	`,
});
