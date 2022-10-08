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
};

export class DocumentOnboardingManager
{
	#onboardingData: OnboardingData;
	#documentGuid: string;

	constructor(params: Params)
	{
		this.#onboardingData = params.onboardingData;
		this.#documentGuid = params.documentGuid;
	}

	processOnboarding(): void
	{
		const chain = this.#onboardingData.chain;
		const step = this.#onboardingData.chainStep;

		if (chain === 1 && step === 1)
		{
			this.#hintProductListField();
		}
	}

	#hintProductListField(): void
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

			productListEditor.showFieldTourHint(
				'AMOUNT',
				{
					title: Loc.getMessage('CRM_STORE_DOCUMENT_WAREHOUSE_PRODUCT_AMOUNT_GUIDE_TITLE'),
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
				}
			)
		};

		EventEmitter.subscribe(
			'onDemandRecalculateWrapper',
			productListTabListener
		);
	}
}