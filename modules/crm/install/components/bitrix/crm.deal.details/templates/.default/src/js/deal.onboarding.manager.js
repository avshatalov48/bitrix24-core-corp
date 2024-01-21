//@flow
import {userOptions, Loc, Dom, Event} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Guide} from 'ui.tour';
import 'spotlight';
import {PopupWindowManager} from "main.popup";

export type OnboardingData = {
	chain: number;
	chainStep: number;
	successDealGuideIsOver: boolean;
};

type Params = {
	onboardingData: OnboardingData;
	contentContainer: HTMLElement;
	serviceUrl: string;
	dealDetailManager: BX.Crm.DealDetailManager;
};

export class DealOnboardingManager
{
	#onboardingData: OnboardingData;
	#contentContainer: HTMLElement;
	#serviceUrl: string;
	#dealDetailManager: BX.Crm.DealDetailManager;

	#activeDocumentGuide: Guide|null = null;

	static get productsTabId(): string
	{
		return 'tab_products';
	}

	constructor(params: Params)
	{
		this.#onboardingData = params.onboardingData;
		this.#contentContainer = params.contentContainer;
		this.#serviceUrl = params.serviceUrl;
		this.#dealDetailManager = params.dealDetailManager;
	}

	#getContentContainer(): HTMLElement
	{
		return this.#contentContainer;
	}

	#getButtonsContainer(): HTMLElement
	{
		return this.#getContentContainer().querySelector('.main-buttons');
	}

	#isHintCanBeShown(): boolean
	{
		if (PopupWindowManager && PopupWindowManager.isAnyPopupShown())
		{
			return false;
		}

		return true;
	}

