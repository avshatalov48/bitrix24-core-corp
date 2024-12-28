/**
 * @module calendar/layout/dialog/dialog-sharing
 */
jn.define('calendar/layout/dialog/dialog-sharing', (require, exports, module) => {
	const { NotifyManager } = require('notify-manager');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');

	const { ModelSharingStatus } = require('calendar/model/sharing');
	const { SharingPanel } = require('calendar/layout/sharing-panel');
	const { SharingContext } = require('calendar/sharing');
	const { SharingSwitcher } = require('calendar/layout/sharing-switcher');
	const { SharingEmptyState } = require('calendar/layout/sharing-empty-state');
	const { SharingSettings } = require('calendar/layout/sharing-settings');

	/**
	 * @class DialogSharing
	 */
	class DialogSharing extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				model: {
					...this.sharing.getModel().getFieldsValues(),
				},
			};

			this.sharingSettings = null;

			this.layoutWidget = props.layoutWidget || PageManager;
			this.layoutWidget.setListener((eventName) => {
				if (eventName === 'onViewHidden')
				{
					this.sharing.getModel().clearMembers();
				}
			});
		}

		get readOnly()
		{
			return this.props.readOnly || false;
		}

		get sharing()
		{
			return this.props.sharing;
		}

		isCalendarContext()
		{
			return this.sharing.getModel().getContext() === SharingContext.CALENDAR;
		}

		render()
		{
			return View(
				{
					safeArea: {
						bottom: this.isCalendarContext(),
					},
				},
				this.isLoading() && new LoadingScreenComponent(),
				!this.isLoading() && this.renderContent(),
			);
		}

		isLoading()
		{
			return !this.sharing.model.userInfo;
		}

		renderContent()
		{
			return View(
				{},
				this.renderSwitcher(),
				this.renderBodyContainer(),
			);
		}

		renderSwitcher()
		{
			return View(
				{
					style: styles.switcher,
				},
				new SharingSwitcher({
					onSettingsClick: this.props.onSettingsClick,
					isCalendarContext: this.isCalendarContext(),
					isOn: this.isSharingEnabled(),
					onChange: this.onSwitcherChangeHandler,
					model: this.sharing.getModel(),
				}),
			);
		}

		onSwitcherChangeHandler = (status) => {
			this.handleSwitcher(status);
		};

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

				this.setState({
					model: {
						...this.sharing.getModel().getFieldsValues(),
					},
				}, () => this.props.onSharing(fields));

				NotifyManager.hideLoadingIndicator(true);
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
				this.isCalendarContext() && this.renderPanelContainer(),
			);
		}

		renderSettings()
		{
			this.sharingSettings = new SharingSettings({
				model: this.sharing.getModel(),
				readOnly: this.readOnly,
				customEventEmitter: this.props.customEventEmitter || null,
				layoutWidget: this.layoutWidget,
			});

			return this.sharingSettings;
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
		switcher: {
			borderRadius: 12,
			marginBottom: 10,
			marginTop: 10,
		},
		panelContainer: {
			borderRadius: 12,
			marginBottom: 15,
		},
	};

	module.exports = { DialogSharing };
});
