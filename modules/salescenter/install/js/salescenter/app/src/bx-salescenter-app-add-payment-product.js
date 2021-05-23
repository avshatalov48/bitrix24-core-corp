import {config} from "./config";
import {Vue} from "ui.vue";
import {PopupMenuWindow} from 'main.popup';
import {Popup} from 'main.popup';

import "ui.dropdown";
import "ui.common";
import "ui.alerts";
import {MixinTemplatesType} from "./components/deal-receiving-payment/templates-type-mixin";
import {type BaseEvent, EventEmitter} from 'main.core.events';

Vue.component(config.templateAddPaymentProductName,
	{
		/**
		 * @emits 'changeBasketItem' {index: number, fields: object}
		 * @emits 'refreshBasket'
		 * @emits 'removeItem' {index: number}
		 */

		props: ['basketItem', 'basketItemIndex', 'countItems', 'selectedProductIds'],
		mixins:[MixinTemplatesType],
		data()
		{
			return {
				timer: null,
				productSelector: null,
				isImageAdded: false,
				imageControlId: null,
			};
		},
		created()
		{
			this.currencySymbol = this.$root.$app.options.currencySymbol;
			this.defaultMeasure = {
				name: '',
				id: null,
			};
			this.measures = this.$root.$app.options.measures || [];
			if (BX.type.isArray(this.measures) && this.measures)
			{
				this.measures.map((measure) => {
					if (measure['IS_DEFAULT'] === 'Y')
					{
						this.defaultMeasure.name = measure.SYMBOL;
						this.defaultMeasure.code = measure.CODE;

						if (!this.basketItem.measureName && !this.basketItem.measureName)
						{
							this.changeData({
								measureCode: this.defaultMeasure.code,
								measureName: this.defaultMeasure.name
							});
						}
					}
				});
			}

			EventEmitter.subscribe('onUploaderIsInited', this.onUploaderIsInitedHandler.bind(this));
		},
		mounted()
		{
			this.productSelector = new BX.UI.Dropdown(
				{
					searchAction: "salescenter.api.order.searchProduct",
					searchOptions: {
						restrictedSearchIds: this.selectedProductIds
					},
					enableCreation: true,
					enableCreationOnBlur: false,
					searchResultRenderer: null,
					targetElement: this.$refs.searchProductLine,
					items: this.getProductSelectorItems(),
					messages:
						{
							creationLegend: this.localize.SALESCENTER_PRODUCT_CREATE,
							notFound: this.localize.SALESCENTER_PRODUCT_NOT_FOUND,
						},
					events:
						{
							onSelect: this.selectCatalogItem.bind(this),
							onAdd: this.showCreationForm.bind(this),
							onReset: this.resetSearchForm.bind(this)
						}
				}
			);

			if (!this.basketItem.hasOwnProperty('id'))
			{
				this.initDefaultFileControl();
			}
		},
		updated()
		{
			if (this.basketItem.hasOwnProperty('fileControlJs') && !this.basketItem.productId)
			{
				let fileControlJs = this.basketItem.fileControlJs;
				if (fileControlJs)
				{
					fileControlJs.forEach((fileControlJsItem) => {
						BX.evalGlobal(fileControlJsItem);
					});
				}
			}
		},
		directives:
			{
				'bx-search-product':
					{
						inserted(element, binding)
						{
							if (binding.value.selector instanceof BX.UI.Dropdown)
							{
								const restrictedSearchIds = binding.value.restrictedIds;
								binding.value.selector.targetElement = element;
								if (BX.type.isArray(restrictedSearchIds))
								{
									binding.value.selector.searchOptions = {restrictedSearchIds};
									binding.value.selector.items = binding.value.selector.items.filter(
										item => !restrictedSearchIds.includes(item.id)
									);
								}
								binding.value.selector.init()
							}
						}
					}
			},

		methods:
			{
				onUploaderIsInitedHandler(event: BaseEvent)
				{
					if (this.basketItem.productId)
					{
						return;
					}

					const [, uploader] = event.getCompatData();

					EventEmitter.subscribe(uploader, 'onFileIsUploaded', this.onFileIsUploadedHandler.bind(this));
					EventEmitter.subscribe(uploader, 'onFileIsDeleted', this.onFileIsDeleteHandler.bind(this));
				},
				onFileIsUploadedHandler(event: BaseEvent)
				{
					const [fileId, , params, uploader] = event.getCompatData();

					if (!this.imageControlId)
					{
						this.imageControlId = uploader.CID;
					}
					else if (this.imageControlId !== uploader.CID)
					{
						return;
					}

					let images = this.basketItem.image,
						file = params && params['file'] && params['file']['files'] && params['file']['files']['default']
							? params['file']['files']['default']
							: false;

					if (file)
					{
						images.push({
							fileId: fileId,
							data: {
								name: file.name,
								type: file.type,
								tmp_name: file.path,
								size: file.size,
								error: null,
							}
						});
						let fields = {
							image: images,
						};

						fields.isCreatedProduct = 'Y';

						this.changeData(fields);
					}

					this.isImageAdded = true;
				},
				onFileIsDeleteHandler(event: BaseEvent)
				{
					const [fileId] = event.getCompatData();

					let images = this.basketItem.image;
					images.forEach(function (item, index, object) {
						if (item.fileId === fileId)
						{
							object.splice(index, 1);
						}
					});

					let fields = {
						image: images,
					};

					this.changeData(fields);
				},
				toggleDiscount(value)
				{
					this.changeData(
						{showDiscount: value}
					);

					value === 'Y' ? setTimeout(() => this.$refs.discountInput.focus()) : null;
				},
				changeData(fields)
				{
					this.$emit('changeBasketItem', {
						index: this.basketItemIndex,
						fields: fields
					});
				},
				isNeedRefreshAfterChanges()
				{
					if (this.isCreationMode)
					{
						return this.basketItem.name.length > 0
							&& this.basketItem.quantity > 0
							&& this.basketItem.price > 0
					}

					return true;
				},
				refreshBasket()
				{
					if (this.isNeedRefreshAfterChanges())
					{
						this.$emit('refreshBasket');
					}
				},
				debouncedRefresh(delay)
				{
					if (this.timer)
					{
						clearTimeout(this.timer);
					}

					this.timer = setTimeout(() => {
						this.refreshBasket();
						this.timer = null;
					}, delay);
				},
				changeQuantity(event)
				{
					event.target.value = event.target.value.replace(/[^.\d]/g,'.');
					let newQuantity = parseFloat(event.target.value);
					let lastSymbol = event.target.value.substr(-1);

					if (!newQuantity || lastSymbol === '.')
					{
						return;
					}

					let fields = this.basketItem;
					fields.quantity = newQuantity;
					this.changeData(fields);

					this.debouncedRefresh(300);
				},
				changeName(event)
				{
					let newName = event.target.value;
					let fields = this.basketItem;
					fields.name = newName;
					this.changeData(fields);
					this.refreshBasket();
				},
				changePrice(event)
				{
					event.target.value = event.target.value.replace(/[^.,\d]/g,'');
					if (event.target.value === '')
					{
						event.target.value = 0;
					}
					let lastSymbol = event.target.value.substr(-1);
					if (lastSymbol === ',')
					{
						event.target.value = event.target.value.replace(',', ".");
					}
					let newPrice = parseFloat(event.target.value);
					if (newPrice < 0|| lastSymbol === '.' || lastSymbol === ',')
					{
						return;
					}

					let fields = this.basketItem;
					fields.price = newPrice;
					fields.discount = 0;

					if (fields.module !== 'catalog')
					{
						fields.basePrice = newPrice;
					}
					else
					{
						fields.isCustomPrice = 'Y';
					}

					this.changeData(fields);

					this.refreshBasket();
				},
				/**
				 *
				 * @param discountType {string}
				 */
				changeDiscountType(discountType)
				{
					let type = (discountType === 'currency') ? 'currency' : 'percent';
					let fields = this.basketItem;
					fields.discountType = type;
					fields.price = fields.basePrice;
					fields.isCustomPrice = 'Y';

					this.changeData(fields);

					this.refreshBasket();
				},
				changeDiscount(event)
				{
					let discountValue = parseFloat(event.target.value) || 0;
					if (discountValue === parseFloat(this.basketItem.discount))
					{
						return;
					}

					let fields = this.basketItem;
					fields.discount = discountValue;
					fields.price = fields.basePrice;
					fields.isCustomPrice = 'Y';

					this.changeData(fields);

					this.refreshBasket();
				},
				showCreationForm()
				{
					if (!(this.productSelector instanceof BX.UI.Dropdown))
						return true;

					const value = this.productSelector.targetElement.value;
					let fields = {
						productId: '',
						quantity: 1,
						module: null,
						sort: this.basketItemIndex,
						isCreatedProduct: 'Y',
						name: value,
						isCustomPrice: 'Y',
						discountInfos: [],
						errors: [],
					};

					if (!this.isImageAdded)
					{
						this.initDefaultFileControl(fields);
					}
					else
					{
						this.changeData(fields);
					}

					this.productSelector.destroyPopupWindow();
				},
				resetSearchForm()
				{
					if (!(this.productSelector instanceof BX.UI.Dropdown))
						return true;

					this.productSelector.targetElement.value = '';
					this.productSelector.updateItemsList(this.getProductSelectorItems());

					let fields = {
						productId: '',
						code: null,
						module: null,
						name: '',
						quantity: 0,
						price: 0,
						basePrice: 0,
						discount: 0,
						discountInfos: [],
						image:[],
						errors:['SALE_BASKET_ITEM_NAME'],
					};

					this.initDefaultFileControl(fields).then(() => {
						this.refreshBasket();
					});

					this.isImageAdded = false;
					this.imageControlId = null;
					this.productSelector.destroyPopupWindow();
				},
				hideCreationForm()
				{
					if (!(this.productSelector instanceof BX.UI.Dropdown))
						return true;

					let fields = {
						isCreatedProduct: 'N',
						productId: '',
						name: '',
						quantity: 0,
						price: 0,
						basePrice: 0,
						discount: 0,
						discountInfos: [],
						image:[],
						errors:[],
					};

					this.initDefaultFileControl(fields).then(() => {
						this.refreshBasket();
					});

					this.isImageAdded = false;
					this.imageControlId = null;
				},
				removeItem()
				{
					this.$emit('removeItem', {
						index: this.basketItemIndex
					});
				},
				selectCatalogItem(sender, item)
				{
					this.$root.$app.startProgress();
					if (!sender instanceof BX.UI.Dropdown)
					{
						return true;
					}

					if (item.id === undefined || parseInt(item.id) <= 0)
					{
						return true;
					}

					let quantity = item.attributes && item.attributes.measureRatio ? item.attributes.measureRatio : item.quantity;

					let fields = {
						name: item.title,
						productId: item.id,
						sort: this.basketItemIndex,
						module: 'catalog',
						isCustomPrice: 'N',
						discount: 0,
						quantity: quantity,
						isCreatedProduct: 'N',
						image: [],
					};

					if (this.basketItemIndex.productId !== item.id)
					{
						fields.encodedFields = null;
						fields.discount = 0;
						fields.isCustomPrice = 'N';
					}

					BX.ajax.runAction(
						"salescenter.api.order.getFileControl",
						{ data: { productId: item.id } }
					).then((result) => {
						let data = BX.prop.getObject(result, "data", {});
						if (data.fileControl)
						{
							let fileControl = BX.processHTML(data.fileControl);
							fields.fileControlHtml = fileControl['HTML'];
						}

						this.changeData(fields);
						this.$emit('refreshBasket');
					});

					sender.destroyPopupWindow();
				},
				openDiscountEditor(e, url)
				{
					if(!(window.top.BX.SidePanel && window.top.BX.SidePanel.Instance))
					{
						return;
					}

					window.top.BX.SidePanel.Instance.open (
						BX.util.add_url_param( url, { "IFRAME": "Y", "IFRAME_TYPE": "SIDE_SLIDER", "publicSidePanel": "Y" } ),
						{ allowChangeHistory: false }
					);

					e.preventDefault ? e.preventDefault() : (e.returnValue = false);
				},
				isEmptyProductName()
				{
					return (this.basketItem.name.length === 0);
				},
				calculateCorrectionFactor(quantity, measureRatio)
				{
					let factoredQuantity = quantity;
					let factoredRatio = measureRatio;
					let correctionFactor = 1;

					while (!(Number.isInteger(factoredQuantity) && Number.isInteger(factoredRatio)))
					{
						correctionFactor *= 10;
						factoredQuantity = quantity * correctionFactor;
						factoredRatio = measureRatio * correctionFactor;
					}

					return correctionFactor;
				},
				incrementQuantity()
				{
					let correctionFactor = this.calculateCorrectionFactor(this.basketItem.quantity, this.basketItem.measureRatio);
					this.basketItem.quantity = (this.basketItem.quantity * correctionFactor + this.basketItem.measureRatio * correctionFactor) / correctionFactor;
					this.changeData(this.basketItem);

					this.debouncedRefresh(300);
				},
				decrementQuantity()
				{
					if (this.basketItem.quantity > this.basketItem.measureRatio)
					{
						let correctionFactor = this.calculateCorrectionFactor(this.basketItem.quantity, this.basketItem.measureRatio);
						this.basketItem.quantity = (this.basketItem.quantity * correctionFactor - this.basketItem.measureRatio * correctionFactor) / correctionFactor;
						this.changeData(this.basketItem);

						this.debouncedRefresh(300);
					}
				},
				showPopupMenu(target, array, type)
				{
					if (!this.editable)
					{
						return;
					}

					let menuItems = [];
					let setItem = (ev, param) => {
						target.innerHTML = ev.target.innerHTML;

						if(type === 'discount')
						{
							this.changeDiscountType(param.options.type);
						}

						this.popupMenu.close();
					};

					if(type === 'discount')
					{
						array = [];
						array.percent = '%';
						array.currency = this.currencySymbol;
					}

					if(array)
					{
						for(let item in array)
						{
							let text = array[item];

							if(type === 'measures')
							{
								text = array[item].SYMBOL;
							}

							menuItems.push({
								text: text,
								onclick: setItem.bind({ value: 'settswguy' }),
								type: type === 'discount' ? item : null
							})
						}
					}

					this.popupMenu = new PopupMenuWindow({
						bindElement: target,
						items: menuItems
					});

					this.popupMenu.show();
				},
				initDefaultFileControl(fields = {})
				{
					return this.getDefaultFileControl().then((fileControl) => {

						let fileControlData = BX.processHTML(fileControl);
						fields.fileControlHtml = fileControlData['HTML'];
						fields.fileControlJs = [];
						for (let i in fileControlData['SCRIPT'])
						{
							if (fileControlData['SCRIPT'].hasOwnProperty(i))
							{
								fields.fileControlJs.push(fileControlData['SCRIPT'][i]['JS']);
							}
						}

						this.changeData(fields);
					});
				},
				getDefaultFileControl()
				{
					return new Promise(function (resolve, reject) {
						BX.ajax.runAction(
							"salescenter.api.order.getFileControl"
						).then(
							(result) => {
								let data = BX.prop.getObject(result, "data", {'fileControl': ''});
								if (data.fileControl)
								{
									resolve(data.fileControl);
								}
							},
							(error) => {
								reject(new Error(error.errors.join('<br />')));
							}
						);
					});
				},
				getProductSelectorItems()
				{
					let initialProducts = this.$root.$app.options.mostPopularProducts.map((item) => {
						return {
							id: item.ID,
							title: item.NAME,
							quantity: item.MEASURE_RATIO,
							module: 'salescenter',
						};
					});

					let selectedProductIds = Array.isArray(this.selectedProductIds) ? this.selectedProductIds : [];

					let productSelectorItems = initialProducts.filter((item) => {
						return !item.id || !selectedProductIds.includes(item.id)
					});

					if (productSelectorItems.length)
					{
						return productSelectorItems;
					}
					else
					{
						return [
							{
								title: '',
								subTitle: this.localize.SALESCENTER_PRODUCT_BEFORE_SEARCH_TITLE
							}
						];
					}
				},
				showProductTooltip(e)
				{
					if(!this.productTooltip)
					{
						this.productTooltip = new Popup({
							bindElement: e.target,
							maxWidth: 400,
							darkMode: true,
							innerHTML: e.target.value,
							animation: 'fading-slide'
						});
					}

					this.productTooltip.setContent(e.target.value);
					e.target.value.length > 0 ? this.productTooltip.show() : null;
				},
				hideProductTooltip()
				{
					this.productTooltip ? this.productTooltip.close() : null;
				}
			},
		watch: {
			selectedProductIds(newValue, oldValue) {
				let newValueArray = Array.isArray(newValue) ? newValue : [];
				let oldValueArray = Array.isArray(oldValue) ? oldValue : [];

				if (newValueArray.join() === oldValueArray.join())
				{
					return;
				}

				this.productSelector.updateItemsList(this.getProductSelectorItems());
			}
		},
		computed:
			{
				localize()
				{
					return Vue.getFilteredPhrases('SALESCENTER_PRODUCT_');
				},
				showDiscount()
				{
					return this.basketItem.showDiscount === 'Y';
				},
				showPrice()
				{
					return this.basketItem.discount > 0 || parseFloat(this.basketItem.price) !== parseFloat(this.basketItem.basePrice);
				},
				getMeasureName()
				{
					return this.basketItem.measureName || this.defaultMeasure.name;
				},
				getMeasureCode()
				{
					return this.basketItem.measureCode || this.defaultMeasure.code;
				},
				getBasketFileControl()
				{
					let fileControl = this.basketItem.fileControl, html = '';
					if (fileControl)
					{
						let data = BX.processHTML(fileControl);
						html = data['HTML'];
					}

					return html;
				},
				restrictedSearchIds()
				{
					let restrictedSearchIds = this.selectedProductIds;
					if (this.basketItem.module === 'catalog')
					{
						restrictedSearchIds = restrictedSearchIds.filter(id => id !== this.basketItem.productId);
					}

					return restrictedSearchIds;
				},
				isCreationMode()
				{
					return this.basketItem.isCreatedProduct === 'Y';
				},
				isNotEnoughQuantity()
				{
					return this.basketItem.errors.includes('SALE_BASKET_AVAILABLE_QUANTITY');
				},
				hasPriceError()
				{
					return this.basketItem.errors.includes('SALE_BASKET_ITEM_WRONG_PRICE');
				},
				hasNameError()
				{
					return this.basketItem.errors.includes('SALE_BASKET_ITEM_NAME');
				},
				productInputWrapperClass()
				{
					return {
						'ui-ctl': true,
						'ui-ctl-w100': true,
						'ui-ctl-md': true,
						'ui-ctl-after-icon': true,
						'ui-ctl-danger': this.hasNameError,
					};
				}
			},
		template: `
		<div class="salescenter-app-page-content-item">
			<!--counters anr remover-->
			<div class="salescenter-app-counter">{{basketItemIndex + 1}}</div>
			<div class="salescenter-app-remove" @click="removeItem" v-if="countItems > 1 && editable"></div>
			<!--counters anr remover end-->
			
			<!--if isCreationMode-->
			<div class="salescenter-app-form-container" v-if="!isCreationMode">
				<div class="salescenter-app-form-row">
					<!--col 1-->
					<div class="salescenter-app-form-col salescenter-app-form-col-prod" style="flex:8">
						<div class="salescenter-app-form-col-input">
							<label class="salescenter-app-ctl-label-text ui-ctl-label-text">{{localize.SALESCENTER_PRODUCT_NAME}}</label>
							<div :class="productInputWrapperClass">
								<button class="ui-ctl-after ui-ctl-icon-clear" @click="resetSearchForm" v-if="basketItem.name.length > 0 && editable"/>
								<!--<button class="ui-ctl-after ui-ctl-icon-clear" @click="removeItem" v-if="countItems > 1 && editable"/>-->
								<input
									type="text"
									ref="searchProductLine" 
									class="ui-ctl-element ui-ctl-textbox salescenter-app-product-search" 
									:value="basketItem.name"
									v-bx-search-product="{selector: productSelector, restrictedIds: restrictedSearchIds}"
									:disabled="!editable"
									:placeholder="localize.SALESCENTER_PRODUCT_NAME_PLACEHOLDER" 
									@mouseover="showProductTooltip(event)"
									@mouseleave="hideProductTooltip(event)"
								>
							</div>
							<div class="salescenter-form-error" v-if="hasNameError">{{localize.SALESCENTER_PRODUCT_CHOOSE_PRODUCT}}</div>
						</div>
						<div v-if="getBasketFileControl" class="salescenter-app-form-col-img">
							<!-- loaded product -->
							<div v-html="getBasketFileControl"></div>
						</div>
						<div v-else class="salescenter-app-form-col-img">
							<!-- selected product -->
							<div v-html="basketItem.fileControlHtml"></div>
						</div>
					</div>
					<!--col 1 end-->

					<!--col 2-->
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2">
						<label class="salescenter-app-ctl-label-text salescenter-app-ctl-label-text-link ui-ctl-label-text">
							{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', ' ')}}
							<span @click="showPopupMenu($event.target, measures, 'measures')">{{ getMeasureName }}</span>
						</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100" :class="isNotEnoughQuantity ? 'ui-ctl-danger' : ''">
							<input 	type="text" class="ui-ctl-element ui-ctl-textbox" 
									:value="basketItem.quantity"
									@change="changeQuantity"
									:disabled="!editable">
							<div class="salescenter-app-input-counter" v-if="editable">
								<div class="salescenter-app-input-counter-up" @click="incrementQuantity"></div>
								<div class="salescenter-app-input-counter-down" @click="decrementQuantity"></div>
							</div>
						</div>
						<div class="salescenter-form-error" v-if="isNotEnoughQuantity">{{localize.SALESCENTER_PRODUCT_IS_NOT_AVAILABLE}}</div>
					</div>
					<!--col 2 end-->
					
					<!--col 3-->
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2">
						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">{{localize.SALESCENTER_PRODUCT_PRICE_2}}</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency" :class="hasPriceError ? 'ui-ctl-danger' : ''">
							<input 	type="text" class="ui-ctl-element ui-ctl-textbox"
									:value="basketItem.price"
									@change="changePrice"
									:disabled="!editable">
							<div class="salescenter-app-col-currency-symbol" v-html="currencySymbol"></div>
						</div>
					</div>
					<!--col 3 end-->
				</div>
				
				<!--show discount link-->
				<div class="salescenter-app-form-row" v-if="editable || (!editable && showPrice)">
					<div style="flex: 8;"></div>
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex: 2;">
						<div v-if="showDiscount" class="salescenter-app-collapse-link-pointer-event">{{localize.SALESCENTER_PRODUCT_DISCOUNT_PRICE_TITLE}}</div>
					</div>
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2" v-if="showDiscount">
						<div class="salescenter-app-collapse-link-hide"  @click="toggleDiscount('N')">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>
					</div>
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2" v-else>
						<div class="salescenter-app-collapse-link-show"  @click="toggleDiscount('Y')">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>
					</div>
				</div>
				<!--show discount link end-->
				
				<!--dicount controller-->
				<div class="salescenter-app-form-row" style="margin-bottom: 7px" v-if="showDiscount">
					<div class="salescenter-app-form-collapse-container">
						<div class="salescenter-app-form-row">					
							<div class="salescenter-app-form-col" style="flex: 8"></div>
							<div class="salescenter-app-form-col  salescenter-app-form-col-sm" style="flex:2; overflow: hidden;">
								<div class="ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency">
									<div class="ui-ctl-element ui-ctl-textbox salescenter-ui-ctl-element" v-html="basketItem.basePrice" disabled="true"></div>
									<div class="salescenter-app-col-currency-symbol" v-html="currencySymbol"></div>
								</div>
							</div>
							<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2; overflow: hidden;">
								<div class="ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown salescenter-app-col-currency">
									<input 	type="text" class="ui-ctl-element ui-ctl-textbox"
											ref="discountInput" 
											:value="basketItem.discount"
											@change="changeDiscount"
											:disabled="!editable">
									<div class="salescenter-app-col-currency-symbol salescenter-app-col-currency-symbol-link" @click="showPopupMenu($event.target.firstChild, null, 'discount')"><span v-html="basketItem.discountType === 'percent' ? '%' : currencySymbol"></span></div>
								</div>
							</div>
						</div>
						<div class="salescenter-app-form-row" style="margin-bottom: 0;" v-if="editable">
							<div class="salescenter-app-form-col" v-for="discount in basketItem.discountInfos"">
								<span class="ui-text-4 ui-color-light"> {{discount.name}}<a :href="discount.editPageUrl" @click="openDiscountEditor(event, discount.editPageUrl)">{{localize.SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE}}</a></span>
							</div>
						</div>
					</div>
				</div>
				<!--dicount controller end-->
				
			</div>
			<!--endif isCreationMode-->
			
			<!--else isCreationMode-->
			<div class="salescenter-app-form-container" v-else>
				<div class="salescenter-app-form-row">
					<!--col 1-->
					<div class="salescenter-app-form-col salescenter-app-form-col-prod" style="flex:8">
						<div class="salescenter-app-form-col-input">
							<label class="salescenter-app-ctl-label-text ui-ctl-label-text">{{localize.SALESCENTER_PRODUCT_TITLE}}</label>
							<div :class="productInputWrapperClass">
								<button class="ui-ctl-after ui-ctl-icon-clear" @click="hideCreationForm"> </button>
								<input 
									type="text" 
									class="ui-ctl-element ui-ctl-textbox" 
									@change="changeName" 
									:value="basketItem.name"
									@mouseover="showProductTooltip(event)"
									@mouseleave="hideProductTooltip(event)"
								>
								<div class="ui-ctl-tag">{{localize.SALESCENTER_PRODUCT_NEW_LABEL}}</div>
							</div>
							<div class="salescenter-form-error" v-if="hasNameError">{{localize.SALESCENTER_PRODUCT_EMPTY_PRODUCT_NAME}}</div>
						</div>
						<div class="salescenter-app-form-col-img">
							<!-- new product -->
							<div v-html="basketItem.fileControlHtml"></div>
						</div>
					</div>
					<!--col 1 end-->
					
					<!--col 2-->
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2">
						<label class="salescenter-app-ctl-label-text salescenter-app-ctl-label-text-link ui-ctl-label-text">
							{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', ' ')}}
							<span @click="showPopupMenu($event.target, measures, 'measures')">{{ getMeasureName }}</span>
						</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100">
							<input 	type="text" 
									class="ui-ctl-element ui-ctl-textbox" 
									:value="basketItem.quantity" 
									@input="changeQuantity" 
									@change="refreshBasket">
							<div class="salescenter-app-input-counter" v-if="editable">
								<div class="salescenter-app-input-counter-up" @click="incrementQuantity"></div>
								<div class="salescenter-app-input-counter-down" @click="decrementQuantity"></div>
							</div>
						</div>
					</div>
					<!--col 2 end-->
					
					<!--col 3-->
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2">
					
						<label class="salescenter-app-ctl-label-text ui-ctl-label-text">{{localize.SALESCENTER_PRODUCT_PRICE_2}}</label>
						<div class="ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency" :class="hasPriceError ? 'ui-ctl-danger' : ''">
							<input 	type="text" class="ui-ctl-element ui-ctl-textbox"
									:value="basketItem.price"
									@change="changePrice"
									:disabled="!editable">
							<div class="salescenter-app-col-currency-symbol" v-html="currencySymbol"></div>
						</div>
					</div>
					<!--col 3 end-->
				</div>
				
				<!--show discount link-->
				<div class="salescenter-app-form-row" v-if="editable || (!editable && showPrice)">
					<div style="flex: 8;"></div>
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex: 2;">
						<div v-if="showDiscount" class="salescenter-app-collapse-link-pointer-event">{{localize.SALESCENTER_PRODUCT_DISCOUNT_PRICE_TITLE}}</div>
					</div>
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2" v-if="showDiscount">
						<div class="salescenter-app-collapse-link-hide"  @click="toggleDiscount('N')">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>
					</div>
					<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2" v-else>
						<div class="salescenter-app-collapse-link-show"  @click="toggleDiscount('Y')">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>
					</div>
				</div>
				<!--show discount link end-->
				
				<!--dicount controller-->
				<div class="salescenter-app-form-row" style="margin-bottom: 7px" v-if="showDiscount">
					<div class="salescenter-app-form-collapse-container">
						<div class="salescenter-app-form-row">					
							<div class="salescenter-app-form-col" style="flex: 8"></div>
							<div class="salescenter-app-form-col  salescenter-app-form-col-sm" style="flex:2; overflow: hidden;">
								<div class="ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency">
									<div class="ui-ctl-element ui-ctl-textbox salescenter-ui-ctl-element" v-html="basketItem.basePrice" disabled="true"></div>
									<div class="salescenter-app-col-currency-symbol" v-html="currencySymbol"></div>
								</div>
							</div>
							<div class="salescenter-app-form-col salescenter-app-form-col-sm" style="flex:2; overflow: hidden;">
								<div class="ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown salescenter-app-col-currency">
									<input 	type="text" class="ui-ctl-element ui-ctl-textbox"
											ref="discountInput"
											:value="basketItem.discount"
											@change="changeDiscount"
											:disabled="!editable">
									<div class="salescenter-app-col-currency-symbol salescenter-app-col-currency-symbol-link" @click="showPopupMenu($event.target.firstChild, null, 'discount')"><span v-html="basketItem.discountType === 'percent' ? '%' : currencySymbol"></span></div>
								</div>
							</div>
						</div>
						<div class="salescenter-app-form-row" style="margin-bottom: 0;" v-if="editable">
							<div class="salescenter-app-form-col" v-for="discount in basketItem.discountInfos"">
								<span class="ui-text-4 ui-color-light"> {{discount.name}} 
								<a :href="discount.editPageUrl" @click="openDiscountEditor(event, discount.editPageUrl)">{{localize.SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE}}</a></span>
							</div>
						</div>
					</div>
				</div>
				<!--dicount controller end-->
			</div>
			<!--endelse isCreationMode-->
		</div>
	`
	});