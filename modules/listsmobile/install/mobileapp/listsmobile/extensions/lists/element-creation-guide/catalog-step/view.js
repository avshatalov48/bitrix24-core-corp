/**
 * @module lists/element-creation-guide/catalog-step/view
 */
jn.define('lists/element-creation-guide/catalog-step/view', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');
	const { Stub } = require('lists/element-creation-guide/stub');
	const { CatalogStepSkeleton } = require('lists/element-creation-guide/catalog-step/skeleton');
	const { PureComponent } = require('layout/pure-component');

	class CatalogStepView extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {String} props.uid
		 * @param {Array} props.items
		 * @param {Boolean} props.isLoaded
		 * @param {Object} props.selectedItem
		 * @param {Object} props.layout
		 */
		constructor(props)
		{
			super(props);

			this.isLoaded = this.props.isLoaded || false;
			this.items = this.props.items || [];
			this.selectedItem = this.props.selectedItem || null;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.listViewRef = null;
		}

		componentWillReceiveProps(props)
		{
			super.componentWillReceiveProps(props);

			this.isLoaded = props.isLoaded;
			this.items = props.items;
		}

		get hasSelectedItem()
		{
			return this.selectedItem !== null;
		}

		render()
		{
			const hasItems = this.items.length > 0;

			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderTopLeftRadius: 12,
						borderTopRightRadius: 12,
						flex: 1,
					},
				},
				!this.isLoaded && this.renderSkeleton(),
				this.isLoaded && !hasItems && this.renderStub(),
				this.isLoaded && hasItems && this.renderList(),
			);
		}

		renderSkeleton()
		{
			return new CatalogStepSkeleton({});
		}

		renderStub()
		{
			return new Stub({
				description: Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_CATALOG_STEP_EMPTY_STATE_TITLE'),
				backgroundColor: AppTheme.colors.bgContentPrimary,
			});
		}

		renderList()
		{
			// eslint-disable-next-line no-undef
			return ListView({
				style: { flex: 1 },
				data: [{ items: this.items }],
				isRefreshing: false,
				renderItem: this.renderItem.bind(this),
				ref: (ref) => {
					this.listViewRef = ref;
				},
			});
		}

		renderItem(item, section, row)
		{
			return View(
				{
					onClick: this.handleItemClick.bind(this, item),
					testId: `${item.type}-${item.key}`,
				},
				row !== 0 && this.renderSeparator(),
				this.renderBody(item),
			);
		}

		renderBody(item)
		{
			const isSelected = (item.isSelected) || (this.selectedItem && this.selectedItem.key === item.key);

			return View(
				{
					style: {
						margin: 4,
						borderRadius: 4,
						backgroundColor: isSelected ? AppTheme.colors.accentSoftBlue1 : 'initial',
						paddingHorizontal: 12,
						paddingVertical: 11,
						display: 'flex',
						flexDirection: 'row',
					},
				},
				Text({
					text: item.title,
					style: {
						fontSize: 18,
						fontWeight: '400',
						color: AppTheme.colors.base1,
						flexGrow: 1,
						flexShrink: 1,
					},
					numberOfLines: 2,
					ellipsize: 'end',
				}),
				item.formattedTime && this.renderTimeBody(item),
			);
		}

		renderSeparator()
		{
			return View(
				{
					style: {
						height: 1,
						marginLeft: 16,
						backgroundColor: AppTheme.colors.bgSeparatorSecondary,
					},
				},
			);
		}

		renderTimeBody(item)
		{
			return View(
				{
					style: {
						flexShrink: 1,
						minWidth: 96,
						display: 'flex',
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'flex-end',
					},
				},
				Text({
					text: item.formattedTime,
					style: {
						flexShrink: 1,
						fontWeight: '500',
						fontSize: 14,
						color: AppTheme.colors.base4,
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				Image({
					style: {
						height: 24,
						width: 24,
						marginLeft: 4,
					},
					svg: {
						content: `
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path 
									fill-rule="evenodd"
									clip-rule="evenodd"
									d="M11.9999 3.35364C8.58938 3.35364 5.84875 6.09319 5.84875 9.50481C5.84875 13.1058 9.5034 18.1321 11.1787 20.247C11.6031 20.7828 12.3926 20.779 12.8133 20.2402C14.4849 18.099 18.1511 13.0039 18.1511 9.50481C18.1511 6.09319 15.4105 3.35364 11.9999 3.35364ZM12 13.4948C9.76584 13.4948 8.00901 11.7395 8.00901 9.50379C8.00901 7.26963 9.76431 5.5128 12 5.5128C14.2342 5.5128 15.991 7.2681 15.991 9.50379C15.991 11.7394 14.2342 13.4948 12 13.4948Z"
									fill="${AppTheme.colors.base5}"
								/>
								<path
									d="M11.6931 6.77423C11.3843 6.77423 11.1339 7.02458 11.1339 7.3334V9.93783C11.1339 10.2467 11.3843 10.4972 11.6931 10.4972H12.0809C12.0852 10.4972 12.0896 10.4971 12.0939 10.497H14.1067C14.4155 10.497 14.6659 10.2467 14.6659 9.93783V9.54999C14.6659 9.24116 14.4155 8.99081 14.1067 8.99081H12.6401V7.3334C12.6401 7.02458 12.3897 6.77423 12.0809 6.77423H11.6931Z"
									fill="${AppTheme.colors.base5}"
								/>
							</svg>
						`,
					},
				}),
			);
		}

		handleItemClick(item)
		{
			const changedItems = [];

			if (this.hasSelectedItem)
			{
				if (this.selectedItem.key === item.key)
				{
					return;
				}

				this.selectedItem.isSelected = false;
				changedItems.push(this.selectedItem);
			}

			this.customEventEmitter.emit('CatalogStepView:OnSelectItem', [item]);

			this.selectedItem = item;
			this.selectedItem.isSelected = true;
			changedItems.push(this.selectedItem);

			this.listViewRef.updateRows(changedItems, 'none');
		}
	}

	module.exports = { CatalogStepView };
});