	processOnboarding(): void
	{
		if (!this.#isHintCanBeShown())
		{
			return;
		}

		const chain = this.#onboardingData.chain;
		const step = this.#onboardingData.chainStep;
		const successDealGuideIsOver = this.#onboardingData.successDealGuideIsOver;

		if (chain === 0)
		{
			if (step < 1)
			{
				this.#processProductTabHint();
			}
			if (step < 2)
			{
				this.#hintProductListField();
			}
		}
		else if (chain === 1 && step === 0)
		{
			this.#hintAddDocumentLink();
		}

		if (!successDealGuideIsOver)
		{
			this.#hintSuccessDealDocumentInTimeline();
		}
	}

	#processProductTabHint(): void
	{
		const guideText = {
			title: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TITLE'),
			text: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TEXT'),
		};

		if (this.#dealDetailManager.isTabButtonVisible(DealOnboardingManager.productsTabId))
		{
			this.#hintToVisibleProductTab();
		}
		else
		{
			this.#hintToHiddenProductTab();
		}
	}

	#createHintToProductTab(target: HTMLElement, guideEvents: Object = {}): Guide
	{
		const guideText = {
			title: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TITLE'),
			text: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_AUTOMATIC_RESERVATION_GUIDE_TEXT'),
		};

		return new Guide({
			steps: [
				{
					target: target,
					title: guideText.title,
					text: guideText.text,
					position: 'bottom',
					events: guideEvents
				}
			],
			onEvents: true
		});
	}

	#hintToVisibleProductTab(): void
	{
		const productsTabButton = this.#dealDetailManager.getTabMenuItemContainer(DealOnboardingManager.productsTabId);

		const productsTabGuide = this.#createHintToProductTab(
			productsTabButton,
			{
				onClose: () => {
					userOptions.save('crm', 'warehouse-onboarding', 'firstChainStage', 1);
				},
			}
		);
		productsTabGuide.showNextStep();
		const tabsContainer = this.#dealDetailManager.getTabManager().getTabMenuContainer();

		const windowResizeHandler = () => {
			if (!this.#dealDetailManager.isTabButtonVisible(DealOnboardingManager.productsTabId))
			{
				productsTabGuide.close();
				Event.unbind(window, 'resize', windowResizeHandler);
			}
		};

		Event.bind(window, 'resize', windowResizeHandler);
		Event.bindOnce(tabsContainer, 'mousedown', () => {
			productsTabGuide.close();
			Event.unbind(window, 'resize', windowResizeHandler);
		});
	}

	#hintToHiddenProductTab(): void
	{
		const moreButton = this.#dealDetailManager.getTabManager().getMoreButton();
		const spotlight = new BX.SpotLight(
			{
				id: `${DealOnboardingManager.productsTabId}_spotlight`,
				targetElement: moreButton,
				autoSave: true,
				targetVertex: "middle-center",
				zIndex: 200,
			}
		);
		spotlight.show();
		spotlight.container.style.pointerEvents = "none";

		const onOpenMoreMenuHandler = (event) => {
			const eventMoreMenu = event.target.getMoreMenu();
			const dealMoreMenu = this.#dealDetailManager.getTabManager().getMoreMenu();

			if (eventMoreMenu === dealMoreMenu)
			{
				spotlight.close();
				const productsTabGuide = this.#createHintToProductTab(
					this.#dealDetailManager.getTabFromMoreMenu(DealOnboardingManager.productsTabId)
				);

				const moreMenuContainer = eventMoreMenu.getMenuContainer();

				const tabHintTimeout = setTimeout(() => {
					productsTabGuide.showNextStep();

					BX.bindOnce(moreMenuContainer, 'click', moreMenuClickHandler);

					const productsTabPopup = productsTabGuide.getPopup();
					EventEmitter.subscribeOnce(productsTabPopup, 'onClose', onPopupCloseHandler);

					const popupContainer = productsTabGuide.getPopup().getContentContainer().parentNode;
					BX.bind(popupContainer, 'mouseenter', () => {
						event.target.showMoreMenu();
					});
					BX.bind(popupContainer, 'mouseleave', () => {
						const outOfPopupTimeout = setTimeout(() => {
							event.target.closeMoreMenu();
						}, 300);
						Event.bindOnce(dealMoreMenu.getMenuContainer(), 'mouseenter', () => {
							clearTimeout(outOfPopupTimeout);
						});
					});
				}, 50);


				const onPopupCloseHandler = (event) => {
					userOptions.save('crm', 'warehouse-onboarding', 'firstChainStage', 1);

					Event.unbind(window, 'resize', windowResizeHandler);
					Event.unbind(moreMenuContainer, 'click', moreMenuClickHandler);
					EventEmitter.unsubscribe('BX.Main.InterfaceButtons:onMoreMenuShow', onOpenMoreMenuHandler);
				};


				const moreMenuClickHandler = () => {
					userOptions.save('crm', 'warehouse-onboarding', 'firstChainStage', 1);
					productsTabGuide.close();
				};
				Event.bind(dealMoreMenu.getMenuContainer(), 'click', onPopupCloseHandler);

				EventEmitter.subscribeOnce('BX.Main.InterfaceButtons:onMoreMenuClose', (event) => {
					const eventMoreMenu = event.target.getMoreMenu();
					const dealMoreMenu = this.#dealDetailManager.getTabManager().getMoreMenu();

					if (eventMoreMenu === dealMoreMenu)
					{
						clearTimeout(tabHintTimeout);
						Event.unbind(moreMenuContainer, 'click', moreMenuClickHandler);
						productsTabGuide.close();
					}
				});
			}
		};
		EventEmitter.subscribe('BX.Main.InterfaceButtons:onMoreMenuShow', onOpenMoreMenuHandler);

		const windowResizeHandler = () => {
			if (this.#dealDetailManager.isTabButtonVisible(DealOnboardingManager.productsTabId))
			{
				spotlight.close();
				this.#hintToVisibleProductTab();
				Event.unbind(window, 'resize', windowResizeHandler);
				EventEmitter.unsubscribe('BX.Main.InterfaceButtons:onMoreMenuShow', onOpenMoreMenuHandler);
			}
		}
		Event.bind(window, 'resize', windowResizeHandler);
	}

	#hintProductListField(): void
	{
		const buttonsContainer = this.#getContentContainer().querySelector('.main-buttons');
		const productListTabListener = (event) => {
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

			const productList = productListEditor.products;
			let rowId = '';
			if (productList instanceof Array)
			{
				const firstProductRow = productList.find((row) => !row.getModel().isService());
				if (firstProductRow)
				{
					rowId = firstProductRow.getId();
				}
			}

			if (!rowId)
			{
				return;
			}

			productListEditor.showFieldTourHint(
				'STORE_INFO',
				{
					title: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_PRODUCT_STORE_GUIDE_TITLE'),
					text: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_PRODUCT_STORE_GUIDE_TEXT'),
				},
				() => {
					userOptions.save('crm', 'warehouse-onboarding', 'firstChainStage', 2);
					BX.ajax.post(
						this.#serviceUrl,
						{
							ACTION: 'FIX_FIRST_ONBOARD_CHAIN_VIEW',
						}
					);
					Event.unbind(buttonsContainer, 'click', buttonsPanelListener);

					EventEmitter.unsubscribe(
						'onDemandRecalculateWrapper',
						productListTabListener
					);
				},
				['RESERVE_INFO'],
				rowId
			);
		}

		EventEmitter.subscribe(
			'onDemandRecalculateWrapper',
			productListTabListener
		);
	}

	#hintAddDocumentLink()
	{
		const documentsListTourListener = (event) => {
			if (this.#activeDocumentGuide !== null)
			{
				this.#activeDocumentGuide.close();
			}

			const buttonsContainer = this.#getButtonsContainer();
			const sumControlContainer = document.querySelector('[data-cid="OPPORTUNITY_WITH_CURRENCY"]');
			const addDocumentButton = sumControlContainer && sumControlContainer.querySelector('.crm-entity-widget-payment-add-box');
			if (addDocumentButton !== null) {
				const settingsButton = sumControlContainer.querySelector('.ui-entity-editor-block-context-menu');
				const dragButton = sumControlContainer.querySelector('.ui-entity-editor-draggable-btn');
				const guideText = {
					title: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_ADD_DOCUMENT_GUIDE_TITLE'),
					text: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_ADD_DOCUMENT_GUIDE_TEXT'),
				};

				const addDocumentGuide = new Guide({
					steps: [
						{
							target: addDocumentButton,
							title: guideText.title,
							text: guideText.text,
							events: {
								onClose: () => {
									Event.unbind(buttonsContainer, 'click', userCloseHintHandler);
									Event.unbind(settingsButton, 'click', userCloseHintHandler);
									Event.unbind(dragButton, 'mousedown', userCloseHintHandler);
								},
							}
						}
					],
					onEvents: true
				});
				this.#activeDocumentGuide = addDocumentGuide;

				const userCloseHintHandler = () => {
					Event.unbind(buttonsContainer, 'click', userCloseHintHandler);
					EventEmitter.unsubscribe(
						'PaymentDocuments.EntityEditor:changeDocuments',
						documentsListTourListener
					);

					addDocumentGuide.close();
					userOptions.save('crm', 'warehouse-onboarding', 'secondChainStage', 1);

				}

				addDocumentGuide.showNextStep();
				Event.bind(addDocumentGuide.getPopup().closeIcon, 'click', userCloseHintHandler);
				Event.bind(buttonsContainer, 'click', userCloseHintHandler);
				Event.bind(addDocumentButton, 'click', userCloseHintHandler);
				Event.bind(settingsButton, 'click', userCloseHintHandler);
				Event.bind(dragButton, 'mousedown', userCloseHintHandler);
			}
		};

		EventEmitter.subscribe(
			'PaymentDocuments.EntityEditor:changeDocuments',
			documentsListTourListener
		);
	}

	#hintSuccessDealDocumentInTimeline(): void
	{
		const timelineGuideListener = (event) => {
			if (event.data[1].currentStepId === 'WON' && event.data[1].currentSemantics === 'success')
			{
				EventEmitter.unsubscribe(
					'Crm.EntityProgress.Saved',
					timelineGuideListener
				);

				const onHistoryNodeAddedHandler = (event) => {
					EventEmitter.unsubscribe(
						'BX.Crm.Timeline.Items.FinalSummaryDocuments:onHistoryNodeAdded',
						onHistoryNodeAddedHandler
					);

					BX.onCustomEvent(window, 'OpenEntityDetailTab', ['main']);
					const [timelineDocsNode] = event.data;
					const previousNodePos = {
						x: 0,
						y: 0,
					};
					const documentLinkNodeWatcherId = setInterval(() => {
						const documentLinkNode = timelineDocsNode.querySelector('.crm-entity-stream-content-document-description');
						if (documentLinkNode === null)
						{
							return;
						}

						const nodePos = Dom.getPosition(documentLinkNode);
						if (nodePos.x === 0 && nodePos.y === 0)
						{
							return;
						}

						if (
							nodePos.x !== previousNodePos.x
							|| nodePos.y !== previousNodePos.y
						)
						{
							previousNodePos.x = nodePos.x;
							previousNodePos.y = nodePos.y;
							return;
						}
						clearInterval(documentLinkNodeWatcherId);

						const successDealGuide = this.#createHintToSuccessDocument(documentLinkNode, {
							onClose: () => {
								userOptions.save('crm', 'warehouse-onboarding', 'successDealGuideIsOver', true);
								unsubscribeFromHintClicks();
							},
						});

						const dealContainer = this.#getContentContainer();
						const buttonsContainer = this.#getButtonsContainer();

						const unsubscribeFromHintClicks = () => {
							Event.unbind(dealContainer, 'click', successDealGuide.close.bind(successDealGuide));
							Event.unbind(buttonsContainer, 'click', successDealGuide.close.bind(successDealGuide));
						};

						window.scrollTo(0, nodePos.y - 250);
						successDealGuide.showNextStep();

						Event.bind(buttonsContainer, 'click', successDealGuide.close.bind(successDealGuide));
						setTimeout(() => {
							Event.bind(dealContainer, 'click', successDealGuide.close.bind(successDealGuide));
						}, 3000);
					}, 100);
				}

				EventEmitter.subscribe(
					'BX.Crm.Timeline.Items.FinalSummaryDocuments:onHistoryNodeAdded',
					onHistoryNodeAddedHandler
				);
			}
		}

		EventEmitter.subscribe(
			'Crm.EntityProgress.Saved',
			timelineGuideListener
		);
	}

	#createHintToSuccessDocument(target: HTMLElement, guideEvents: Object = {}): Guide
	{
		const guideText = {
			title: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_SUCCESS_DEAL_GUIDE_TITLE'),
			text: Loc.getMessage('CRM_DEAL_DETAIL_WAREHOUSE_SUCCESS_DEAL_GUIDE_TEXT'),
		};

		return new Guide({
			steps: [
				{
					target: target,
					title: guideText.title,
					text: guideText.text,
					position: 'bottom',
					events: guideEvents,
				},
			],
			onEvents: true
		});
	}
}