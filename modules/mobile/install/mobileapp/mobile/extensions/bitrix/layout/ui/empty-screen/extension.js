/**
 * @module layout/ui/empty-screen
 */
jn.define('layout/ui/empty-screen', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Color } = require('tokens');
	const { stringify } = require('utils/string');
	const { mergeImmutable } = require('utils/object');
	const { PureComponent } = require('layout/pure-component');
	const { makeLibraryImagePathByModule } = require('asset-manager');

	const RELATIVE_PATH = `${currentDomain}/bitrix/mobileapp`;
	const IMAGE_PATH = `${RELATIVE_PATH}/mobile/extensions/bitrix/assets/empty-states`;

	/**
	 * @class EmptyScreen
	 * @deprecated use StatusBlock
	 */
	class EmptyScreen extends PureComponent
	{
		/**
		 * @return {string}
		 */
		get backgroundColor()
		{
			return this.props.backgroundColor || Color.bgPrimary.toHex();
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
			return this.props.styles || {};
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

		get rootContainerStyle()
		{
			const defaultStyles = {
				flexDirection: 'column',
				flexGrow: 1,
				backgroundColor: this.backgroundColor,
				width: '100%',
				height: '100%',
			};

			if (this.styles.rootContainer)
			{
				return mergeImmutable(defaultStyles, this.styles.rootContainer);
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
		 * @param {string} moduleId
		 * @return {string}
		 */
		static makeLibraryImagePath(filename, moduleId)
		{
			if (moduleId)
			{
				return makeLibraryImagePathByModule(filename, 'empty-states', moduleId);
			}

			return `${IMAGE_PATH}/${AppTheme.id}/${filename}`;
		}

		render()
		{
			return View(
				{
					style: this.rootContainerStyle,
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
					),
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
					style: this.iconStyle,
				},
				Image(this.image),
			);
		}

		renderTitle()
		{
			const title = this.title;
			if (typeof title === 'string')
			{
				return title.length > 0 && Text({
					text: jnComponent.convertHtmlEntities(title),
					style: {
						color: AppTheme.colors.base1,
						fontSize: 25,
						textAlign: 'center',
						marginBottom: 12,
					},
				});
			}

			return title;
		}

		renderDescription()
		{
			const description = this.description;
			if (typeof description === 'string')
			{
				return description.length > 0 && Text({
					text: jnComponent.convertHtmlEntities(description),
					style: {
						color: AppTheme.colors.base3,
						fontSize: 15,
						textAlign: 'center',
						lineHeightMultiple: 1.2,
					},
				});
			}

			return description;
		}
	}

	module.exports = { EmptyScreen };
});
