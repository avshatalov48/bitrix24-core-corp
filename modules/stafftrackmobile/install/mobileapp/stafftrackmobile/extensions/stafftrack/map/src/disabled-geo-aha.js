/**
 * @module stafftrack/map/disabled-geo-aha
 */
jn.define('stafftrack/map/disabled-geo-aha', (require, exports, module) => {
	const { Loc } = require('loc');
	const { AhaMoment } = require('ui-system/popups/aha-moment');
	const { NotifyManager } = require('notify-manager');

	const { checkInAhaIcon } = require('stafftrack/ui');
	const { SettingsPage } = require('stafftrack/check-in/pages/settings');
	const { FeatureAjax } = require('stafftrack/ajax');
	const { DisabledGeoUserEnum } = require('stafftrack/map/disabled-geo-user-enum');

	class DisabledGeoAha
	{
		constructor(props)
		{
			this.props = props;
		}

		get layoutWidget()
		{
			return this.props.layoutWidget;
		}

		get targetRef()
		{
			return this.props.targetRef;
		}

		get type()
		{
			return this.props.type;
		}

		show()
		{
			AhaMoment.show({
				testId: `stafftrack-disabled-geo-aha-${this.type.getValue()}`,
				targetRef: this.targetRef,
				image: Image(
					{
						svg: {
							content: checkInAhaIcon,
						},
						style: {
							width: '100%',
							height: 78,
						},
						resizeMode: 'contain',
					},
				),
				title: Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_TITLE'),
				description: this.type === DisabledGeoUserEnum.ADMIN
					? Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_ADMIN_DESCRIPTION')
					: Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_REGULAR_DESCRIPTION'),
				buttonText: this.type === DisabledGeoUserEnum.ADMIN
					? Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_ADMIN_BUTTON')
					: Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_REGULAR_BUTTON'),
				onClick: this.type === DisabledGeoUserEnum.ADMIN
					? this.openSettingsPage
					: this.openManagerChat
				,
			});
		}

		openSettingsPage = () => {
			SettingsPage.show({
				isAdmin: true,
				parentLayout: this.layoutWidget,
			});
		};

		openManagerChat = async () => {
			void NotifyManager.showLoadingIndicator();

			const result = await FeatureAjax.createDepartmentHeadChat('enable_check_in_geo');

			if (result?.data?.chatId)
			{
				void NotifyManager.hideLoadingIndicator(true);

				const dialogId = `chat${result.data.chatId}`;
				BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{ dialogId }], 'im.messenger');

				this.layoutWidget.close();
			}
			else
			{
				void NotifyManager.hideLoadingIndicator(false);
			}
		};
	}

	module.exports = { DisabledGeoAha };
});
