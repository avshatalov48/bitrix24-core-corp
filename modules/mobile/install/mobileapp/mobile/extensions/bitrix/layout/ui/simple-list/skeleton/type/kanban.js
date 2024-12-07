/**
 * @module layout/ui/simple-list/skeleton/type/kanban
 */
jn.define('layout/ui/simple-list/skeleton/type/kanban', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const DEFAULT_LENGTH = 2;

	/**
	 * @class Kanban
	 */
	class Kanban extends LayoutComponent
	{
		get colors()
		{
			return this.props.showAirStyle ? AppTheme.realColors : AppTheme.colors;
		}

		render()
		{
			return View(
				{
					style: {
						width: '100%',
						marginTop: this.props.fullScreen ? 20 : 0,
					},
				},

				...this.renderItems(),
			);
		}

		renderItems()
		{
			const length = (this.props.length || DEFAULT_LENGTH);

			return new Array(length).fill(this.renderItem());
		}

		renderItem()
		{
			const { itemParams } = this.props;
			const hasStageField = (itemParams && itemParams.useStageFieldInSkeleton);

			return View(
				{
					style: {
						backgroundColor: this.colors.bgContentPrimary,
						paddingTop: 23,
						paddingLeft: 25,
						paddingBottom: 25,
						marginBottom: 12,
						borderRadius: 12,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'flex-start',
							justifyContent: 'space-between',
							marginBottom: 17,
							paddingRight: 3,
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						this.renderLine(190, 10),
						this.renderLine(130, 5, 14),
					),
					this.renderCircle(true),
				),
				hasStageField && View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'flex-start',
							marginTop: 7,
							marginBottom: 24,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								marginRight: 6,
							},
						},
						this.renderLine(170, 34),
						Image({
							style: {
								width: 25,
								height: 34,
								marginLeft: -10,
							},
							svg: {
								content: `<svg width="25" height="34" viewBox="0 0 25 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 6C0 2.68629 2.68629 0 6 0H12.1383C14.2817 0 16.2623 1.1434 17.3342 2.99956L24.5526 15.4998C25.0887 16.4281 25.0887 17.5719 24.5526 18.5002L17.3342 31.0004C16.2623 32.8566 14.2817 34 12.1383 34H6C2.68629 34 0 31.3137 0 28V6Z" fill="${this.colors.base6}"/></svg>`,
							},
						}),
					),
					View(
						{
							style: {
								flexDirection: 'row',
								marginLeft: 5,
							},
						},
						this.renderLine('100%', 34),
					),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
							marginBottom: 26,
							marginRight: 3,
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						this.renderLine(44, 3, 6, 17),
						this.renderLine(80, 5),
					),
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						this.renderCircle(true, 14),
					),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'flex-start',
							justifyContent: 'space-between',
							paddingRight: 23,
							marginTop: -10,
							marginRight: 1,
						},
					},
					this.renderLine(35, 3),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'flex-start',
							justifyContent: 'space-between',
						},
					},
					View(
						{
							style: {
								flexDirection: 'column',
							},
						},
						this.renderLine(184, 5, 18, 15),
						this.renderLine(132, 5, 0, 39),
						this.renderLine(44, 3, 0, 15),
						this.renderLine(75, 5, 0, 39),
						this.renderLine(133, 3, 0, 15),
						this.renderLine(222, 5),
					),
					View(
						{
							style: {
								flexDirection: 'column',
								marginTop: 14,
								marginRight: 3,
							},
						},
						this.renderCircle(false),
						this.renderCircle(false, -2),
						this.renderCircle(false, -2),
					),
				),
			);
		}

		renderCircle(active = false, marginTop = 0)
		{
			return View(
				{
					style: {
						marginRight: 28,
						marginVertical: 17,
						marginTop,
					},
				},
				View({
					style: {
						height: 14,
						width: 14,
						borderRadius: 7,
						backgroundColor: active ? this.colors.base6 : this.colors.base7,
						position: 'relative',
					},
				}),
			);
		}

		renderLine(width, height, marginTop = 0, marginBottom = 0)
		{
			const style = {
				width,
				height,
				borderRadius: 3,
				backgroundColor: this.colors.base6,
			};

			if (marginTop)
			{
				style.marginTop = marginTop;
			}

			if (marginBottom)
			{
				style.marginBottom = marginBottom;
			}

			return View({
				style,
			});
		}
	}

	module.exports = { Kanban };
});
