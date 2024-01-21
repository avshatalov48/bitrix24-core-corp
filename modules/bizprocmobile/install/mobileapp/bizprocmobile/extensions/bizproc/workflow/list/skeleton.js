/**
 * @module bizproc/workflow/list/skeleton
 */
jn.define('bizproc/workflow/list/skeleton', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { Line, Circle } = require('utils/skeleton');
	class Skeleton extends PureComponent
	{
		render()
		{
			return View(
				{ style: { backgroundColor: AppTheme.colors.bgSecondary } },
				this.renderItem(),
				this.renderItem(),
				this.renderItem(),
			);
		}

		renderItem()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.base8,
						borderRadius: 12,
						paddingVertical: 18,
						paddingHorizontal: 24,
						marginTop: 13,
					},
				},
				this.renderHeader(),
				View(
					{},
					this.renderTitle(),
					this.renderFaces(),
					this.renderButtons(),
				),
			);
		}

		renderHeader()
		{
			return Line('36%', 8, 4, 8, 4);
		}

		renderTitle()
		{
			return View(
				{},
				Line('67%', 12, 6, 6, 4),
				Line('45%', 12, 6, 12, 4),
			);
		}

		renderFaces()
		{
			return View(
				{ style: { marginVertical: 8 } },
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'stretch',
							paddingTop: 5,
							paddingBottom: 16,
						},
					},
					View(
						{
							style: { flex: 1, flexDirection: 'row', alignItems: 'flex-start' },
						},
						this.renderFace(true),
						this.renderFace(false),
						this.renderFace(false),
					),
				),
			);
		}

		renderFace(isFirst)
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						width: isFirst ? 60 : 66,
						alignItems: 'center',
						marginLeft: isFirst ? 0 : 24,
					},
				},
				Line(44, 6, 8, 11, 3),
				Circle(30),
			);
		}

		renderButtons()
		{
			return View(
				{ style: { paddingTop: 8 } },
				View(
					{
						style: {
							width: '100%',
							flexDirection: 'row',
							flexWrap: 'no-wrap',
							// maxWidth: 375,
						},
					},
					View(
						{
							style: {
								flex: 1,
								marginRight: 6,
								maxWidth: '50%',
								width: '50%',
							},
						},
						Line('100%', 36, 0, 8, 122),
					),
					View(
						{
							style: {
								height: 36,
								flex: 1,
								marginLeft: 6,
								borderWidth: 1,
								borderColor: AppTheme.colors.base5,
								maxWidth: '50%',
								borderRadius: 122,
							},
						},
					),
				),
			);
		}
	}

	module.exports = { Skeleton };
});
