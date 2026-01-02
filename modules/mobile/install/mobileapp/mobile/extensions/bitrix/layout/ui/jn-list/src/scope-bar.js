/**
 * @module layout/ui/jn-list/src/scope-bar
 */
jn.define('layout/ui/jn-list/src/scope-bar', (require, exports, module) => {
	const { ChipInnerTab } = require('ui-system/blocks/chips/chip-inner-tab');
	const { Color, Indent } = require('tokens');
	const { UIScrollView } = require('layout/ui/scroll-view');

	class JnListScopeBar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.scrollViewRef = null;
		}

		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].join('-').trim();
		}

		render()
		{
			const { scopes, selectedScopeId } = this.props;

			const renderedScopes = scopes.map((scope, index) => this.renderScope({
				scope,
				selected: scope.id === selectedScopeId,
				isFirst: index === 0,
				isLast: index === scopes.length - 1,
			}));

			return UIScrollView(
				{
					testId: this.getTestId('scroll-view'),
					ref: this.#bindScrollViewRef,
					style: {
						flexDirection: 'row',
						width: '100%',
						height: 50,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
					horizontal: true,
					showsHorizontalScrollIndicator: false,
				},
				...renderedScopes,
			);
		}

		renderScope({ scope, selected, isFirst, isLast })
		{
			return ChipInnerTab({
				testId: this.getTestId('tab'),
				text: scope.title,
				selected,
				onClick: this.onScopeClick.bind(this, scope),
				style: {
					marginVertical: Indent.M.toNumber(),
					marginLeft: isFirst ? Indent.XL2.toNumber() : 0,
					marginRight: isLast ? Indent.XL2.toNumber() : Indent.L.toNumber(),
				},
			});
		}

		onScopeClick(scope)
		{
			Keyboard.dismiss();
			const { onScopeSelectionChanged } = this.props;
			onScopeSelectionChanged?.(scope);
		}

		getSelectedScopeIndex()
		{
			const { selectedScopeId, scopes } = this.props;
			const selectedScope = scopes.find((scope) => scope.id === selectedScopeId);

			return scopes.indexOf(selectedScope);
		}

		scrollToSelectedScope(animated = false)
		{
			this.scrollViewRef?.scrollToChild(this.getSelectedScopeIndex(), animated);
		}

		scrollToScopesEnd(animated = false)
		{
			this.scrollViewRef?.scrollToEnd(animated);
		}

		#bindScrollViewRef = (ref) => {
			this.scrollViewRef = ref;
		};
	}

	module.exports = {
		JnListScopeBar: (props) => new JnListScopeBar(props),
	};
});
