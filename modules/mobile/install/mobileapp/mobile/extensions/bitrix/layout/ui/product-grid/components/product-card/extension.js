/**
 * @module layout/ui/product-grid/components/product-card
 */
jn.define('layout/ui/product-grid/components/product-card', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Styles } = require('layout/ui/product-grid/components/product-card/styles');
	const { SvgIcons } = require('layout/ui/product-grid/components/product-card/icons');
	const { FocusContext } = require('layout/ui/product-grid/services/focus-context');
	const { transition, pause, chain } = require('animation');

	class ProductCard extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.containerRef = null;
		}

		render()
		{
			const { id, style } = this.props;

			return this.wrap(View(
				{
					testId: `product-grid-item-card-${id}`,
					ref: (ref) => {
						this.containerRef = ref;
					},
					style: Styles.container(style),
					onClick: () => this.onClick(),
					onLongClick: () => this.onLongClick(),
				},
				this.renderIndex(),
				this.renderImageStack(),
				View(
					{
						style: Styles.content,
					},
					this.renderName(),
					this.renderContextMenu(),
					this.renderInnerContent(),
				),
				this.renderDeleteButton(),
			));
		}

		wrap(content)
		{
			return this.props.wrap
				? this.props.wrap(content)
				: content;
		}

		renderIndex()
		{
			if (!this.props.hasOwnProperty('index'))
			{
				return null;
			}

			const displayIndex = parseInt(this.props.index, 10);
			const fontSize = displayIndex < 100 ? 12 : 10;

			return View(
				{
					style: Styles.index.wrapper,
				},
				Text({
					style: { ...Styles.index.text, fontSize },
					text: String(displayIndex),
				}),
			);
		}

		renderImageStack()
		{
			if (!this.props.hasOwnProperty('gallery'))
			{
				return null;
			}

			return View(
				{
					style: Styles.image.container,
					onClick: () => this.onImageClick(),
				},
				new ImageStack({
					images: this.props.gallery,
					style: Styles.image.inner,
				}),
			);
		}

		renderName()
		{
			return View(
				{
					style: Styles.name(this.props.onContextMenuClick),
					onClick: () => this.onNameClick(),
				},
				Text({
					text: this.props.name,
					style: {
						fontSize: 18,
						color: AppTheme.colors.base1,
					},
				}),
			);
		}

		renderContextMenu()
		{
			if (this.props.onContextMenuClick)
			{
				return View(
					{
						testId: 'product-grid-item-card-context-menu',
						style: Styles.contextMenu.container,
						onClick: () => this.props.onContextMenuClick(this),
					},
					Image({
						tintColor: AppTheme.colors.base3,
						style: Styles.contextMenu.icon,
						svg: SvgIcons.contextMenu,
					}),
				);
			}

			return null;
		}

		renderInnerContent()
		{
			if (this.props.renderInnerContent)
			{
				return this.props.renderInnerContent();
			}

			return null;
		}

		renderDeleteButton()
		{
			if (!this.props.onRemove)
			{
				return null;
			}

			return View(
				{
					style: Styles.deleteButton.container,
					onClick: () => this.props.onRemove(this),
				},
				Image({
					tintColor: AppTheme.colors.base3,
					style: Styles.deleteButton.icon,
					svg: SvgIcons.delete,
				}),
			);
		}

		onClick()
		{
			FocusContext.blur();

			if (this.props.onClick)
			{
				return this.props.onClick(this);
			}

			return null;
		}

		onImageClick()
		{
			FocusContext.blur();

			if (this.props.onImageClick)
			{
				return this.props.onImageClick(this);
			}

			return null;
		}

		onNameClick()
		{
			FocusContext.blur();

			if (this.props.onNameClick)
			{
				return this.props.onNameClick(this);
			}

			return null;
		}

		onLongClick()
		{
			if (this.props.onLongClick)
			{
				return this.props.onLongClick(this);
			}

			return null;
		}

		/**
		 * Method triggers background animation, means that element was changed
		 * @public
		 */
		blink()
		{
			const toYellow = transition(this.containerRef, {
				duration: 500,
				backgroundColor: AppTheme.colors.accentSoftOrange3,
			});

			const toWhite = transition(this.containerRef, {
				duration: 500,
				backgroundColor: AppTheme.colors.bgContentPrimary,
			});

			chain(pause(100), toYellow, pause(3000), toWhite)();
		}
	}

	module.exports = { ProductCard };
});
