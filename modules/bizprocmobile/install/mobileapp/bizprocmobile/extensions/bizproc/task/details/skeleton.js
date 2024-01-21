/**
 * @module bizproc/task/details/skeleton
 */
jn.define('bizproc/task/details/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { Line, Circle } = require('utils/skeleton');
	const { ShimmerView } = require('layout/polyfill');

	class Skeleton extends PureComponent
	{
		render()
		{
			return View(
				{ style: { backgroundColor: AppTheme.colors.base7 } },
				this.renderTask(),
				this.renderComments(),
			);
		}

		renderTask()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						paddingHorizontal: 5,
						paddingVertical: 11,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						minHeight: device.screen.height * 0.85 - 250,
					},
				},
				View(
					{ style: { flexGrow: 1 } },
					View(
						{ style: { marginLeft: 11 } },
						Line(159, 10, 10, 2, 12),
						Line(196, 10, 12, 12, 12),
					),
					View(
						{ style: { marginLeft: 11, marginTop: 5 } },
						Line(307, 4, 10, 3, 12),
						Line(278, 4, 11, 4, 12),
						Line(307, 4, 11, 4, 12),
						Line(175, 4, 11, 11, 12),
					),
					this.renderEditor(),
				),
				this.renderButtons(),
			);
		}

		renderEditor()
		{
			return View(
				{
					style: {
						marginTop: 20,
						marginBottom: 2,
						padding: 11,
						borderRadius: 12,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						backgroundColor: AppTheme.colors.bgContentSecondary,
					},
				},
				Line(74, 4, 11, 2, 12),
				Line(149, 8, 14, 15, 12),
				View({ style: { height: 1, backgroundColor: AppTheme.colors.bgSeparatorPrimary } }),
				Line(44, 4, 15, 1, 12),
				Line(216, 8, 13, 18, 12),
			);
		}

		renderButtons()
		{
			return View(
				{
					style: {
						height: 36,
						marginTop: 22,
						marginBottom: 3,
						flexDirection: 'row',
						alignContent: 'center',
						alignItems: 'center',
						marginHorizontal: 10,
					},
				},
				View(
					{ style: { maxWidth: (device.screen.width) * 0.69 } },
					View(
						{
							style: {
								width: '100%',
								// maxWidth: 375,
								flexDirection: 'row',
								flexWrap: 'no-wrap',
							},
						},
						View(
							{
								style: {
									flexGrow: 1,
									flexShrink: 1,
									flexDirection: 'row',
									marginLeft: 0,
									marginRight: 6,
									justifyContent: 'center',
									height: 36,
									borderRadius: 100,
									borderWidth: 1,
									borderColor: AppTheme.colors.bgSeparatorPrimary,
									padding: 8,
									paddingHorizontal: 16,
									maxWidth: '50%',
									width: '50%',
								},
							},
							Line(60, 8, 6, 6, 20),
						),
						View(
							{
								style: {
									flexGrow: 1,
									flexShrink: 1,
									flexDirection: 'row',
									marginLeft: 6,
									marginRight: 0,
									justifyContent: 'center',
									height: 36,
									borderRadius: 100,
									borderWidth: 1,
									borderColor: AppTheme.colors.bgSeparatorPrimary,
									padding: 8,
									paddingHorizontal: 16,
									maxWidth: '50%',
									width: '50%',
								},
							},
							Line(60, 8, 6, 6, 20),
						),
					),
				),
				View(
					{
						style: {
							marginLeft: 12,
							width: 1,
							height: 19,
							backgroundColor: AppTheme.colors.base7,
						},
					},
				),
				this.renderTimeLineButton(),
			);
		}

		renderTimeLineButton()
		{
			return View(
				{
					style: {
						paddingLeft: 12,
						height: 64,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							height: 36,
							borderRadius: 8,
							borderWidth: 1,
							borderColor: AppTheme.colors.bgSeparatorPrimary,
							padding: 8,
							paddingHorizontal: 10,
							marginRight: 12,
							maxWidth: 157,
						},
					},
					Line(60, 8, 0, 0, 20),
				),
			);
		}

		renderComments()
		{
			return View(
				{ style: { flex: 1 } },
				this.renderCommentBody(),
				this.renderCommentInput(),
			);
		}

		renderCommentBody()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingLeft: 17,
						paddingRight: 17,
						paddingTop: 14,
						paddingBottom: 22,
					},
				},
				this.renderCommentsTitle(),
				View(
					{
						style: {
							marginTop: 19,
							flexDirection: 'row',
							flexWrap: 'no-wrap',
						},
					},
					View(
						{
							style: { width: 32, marginRight: 14 },
						},
						this.renderWhiteCircle(32),
					),
					View(
						{
							style: { flex: 1 },
						},
						this.renderWhiteLine('100%', 96, 0, 8, 12),
						View(
							{
								style: {
									flexDirection: 'row',
									flexWrap: 'no-wrap',
									justifyContent: 'space-between',
									marginLeft: 10,
									width: 120,
								},
							},
							this.renderWhiteLine(54, 8, 0, 0, 20),
							this.renderWhiteLine(47, 8, 0, 0, 20),
						),
					),
				),
			);
		}

		renderCommentsTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
					},
				},
				this.renderWhiteLine('37%', 8, 10, 0, 20),
				View(
					{
						style: {
							flexDirection: 'row',
							borderWidth: 1,
							borderColor: AppTheme.colors.bgContentPrimary,
							borderRadius: 20,
							paddingHorizontal: 15,
							paddingVertical: 9,
							height: 24,
							width: 100,
						},
					},
					this.renderWhiteLine('100%', 6, 0, 0, 20),
				),
			);
		}

		renderCommentInput()
		{
			return View(
				{
					style: {
						height: 52,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						paddingVertical: 8,
					},
				},
				View(
					{
						style: {
							marginLeft: 17,
							marginRight: 30,
							flexDirection: 'row',
							flexWrap: 'no-wrap',
							justifyContent: 'space-between',
							alignItems: 'center',
						},
					},
					View(
						{ style: { width: 22 } },
						Circle(22),
					),
					View(
						{
							style: {
								flex: 1,
								borderColor: AppTheme.colors.base7,
								borderWidth: 1,
								borderRadius: 8,
								paddingHorizontal: 17,
								paddingVertical: 14,
								marginHorizontal: 13,
							},
						},
						Line('75%', 8, 0, 0, 20),
					),
					View(
						{ style: { width: 22 } },
						Circle(22),
					),
				),
			);
		}

		renderWhiteLine(width, height, marginTop, marginBottom, borderRadius)
		{
			const backgroundColor = AppTheme.colors.bgContentPrimary;

			return View(
				{ style: { width, height, marginTop, marginBottom } },
				ShimmerView(
					{ animating: true },
					View(
						{ style: { width, height, borderRadius, backgroundColor } },
					),
				),
			);
		}

		renderWhiteCircle(size)
		{
			return ShimmerView(
				{ animating: true },
				View(
					{
						style: {
							width: size,
							height: size,
							borderRadius: Math.ceil(size / 2),
							backgroundColor: AppTheme.colors.bgContentPrimary,
						},
					},
				),
			);
		}
	}

	module.exports = { Skeleton };
});
