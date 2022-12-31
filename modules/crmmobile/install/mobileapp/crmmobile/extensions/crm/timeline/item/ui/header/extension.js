/**
 * @module crm/timeline/item/ui/header
 */
jn.define('crm/timeline/item/ui/header', (require, exports, module) => {

	const { VerticalSeparator } = require('crm/timeline/item/ui/header/vertical-separator');
	const { Checkbox } = require('crm/timeline/item/ui/header/checkbox');
	const { PinButton } = require('crm/timeline/item/ui/header/pin-button');
	const { Tag } = require('crm/timeline/item/ui/header/tag');
	const { InfoHelper } = require('crm/timeline/item/ui/header/info-helper');
	const { TimelineItemUserAvatar } = require('crm/timeline/item/ui/user-avatar');
	const { TimelineButtonVisibilityFilter, TimelineButtonSorter } = require('crm/timeline/item/ui/styles');
	const { Moment } = require('utils/date');
	const { dayMonth, longDate, shortTime } = require('utils/date/formats');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { TimeAgo } = require('layout/ui/friendly-date/time-ago');
	const { Haptics } = require('haptics');

	const ChangeStreamButtonTypes = {
		PIN: 'pin',
		UNPIN: 'unpin',
		COMPLETE: 'complete',
	};

	/**
	 * @class TimelineItemHeader
	 */
	class TimelineItemHeader extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.onAction = this.onAction.bind(this);
		}

		get hasIcon()
		{
			return Boolean(this.props.hasIcon);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						flexGrow: 1,
						opacity: BX.prop.getNumber(this.props, 'opacity', 1),
					}
				},
				View(
					{
						style: {
							paddingTop: 12,
							paddingLeft: this.hasIcon ? 12 : 16,
							flexDirection: 'column',
							flex: 1,
						}
					},
					this.renderTitle(),
					this.renderTags(),
					this.hasIcon && this.renderTime(),
				),
				View(
					{},
					View(
						{
							style: {
								flexDirection: 'row',
								flexShrink: 2,
							},
						},
						this.renderUser(),
						this.renderChangeStreamButton(),
					),
				),
			);
		}

		renderTitle()
		{
			if (!this.props.title)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
						marginBottom: 4,
					}
				},
				View(
					{
						onClick: () => this.onAction(this.props.titleAction),
					},
					Text({
						text: this.props.title,
						style: {
							fontSize: 15,
							fontWeight: '500',
							color: '#333333',
							marginRight: 6,
						}
					})
				),
				this.props.infoHelper && new InfoHelper({
					...this.props.infoHelper,
					onAction: this.onAction,
				}),
				!this.hasIcon && View(
					{
						style: {
							flexDirection: 'column',
							justifyContent: 'center',
						}
					},
					this.renderTime()
				),
			);
		}

		renderTags()
		{
			if (!this.props.tags)
			{
				return null;
			}

			const tags = Object.values(this.props.tags)
				.filter((props) => TimelineButtonVisibilityFilter(props, this.props.isReadonly))
				.sort(TimelineButtonSorter)
				.map(props => new Tag(props));

			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
					},
				},
				...tags,
			);
		}

		renderTime()
		{
			if (!this.props.date)
			{
				return null;
			}

			const moment = Moment.createFromTimestamp(this.props.date);
			const dateFormat = moment.inThisYear ? dayMonth() : longDate();
			const style = {
				color: '#A8ADB4',
				fontSize: 13,
				fontWeight: '400',
			};

			return View(
				{
					style: {

					}
				},
				this.props.useFriendlyDate
					? new FriendlyDate({
						moment,
						style,
						defaultFormat: (moment) => {
							const day = moment.format(dateFormat);
							const time = moment.format(shortTime).toLocaleLowerCase(env.languageId);
							return `${day}, ${time}`;
						},
						showTime: true,
						useTimeAgo: true,
						futureAllowed: true,
					})
					: new TimeAgo({
						moment,
						style,
					}),
			);
		}

		renderUser()
		{
			if (!this.props.user)
			{
				return null;
			}

			return TimelineItemUserAvatar(this.props.user);
		}

		renderChangeStreamButton()
		{
			if (!this.props.changeStreamButton)
			{
				return null;
			}

			const { type, action } = this.props.changeStreamButton;
			const props = {
				onClick: () => {
					Haptics.impactMedium();
					this.onAction(action)
				},
			};

			const ChangeStreamButton = () => {
				switch (type)
				{
					case ChangeStreamButtonTypes.COMPLETE:
						return new Checkbox(props);

					case ChangeStreamButtonTypes.PIN:
						return new PinButton({...props, pinned: false});

					case ChangeStreamButtonTypes.UNPIN:
						return new PinButton({...props, pinned: true});
				}
			};

			return View(
				{
					style: {
						flexDirection: 'row',
						marginLeft: 1,
					}
				},
				VerticalSeparator(),
				ChangeStreamButton(),
			);
		}

		onAction(action)
		{
			if (action && this.props.onAction)
			{
				this.props.onAction(action);
			}
		}
	}

	module.exports = { TimelineItemHeader };

});
