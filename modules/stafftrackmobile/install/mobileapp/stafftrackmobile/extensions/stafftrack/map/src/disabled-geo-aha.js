/**
 * @module stafftrack/map/disabled-geo-aha
 */
jn.define('stafftrack/map/disabled-geo-aha', (require, exports, module) => {
	const { Loc } = require('loc');
	const { AhaMoment } = require('ui-system/popups/aha-moment');
	const { BaseEnum } = require('utils/enums/base');
	const { NotifyManager } = require('notify-manager');

	const { checkInAhaIcon } = require('stafftrack/ui');
	const { SettingsPage } = require('stafftrack/check-in/pages/settings');
	const { FeatureAjax } = require('stafftrack/ajax');

	class DisabledGeoUserEnum extends BaseEnum
	{
		static REGULAR = new DisabledGeoUserEnum('REGULAR', 'regular');
		static ADMIN = new DisabledGeoUserEnum('ADMIN', 'admin');
	}

	const showDisabledGeoAhaMoment = (props) => {
		AhaMoment.show({
			testId: `stafftrack-disabled-geo-aha-${props.type.getValue()}`,
			targetRef: props.targetRef,
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
			description: getDescription(props.type),
			buttonText: getButtonText(props.type),
			onClick: getOnClick(props),
		});
	};

	const getDescription = (type) => {
		if (type === DisabledGeoUserEnum.ADMIN)
		{
			return Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_ADMIN_DESCRIPTION');
		}

		return Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_REGULAR_DESCRIPTION');
	};

	const getButtonText = (type) => {
		if (type === DisabledGeoUserEnum.ADMIN)
		{
			return Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_ADMIN_BUTTON');
		}

		return Loc.getMessage('M_STAFFTRACK_MAP_GEO_ENABLE_AHA_REGULAR_BUTTON');
	};

	const getOnClick = (props) => {
		const { type, layoutWidget } = props;

		if (type === DisabledGeoUserEnum.ADMIN)
		{
			return () => {
				(new SettingsPage({ isAdmin: true }).show(layoutWidget));
			};
		}

		return async () => {
			void NotifyManager.showLoadingIndicator();

			const result = await FeatureAjax.createDepartmentHeadChat('enable_check_in_geo');

			if (result?.data?.chatId)
			{
				void NotifyManager.hideLoadingIndicator(true);

				const dialogId = `chat${result.data.chatId}`;
				BX.postComponentEvent('ImMobile.Messenger.Dialog:open', [{ dialogId }], 'im.messenger');

				layoutWidget.close();
			}
			else
			{
				void NotifyManager.hideLoadingIndicator(false);
			}
		};
	};

	module.exports = { DisabledGeoUserEnum, showDisabledGeoAhaMoment };
});
