/**
 * @module user-profile/common-tab/src/block/tags/view
 */
jn.define('user-profile/common-tab/src/block/tags/view', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Type } = require('type');
	const { Text4 } = require('ui-system/typography/text');
	const { Color, Indent, Component } = require('tokens');
	const { Loc } = require('loc');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { ProfileTagSelector } = require('selector/widget/entity/intranet/profile/tag');
	const { uniqBy } = require('utils/array');

	const MAX_TAGS_COUNT_FOR_COLLAPSED = 4;

	class Tags extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.parentWidget = props.parentWidget ?? PageManager;
			this.getTestId = createTestIdGenerator({
				prefix: 'tags',
				context: this,
			});
			this.initState(props);
		}

		componentWillReceiveProps(props)
		{
			this.initState(props);
		}

		initState(props)
		{
			this.state.items = Type.isArrayFilled(props.items) ? props.items : [];
			this.state.isCollapsed = !props.isEditMode && props.items.length > MAX_TAGS_COUNT_FOR_COLLAPSED;
		}

		get items()
		{
			return this.state.items ?? [];
		}

		get isEditMode()
		{
			return this.props.isEditMode ?? false;
		}

		get ownerId()
		{
			return this.props.ownerId ?? null;
		}

		getIsCollapsed()
		{
			if (this.isEditMode)
			{
				return false;
			}

			if (this.items.length <= MAX_TAGS_COUNT_FOR_COLLAPSED)
			{
				return false;
			}

			return this.state.isCollapsed;
		}

		expand = () => {
			this.setState({ isCollapsed: false });
		};

		render()
		{
			return View(
				{
					testId: this.getTestId('content'),
					style: {
						width: '100%',
						maxWidth: '100%',
						flexWrap: 'wrap',
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'flex-start',
					},
				},
				...this.renderTags(),
				this.renderAddTagButton(),
				this.renderCollapseButton(),
			);
		}

		renderCollapseButton()
		{
			if (!this.getIsCollapsed())
			{
				return null;
			}

			return View(
				{
					style: {
						width: '100%',
						alignItems: 'center',
					},
					onClick: this.expand,
				},
				this.#renderDivider(),
				Text4({
					color: Color.base3,
					text: Loc.getMessage('M_PROFILE_TAGS_EXPAND_BUTTON'),
					testId: this.getTestId('show-all-tags-link'),
				}),
			);
		}

		#renderDivider()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: Color.bgSeparatorSecondary.toHex(),
					marginVertical: Indent.XL2.toNumber(),
				},
			});
		}

		renderTags()
		{
			if (!Type.isArrayFilled(this.items))
			{
				return [];
			}

			if (this.getIsCollapsed())
			{
				return this.items.slice(0, MAX_TAGS_COUNT_FOR_COLLAPSED).map((item) => this.renderTag(item));
			}

			return this.items.map((item) => this.renderTag(item));
		}

		renderTag(item)
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignContent: 'center',
						alignItems: 'center',
						flexDirection: 'row',
						borderRadius: Component.elementAccentCorner.toNumber(),
						backgroundColor: Color.accentSoftBlue3.toHex(),
						padding: Indent.S.toNumber(),
						marginRight: Indent.XL.toNumber(),
						marginBottom: Indent.XL.toNumber(),
						maxWidth: '100%',
					},
				},
				IconView({
					icon: Icon.TAG,
					color: Color.accentMainPrimaryalt,
					size: 20,
				}),
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							alignContent: 'center',
							marginLeft: Indent.XS.toNumber(),
							flexShrink: 1,
							flexGrow: 1,
						},
					},
					Text4({
						text: item.name,
						color: Color.accentMainPrimaryalt,
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
				this.isEditMode && IconView({
					icon: Icon.CROSS,
					color: Color.base3,
					size: 20,
					onClick: () => this.onClickRemoveTag(item),
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
				}),
				!this.isEditMode && AvatarStack({
					testId: this.getTestId('avatar-stack'),
					entities: item.userIds,
					size: 20,
					visibleEntityCount: 2,
					withRedux: true,
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
				}),
			);
		}

		renderAddTagButton()
		{
			if (!this.isEditMode)
			{
				return null;
			}

			return ChipButton({
				testId: this.getTestId('add-tag-button'),
				text: Loc.getMessage('M_PROFILE_TAGS_ADD_BUTTON'),
				onClick: this.onClickAddTagButton,
				mode: ChipButtonMode.OUTLINE,
				design: ChipButtonDesign.GREY,
				icon: Icon.PLUS,
				style: {
					marginBottom: Indent.XL.toNumber(),
				},
			});
		}

		onClickAddTagButton = () => {
			const selector = ProfileTagSelector.make({
				closeOnSelect: true,
				events: {
					onSelectedChanged: (tags) => this.onAddTag(tags[0]),
					onCreate: (data) => {
						this.onCreateTag(data.items[0]);
					},
				},
				provider: {
					ownerId: this.ownerId,
					selectedItems: this.items,
				},
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 80,
						horizontalSwipeAllowed: false,
					},
				},
			});
			selector.show({}, this.parentWidget);
		};

		onCreateTag = (tag) => {
			tag.name = tag.name.trim().toLowerCase();
			this.onChange([...this.items, tag]);
		};

		onAddTag = (tag) => {
			this.onChange(uniqBy([...this.items, tag], 'name'));
		};

		onClickRemoveTag = async (item) => {
			const { name } = item;

			const prevItems = this.items;

			const nextItems = prevItems.filter((tag) => tag.name !== name);

			this.onChange(nextItems);
		};

		onChange = (nextItems) => {
			this.setState({ items: nextItems }, () => {
				this.props.onChange?.('tags', nextItems);
			});
		};
	}

	module.exports = {
		Tags: (props) => new Tags(props),
	};
});
