/**
 * @module layout/ui/copilot-role-selector
 */
jn.define('layout/ui/copilot-role-selector', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { CopilotRoleSelectorListFactory } = require('layout/ui/copilot-role-selector/src/list-factory');
	const { ListType, ListItemType } = require('layout/ui/copilot-role-selector/src/types');

	/**
	 * @class CopilotRoleSelector
	 */
	class CopilotRoleSelector extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.selectedRole = null;
			this.listItemClickHandler = this.listItemClickHandler.bind(this);
		}

		listItemClickHandler(item, type, universalRoleItemData = null)
		{
			switch (type)
			{
				case ListItemType.INDUSTRY:
					CopilotRoleSelector.open({
						parentLayout: this.props.layout,
						isControlRoot: false,
						showOpenFeedbackItem: this.props.showOpenFeedbackItem,
						skipButtonText: this.props.skipButtonText,
						enableUniversalRole: this.props.enableUniversalRole,
						universalRoleItemData,
					})
						.then((selectedContext) => {
							if (this.props.contextSelectedHandler)
							{
								this.props.contextSelectedHandler(selectedContext);
							}
						})
						.catch(console.error);
					break;
				case ListItemType.ROLE:
				case ListItemType.UNIVERSAL_ROLE:
					this.selectedRole = item;
					if (this.props.contextSelectedHandler)
					{
						this.props.contextSelectedHandler(this.getSelectedContext());
					}
					break;
				default:
			}
		}

		getSelectedContext()
		{
			return {
				industry: this.props.selectedIndustry,
				role: this.selectedRole,
			};
		}

		render()
		{
			const factoryCreateProps = {
				listItemClickHandler: this.listItemClickHandler,
				showOpenFeedbackItem: this.props.showOpenFeedbackItem,
				enableUniversalRole: this.props.enableUniversalRole,
				universalRoleItemData: this.props.universalRoleItemData,
			};

			return CopilotRoleSelectorListFactory.create(ListType.ROLES, factoryCreateProps);
		}

		/**
		 * @public
		 * @function open
		 * @params {object} params
		 * @params {layout} [params.parentLayout = null]
		 * @params {boolean} [params.isControlRoot = true]
		 * @params {boolean} [params.closeLayoutAfterContextSelection = true]
		 * @params {boolean} [params.showOpenFeedbackItem = true]
		 * @params {object} [params.openWidgetConfig = {}]
		 * @params {string} [params.skipButtonText = null]
		 * @params {boolean} [params.enableUniversalRole = true]
		 * @params {object} [params.universalRoleItemData = null]
		 * @return Promise
		 */
		static open({
			parentLayout = null,
			isControlRoot = true,
			closeLayoutAfterContextSelection = true,
			showOpenFeedbackItem = true,
			openWidgetConfig = {},
			skipButtonText = null,
			enableUniversalRole = true,
			universalRoleItemData = null,
		})
		{
			return new Promise((resolve) => {
				const config = {
					enableNavigationBarBorder: false,
					titleParams: {
						text: Loc.getMessage('COPILOT_CONTEXT_STEPPER_CHOOSE_ROLE_TITLE'),
					},
					...openWidgetConfig,
					onReady: (readyLayout) => {
						readyLayout.enableNavigationBarBorder(config.enableNavigationBarBorder);
						const stepperInstance = new CopilotRoleSelector({
							layout: readyLayout,
							contextSelectedHandler: (selectedContext) => {
								resolve(selectedContext);
								if (isControlRoot && closeLayoutAfterContextSelection)
								{
									readyLayout.close();
								}
							},
							showOpenFeedbackItem,
							skipButtonText,
							enableUniversalRole,
							universalRoleItemData,
						});
						readyLayout.setRightButtons([
							{
								name: skipButtonText ?? Loc.getMessage('COPILOT_CONTEXT_STEPPER_SKIP_BUTTON_TEXT'),
								type: 'text',
								color: AppTheme.colors.accentMainLinks,
								callback: () => {
									resolve(stepperInstance.getSelectedContext());
									if (isControlRoot && closeLayoutAfterContextSelection)
									{
										readyLayout.close();
									}
								},
							},
						]);
						readyLayout.showComponent(stepperInstance);
					},
				};

				if (parentLayout)
				{
					parentLayout.openWidget('layout', config);

					return;
				}

				PageManager.openWidget('layout', config);
			});
		}
	}

	module.exports = { CopilotRoleSelector };
});
