/**
 * @module layout/ui/plan-restriction
 */
jn.define('layout/ui/plan-restriction', (require, exports, module) => {
	/**
	 * @class PlanRestriction
	 */
	class PlanRestriction extends LayoutComponent
	{
		static open(props, parentWidget = PageManager)
		{
			return new Promise(resolve => {
				const params = {
					modal: true,
					title: BX.prop.getString(props, 'title', ''),
					backdrop: {
						mediumPositionHeight: 464,
						forceDismissOnSwipeDown: true,
						horizontalSwipeAllowed: false,
						navigationBarColor: '#eef2f4',
					},
				};

				parentWidget
					.openWidget('layout', params)
					.then((layout) => {
						layout.showComponent(new this({ ...props, layout }));
						resolve(layout);
					})
				;
			});
		}

		constructor(props)
		{
			super(props);
			this.layout = props.layout || layout;
		}

		componentDidMount()
		{
			this.layout.enableNavigationBarBorder(false);
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: '#ffffff',
					},
				},
				View(
					{
						style: {
							backgroundColor: '#d5f4fd',
							paddingVertical: 24,
							alignItems: 'center',
							justifyContent: 'center',
							flexDirection: 'row',
							marginBottom: 24,
						},
					},
					View(
						{
							style: {
								width: 172,
								height: 172,
								alignItems: 'center',
								justifyContent: 'center',
							},
						},
						Image({
							style: {
								width: 116,
								height: 144,
							},
							svg: {
								content: icons.lock,
							},
						}),
					),
				),
				Text({
						style: {
							marginBottom: 24,
							marginHorizontal: 20,
							color: '#333333',
							fontSize: 16,
						},
						text: BX.prop.getString(this.props, 'text', BX.message('PLAN_RESTRICTION_DESCRIPTION')),
					},
				),
				View(
					{
						style: {
							flexDirection: 'row',
							marginHorizontal: 18,
						},
					},
					View(
						{
							style: {
								flex: 1,
								justifyContent: 'center',
								alignItems: 'center',
								borderWidth: 1,
								borderColor: '#828b95',
								borderRadius: 24,
								padding: 15,
								marginRight: 15,
							},
							onClick: () => {
								this.layout.close();
							},
						},
						Text({
							style: {
								color: '#333',
								fontWeight: '500',
								fontSize: 17,
								textAlign: 'center',
							},
							numberOfLines: 1,
							ellipsize: 'end',
							text: BX.message('PLAN_RESTRICTION_OK'),
						}),
					),
					View(
						{
							style: {
								flex: 1,
								justifyContent: 'center',
								alignItems: 'center',
								borderRadius: 24,
								backgroundColor: '#9dcf00',
								padding: 15,
							},
							onClick: () => {
								helpdesk.openHelpArticle('14095004', 'helpdesk');
							},
						},
						Text({
							style: {
								color: '#ffffff',
								fontWeight: '500',
								fontSize: 17,
								textAlign: 'center',
							},
							numberOfLines: 1,
							ellipsize: 'end',
							text: BX.message('PLAN_RESTRICTION_SEE_PLANS', 'helpdesk'),
						}),
					),
				),
			);
		}
	}

	const icons = {
		lock: `<svg width="116" height="144" viewBox="0 0 116 144" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M57.9996 22.8162C45.7146 22.8162 35.7554 32.8268 35.7554 45.1754V64H24V45.1754C24 26.3006 39.2225 11 57.9998 11C76.7775 11 92 26.3006 92 45.1754V64H80.2441V45.1754C80.2441 32.8268 70.2849 22.8162 57.9996 22.8162Z" fill="#E5F9FF"/><path d="M57.9996 22.2489C45.3985 22.2489 35.1881 32.5162 35.1881 45.1754V63.4327H24.5673V45.1754C24.5673 26.6112 39.5386 11.5673 57.9998 11.5673C76.4615 11.5673 91.4327 26.6112 91.4327 45.1754V63.4327H80.8114V45.1754C80.8114 32.5162 70.6009 22.2489 57.9996 22.2489Z" stroke="#2FC6F6" stroke-width="1.13462"/><path fill-rule="evenodd" clip-rule="evenodd" d="M26 62.4553C19.3726 62.4553 14 67.8279 14 74.4553V115.688C14 122.316 19.3726 127.688 26 127.688H90.7706C97.398 127.688 102.771 122.316 102.771 115.688V74.4553C102.771 67.8279 97.398 62.4553 90.7706 62.4553H26ZM63.5678 96.6972C63.5678 96.5776 63.622 96.4647 63.713 96.3871C65.4714 94.8857 66.5882 92.6466 66.5882 90.1443C66.5882 85.6231 62.9452 81.958 58.4519 81.958C53.9583 81.958 50.3153 85.6231 50.3153 90.1443C50.3153 92.6466 51.4324 94.8857 53.1905 96.3871C53.2815 96.4647 53.3356 96.5775 53.3356 96.6971V104.311C53.3356 107.136 55.6262 109.427 58.4517 109.427C61.2773 109.427 63.5678 107.136 63.5678 104.311V96.6972Z" fill="#E5F9FF"/><path d="M63.713 96.3871L64.4497 97.2499L64.4497 97.2499L63.713 96.3871ZM53.1905 96.3871L53.9273 95.5243L53.9273 95.5243L53.1905 96.3871ZM15.1346 74.4553C15.1346 68.4545 19.9992 63.5899 26 63.5899V61.3207C18.746 61.3207 12.8654 67.2013 12.8654 74.4553H15.1346ZM15.1346 115.688V74.4553H12.8654V115.688H15.1346ZM26 126.554C19.9992 126.554 15.1346 121.689 15.1346 115.688H12.8654C12.8654 122.942 18.746 128.823 26 128.823V126.554ZM90.7706 126.554H26V128.823H90.7706V126.554ZM101.636 115.688C101.636 121.689 96.7714 126.554 90.7706 126.554V128.823C98.0247 128.823 103.905 122.942 103.905 115.688H101.636ZM101.636 74.4553V115.688H103.905V74.4553H101.636ZM90.7706 63.5899C96.7714 63.5899 101.636 68.4545 101.636 74.4553H103.905C103.905 67.2013 98.0246 61.3207 90.7706 61.3207V63.5899ZM26 63.5899H90.7706V61.3207H26V63.5899ZM62.9762 95.5242C62.6436 95.8082 62.4332 96.2316 62.4332 96.6972H64.7025C64.7025 96.9235 64.6005 97.1213 64.4497 97.2499L62.9762 95.5242ZM65.4536 90.1443C65.4536 92.3016 64.4922 94.2298 62.9762 95.5242L64.4497 97.2499C66.4505 95.5416 67.7228 92.9916 67.7228 90.1443H65.4536ZM58.4519 83.0926C62.312 83.0926 65.4536 86.2432 65.4536 90.1443H67.7228C67.7228 85.003 63.5783 80.8233 58.4519 80.8233V83.0926ZM51.4499 90.1443C51.4499 86.2432 54.5914 83.0926 58.4519 83.0926V80.8233C53.3252 80.8233 49.1806 85.0029 49.1806 90.1443H51.4499ZM53.9273 95.5243C52.4115 94.2298 51.4499 92.3015 51.4499 90.1443H49.1806C49.1806 92.9916 50.4533 95.5416 52.4537 97.2499L53.9273 95.5243ZM54.4703 96.6971C54.4703 96.2317 54.2599 95.8083 53.9273 95.5243L52.4537 97.2499C52.303 97.1212 52.201 96.9234 52.201 96.6971H54.4703ZM54.4703 104.311V96.6971H52.201V104.311H54.4703ZM58.4517 108.292C56.2528 108.292 54.4703 106.51 54.4703 104.311H52.201C52.201 107.763 54.9996 110.562 58.4517 110.562V108.292ZM62.4332 104.311C62.4332 106.51 60.6506 108.292 58.4517 108.292V110.562C61.9039 110.562 64.7025 107.763 64.7025 104.311H62.4332ZM62.4332 96.6972V104.311H64.7025V96.6972H62.4332Z" fill="#2FC6F6"/></svg>`,
	};

	module.exports = { PlanRestriction };
});