/**
 * @module crm/phone-drawer
 */
jn.define('crm/phone-drawer', (require, exports, module) => {
	const { Loc } = require('loc');
	const { getCountryCode } = require('utils/phone');
	const { NotifyManager } = require('notify-manager');
	const { PhoneField } = require('layout/ui/fields/phone');
	const { WarningBlock } = require('layout/ui/warning-block');
	const { handleErrors } = require('crm/error');
	const { TypeId } = require('crm/type');
	const { get } = require('utils/object');

	class PhoneDrawer extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.contactId = props.contactId;

			this.phoneFieldRef = null;

			this.savePhoneToContact = this.savePhoneToContact.bind(this);
			this.onPhoneFieldChange = this.onPhoneFieldChange.bind(this);
			this.setPhoneFieldRef = this.setPhoneFieldRef.bind(this);

			this.state = {
				phone: '',
				countryCode: getCountryCode(),
				saving: false,
			};
		}

		show(parentWidget = PageManager)
		{
			const widgetParams = {
				backdrop: {
					swipeAllowed: false,
					forceDismissOnSwipeDown: false,
					horizontalSwipeAllowed: false,
					mediumPositionHeight: device.screen.width > 375 ? 220 : 260,
					showOnTop: false,
					adoptHeightByKeyboard: true,
					shouldResizeContent: true,
				},
			};
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', widgetParams)
					.then((layoutWidget) => {
						this.layoutWidget = layoutWidget;

						layoutWidget.setTitle({ text: this.props.title });
						layoutWidget.enableNavigationBarBorder(false);
						layoutWidget.setRightButtons(this.getRightButtons());

						layoutWidget.showComponent(this);

						resolve();
					})
					.catch(reject)
				;
			});
		}

		getRightButtons()
		{
			return [
				{
					name: (
						this.state.saving
							? Loc.getMessage('MCRM_PHONE_DRAWER_SAVING')
							: Loc.getMessage('MCRM_PHONE_DRAWER_SAVE')
					),
					type: 'text',
					color: this.state.saving ? '#8EBAD4' : '#0B66C3',
					callback: this.savePhoneToContact,
				},
			];
		}

		onPhoneFieldChange(data)
		{
			this.setState({
				phone: data.phoneNumber,
				countryCode: data.countryCode,
			});
		}

		savePhoneToContact()
		{
			if (this.phoneFieldRef && !this.phoneFieldRef.validate())
			{
				return;
			}

			const data = {
				entityId: this.contactId,
				entityTypeId: TypeId.Contact,
				phone: this.state.phone,
				countryCode: this.state.countryCode,
			};

			NotifyManager.showLoadingIndicator();

			this.setState({ saving: true }, () => {
				this.layoutWidget.setRightButtons(this.getRightButtons());
				const action = 'crmmobile.Phone.addToContact';
				BX.ajax.runAction(action, { json: data })
					.then(() => {
						BX.postComponentEvent('PhoneDrawer::onSave', [data]);
						this.layoutWidget.close(() => {
							NotifyManager.hideLoadingIndicator(true);
							if (this.props.onSuccess)
							{
								this.props.onSuccess(data);
							}
						});
					})
					.catch((response) => {
						this.setState({ saving: false }, () => {
							this.layoutWidget.setRightButtons(this.getRightButtons());
							void handleErrors(response);
						});
					})
				;
			});
		}

		setPhoneFieldRef(ref)
		{
			this.phoneFieldRef = ref;
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#EEF2F4',
					},
				},
				this.renderWarningBlock(),
				View(
					{
						style: {
							marginTop: 12,
							padding: 16,
							paddingTop: 0,
							paddingBottom: 0,
							backgroundColor: '#FFFFFF',
							borderRadius: 12,
						},
					},
					FieldsWrapper({
						fields: this.getFields(),
					}),
				),
			);
		}

		renderWarningBlock()
		{
			const params = {
				description: this.getWarningBlockDescription(),
				backgroundColor: this.getWarningBlockBackgroundColor(),
			}

			const icon = this.getWarningBlockIcon();
			if (icon)
			{
				params.icon = icon;
			}

			if (this.isShowWarningBlockTitle())
			{
				params.title = Loc.getMessage('MCRM_PHONE_DRAWER_WARNING_TITLE');
			}

			return new WarningBlock(params);
		}

		getWarningBlockDescription()
		{
			return get(this.props, 'warningBlock.text', Loc.getMessage('MCRM_PHONE_DRAWER_WARNING_TEXT'));
		}

		getWarningBlockBackgroundColor()
		{
			return get(this.props, 'warningBlock.backgroundColor', '#FEF3B8');
		}

		getWarningBlockIcon()
		{
			return get(this.props, 'warningBlock.icon');
		}

		isShowWarningBlockTitle()
		{
			return get(this.props, 'warningBlock.showTitle', true);
		}

		getFields()
		{
			return [
				PhoneField({
					title: Loc.getMessage('MCRM_PHONE_DRAWER_FIELD'),
					value: {
						phoneNumber: this.state.phone,
						countryCode: this.state.countryCode,
					},
					required: true,
					focus: true,
					onChange: this.onPhoneFieldChange,
					ref: this.setPhoneFieldRef,
				}),
			];
		}
	}

	module.exports = { PhoneDrawer };
});
