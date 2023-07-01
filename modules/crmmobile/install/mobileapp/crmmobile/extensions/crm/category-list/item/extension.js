/**
 * @module crm/category-list/item
 */
jn.define('crm/category-list/item', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { PureComponent } = require('layout/pure-component');
	const ACTIVE_COLOR = '#c3f0ff';

	/**
	 * @class CategoryListItem
	 */
	class CategoryListItem extends PureComponent
	{
		get enabled()
		{
			return BX.prop.getBoolean(this.props, 'enabled', true);
		}

		get showBottomBorder()
		{
			return BX.prop.getBoolean(this.props, 'showBottomBorder', true);
		}

		isActiveCategory()
		{
			return BX.prop.getBoolean(this.props, 'active', false);
		}

		shouldShowTunnels()
		{
			return BX.prop.getBoolean(this.props, 'showTunnels', true);
		}

		shouldShowCounters()
		{
			return BX.prop.getBoolean(this.props, 'showCounters', true);
		}

		onSelectCategory()
		{
			if (!this.enabled)
			{
				return;
			}

			if (!this.isActiveCategory())
			{
				Haptics.impactLight();
			}

			const { category, onSelectCategory } = this.props;
			if (onSelectCategory)
			{
				onSelectCategory(category);
			}
		}

		render()
		{
			return View(
				{
					style: styles.categoryWrapper(this.enabled),
					onClick: () => this.onSelectCategory(),
				},
				this.renderFunnelIcon(),
				this.renderCategoryContent(),
			);
		}

		renderFunnelIcon()
		{
			const hasTunnelsToRender = this.hasTunnelsToRender();
			return View(
				{
					style: {
						...styles.funnelIconContainer(
							this.isActiveCategory(),
							this.showBottomBorder,
							hasTunnelsToRender,
						),
						...styles.rowIndent(hasTunnelsToRender),
					},
				},
				Image(
					{
						style: styles.funnelIcon(hasTunnelsToRender),
						svg: {
							content: svgImages.funnelIcon,
						},
					},
				),
			);
		}

		renderCategoryContent()
		{
			return View(
				{
					style: styles.categoryContentWrapper(this.showBottomBorder),
				},
				View(
					{
						style: {
							...styles.categoryRowWrapper(this.isActiveCategory()),
							...styles.rowIndent(this.hasTunnelsToRender()),
						},
					},
					View(
						{
							style: styles.categoryContentContainer,
						},
						View(
							{
								style: styles.categoryContent,
							},
							this.renderCategoryTitle(),
						),
						View(
							{
								style: styles.actionContainer,
							},
							this.renderCounter(),
							this.renderEditButton(),
						),
					),
					this.hasTunnelsToRender() && View(
						{
							style: styles.tunnelsWrapper,
						},
						...this.renderTunnels(),
					),
				),
			);
		}

		hasTunnelsToRender()
		{
			if (!this.shouldShowTunnels())
			{
				return false;
			}

			const {
				category: {
					tunnels: categoryTunnels = [],
				},
			} = this.props;

			return Array.isArray(categoryTunnels) && categoryTunnels.length;
		}

		renderTunnels()
		{
			const {
				category: {
					tunnels: categoryTunnels = [],
				},
			} = this.props;

			return categoryTunnels.map((tunnel) => new Crm.Tunnel({ ...tunnel }));
		}

		renderCategoryTitle()
		{
			const {
				category: {
					name: categoryName,
				},
			} = this.props;

			return View(
				{
					style: styles.categoryTitleContainer,
				},
				Text(
					{
						style: styles.categoryTitle,
						text: categoryName,
						numberOfLines: 1,
						ellipsize: 'end',
					},
				),
				this.renderCurrentCategoryIcon(),
			);
		}

		renderCurrentCategoryIcon()
		{
			if (!this.isActiveCategory())
			{
				return null;
			}

			return Image(
				{
					style: styles.currentCategoryIcon,
					svg: {
						content: svgImages.currentCategoryIcon,
					},
				},
			);
		}

		renderCounter()
		{
			if (!this.shouldShowCounters())
			{
				return null;
			}

			const {
				category: {
					counter: categoryCounter,
				},
			} = this.props;

			if (Number.isInteger(categoryCounter) && categoryCounter > 0)
			{
				return View(
					{
						style: styles.counterContainer,
					},
					Text(
						{
							text: String(categoryCounter),
							style: styles.counterText,
						},
					),
				);
			}

			return null;
		}

		renderEditButton()
		{
			const {
				readOnly,
				category: {
					id: categoryId,
				},
				canUserEditCategory,
			} = this.props;

			if (readOnly || !canUserEditCategory)
			{
				return null;
			}

			return Image(
				{
					style: styles.editButtonIcon,
					onClick: () => this.onEditCategory(categoryId),
					svg: {
						content: svgImages.editButton,
					},
				},
			);
		}

		onEditCategory(id)
		{
			const { onEditCategory } = this.props;
			if (typeof onEditCategory === 'function')
			{
				onEditCategory(id);
			}
		}
	}

	const styles = {
		rowIndent: (showTunnels = false) => ({
			paddingVertical: showTunnels ? 0 : 10,
			marginVertical: 4,
		}),
		categoryWrapper: (enabled) => ({
			flexDirection: 'row',
			opacity: enabled ? 1 : 0.52,
		}),
		categoryRowWrapper: (isActiveCategory) => ({
			backgroundColor: isActiveCategory ? ACTIVE_COLOR : '#ffffff',
			marginRight: 4,
			borderTopRightRadius: 8,
			borderBottomRightRadius: 8,
		}),
		funnelIconContainer: (isActiveCategory, showBottomBorder, showTunnels) => ({
			justifyContent: showTunnels ? 'flex-start' : 'center',
			borderBottomWidth: showBottomBorder ? 1 : 0,
			borderBottomColor: '#ffffff',
			marginLeft: 4,
			borderTopLeftRadius: 8,
			borderBottomLeftRadius: 8,
			backgroundColor: isActiveCategory ? ACTIVE_COLOR : '#ffffff',
		}),
		funnelIcon: (showTunnels) => ({
			marginTop: showTunnels ? 10 : 0,
			width: 22,
			height: 16,
			marginLeft: 19,
			marginRight: 14,
		}),
		categoryContentWrapper: (showBottomBorder) => ({
			flex: 1,
			borderBottomWidth: showBottomBorder ? 1 : 0,
			borderBottomColor: '#edeef0',
		}),
		categoryContentContainer: {
			flexDirection: 'row',
			alignItems: 'center',
			height: 37,
		},
		categoryContent: {
			flex: 1,
			flexDirection: 'row',
			marginLeft: 3,
		},
		actionContainer: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		categoryTitleContainer: {
			flexShrink: 2,
			flexDirection: 'row',
			alignItems: 'center',
		},
		categoryTitle: {
			flexShrink: 1,
			fontSize: 18,
			color: '#333333',
		},
		currentCategoryIcon: {
			marginHorizontal: 8,
			width: 18.27,
			height: 13.7,
		},
		counterContainer: {
			marginHorizontal: 10,
			borderRadius: 11,
			paddingVertical: 2,
			paddingHorizontal: 6,
			backgroundColor: '#ff5752',
			justifyContent: 'center',
			alignItems: 'center',
		},
		counterText: {
			fontSize: 12,
			color: '#ffffff',
			fontWeight: '500',
		},
		editButtonIcon: {
			width: 46,
			height: 37,
		},
		tunnelsWrapper: {
			marginTop: -3,
			marginBottom: 6,
			marginLeft: 3,
		},
	};
	const svgImages = {
		funnelIcon: '<svg width="22" height="17" viewBox="0 0 22 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.936832 0H20.9798C21.4971 0 21.9164 0.40052 21.9164 0.894592C21.9164 0.994736 21.8988 1.09417 21.8643 1.18875L21.2587 2.85015C21.1277 3.20967 20.7728 3.45058 20.3743 3.45058H1.53753C1.13811 3.45058 0.78264 3.20864 0.652256 2.84802L0.0515613 1.18662C-0.11729 0.719618 0.142178 0.210288 0.631099 0.0490051C0.729452 0.0165609 0.832779 0 0.936832 0ZM4.23803 6.37841H17.6786C18.1959 6.37841 18.6152 6.77894 18.6152 7.27301C18.6152 7.38027 18.595 7.48665 18.5556 7.58709L17.9034 9.24848C17.7664 9.59766 17.4169 9.829 17.0265 9.829H4.85552C4.45906 9.829 4.10555 9.59056 3.97291 9.23369L3.35542 7.5723C3.18237 7.10669 3.43724 6.59525 3.92469 6.42996C4.0253 6.39584 4.13127 6.37841 4.23803 6.37841ZM8.41085 12.7768H13.5058C14.0231 12.7768 14.4424 13.1774 14.4424 13.6714C14.4424 13.7585 14.4291 13.845 14.4029 13.9284L13.8813 15.5898C13.7625 15.9682 13.3978 16.2274 12.9842 16.2274H8.98901C8.58558 16.2274 8.22749 15.9807 8.10024 15.615L7.52209 13.9536C7.35893 13.4848 7.62458 12.9783 8.11543 12.8225C8.2107 12.7923 8.31045 12.7768 8.41085 12.7768Z" fill="#525C69"/></svg>',
		currentCategoryIcon: '<svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.47688 14.351L0 8.03873L2.26691 5.82945L6.47688 9.9324L16.0025 0.648926L18.2694 2.85821L6.47688 14.351Z" fill="#0091e3"/></svg>',
		tunnelArrow: '<svg width="5" height="7" viewBox="0 0 5 7" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M0 5.85478L2.10294 3.80195L2.64763 3.28983L2.10294 2.7774L0 0.724579L0.742066 0.000195479L4.11182 3.28965L0.742066 6.5791L0 5.85478Z" fill="#A8ADB4"/></svg>',
		tunnelStageIcon: '<svg width="13" height="11" viewBox="0 0 13 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 3C0 1.34315 1.34315 0 3 0L7.96554 0C9.01239 0 9.98354 0.5457 10.528 1.43986L13 5.5L10.5279 9.56015C9.98353 10.4543 9.01239 11 7.96554 11H3C1.34315 11 0 9.65685 0 8V3Z" fill="#COLOR#"/></svg>',
		editButton: '<svg width="46" height="37" viewBox="0 0 46 37" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M27.4358 11.0352L29.9863 13.6126L20.0087 23.5634L17.4581 20.986L27.4358 11.0352ZM16.0255 24.673C16.0014 24.7643 16.0272 24.8607 16.0927 24.9279C16.1599 24.995 16.2563 25.0209 16.3476 24.995L19.1988 24.2269L16.7938 21.8227L16.0255 24.673Z" fill="#D5D7DB"/></svg>',
	};

	module.exports = { CategoryListItem };
});
