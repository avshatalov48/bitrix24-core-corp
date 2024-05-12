/**
 * @module bizproc/workflow/faces/column-view
 * */
jn.define('bizproc/workflow/faces/column-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');
	const { PureComponent } = require('layout/pure-component');
	const { AvatarStack } = require('layout/ui/user/avatar-stack');

	class WorkflowFacesColumn extends PureComponent
	{
		get outerColumnStyles()
		{
			return Type.isPlainObject(this.props.columnStyles) ? this.props.columnStyles : {};
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						alignItems: 'center',
						marginLeft: 4,
						minWidth: 61,
						flexShrink: 1,
						...this.outerColumnStyles,
					},
					testId: this.props.testId,
				},
				this.renderLabel(),
				this.renderPhotos(),
				this.renderTime(),
			);
		}

		renderLabel()
		{
			if (Type.isNil(this.props.label))
			{
				return View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: AppTheme.colors.base7,
							width: 42,
							height: 6,
							marginBottom: 10,
							marginTop: 4,
						},
					},
				);
			}

			return Text({
				text: this.props.label,
				style: {
					fontWeight: '400',
					fontSize: 11,
					color: AppTheme.colors.base4,
					marginBottom: 4,
				},
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		renderPhotos()
		{
			if (Type.isNil(this.props.avatars))
			{
				const icon = icons[this.props.status];

				if (Type.isStringFilled(icon))
				{
					return View(
						{
							style: {},
						},
						View(
							{
								style: {
									width: 33,
									height: 33,
									backgroundColor: AppTheme.colors.bgContentPrimary,
									borderRadius: 16,
									marginTop: 4,
									marginBottom: 2,
									borderColor: AppTheme.colors.base6,
									borderWidth: 1,
									justifyContent: 'center',
									alignItems: 'center',
								},
							},
							Image({
								style: { width: 18, height: 18 },
								svg: { content: icon },
							}),
						),
					);
				}

				return View(
					{
						style: {},
					},
					View(
						{
							style: {
								width: 33,
								height: 33,
								backgroundColor: AppTheme.colors.base7,
								borderRadius: 16,
								marginBottom: 2,
							},
						},
					),
				);
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						paddingTop: 4,
						paddingBottom: 2,
					},
				},
				new AvatarStack({
					avatars: this.props.avatars,
					reverse: false,
					styles: {
						avatar: {
							borderWidth: 1,
							borderColor: AppTheme.colors.bgContentPrimary,
						},
					},
					size: 33,
				}),
				this.renderStatus(),
			);
		}

		renderStatus()
		{
			const icon = icons[this.props.status];

			if (Type.isStringFilled(icon))
			{
				return View(
					{
						style: {
							marginLeft: -12,
							marginTop: -4,
						},
					},
					Image({
						style: {
							width: 18,
							height: 18,
							alignSelf: 'center',
						},
						svg: { content: icon },
					}),
				);
			}

			return null;
		}

		renderTime()
		{
			if (Type.isNil(this.props.time))
			{
				return View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: AppTheme.colors.base7,
							width: 32,
							height: 6,
							marginTop: 8,
						},
					},
				);
			}

			return Text({
				text: this.props.time,
				style: {
					fontSize: 11,
					fontWeight: '400',
					color: AppTheme.colors.base4,
					marginTop: 4,
				},
				ellipsize: 'end',
				numberOfLines: 1,
			});
		}
	}

	const icons = {
		accept: (() => {
			const fillBack = AppTheme.colors.base8;
			const fillMiddle = AppTheme.colors.baseWhiteFixed;
			const fillFore = AppTheme.colors.accentMainSuccess;

			return `
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g clip-path="url(#clip0_1651_44333)">
					<circle cx="9" cy="9" r="9" fill="${fillBack}"/>
					<circle cx="9" cy="9" r="7" fill="${fillMiddle}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M17 9C17 13.4183 13.4183 17 9 17C4.58172 17 1 13.4183 1 9C1 4.58172 4.58172 1 9 1C13.4183 1 17 4.58172 17 9ZM7.73785 9.87379L5.88808 7.95795L4.43469 9.80772L7.73785 13.1109L13.9836 6.86519L12.3623 5.18329L7.73785 9.87379Z" fill="${fillFore}"/>
					</g>
					<defs>
					<clipPath id="clip0_1651_44333">
					<rect width="18" height="18" fill="${fillBack}"/>
					</clipPath>
					</defs>
				</svg>
			`;
		})(),
		decline: (() => {
			const fillBack = AppTheme.colors.base8;
			const fillMiddle = AppTheme.colors.baseWhiteFixed;
			const fillFore = AppTheme.colors.base5;

			return `
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="9" cy="9" r="9" fill="${fillBack}"/>
					<circle cx="9" cy="9" r="8" fill="${fillFore}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M10.1127 9L13 11.8873L11.8873 13L9 10.1127L6.11272 13L5 11.8873L7.88728 9L5 6.11272L6.11272 5L9 7.88728L11.8873 5L13 6.11272L10.1127 9Z" fill="${fillMiddle}"/>
				</svg>
			`;
		})(),
		progress: (() => {
			const fillBack = AppTheme.colors.base8;
			const fillMiddle = AppTheme.colors.baseWhiteFixed;
			const fillFore = AppTheme.colors.accentMainPrimary;

			return `
				<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="9" cy="9" r="9" fill="${fillBack}"/>
					<circle cx="9" cy="9" r="6" fill="${fillMiddle}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17ZM9.99015 4.04879H8.00983V8.00941V8.99957V9.98972H12.9606V8.00941H9.99015V4.04879Z" fill="${fillFore}"/>
				</svg>
			`;
		})(),
	};

	module.exports = { WorkflowFacesColumn };
});
