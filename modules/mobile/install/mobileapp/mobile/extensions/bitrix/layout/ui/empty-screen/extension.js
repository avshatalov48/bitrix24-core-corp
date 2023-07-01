/**
 * @module layout/ui/empty-screen
 */
jn.define('layout/ui/empty-screen', (require, exports, module) => {

	const { stringify } = require('utils/string');
	const { mergeImmutable } = require('utils/object');

	/**
	 * @class EmptyScreen
	 */
	class EmptyScreen extends LayoutComponent
	{
		/**
		 * @return {string}
		 */
		get backgroundColor()
		{
			return this.props.backgroundColor || 'transparent';
		}

		/**
		 * @return {object|null}
		 */
		get image()
		{
			return this.props.image || null;
		}

		/**
		 * @return {string|LayoutComponent}
		 */
		get title()
		{
			if (typeof this.props.title === 'function')
			{
				return this.props.title();
			}

			return stringify(this.props.title);
		}

		/**
		 * @return {string|LayoutComponent}
		 */
		get description()
		{
			if (typeof this.props.description === 'function')
			{
				return this.props.description();
			}

			return stringify(this.props.description);
		}

		get styles()
		{
			return BX.prop.get(this.props, 'styles', {});
		}

		get containerStyle()
		{
			const defaultStyles = {
				flexDirection: 'column',
				flexGrow: 1,
				justifyContent: 'center',
				alignItems: 'center',
				paddingHorizontal: 35,
			};

			if (this.styles.container)
			{
				return mergeImmutable(defaultStyles, this.styles.container);
			}

			return defaultStyles;
		}

		get iconStyle()
		{
			const defaultStyles = {
				marginBottom: 36,
			};

			if (this.styles.icon)
			{
				return mergeImmutable(defaultStyles, this.styles.icon);
			}

			return defaultStyles;
		}

		/**
		 * @return {boolean}
		 */
		get isRefreshable()
		{
			return this.props.hasOwnProperty('onRefresh');
		}

		/**
		 * @public
		 * @param {string} filename
		 * @return {string}
		 */
		static makeLibraryImagePath(filename)
		{
			return `${currentDomain}/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/empty-screen/images/${filename}`;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: this.backgroundColor,
						width: '100%',
						height: '100%',
					},
					safeArea: {
						bottom: true,
					},
				},
				RefreshView(
					{
						style: {
							flexDirection: 'column',
							flexGrow: 1,
						},
						refreshing: false,
						enabled: this.isRefreshable,
						onRefresh: () => this.props.onRefresh(),
					},
					View(
						{
							style: this.containerStyle,
						},
						this.renderIcon(),
						this.renderTitle(),
						this.renderDescription(),
					)
				),
			);
		}

		renderIcon()
		{
			if (!this.image)
			{
				return null;
			}

			return View(
				{
					style: this.iconStyle
				},
				Image(this.image)
			);
		}

		renderTitle()
		{
			const title = this.title;
			if (typeof title === 'string')
			{
				return title.length && Text({
					text: jnComponent.convertHtmlEntities(title),
					style: {
						color: '#525C69',
						fontSize: 25,
						textAlign: 'center',
						marginBottom: 12,
					}
				});
			}
			return title;
		}

		renderDescription()
		{
			const description = this.description;
			if (typeof description === 'string')
			{
				return description.length && Text({
					text: jnComponent.convertHtmlEntities(description),
					style: {
						color: '#525C69',
						fontSize: 15,
						textAlign: 'center',
						lineHeightMultiple: 1.2,
					}
				});
			}
			return description;
		}
	}

	module.exports = { EmptyScreen };

});