/**
 * @module crm/mail/chain/action-panel
 */
jn.define('crm/mail/chain/action-panel', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { throttle } = require('utils/function');
	const height = 70;

	const svgIcons = {
		replyButton: {
			content: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.8828 5.063C11.0759 4.88918 11.3836 5.02617 11.3836 5.28594V8.65572C11.4181 8.64284 11.4555 8.63625 11.4943 8.63735C18.107 8.82518 20.1512 15.1141 20.6954 17.5467C20.7598 17.8342 20.4088 18.0113 20.1954 17.8082C18.8028 16.4824 15.4121 13.6975 11.5018 13.6971C11.4602 13.6971 11.4202 13.6889 11.3836 13.6742V17.5915C11.3836 17.8513 11.0759 17.9883 10.8828 17.8145L4.13234 11.736C3.95589 11.5771 3.95589 11.3004 4.13234 11.1415L10.8828 5.063Z" fill="#00A2E8"/></svg>',
		},
		replyAllButton: {
			content: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.2049 5.28594C14.2049 5.02617 13.8972 4.88918 13.7042 5.063L6.95366 11.1415C6.77721 11.3004 6.77721 11.5771 6.95366 11.736L13.7042 17.8145C13.8972 17.9883 14.2049 17.8513 14.2049 17.5915V13.7843C17.5917 14.2667 20.438 16.6215 21.6843 17.808C21.8977 18.0111 22.2486 17.834 22.1843 17.5465C21.6752 15.2706 19.853 9.61904 14.2049 8.74667V5.28594ZM10.155 17.5929V16.0857L5.32587 11.7374C5.14942 11.5785 5.14942 11.3017 5.32587 11.1429L10.155 6.79448V5.28732C10.155 5.02755 9.84729 4.89055 9.65425 5.06438L2.90376 11.1429C2.7273 11.3017 2.7273 11.5785 2.90375 11.7374L9.65424 17.8158C9.84728 17.9897 10.155 17.8527 10.155 17.5929Z" fill="#00A2E8"/></svg>',
		},
		forwardButton: {
			content: `<svg width="31" height="30" viewBox="0 0 31 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M24.6954 14.7227L14.9727 5V11.25H9V18.75H14.9727V24.4454L24.6954 14.7227Z" fill="#00A2E8"/>
				</svg>`,
		},
		moreButton: {
			content: `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M7.5 17.5C8.88071 17.5 10 16.3807 10 15C10 13.6193 8.88071 12.5 7.5 12.5C6.11929 12.5 5 13.6193 5 15C5 16.3807 6.11929 17.5 7.5 17.5Z" fill="#00A2E8"/>
				<path d="M15 17.5C16.3807 17.5 17.5 16.3807 17.5 15C17.5 13.6193 16.3807 12.5 15 12.5C13.6193 12.5 12.5 13.6193 12.5 15C12.5 16.3807 13.6193 17.5 15 17.5Z" fill="#00A2E8"/>
				<path d="M25 15C25 16.3807 23.8807 17.5 22.5 17.5C21.1193 17.5 20 16.3807 20 15C20 13.6193 21.1193 12.5 22.5 12.5C23.8807 12.5 25 13.6193 25 15Z" fill="#00A2E8"/>
				</svg>`,
		},
		attachmentsButton: {
			content: `<svg width="30" height="30" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill="#00A2E8" d="m24.021021,12.708223c0.162148,0.162218 0.162148,0.425219 0,0.587425l-1.088606,1.088548c-0.162148,0.162206 -0.425242,0.162206 -0.58739,0l-6.410173,-6.410162c-1.843561,-1.843561 -4.860296,-1.843561 -6.703868,0c-1.843561,1.843561 -1.843561,4.860308 0,6.703868l7.863641,7.8637c1.156139,1.156139 3.033794,1.156139 4.189932,0c1.156139,-1.156139 1.156139,-3.033794 0,-4.189932l-7.025667,-7.025702c-0.469408,-0.469408 -1.206565,-0.469408 -1.675973,0c-0.469396,0.469408 -0.469396,1.206565 0,1.675973l5.572175,5.57214c0.162265,0.162265 0.162265,0.425242 0,0.587507l-1.088489,1.088489c-0.162265,0.162265 -0.425266,0.162265 -0.587472,0l-5.572175,-5.572175c-1.391188,-1.391188 -1.391188,-3.636719 0,-5.027895c1.391188,-1.391188 3.636719,-1.391188 5.027895,0l7.025702,7.025667c2.077907,2.077907 2.077907,5.464018 0,7.541925c-2.077907,2.07779 -5.464018,2.07779 -7.541855,0l-7.863677,-7.863759c-2.765341,-2.765318 -2.765341,-7.290426 0,-10.055767c2.765341,-2.765353 7.290461,-2.765353 10.055826,0l6.410173,6.41015z" clip-rule="evenodd" fill-rule="evenodd"/>
				</svg>`,
		},
	};

	const titles = {
		buttons: {
			replyButton: BX.message('MESSAGE_CHAIN_ACTION_PANEL_CONTEXT_MENU_REPLY'),
			replyAllButton: BX.message('MESSAGE_CHAIN_ACTION_PANEL_CONTEXT_MENU_REPLY_ALL'),
			forwardButton: BX.message('MESSAGE_CHAIN_ACTION_PANEL_CONTEXT_MENU_FORWARD'),
			moreButton: BX.message('MESSAGE_CHAIN_ACTION_PANEL_CONTEXT_MENU_MORE'),
		},
	};

	function TitleButton(text)
	{
		if (text)
		{
			return Text({
				style: {
					alignSelf: 'center',
					fontSize: 13,
					color: '#828B95',
				},
				text,
			});
		}

		return null;
	}

	function Button(props)
	{
		const {
			buttonKey,
		} = props;

		let {
			action,
		} = props;

		action = throttle(action, 500, this);

		return View(
			{
				testId: (`mail-action-panel-button-${buttonKey}`),
				onClick: () => {
					action();
				},
				style: {
					marginLeft: 5,
					marginRight: 5,
				},
			},
			Image({
				style: {
					alignSelf: 'center',
					width: 30,
					height: 30,
				},
				svg: svgIcons[buttonKey],
			}),
			TitleButton(titles.buttons[buttonKey]),
		);
	}

	class ActionPanel extends PureComponent
	{
		constructor(props)
		{
			super(props);

			const { actions } = props;

			this.actions = actions;
		}

		render()
		{
			if (this.props.indentStub)
			{
				return View({
					style: {
						height: (height * 1.5),
					},
				});
			}

			let style;
			let shadowProps = {};

			if (this.props.withoutStyles)
			{
				style = {
					width: '100%',
					paddingLeft: 12,
					paddingRight: 12,
					paddingTop: 6,
					paddingBottom: 6,
					backgroundColor: '#fff',
					height,
				};
			}
			else
			{
				shadowProps = {
					radius: 4,
					color: '#bbbbbb',
					offset: {
						x: 0,
						y: 2,
					},
					style: {
						borderRadius: 12,
					},
				};

				style = {
					width: '100%',
					paddingLeft: 12,
					paddingRight: 12,
					paddingTop: 6,
					paddingBottom: 6,
					borderRadius: 12,
					backgroundColor: '#fff',
					height,
				};
			}

			const buttons = Object.entries(this.actions).map(([key, value]) => {
				return Button({
					buttonKey: key,
					action: value,
				});
			});

			let justifyContent = 'flex-end';

			if (buttons.length >= 4)
			{
				justifyContent = 'space-around';
			}

			return View(
				{
					safeArea: {
						bottom: true,
						top: false,
						left: false,
						right: false,
					},
					style: {
						bottom: 0,
						position: 'absolute',
						width: '100%',
					},
				},
				Shadow(
					shadowProps,
					View(
						{
							style,
						},
						View(
							{
								style: {
									flex: 1,
									justifyContent,
									flexDirection: 'row',
									alignItems: 'center',
								},
							},
							...buttons,
						),
					),
				),
			);
		}
	}

	module.exports = { ActionPanel };
});
