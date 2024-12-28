/**
 * @module call/calls/layout/copilot-drawer
 */
jn.define('call/calls/layout/copilot-drawer', (require, exports, module) => {
	const { Color, Corner } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const Icons = {
		main: '<svg xmlns="http://www.w3.org/2000/svg" width="129" height="159" fill="none"><path fill="#B15EF5" d="M.103 19.892C.103 8.588 8.355-.117 18.34.41l51.02 2.637c8.818.453 15.864 9.195 15.864 19.557v56.183c0 10.324-7.046 19.066-15.863 19.519l-51.02 2.486C8.354 101.283.102 92.541.102 81.237V19.892Z" opacity=".8"/><path fill="#fff" fill-opacity=".18" fill-rule="evenodd" d="m69.36 4.216-51.02-2.6C8.958 1.126 1.233 9.341 1.233 19.892v61.27c0 10.588 7.725 18.765 17.107 18.313l51.02-2.45c8.29-.414 14.885-8.591 14.885-18.275V22.492c0-9.722-6.595-17.899-14.884-18.313v.037ZM18.34.411C8.355-.117.103 8.625.103 19.89v61.346c0 11.304 8.252 20.046 18.237 19.556l51.02-2.487c8.818-.414 15.864-9.156 15.864-19.519V22.605c0-10.325-7.046-19.105-15.863-19.557L18.34.411Z" clip-rule="evenodd"/><path fill="#fff" fill-opacity=".9" fill-rule="evenodd" d="M44.68 71.025c9.721-.188 17.483-9.307 17.483-20.348 0-11.04-7.762-20.197-17.484-20.423-9.948-.226-18.124 8.893-18.124 20.348 0 11.455 8.214 20.612 18.124 20.423Zm-2.864-35.684c-.151-.49-.792-.528-.943 0l-1.77 5.502c-.528 1.582-1.583 2.788-2.977 3.353l-4.899 2.035c-.452.188-.452.904 0 1.093l4.899 2.072c1.394.603 2.487 1.809 2.977 3.392l1.77 5.5c.151.49.792.49.943 0l1.77-5.5c.49-1.545 1.583-2.789 2.94-3.354l4.748-1.997c.414-.189.414-.867 0-1.055l-4.748-2.035c-1.357-.603-2.45-1.846-2.94-3.391l-1.77-5.502v-.113Zm10.136 15.901c-.075-.3-.452-.3-.528 0l-1.017 3.166c-.302.904-.904 1.62-1.696 1.96l-2.75 1.167c-.264.113-.264.528 0 .603l2.75 1.168c.791.34 1.394 1.018 1.696 1.922l1.017 3.128c.076.301.453.263.528 0l1.017-3.166c.302-.904.905-1.62 1.658-1.959l2.713-1.168c.226-.113.226-.49 0-.603l-2.713-1.13c-.791-.34-1.394-1.018-1.658-1.922l-1.017-3.128v-.038Z" clip-rule="evenodd"/><path fill="#fff" fill-opacity=".9" d="M69.662 51.883c1.658.076 2.94 1.734 2.713 3.655-1.997 15.412-13.452 27.62-27.658 28.073-14.206.452-29.693-14.244-29.693-33.009S28.558 16.947 44.717 17.67c9.763.436 14.281 3.805 19.218 9.345 1.243 1.356 1.092 3.617-.189 4.898-1.28 1.244-3.203 1.018-4.484-.301-3.843-3.994-8.93-6.52-14.545-6.67-12.585-.339-22.985 11.154-22.985 25.699 0 14.545 10.437 26.075 22.985 25.736 12.548-.34 19.745-9.383 21.591-21.101.302-1.922 1.696-3.467 3.354-3.392Z"/><path fill="#35E961" fill-opacity=".74" d="M71.395 85.118c0-10.513 7.122-19.104 15.789-19.255l27.319-.415c7.988-.113 14.394 7.763 14.394 17.635v32.331c0 9.872-6.368 18.426-14.394 19.179l-27.32 2.525c-8.666.791-15.788-7.046-15.788-17.559v-34.44Z"/><path fill="#fff" fill-opacity=".18" fill-rule="evenodd" d="m114.503 66.579-27.32.452c-8.1.113-14.808 8.215-14.808 18.05v34.402c0 9.835 6.67 17.183 14.809 16.467l27.319-2.487c7.498-.678 13.49-8.742 13.49-17.974V83.121c0-9.232-5.992-16.655-13.49-16.542Zm-27.32-.716c-8.666.113-15.788 8.742-15.788 19.255v34.441c0 10.513 7.122 18.35 15.789 17.559l27.319-2.525c7.988-.753 14.394-9.307 14.394-19.179v-32.33c0-9.873-6.368-17.749-14.394-17.636l-27.32.415Z" clip-rule="evenodd"/><path fill="#fff" fill-opacity=".9" d="M102.633 79.39c0-1.168-.753-2.072-1.733-2.072-.98 0-1.734.98-1.734 2.148v2.072c0 1.168.792 2.073 1.734 2.073s1.733-1.018 1.733-2.186V79.39ZM111.752 99.7c0 3.429-2.035 6.67-3.768 9.119-.603.867-.641 1.734-.679 2.525-.075 1.733-.113 3.127-6.405 3.542-6.369.452-6.482-.942-6.595-2.713-.037-.791-.113-1.658-.716-2.449-1.808-2.299-3.805-5.351-3.805-8.893 0-7.499 5.011-13.754 11.116-14.018 6.104-.263 10.852 5.502 10.852 12.85v.037ZM94.87 119.935c0-1.394.943-2.6 2.11-2.713l8.102-.565c1.131-.075 2.035.98 2.035 2.374 0 1.394-.904 2.562-2.035 2.675l-8.101.641c-1.168.075-2.11-.98-2.11-2.374v-.038ZM88.917 81.99c.791-.753 1.884-.603 2.487.302l1.055 1.582c.603.905.452 2.261-.301 3.015-.754.754-1.884.64-2.487-.264l-1.055-1.62c-.603-.904-.453-2.261.301-3.015ZM84.546 92.541c-.942-.264-1.96.452-2.185 1.62-.264 1.168.301 2.299 1.28 2.563l1.696.452c.942.263 1.922-.452 2.186-1.62.264-1.169-.301-2.3-1.243-2.563l-1.696-.452h-.038ZM118.761 92.503c-.226-1.093-1.168-1.695-2.035-1.356l-1.583.602c-.904.34-1.432 1.508-1.168 2.6.226 1.093 1.168 1.696 2.035 1.357l1.583-.603c.904-.339 1.432-1.507 1.168-2.6ZM110.245 81.651c.565-.942 1.62-1.13 2.374-.452.716.678.867 1.997.301 2.902l-1.017 1.62c-.565.942-1.62 1.13-2.374.452-.754-.678-.867-1.997-.301-2.94l1.017-1.62v.038Z"/></svg>',
	};

	const Events = {
		onToggleCopilot: 'onToggleCopilot',
	};

	class CopilotDrawer extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			this.state = {
				copilotConnected: props.copilotConnected,
			};

			if (typeof props[Events.onToggleCopilot] === 'function')
			{
				this.on(Events.onToggleCopilot, props.onToggleCopilot);
			}
		}

		render()
		{
			return View(
				{
					style: { flex: 1, backgroundColor: Color.bgContentPrimary.toHex() },
				},
				View(
					{
						style: {
							marginLeft: 42,
							marginRight: 42,
							marginTop: 18,
						},
					},
					View(
						{
							style: { height: 201, justifyContent: 'center', alignItems: 'center' },
						},
						Image({
							style: { width: 129, height: 159 },
							svg: { content: Icons.main },
						}),
					),
					Text({
						style: { fontWeight: 500, fontSize: 21, lineHeight: 25, textAlign: 'center' },
						text: BX.message('MOBILE_CALL_COPILOT_DRAWER_TITLE'),
					}),
					Text({
						style: {
							marginTop: 10,
							fontWeight: 400,
							fontSize: 16,
							lineHeight: 19,
							color: Color.base3.toHex(),
						},
						text: BX.message('MOBILE_CALL_COPILOT_DRAWER_MESSAGE'),
					}),
					View(
						{
							style: { marginTop: 18, minHeight: 150, justifyContent: 'space-between' },
						},
						this.bulletPoint(Icon.BULLETED_LIST, BX.message('MOBILE_CALL_COPILOT_DRAWER_POINT_1')),
						this.bulletPoint(Icon.ALERT, BX.message('MOBILE_CALL_COPILOT_DRAWER_POINT_2')),
						this.bulletPoint(Icon.GRADUATION_CAP, BX.message('MOBILE_CALL_COPILOT_DRAWER_POINT_3')),
						this.bulletPoint(Icon.A_LETTER, BX.message('MOBILE_CALL_COPILOT_DRAWER_POINT_4')),
					),
				),
				View({
					style: { flex: 1 },
				}),
				View(
					{
						style: {
							width: '100%',
							paddingLeft: 24,
							paddingRight: 24,
							marginBottom: device.screen.safeArea.bottom + 12,
						},
						onClick: () => this.emit(Events.onToggleCopilot),
					},
					Text({
						style: {
							color: Color.baseWhiteFixed.toHex(),
							backgroundColor: Color.accentMainPrimary.toHex(),
							width: '100%',
							height: 42,
							borderRadius: Corner.M.toNumber(),
							fontWeight: 500,
							fontSize: 17,
							textAlign: 'center',
						},
						text: this.state.copilotConnected
							? BX.message('MOBILE_CALL_COPILOT_DRAWER_DISCONNECT_FROM_CALL')
							: BX.message('MOBILE_CALL_COPILOT_DRAWER_CONNECT_TO_CALL'),
					}),
				),
			);
		}

		bulletPoint(icon, text)
		{
			return View(
				{
					style: { flexDirection: 'row' },
				},
				IconView({
					size: 24,
					color: Color.base1,
					icon,
				}),
				Text({
					style: { marginLeft: 8, fontSize: 15, fontWeight: 400, lineHeight: 18, color: Color.base1.toHex() },
					text,
				}),
			);
		}
	}

	module.exports = {
		CopilotDrawer,
	};
});
