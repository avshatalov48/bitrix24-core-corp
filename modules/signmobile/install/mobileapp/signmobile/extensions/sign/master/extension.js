/**
 * @module sign/master
 */
jn.define('sign/master', (require, exports, module) => {
	const { getTemplateListPromise, getFieldsPromise, } = require('sign/connector');
	const { FieldsInputStep } = require('sign/master/steps/fields-input-step');
	const { MasterWizard } = require('sign/master/master-wizard');
	const { PureComponent } = require('layout/pure-component');
	const { Banner } = require('sign/banner');
	const { Notify } = require('notify');
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @class Master
	 */
	class Master extends PureComponent
	{
		#fromAvaMenu;
		/**
		 * @param {boolean} fromAvaMenu
		 */
		constructor(fromAvaMenu = false)
		{
			super();

			this.#fromAvaMenu = fromAvaMenu;
			this.analyticsEvent = new AnalyticsEvent({
				tool: 'sign',
				category: 'documents',
				type: 'from_employee',
				c_section: this.#fromAvaMenu ? 'ava_menu' : 'sign',
				c_element: 'create_button',
			});
		}

		openMaster(layout = PageManager)
		{
			this.baseLayout = layout;
			Notify.showIndicatorLoading();
			getTemplateListPromise().then(({ data }) => {
				Notify.hideCurrentIndicator();
				if (data.length <= 0)
				{
					this.analyticsEvent.setEvent('show_empty_state');
					this.analyticsEvent.send();
					this.openNoTemplateState(layout);
				}
				else
				{
					this.analyticsEvent.setEvent('click_create_document');
					this.analyticsEvent.send();
					this.openSelector(layout, data)
				}
			});
		}

		openSelector(layout, data)
		{
			layout.openWidget(
				'selector',
				{
					modal: true,
					titleParams: { text: Loc.getMessage('SIGN_MOBILE_MASTER_TEMPLATE_SELECTOR_TITLE') },
					rightButtons: [{ type: 'cross', isCloseButton: true }],
					resizableByKeyboard: true,
					leftButtons: [],
					backgroundColor: Color.bgContentPrimary.toHex(),
					backdrop: {
						forceDismissOnSwipeDown: true,
						hideNavigationBar: false,
						horizontalSwipeAllowed: false,
						mediumPositionPercent: 88,
						onlyMediumPosition: true,
						shouldResizeContent: true,
						swipeAllowed: false,
						swipeContentAllowed: false,
					},
					onReady: (readyLayout) => {
						this.prepareSelector(readyLayout, data);
					},
				},
				layout,
			);
		}

		prepareSelector(selector, data)
		{
			selector.setLeftButtons([]);
			selector.allowMultipleSelection(false);

			const { scopes, items } = this.prepareTemplates(data);
			let scopeId = scopes[0].id;
			selector.setScopes(scopes);
			selector.setItems(items[scopeId]);

			selector.on('onScopeChanged', (selectedScope) => {
				const newScopeId = Number(selectedScope.scope.id);
				if (newScopeId > scopeId)
				{
					selector.setItems(items[newScopeId]);
					scopeId = newScopeId;
				}

				if (newScopeId < scopeId)
				{
					selector.setItems(items[newScopeId]);
					scopeId = newScopeId;
				}
				selector.setQueryText('');
			});

			selector.on('onListFill', (input) => {
				const filteredItems = items[scopeId].filter(
					(item) => item.title.toLowerCase().includes(input.text.toLowerCase()),
				);

				selector.setItems(
					filteredItems.length > 0
						? filteredItems
						: [this.getEmptySearchResult()],
				);
			});

			selector.on('onItemSelected', (selectedData) => {
				const id = Number(selectedData.item.id);
				this.selectedTemplate = data.find((item) => item.id === id);
				this.openWizard(selector);
			});
		}

		openWizard(layout)
		{
			layout.openWidget(
				'layout',
				{
					titleParams: { text: Loc.getMessage('SIGN_MOBILE_MASTER_WIDGET_TITLE') },
					rightButtons: [{ type: 'cross', isCloseButton: true }],
					backgroundColor: Color.bgContentPrimary.toHex(),
					resizableByKeyboard: true,
					leftButtons: [],
					onReady: (wizardLayout) => {
						this.prepareWizard(wizardLayout);
					},
				},
				layout,
			);
		}

		prepareWizard(wizardLayout)
		{
			getFieldsPromise(this.selectedTemplate.uid).then(({ data }) => {
				this.initSteps(data, wizardLayout);
				const masterWizard = new MasterWizard({
					parentLayout: wizardLayout,
					steps: Array.from({ length: this.steps.length }).map((value, index) => index),
					stepForId: this.getStepForId.bind(this),
					useProgressBar: false,
					isNavigationBarBorderEnabled: true,
					showNextStepButtonAtBottom: true,
					ref: (ref) => {
						this.wizard = ref;
					},
				});
				wizardLayout.showComponent(masterWizard);
			});
		}

		openNoTemplateState(layout)
		{
			const banner = new Banner({
				title: Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_TITLE_TEMPLATES_NO_EXIST'),
				description: Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_DESCRIPTION_TEMPLATES_NO_EXIST'),
				imageName: 'error.svg',
			});

			layout.openWidget(
				'layout',
				{
					titleParams: { text: Loc.getMessage('SIGN_MOBILE_MASTER_TEMPLATE_SELECTOR_TITLE') },
					backgroundColor: Color.bgContentPrimary.toHex(),
					backdrop: {
						forceDismissOnSwipeDown: true,
						hideNavigationBar: false,
						horizontalSwipeAllowed: false,
						mediumPositionPercent: 88,
						onlyMediumPosition: true,
						shouldResizeContent: true,
						swipeAllowed: false,
						swipeContentAllowed: false,
					},
					onReady: (readyLayout) => {
						readyLayout.showComponent(banner);
						readyLayout.setLeftButtons([]);
						readyLayout.setRightButtons([{ type: 'cross', callback: () => layout.close() }]);
					},
				},
				layout,
			);
		}

		initSteps(fields, wizardLayout)
		{
			this.analyticsEvent.setP1(fields.providerCodeForAnalytics);
			const props = {
				selectedTemplate: { data: this.selectedTemplate },
				analyticsEvent: this.analyticsEvent,
				fromAvaMenu: this.#fromAvaMenu,
				baseLayout: this.baseLayout,
				layout: wizardLayout,
				totalSteps: 1,
				data: fields,
			};

			const fieldsInputStep = new FieldsInputStep({ ...props, stepNumber: 1 });

			this.steps = [ fieldsInputStep ];
		}

		prepareTemplates(templates)
		{
			const scopes = [];
			const items = {};
			const companyMap = new Map();

			templates.forEach((item) => {
				const company = item.company;
				const companyId = company.id.toString();

				if (!companyMap.has(company.id))
				{
					companyMap.set(company.id, {
						id: companyId,
						title: this.truncateText(company.name),
						taxId: company.taxId,
					});
				}

				if (!items[companyId])
				{
					items[companyId] = [];
				}

				items[companyId].push({
					useLetterImage: false,
					title: item.title,
					id: item.id.toString(),
					type: 'info',
				});
			});

			scopes.push(...companyMap.values());

			return { scopes, items };
		}

		getEmptySearchResult()
		{
			return {
				title: Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_SEARCH_STATE_TITLE'),
				type: 'button',
				unselectable: true,
				hideBottomLine: true,
			};
		}

		getStepForId(stepId)
		{
			return this.steps[stepId] || null;
		}

		truncateText(text, maxLength = 25)
		{
			if (text.length > maxLength)
			{
				return `${text.slice(0, maxLength)}...`;
			}

			return text;
		}

		get layout()
		{
			return this.props.layout || layout || {};
		}
	}
	module.exports = { Master };
});
