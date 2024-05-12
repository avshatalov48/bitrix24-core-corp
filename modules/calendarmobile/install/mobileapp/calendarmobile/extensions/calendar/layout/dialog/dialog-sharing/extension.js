/**
 * @module calendar/layout/dialog/dialog-sharing
 */
jn.define('calendar/layout/dialog/dialog-sharing', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { NotifyManager } = require('notify-manager');
	const { ModelSharingStatus } = require('calendar/model/sharing');
	const { SharingPanel } = require('calendar/layout/sharing-panel');
	const { SharingContext } = require('calendar/sharing');
	const { SharingSwitcher } = require('calendar/layout/sharing-switcher');
	const { SharingEmptyState } = require('calendar/layout/sharing-empty-state');
	const { SharingSettings } = require('calendar/layout/sharing-settings');

	const Status = {
		NONE: 'none',
		WAIT: 'wait',
	};

	/**
	 * @class DialogSharing
	 */
	class DialogSharing extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				status: Status.NONE,
				model: {
					...this.sharing.getModel().getFieldsValues(),
				},
			};

			this.context = this.sharing.getModel().getContext();
			this.readOnly = this.props.readOnly || false;
			this.onSwitcherChangeHandler = this.onSwitcherChangeHandler.bind(this);

			this.layoutWidget = null;
		}

		get sharing()
		{
			return this.props.sharing;
		}

		setLayoutWidget(widget)
		{
			this.layoutWidget = widget;
			this.layoutWidget.setListener((eventName) => {
				if (eventName === 'onViewHidden')
				{
					this.sharing.getModel().clearMembers();
				}
			});
		}

		isCalendarContext()
		{
			return this.context === SharingContext.CALENDAR;
		}

		render()
		{
			return View(
				{
					safeArea: {
						bottom: true,
					},
				},
				// eslint-disable-next-line no-undef
				this.isLoading() && new LoadingScreenComponent(),
				!this.isLoading() && this.renderContent(),
			);
		}

		isLoading()
		{
			return this.state.status === Status.WAIT;
		}

		renderContent()
		{
			return ScrollView(
				{
					style: styles.scrollView,
				},
				View(
					{},
					this.renderSwitcher(),
					this.renderBodyContainer(),
				),
			);
		}

		renderSwitcher()
		{
			return View(
				{
					style: styles.switcher,
				},
				new SharingSwitcher({
					isCalendarContext: this.isCalendarContext(),
					isOn: this.isSharingEnabled(),
					onChange: this.onSwitcherChangeHandler,
					model: this.sharing.getModel(),
				}),
			);
		}

		onSwitcherChangeHandler(status)
		{
			this.handleSwitcher(status);
		}

		handleSwitcher(status)
		{
			NotifyManager.showLoadingIndicator();
			// eslint-disable-next-line promise/catch-or-return
			(status === ModelSharingStatus.ENABLE ? this.sharing.on() : this.sharing.off()).then((response) => {
				if (response.errors && response.errors.length > 0)
				{
					NotifyManager.showErrors(response.errors);
					NotifyManager.hideLoadingIndicator(true);

					return;
				}

				const fields = this.sharing.resolveAjaxResponse(response);

				this.sharing.getModel().setFields(fields);
				// eslint-disable-next-line promise/catch-or-return
				this.setStateModel().then(() => this.props.onSharing(fields));

				NotifyManager.hideLoadingIndicator(true);
			});
		}

		setStateModel()
		{
			return new Promise((resolve) => {
				this.setState({
					model: {
						...this.sharing.getModel().getFieldsValues(),
					},
				}, () => resolve());
			});
		}

		renderBodyContainer()
		{
			if (!this.isSharingEnabled())
			{
				return SharingEmptyState();
			}

			return this.renderBody();
		}

		isSharingEnabled()
		{
			return this.state.model.status === ModelSharingStatus.ENABLE;
		}

		renderBody()
		{
			return View(
				{},
				this.renderSettings(),
				this.renderPanelContainer(),
			);
		}

		renderSettings()
		{
			return new SharingSettings({
				model: this.sharing.getModel(),
				readOnly: this.readOnly,
				customEventEmitter: this.props.customEventEmitter || null,
				layoutWidget: this.layoutWidget,
			});
		}

		renderPanelContainer()
		{
			return View(
				{
					style: styles.panelContainer,
				},
				new SharingPanel({
					model: this.sharing.getModel(),
					layoutWidget: this.layoutWidget,
				}),
			);
		}
	}

	const styles = {
		scrollView: {
			boxSizing: 'border-box',
			flex: 1,
		},
		switcher: {
			borderRadius: 12,
			marginBottom: 15,
			marginTop: 20,
			backgroundColor: AppTheme.colors.accentSoftBlue2,
		},
		panelContainer: {
			borderRadius: 12,
			marginBottom: 15,
		},
	};

	module.exports = { DialogSharing, DialogStatus: Status };
});
