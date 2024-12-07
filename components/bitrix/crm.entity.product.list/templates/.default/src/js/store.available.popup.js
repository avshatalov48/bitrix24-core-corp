import { Event, Loc, Tag, Uri, Dom } from 'main.core';
import { Popup, PopupManager } from 'main.popup';
import { ProductModel } from 'catalog.product-model';
import { ModeList } from 'catalog.store-enable-wizard';

export default class StoreAvailablePopup
{
	#rowId;
	#model: ProductModel;
	#inventoryManagementMode: ?string;
	#node: HTMLElement;
	#popup: ?Popup;

	constructor(options)
	{
		this.#rowId = options.rowId
		this.#model = options.model;
		this.#inventoryManagementMode = options.inventoryManagementMode;
		this.setNode(options.node);
	}

	setNode(node: HTMLElement)
	{
		this.#node = node;
		Dom.addClass(this.#node, 'store-available-popup-link');

		Event.bind(this.#node, 'click', this.togglePopup.bind(this));
	}

	#createPopup()
	{
		const popupId = `store-available-popup-row-${this.#rowId}`;
		const popup = PopupManager.getPopupById(popupId);
		if (popup)
		{
			this.#popup = popup;
			this.#popup.setBindElement(this.#node);
			this.#popup.setContent(this.getPopupContent());
		}
		else
		{
			this.#popup = PopupManager.create({
				id: popupId,
				bindElement: this.#node,
				autoHide: true,
				draggable: false,
				offsetLeft: -218,
				offsetTop: 0,
				angle: {
					position: 'top',
					offset: 250
				},
				noAllPaddings: true,
				bindOptions: {
					forceBindPosition: true
				},
				closeByEsc: true,
				content: this.getPopupContent(),
			});
		}
	}

	getPopupContent(): HTMLElement
	{
		const storeId = this.#model.getField('STORE_ID');
		const storeCollection = this.#model.getStoreCollection();

		const storeQuantity = storeCollection.getStoreAmount(storeId);
		const reservedQuantity = storeCollection.getStoreReserved(storeId);
		const availableQuantity = storeCollection.getStoreAvailableAmount(storeId);

		const renderHead = (value) => {
			return `<td class="main-grid-cell-head main-grid-col-no-sortable main-grid-cell-right">
				<div class="main-grid-cell-inner">
					<span class="main-grid-cell-head-container">${value}</span>
				</div>
			</td>`;
		};

		const renderRow = (value) => {
			return `<td class="main-grid-cell main-grid-cell-right">
				<div class="main-grid-cell-inner">
					<span class="main-grid-cell-content">${value}</span>
				</div>
			</td>`;
		};

		const isReservedQuantityLink = reservedQuantity > 0 && this.#inventoryManagementMode !== ModeList.MODE_1C;

		const reservedQuantityContent = isReservedQuantityLink
			? `<a href="#" class="store-available-popup-reserves-slider-link">${reservedQuantity}</a>`
			: reservedQuantity
		;
		const viewAvailableQuantity = availableQuantity <= 0
			? `<span class="text--danger">${availableQuantity}`
			: availableQuantity
		;

		const result = Tag.render`
			<div class="store-available-popup-container">
				<table class="main-grid-table">
					<thead class="main-grid-header">
						<tr class="main-grid-row-head">
							${renderHead(Loc.getMessage('CRM_ENTITY_PL_STORE_AVAILABLE_POPUP_QUANTITY_COMMON_MSGVER_1'))}
							${renderHead(Loc.getMessage('CRM_ENTITY_PL_STORE_AVAILABLE_POPUP_QUANTITY_RESERVED'))}
							${renderHead(Loc.getMessage('CRM_ENTITY_PL_STORE_AVAILABLE_POPUP_QUANTITY_AVAILABLE'))}
						</tr>
					</thead>
					<tbody>
						<tr class="main-grid-row main-grid-row-body">
							${renderRow(storeQuantity)}
							${renderRow(reservedQuantityContent)}
							${renderRow(viewAvailableQuantity)}
						</tr>
					</tbody>
				</table>
			</div>
		`;

		if (isReservedQuantityLink)
		{
			Event.bind(
				result.querySelector('.store-available-popup-reserves-slider-link'),
				'click',
				(e) => {
					e.preventDefault();
					this.openDealsWithReservedProductSlider();
				},
			);
		}

		return result;
	}

	openDealsWithReservedProductSlider()
	{
		const reservedDealsSliderLink = '/bitrix/components/bitrix/catalog.productcard.reserved.deal.list/slider.php';

		const storeId = this.#model.getField('STORE_ID');
		const productId = this.#model.getField('PRODUCT_ID');

		const sliderLink = new Uri(reservedDealsSliderLink);
		sliderLink.setQueryParam('productId', productId);
		sliderLink.setQueryParam('storeId', storeId);

		BX.SidePanel.Instance.open(sliderLink.toString(), {
			allowChangeHistory: false,
			cacheable: false
		});
	}

	togglePopup()
	{
		if (this.#popup)
		{
			if (this.#popup.isShown())
			{
				this.#popup.close();
			}
			else
			{
				this.#popup.setContent(this.getPopupContent());
				this.#popup.show();
			}
		}
		else
		{
			this.#createPopup();
			this.#popup.show();
		}
	}
}
