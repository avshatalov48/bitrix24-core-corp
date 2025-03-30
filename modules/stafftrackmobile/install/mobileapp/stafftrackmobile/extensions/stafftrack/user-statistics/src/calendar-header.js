/**
 * @module stafftrack/user-statistics/calendar-header
 */
jn.define('stafftrack/user-statistics/calendar-header', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Component, Indent } = require('tokens');
	const { Text3 } = require('ui-system/typography/text');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { ShimmerView } = require('layout/polyfill');

	const { PureComponent } = require('layout/pure-component');

	class CalendarHeader extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isLoading: props.isLoading,
				shiftCount: props.shiftCount,
			};
		}

		get user()
		{
			return this.props.user ?? {};
		}

		setShiftCount(shiftCount)
		{
			this.setState({ shiftCount });
		}

		setLoading(isLoading)
		{
			this.setState({ isLoading });
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						borderWidth: 1,
						borderColor: Color.accentSoftBlue1.toHex(),
						backgroundColor: Color.accentSoftBlue3.toHex(),
						borderTopLeftRadius: Component.cardCorner.toNumber(),
						borderTopRightRadius: Component.cardCorner.toNumber(),
						paddingTop: Component.cardPaddingT.toNumber(),
						paddingHorizontal: Component.cardPaddingLr.toNumber(),
						paddingBottom: Component.cardPaddingB.toNumber(),
					},
				},
				this.renderAvatar(),
				this.renderTitle(),
			);
		}

		renderAvatar()
		{
			return View(
				{
					style: {
						marginRight: Indent.M.toNumber(),
					},
				},
				View(
					{
						style: {
							position: 'relative',
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					Image({
						style: {
							height: 40,
							width: 40,
						},
						svg: {
							content: gradientEllipse,
						},
					}),
					Avatar({
						testId: 'stafftrack-user-statistics-avatar',
						id: this.user.id,
						name: this.user.name,
						uri: this.user.avatar,
						size: 32,
						style: {
							position: 'absolute',
						},
					}),
				),
			);
		}

		renderTitle()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				Text3({
					text: this.user.name,
					color: Color.base1,
					accent: true,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				this.renderSkeleton(),
				!this.state.isLoading && Text3({
					testId: 'stafftrack-user-statistics-checkin-count',
					text: this.getTitleText(),
					color: Color.accentMainPrimary,
					accent: true,
				}),
			);
		}

		renderSkeleton()
		{
			return ShimmerView(
				{
					animating: true,
					style: {
						display: this.state.isLoading ? 'flex' : 'none',
						marginTop: Indent.XS.toNumber(),
						marginRight: Indent.XS.toNumber(),
						width: '80%',
					},
				},
				View(
					{
						style: {
							height: 16,
							borderRadius: Component.elementLCorner.toNumber(),
							backgroundColor: Color.accentMainPrimary.toHex(),
						},
					},
				),
			);
		}

		getTitleText()
		{
			const { shiftCount } = this.state;

			if (shiftCount === 0)
			{
				return Loc.getMessage('M_STAFFTRACK_USER_STATISTICS_COUNT_CHECKINS_IN_MONTHS_NONE');
			}

			return Loc.getMessagePlural(
				'M_STAFFTRACK_USER_STATISTICS_COUNT_CHECKINS_IN_MONTHS',
				shiftCount,
				{
					'#COUNT#': shiftCount,
				},
			);
		}
	}

	const gradientEllipse = '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="19.3" stroke="url(#paint0_linear_5422_72107)" stroke-width="1.4"/><defs><linearGradient id="paint0_linear_5422_72107" x1="5.25424" y1="0.847457" x2="21.0169" y2="23.5593" gradientUnits="userSpaceOnUse"><stop stop-color="#4EFFAD"/><stop offset="1" stop-color="#0075FF"/></linearGradient></defs></svg>';

	module.exports = { CalendarHeader };
});
