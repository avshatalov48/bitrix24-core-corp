/**
 * @module layout/ui/button-list/pill-button
 */
jn.define('layout/ui/button-list/pill-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PropTypes } = require('utils/validation');

	/**
	 * @class PillButton
	 */
	class PillButton extends LayoutComponent
	{
		/**
		 * @param {String} props.mainSvgIcon
		 * @param {String} props.additionalSvgIcon
		 * @param {String | Object | Array} props.content
		 * @param {Boolean} props.isActive
		 * @param {Boolean} props.changeActiveAfterClick
		 * @param {Integer} props.borderRadius
		 * @param {Function} props.onClick
		 * @param {Function} props.onDeleteButton
		 * @param {Boolean} props.deleteAfterClick
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				isActive: props?.isActive || false,
			};

			this.handleOnClick = this.handleOnClick.bind(this);
		}

		render()
		{
			const {
				onDeleteButton,
				mainSvgIcon,
				content,
				additionalSvgIcon,
				deleteAfterClick,
				changeActiveAfterClick,
				borderRadius,
			} = this.props;

			return View(
				{
					style: {
						paddingHorizontal: 4,
					},
				},
				View(
					{
						style: {
							height: 30,
							maxHeight: 30,
							alignSelf: 'center',
							borderColor: this.getColor(AppTheme.colors.bgSeparatorPrimary),
							borderWidth: 1,
							borderRadius: borderRadius ?? 8,
							flexDirection: 'row',
							alignItems: 'center',
							paddingHorizontal: 8,
							paddingVertical: 5,
						},
						onClick: () => {
							this.handleOnClick();

							if (deleteAfterClick)
							{
								onDeleteButton();

								return;
							}

							if (changeActiveAfterClick)
							{
								this.setState({
									isActive: !this.state.isActive,
								});
							}
						},
					},
					this.renderIcon(mainSvgIcon),
					this.renderContent(content),
					this.renderIcon(additionalSvgIcon),
				),
			);
		}

		renderContent(content)
		{
			if (typeof content === 'string')
			{
				return this.renderText(content);
			}

			if (Array.isArray(content))
			{
				return this.renderMultiItemsContent(content);
			}

			return content || null;
		}

		renderMultiItemsContent(items)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-around',
						alignItems: 'center',
					},
				},
				...this.prepareMultiItems(items),
			);
		}

		prepareMultiItems(items)
		{
			const preparedItems = [];
			const lastItemIndex = items.length - 1;
			const divider = View(
				{
					style: {
						width: 0.5,
						height: 20,
						backgroundColor: this.getColor(AppTheme.colors.base6),
						marginHorizontal: 5,
					},
				},
			);

			items.forEach((item, index) => {
				const { onClick, mainSvgIcon, content, additionalSvgIcon } = item;

				preparedItems.push(
					View(
						{
							style: {
								paddingHorizontal: 0,
								flexDirection: 'row',
							},
							onClick,
						},
						this.renderIcon(mainSvgIcon),
						this.renderContent(content),
						this.renderIcon(additionalSvgIcon),
					),
					index === lastItemIndex ? null : divider,
				);
			});

			return preparedItems;
		}

		renderText(text)
		{
			if (!text)
			{
				return null;
			}

			return Text(
				{
					style: {
						color: this.getColor(),
						paddingHorizontal: 2,
						textAlign: 'center',
						fontSize: 14,
						fontWeight: '400',
					},
					text,
				},
			);
		}

		renderIcon(icon)
		{
			if (!icon || !icon.startsWith('<svg'))
			{
				return null;
			}

			return Image(
				{
					style: {
						height: 20,
						maxHeight: 20,
						width: 20,
						maxWidth: 20,
						paddingHorizontal: 2,
						flexBasis: 'content',
					},
					tintColor: this.getColor(),
					svg: {
						content: icon,
					},
				},
			);
		}

		handleOnClick()
		{
			const { onClick } = this.props;

			if (onClick)
			{
				onClick();
			}
		}

		getColor(defaultColor = '#909090')
		{
			return this.state.isActive ? AppTheme.colors.accentExtraDarkblue : defaultColor;
		}
	}

	PillButton.propTypes = {
		mainSvgIcon: PropTypes.string,
		additionalSvgIcon: PropTypes.string,
		isActive: PropTypes.bool,
		content: PropTypes.oneOfType([
			PropTypes.string,
			PropTypes.object,
			PropTypes.array,
		]),
		changeActiveAfterClick: PropTypes.bool,
		borderRadius: PropTypes.number,
		onClick: PropTypes.func,
		onDeleteButton: PropTypes.func,
		deleteAfterClick: PropTypes.bool,
	};

	module.exports = {
		PillButton: (props) => new PillButton(props),
	};
});
