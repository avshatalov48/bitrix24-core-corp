/**
 * @module layout/ui/detail-card/tabs/shimmer/timeline
 */
jn.define('layout/ui/detail-card/tabs/shimmer/timeline', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { ShimmerView } = require('layout/polyfill');
	const { BaseShimmer } = require('layout/ui/detail-card/tabs/shimmer');

	/**
	 * @class TimelineTabShimmer
	 */
	class TimelineTabShimmer extends BaseShimmer
	{
		renderContent()
		{
			return View(
				{
					style: {
						marginTop: 15,
						flexDirection: 'column',
					},
				},
				this.renderDivider(77),
				this.renderCreateReminder(),
				this.renderDivider(41),
				this.renderCallIncoming(),
				this.renderRegularActivity(96, 221),
				this.renderRegularActivity(71, 159),
				this.renderRegularActivity(137, 257),
			);
		}

		renderDivider(width)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'center',
						marginBottom: 16,
					},
				},
				this.renderDividerLine(),
				this.renderDividerBadge(width),
			);
		}

		renderDividerLine()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: AppTheme.colors.base6,
					position: 'absolute',
					top: 10,
				},
			});
		}

		renderDividerBadge(width)
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderRadius: 100,
						paddingHorizontal: 18,
						height: 21,
						flexDirection: 'row',
						justifyContent: 'center',
					},
				},
				this.renderLine(width, 2, 9),
			);
		}

		renderCreateReminder()
		{
			return View(
				{
					style: {
						borderRadius: 12,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						padding: 12,
						marginBottom: 18,
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							padding: 8,
							flexDirection: 'column',
							justifyContent: 'center',
						},
					},
					this.renderCircle(22, 3, 0),
				),
				View(
					{
						style: {
							flex: 1,
						},
					},
					this.renderLine(76, 6, 8),
					this.renderLine(222, 3, 13),
					this.renderLine(55, 3, 13, 5),
				),
			);
		}

		renderCallIncoming()
		{
			return View(
				{
					style: {
						borderRadius: 12,
						padding: 0,
						marginBottom: 16,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					this.renderCallIncomingLogo(),
					this.renderCallIncomingHeader(),
				),
				this.renderCallIncomingContent(),
				this.renderCallIncomingFooter(),
			);
		}

		renderCallIncomingLogo()
		{
			const { animating } = this.props;

			return View(
				{
					style: {
						width: 80,
						height: 80,
						marginTop: 12,
						marginLeft: 10,
						marginBottom: 12,
					},
				},
				ShimmerView(
					{ animating },
					View(
						{
							style: {
								width: 80,
								height: 80,
								borderRadius: 12,
								backgroundColor: AppTheme.colors.base6,
							},
						},
					),
				),
			);
		}

		renderCallIncomingHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						flexGrow: 1,
					},
				},
				View(
					{
						style: {
							paddingTop: 12,
							paddingLeft: 12,
							flexDirection: 'column',
							flex: 1,
						},
					},
					this.renderCallIncomingTitle(),
					this.renderCallIncomingTag(),
					this.renderCallIncomingTime(),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							height: 38,
							marginTop: 3,
						},
					},
					this.renderCallIncomingUser(),
				),
			);
		}

		renderCallIncomingTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
					},
				},
				this.renderLine(145, 8, 6),
			);
		}

		renderCallIncomingTag()
		{
			return this.renderLine(49, 20, 9);
		}

		renderCallIncomingTime()
		{
			return this.renderLine(26, 3, 15);
		}

		renderCallIncomingUser()
		{
			const { animating } = this.props;

			return View(
				{
					style: {
						width: 38,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				ShimmerView(
					{ animating },
					View({
						style: {
							width: 20,
							height: 20,
							borderRadius: 20,
							backgroundColor: AppTheme.colors.base6,
						},
					}),
				),
			);
		}

		renderCallIncomingContent()
		{
			return View(
				{
					style: {
						paddingHorizontal: 12,
						paddingTop: 0,
						paddingBottom: 9,
						flexDirection: 'row',
						flexWrap: 'wrap',
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							width: 130,
						},
					},
					this.renderLine(43, 3, 6),
					this.renderLine(71, 3, 26),
				),
				View(
					{
						style: {
							flexDirection: 'column',
							flex: 1,
						},
					},
					this.renderLine(181, 6, 6),
					this.renderLine(123, 6, 26),
				),
			);
		}

		renderCallIncomingFooter()
		{
			return View(
				{
					style: {
						padding: 12,
						flexDirection: 'row',
						flexWrap: 'wrap',
						justifyContent: 'space-between',
					},
				},
				this.renderLine(177, 39, 11),
				View(
					{
						style: {
							flexDirection: 'row',
							paddingTop: 20,
						},
					},
					this.renderCircle(17),
					View(
						{
							style: {
								marginLeft: 18,
							},
						},
						this.renderCircle(17),
					),
				),
			);
		}

		renderRegularActivity(titleWidth, textWidth)
		{
			return View(
				{
					style: {
						borderRadius: 12,
						padding: 0,
						marginBottom: 16,
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					this.renderRegularActivityHeader(titleWidth, textWidth),
				),
			);
		}

		renderRegularActivityHeader(titleWidth, textWidth)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						flexGrow: 1,
					},
				},
				View(
					{
						style: {
							paddingTop: 17,
							paddingLeft: 12,
							flexDirection: 'column',
							flex: 1,
						},
					},
					this.renderRegularActivityTitle(titleWidth),
					this.renderRegularActivityText(textWidth),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							height: 38,
							marginTop: 3,
						},
					},
					this.renderRegularActivityUser(),
				),
			);
		}

		renderRegularActivityTitle(width)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
					},
				},
				this.renderLine(width, 8),
			);
		}

		renderRegularActivityText(width)
		{
			return this.renderLine(width, 6, 22, 20);
		}

		renderRegularActivityUser()
		{
			const { animating } = this.props;

			return View(
				{
					style: {
						width: 38,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				ShimmerView(
					{ animating },
					View({
						style: {
							width: 20,
							height: 20,
							borderRadius: 20,
							backgroundColor: AppTheme.colors.base6,
						},
					}),
				),
			);
		}
	}

	module.exports = { TimelineTabShimmer };
});

