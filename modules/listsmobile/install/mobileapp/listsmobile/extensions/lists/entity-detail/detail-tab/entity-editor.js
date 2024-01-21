/**
 * @module lists/entity-detail/entity-editor
 */
jn.define('lists/entity-detail/entity-editor', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EntityEditor: Extended } = require('layout/ui/entity-editor');
	const { FadeView } = require('animation/components/fade-view');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { Loc } = require('loc');

	class EntityEditor extends Extended
	{
		constructor(props) {
			super(props);

			this.header = props.header;
			this.hasRenderedFields = props.hasRenderedFields ?? true;
		}

		render()
		{
			const { onScroll, showBottomPadding } = this.props;

			if (!this.hasRenderedFields)
			{
				return View(
					{},
					this.header && this.renderHeader(),
					new EmptyScreen({
						image: {
							uri: EmptyScreen.makeLibraryImagePath('products.png'),
							style: { width: 218, height: 178 },
						},
						title: Loc.getMessage('LISTSMOBILE_EXT_ENTITY_DETAILS_TAB_EMPTY_EDITOR_FIELDS_TITLE'),
						styles: {
							container: {
								justifyContent: null,
								paddingVertical: 20,
							},
						},
					}),
				);
			}

			return ScrollView(
				{
					ref: (ref) => {
						this.scrollViewRef = ref;
					},
					style: {
						flex: 1,
					},
					resizableByKeyboard: true,
					showsVerticalScrollIndicator: false,
					showsHorizontalScrollIndicator: false,
					onScroll: (params) => {
						this.scrollY = params.contentOffset.y;
						if (onScroll)
						{
							onScroll(params);
						}
					},
					scrollEventThrottle: 15,
				},
				new FadeView({
					visible: false,
					fadeInOnMount: true,
					notVisibleOpacity: 0.5,
					slot: () => {
						return View(
							{
								style: {
									flexDirection: 'column',
									paddingTop: this.header ? 0 : 12,
								},
							},
							this.renderHeader(),
							...this.renderControls(),
							...this.initializeControllers(),
							showBottomPadding && View({ style: { height: 80 } }),
						);
					},
				}),
			);
		}

		renderHeader()
		{
			if (this.header)
			{
				return Text({
					style: {
						paddingBottom: 6,
						paddingLeft: 14,
						paddingRight: 14,
						color: AppTheme.colors.base3,
						fontWeight: '500',
					},
					text: this.header.trim(),
				});
			}

			return null;
		}
	}

	module.exports = { EntityEditor };
});
