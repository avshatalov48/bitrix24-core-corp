/**
 * @module layout/ui/product-grid/components/product-card/styles
 */
jn.define('layout/ui/product-grid/components/product-card/styles', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { get } = require('utils/object');

	const Styles = {
		container: (componentStyle = {}) => ({
			backgroundColor: get(componentStyle, 'backgroundColor', AppTheme.colors.bgContentPrimary),
			borderRadius: 12,
			padding: 16,
			marginTop: get(componentStyle, 'marginTop', 0),
			marginBottom: get(componentStyle, 'marginBottom', 12),
			flexDirection: 'row',
		}),

		index: {
			wrapper: {
				position: 'absolute',
				width: 24,
				height: 16,
				left: 0,
				top: 0,
				backgroundColor: AppTheme.colors.base5,
				borderTopLeftRadius: 12,
				borderBottomRightRadius: 12,
				alignItems: 'center',
				flexDirection: 'column',
				justifyContent: 'center',
			},
			text: {
				color: AppTheme.colors.baseWhiteFixed,
				fontSize: 10,
			},
		},

		image: {
			container: {
				width: 62,
				height: 62,
				marginRight: 11,
				justifyContent: 'center',
				alignItems: 'center',
			},
			inner: {
				width: 62,
				height: 62,
			},
		},

		content: {
			flexGrow: 1,
			flexShrink: 1,
			width: 0,
		},

		name: (hasContextMenu) => ({
			paddingRight: hasContextMenu ? 40 : 0,
			marginBottom: 14,
		}),

		contextMenu: {
			container: {
				position: 'absolute',
				right: 0,
				top: -8,
				width: 40,
				height: 40,
				alignItems: 'flex-end',
				justifyContent: 'center',
			},
			icon: {
				width: 16,
				height: 4,
			},
		},

		deleteButton: {
			container: {
				position: 'absolute',
				left: 10,
				bottom: 8,
				padding: 10,
			},
			icon: {
				width: 12,
				height: 15,
			},
		},
	};

	module.exports = { Styles };
});
