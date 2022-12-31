//@flow
import {userOptions, Loc, Event} from 'main.core';
import {EventEmitter} from 'main.core.events';
import 'spotlight';


export type OnboardingData = {
	chain: number;
	chainStep: number;
};

type Params = {
	onboardingData: OnboardingData;
	documentGuid: string;
	productListController?: BX.Crm.EntityStoreDocumentProductListController;
};

export class DocumentOnboardingManager
{
	#onboardingData: OnboardingData;
	#documentGuid: string;
	#productListController: BX.Crm.EntityStoreDocumentProductListController = null;

	constructor(params: Params)
	{
		this.#onboardingData = params.onboardingData;
		this.#documentGuid = params.documentGuid;
		if (params.productListController)
		{
			this.#productListController = params.productListController;
		}
	}

	processOnboarding(): void
	{
		const chain = this.#onboardingData.chain;
		const step = this.#onboardingData.chainStep;

		if (chain === 1 && step === 1)
		{
			const rowId = this.#getFirstProductRow();
			if (rowId)
			{
				this.#hintProductListField(rowId);
			}
		}
	}

	#hintProductListField(rowId: string = ''): void
	{
		const buttonsContainer = document.querySelector(`#${this.#documentGuid}_TABS_MENU`);
		const spotlight = new BX.SpotLight(
			{
				id: 'arrow_spotlight',
				targetElement: document.querySelector('[data-tab-id=tab_products]'),
				autoSave: true,
				targetVertex: "middle-center",
				zIndex: 200,
			}
		);
		spotlight.show();
		spotlight.container.style.pointerEvents = "none";

		const productListTabListener = (event) => {
			spotlight.close();
			const [productListEditor] = event.data;

			const buttonsPanelListener = () => {
				const activeHint = productListEditor.getActiveHint();
				if (activeHint !== null)
				{
					activeHint.close();
					Event.unbind(buttonsContainer, 'click', buttonsPanelListener);
				}
			}
			Event.bind(buttonsContainer, 'click', buttonsPanelListener);

			const tabChangeListener = (event) => {
				if (event?.data?.tabId === 'tab_products')
				{
					return;
				}

				productListEditor.getActiveHint()?.close();
			}

			productListEditor.showFieldTourHint(
				'AMOUNT',
				{
					title: Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_AMOUNT_GUIDE_TITLE_2'),
					text: Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_AMOUNT_GUIDE_TEXT'),
				},
				() => {
					userOptions.save('crm', 'warehouse-onboarding', 'secondChainStage', 2);
					userOptions.save('crm', 'warehouse-onboarding', 'chainStage', 2);

					Event.unbind(buttonsContainer, 'click', buttonsPanelListener);

					EventEmitter.unsubscribe(
						'onDemandRecalculateWrapper',
						productListTabListener
					);

					EventEmitter.unsubscribe(
						'BX.Catalog.EntityCard.TabManager:onOpenTab',
						tabChangeListener
					);
				},
				[],
				rowId
			);

			EventEmitter.subscribe('BX.Catalog.EntityCard.TabManager:onOpenTab', tabChangeListener);
		};

		EventEmitter.subscribe(
			'onDemandRecalculateWrapper',
			productListTabListener
		);
	}

	#getFirstProductRow(): string
	{
		const productList = this.#getProductList();
		for (const product of productList)
		{
			if (!product.getModel().isService())
			{
				return product.getId();
			}
		}

		return '';
	}

	#getProductList(): Array
	{
		if (this.#productListController && this.#productListController.productList)
		{
			if (this.#productListController.productList.products instanceof Array)
			{
				return this.#productListController.productList.products;
			}
		}

		return [];
	}
}