/**
 * @module calendar/event-view-form/fields/ics
 */
jn.define('calendar/event-view-form/fields/ics', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');

	const { Link4, LinkMode, Icon } = require('ui-system/blocks/link');
	const { EventAjax } = require('calendar/ajax');

	class IcsField extends PureComponent
	{
		getId()
		{
			return this.props.id;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRequired()
		{
			return false;
		}

		isEmpty()
		{
			return !this.hasDownloadPermissions();
		}

		isHidden()
		{
			return !this.hasDownloadPermissions();
		}

		render()
		{
			return View(
				{
					style: {
						paddingVertical: Indent.XS.toNumber() + Indent.M.toNumber(),
						alignItems: 'center',
					},
				},
				Link4({
					onClick: this.onClick,
					useInAppLink: false,
					testId: 'calendar-event-view-form-ics-button',
					text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_DOWNLOAD_ICS'),
					mode: LinkMode.PLAIN,
					leftIcon: Icon.DOWNLOAD,
					color: Color.base4,
				}),
			);
		}

		onClick = () => {
			const { eventId } = this.props;

			// eslint-disable-next-line default-case
			switch (Application.getPlatform())
			{
				case 'android':
					viewer.openDocument(
						`/bitrix/services/main/ajax.php?action=calendar.api.calendarentryajax.getIcsFile&eventId=${eventId}`,
						'event.ics',
					);
					break;
				case 'ios':
					// eslint-disable-next-line promise/catch-or-return
					EventAjax.getIcsLink({ eventId })
						.then((response) => {
							if (response.status !== 'success')
							{
								return;
							}
							PageManager.openPage({ url: response.data.link });
						});
					break;
			}
		};

		hasDownloadPermissions()
		{
			return this.props.value && this.props.value.view_full;
		}
	}

	module.exports = {
		IcsField: (props) => new IcsField(props),
	};
});
