/**
 * @module bizproc/task/tasks-performer/informers/similar-tasks-informer
 */
jn.define('bizproc/task/tasks-performer/informers/similar-tasks-informer', (require, exports, module) => {
	const { AppTheme } = require('apptheme/extended');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { PureComponent } = require('layout/pure-component');
	const { SafeImage } = require('layout/ui/safe-image');
	const { AcceptButton, DetailButton, Button } = require('bizproc/task/buttons');

	class SimilarTasksInformer extends PureComponent
	{
		static open(props = {}, layout = PageManager)
		{
			return new Promise((resolve, reject) => {
				layout.openWidget(
					'layout',
					{
						modal: true,
						titleParams: {
							text: Loc.getMessage('BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_SIMILAR_TASKS_TITLE'),
							type: 'dialog',
						},
						backgroundColor: AppTheme.colors.bgContentPrimary,
						backdrop: {
							onlyMediumPosition: false,
							mediumPositionPercent: 83,
							navigationBarColor: AppTheme.colors.bgSecondary,
							swipeAllowed: true,
							swipeContentAllowed: true,
							horizontalSwipeAllowed: false,
						},
						onReady: (readyLayout) => {
							readyLayout.showComponent(new SimilarTasksInformer({
								layout: readyLayout,
								onClose: resolve,
								name: props.typeName,
								count: props.count,
								generateExitButton: props.generateExitButton,
							}));
						},
					},
				).then(() => {}).catch(reject);
			});
		}

		constructor(props)
		{
			super(props);

			this.state = {
				name: BX.prop.getString(this.props, 'name', ''),
				count: BX.prop.getInteger(this.props, 'count', 0),
			};

			this.result = {
				applyToAll: false,
				seeDetails: false,
				doOneByOne: false,
				cancel: false,
			};
			this.onViewHidden = () => {
				this.onClose(this.result);
			};

			this.exitButton = null;
			if (Type.isFunction(props.generateExitButton))
			{
				this.exitButton = props.generateExitButton(() => {
					this.result = { cancel: true };
					this.layout.close();
				});
			}
		}

		componentDidMount()
		{
			if (this.exitButton)
			{
				this.layout.setRightButtons([this.exitButton]);
			}

			this.layout.on('onViewHidden', this.onViewHidden);
		}

		componentWillUnmount()
		{
			this.layout.off('onViewHidden', this.onViewHidden);
		}

		get layout()
		{
			return this.props.layout;
		}

		get onClose()
		{
			return BX.prop.getFunction(this.props, 'onClose', () => {});
		}

		render()
		{
			return View(
				{ style: { flexDirection: 'column' } },
				ScrollView(
					{
						style: { flex: 1 },
					},
					View(
						{},
						this.renderContent(),
						this.renderHelp(),
					),
				),
				Shadow(
					{
						inset: { left: 8, right: 8, bottom: 8 },
						radius: 8,
						color: AppTheme.colors.shadowPrimary,
						style: { backgroundColor: AppTheme.colors.bgContentPrimary },
					},
					this.renderButtons(),
				),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						marginTop: 34,
						marginHorizontal: 33,
						marginBottom: 27,
						alignItems: 'center',
					},
				},
				this.renderImage(),
				this.renderTitle(),
			);
		}

		renderImage()
		{
			return SafeImage({
				style: { width: 144, height: 145 },
				resizeMode: 'contain',
				placeholder: {
					content: multipleTasksImage(),
				},
			});
		}

		renderTitle()
		{
			return View(
				{
					style: {
						marginTop: 34,
						flexDirection: 'column',
					},
				},
				Text({
					style: {
						fontSize: 18,
						fontWeight: '400',
						color: AppTheme.colors.base2,
						textAlign: 'center',
					},
					text: Loc.getMessage('BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_SIMILAR_TASKS_DESCRIPTION'),
				}),
				BBCodeText({
					style: {
						marginTop: 6,
						fontSize: 18,
						fontWeight: '500',
						color: AppTheme.colors.base1,
						textAlign: 'center',
						lineHeightMultiple: 1.05,
					},
					value: Loc.getMessage(
						'BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_REMAINING_TASKS_TO_COMPLETE',
						{ '#COUNT#': this.state.count, '#TYPE_NAME#': this.state.name },
					),
				}),
			);
		}

		renderHelp()
		{
			return View(
				{},
				Image({
					style: {
						width: 22,
						height: 27,
						position: 'absolute',
						top: 12,
						left: 3,
						zIndex: 100,
					},
					svg: { content: corner },
				}),
				View(
					{
						style: {
							marginHorizontal: 16,
							marginTop: 16,
							paddingTop: 10,
							paddingHorizontal: 18,
							paddingBottom: 14,
							borderRadius: 18,
							borderStyle: 'solid',
							borderWidth: 1,
							borderColor: AppTheme.colors.accentSoftBlue1,
						},
					},
					Text({
						style: {
							fontSize: 14,
							fontWeight: '400',
							color: AppTheme.colors.base2,
							lineHeightMultiple: 1.1,
						},
						text: Loc.getMessage('BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_MULTIPLE_TASK_COMPLETION'),
					}),
				),
			);
		}

		renderButtons()
		{
			return View(
				{
					style: { marginHorizontal: 18, marginTop: 22 },
					safeArea: { bottom: true },
				},
				View(
					{ style: { marginBottom: 20 } },
					new AcceptButton({
						text: Loc.getMessage('BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_APPLY_TO_ALL'),
						onClick: () => {
							this.result = { applyToAll: true };
							this.layout.close();
						},
						testId: 'MBPTasksPerformerInformersApplyToAllButton',
					}),
				),
				View(
					{ style: { marginBottom: 20 } },
					new Button({
						text: Loc.getMessage('BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_DO_ONE_BY_ONE'),
						style: { borderColor: AppTheme.colors.base3, textColor: AppTheme.colors.base2 },
						onClick: () => {
							this.result = { doOneByOne: true };
							this.layout.close();
						},
						testId: 'MBPTasksPerformerInformersDoOneByOneButton',
					}),
				),
				View(
					{ style: { marginBottom: 21 } },
					new DetailButton({
						text: Loc.getMessage('BPMOBILE_TASK_TASKS_PERFORMER_INFORMERS_SEE_DETAILS'),
						style: { textColor: AppTheme.colors.base2 },
						onClick: () => {
							this.result = { seeDetails: true };
							this.layout.close();
						},
						testId: 'MBPTasksPerformerInformersSeeDetailsButton',
					}),
				),
			);
		}
	}

	const multipleTasksImage = () => {
		return `
			<svg width="148" height="147" viewBox="0 0 148 147" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g opacity="0.3">
					<path
						fill-rule="evenodd"
						clip-rule="evenodd"
						d="M76.5927 39.0293C77.2071 38.9967 77.8104 39.0273 78.3962 39.1158L78.2073 40.3836C77.7058 40.3079 77.1883 40.2814 76.6599 40.3095L76.5641 40.3146L76.4969 39.0344L76.5927 39.0293ZM76.465 39.0361L76.4969 39.0344L76.5641 40.3146L76.5321 40.3163C76.181 40.335 75.8814 40.0636 75.8628 39.7101C75.8443 39.3565 76.1139 39.0548 76.465 39.0361ZM81.7686 40.3682C82.7946 41.0117 83.6826 41.8598 84.375 42.8574L83.3313 43.5917C82.7374 42.736 81.9754 42.0083 81.0954 41.4563L81.7686 40.3682ZM85.795 46.1821C85.9137 46.7664 85.9759 47.3714 85.9759 47.9908C85.9759 48.5867 85.9182 49.1697 85.8077 49.7342L84.5585 49.4864C84.653 49.0031 84.7027 48.5031 84.7027 47.9908C84.7027 47.4581 84.6492 46.9392 84.5475 46.4389L85.795 46.1821ZM84.4866 52.9587C83.8413 53.9345 83.0113 54.7758 82.0464 55.4321L81.3337 54.3698C82.1612 53.8069 82.8734 53.0851 83.4268 52.2482L84.4866 52.9587ZM78.8527 56.7836C78.2928 56.8986 77.7142 56.9607 77.1224 56.9646L75.4378 56.9759L75.4293 55.694L77.1139 55.6827C77.6226 55.6793 78.1189 55.6259 78.5983 55.5275L78.8527 56.7836ZM72.0685 56.9985L68.6992 57.0211L68.6908 55.7392L72.06 55.7166L72.0685 56.9985ZM65.33 57.0437L61.9607 57.0663L61.9522 55.7844L65.3215 55.7618L65.33 57.0437ZM58.5915 57.0889L55.2222 57.1116L55.2137 55.8296L58.583 55.807L58.5915 57.0889ZM51.8529 57.1342L48.4837 57.1568L48.4752 55.8748L51.8445 55.8522L51.8529 57.1342ZM45.1144 57.1794L41.7452 57.202L41.7367 55.92L45.1059 55.8974L45.1144 57.1794ZM38.3759 57.2246L35.0066 57.2472L34.9982 55.9652L38.3674 55.9426L38.3759 57.2246ZM31.6374 57.2698L28.2681 57.2924L28.2596 56.0104L31.6289 55.9878L31.6374 57.2698ZM24.8989 57.315L23.2142 57.3263C22.7223 57.3296 22.2424 57.3812 21.7789 57.4763L21.5245 56.2202C22.0685 56.1085 22.6307 56.0482 23.2058 56.0443L24.8904 56.033L24.8989 57.315ZM19.1339 58.5958C18.3337 59.1401 17.6451 59.838 17.1099 60.6473L16.0501 59.9368C16.6771 58.9886 17.4837 58.1711 18.4212 57.5335L19.1339 58.5958ZM16.0157 63.3178C15.9242 63.7851 15.8762 64.2686 15.8762 64.764C15.8762 65.2615 15.9247 65.747 16.0168 66.2162L14.7678 66.465C14.6596 65.9143 14.603 65.3455 14.603 64.764C14.603 64.185 14.6591 63.6185 14.7664 63.07L16.0157 63.3178ZM17.1201 68.8961C17.6596 69.7075 18.3536 70.4062 19.1594 70.9494L18.4511 72.0147C17.5069 71.3782 16.6942 70.5599 16.0621 69.6093L17.1201 68.8961ZM21.8211 72.0602C22.287 72.153 22.7693 72.2018 23.2635 72.2018H24.9763V73.4838H23.2635C22.6859 73.4838 22.1209 73.4267 21.574 73.3178L21.8211 72.0602ZM28.4021 72.2018H30.1149V73.4838H28.4021V72.2018Z"
						fill="${AppTheme.colors.accentBrandBlue}"/>
					<path
						fill-rule="evenodd"
						clip-rule="evenodd"
						d="M100.817 72.4852C100.817 72.1312 101.102 71.8442 101.454 71.8442H102.019V73.1262H101.454C101.102 73.1262 100.817 72.8392 100.817 72.4852ZM103.15 71.8442H103.715C104.329 71.8442 104.929 71.9048 105.51 72.0206L105.263 73.2782C104.763 73.1785 104.246 73.1262 103.715 73.1262H103.15V71.8442ZM108.828 73.405C109.831 74.0812 110.695 74.9507 111.366 75.9607L110.308 76.6738C109.729 75.8031 108.984 75.0533 108.12 74.4703L108.828 73.405ZM112.741 79.3011C112.856 79.8862 112.916 80.4905 112.916 81.1084C112.916 81.7263 112.856 82.3307 112.741 82.9158L111.492 82.667C111.591 82.1634 111.643 81.6423 111.643 81.1084C111.643 80.5745 111.591 80.0535 111.492 79.5499L112.741 79.3011ZM111.366 86.2562C110.695 87.2662 109.831 88.1357 108.828 88.8118L108.12 87.7466C108.984 87.1636 109.729 86.4138 110.308 85.543L111.366 86.2562ZM105.51 90.1963C104.929 90.312 104.329 90.3726 103.715 90.3726L101.888 90.3726V89.0907L103.715 89.0907C104.246 89.0907 104.763 89.0383 105.263 88.9387L105.51 90.1963ZM98.2321 90.3726L94.5767 90.3726V89.0907L98.2321 89.0907V90.3726ZM90.9213 90.3726L87.266 90.3726V89.0907L90.9213 89.0907V90.3726ZM83.6106 90.3726L79.9552 90.3726L79.9552 89.0907L83.6106 89.0907V90.3726ZM76.2998 90.3726L72.6444 90.3726L72.6444 89.0907L76.2998 89.0907V90.3726ZM68.989 90.3726L65.3336 90.3726V89.0907L68.989 89.0907V90.3726ZM61.6782 90.3726L59.8505 90.3727C59.3524 90.3727 58.8662 90.4218 58.3965 90.5154L58.1494 89.2578C58.7001 89.1481 59.269 89.0907 59.8505 89.0907L61.6782 89.0907L61.6782 90.3726ZM55.7134 91.6352C54.9011 92.1827 54.2015 92.8871 53.6577 93.705L52.5996 92.9918C53.2361 92.0346 54.0544 91.2107 55.0051 90.5699L55.7134 91.6352ZM52.5455 96.4064C52.4526 96.8794 52.4037 97.3689 52.4037 97.8704C52.4037 98.372 52.4526 98.8614 52.5455 99.3344L51.2964 99.5832C51.1875 99.0287 51.1305 98.4559 51.1305 97.8704C51.1305 97.2849 51.1875 96.7121 51.2965 96.1576L52.5455 96.4064ZM53.6576 102.036C54.2014 102.854 54.9009 103.558 55.7132 104.106L55.0048 105.171C54.0542 104.53 53.2359 103.706 52.5995 102.749L53.6576 102.036ZM58.3962 105.225C58.8659 105.319 59.3521 105.368 59.8502 105.368H61.4388V106.65H59.8502C59.2686 106.65 58.6998 106.593 58.1491 106.483L58.3962 105.225ZM64.6159 105.368H66.2045V106.65H64.6159V105.368Z"
						fill="${AppTheme.colors.accentBrandBlue}"
					/>
					<path
						d="M27.3708 70.7693C27.3708 70.0527 28.1941 69.6543 28.7499 70.102L31.4427 72.271C31.8675 72.6131 31.8675 73.2636 31.4427 73.6057L28.7499 75.7748C28.1941 76.2224 27.3708 75.824 27.3708 75.1074V70.7693Z"
						fill="${AppTheme.colors.accentBrandBlue}"
					/>
					<path
						d="M65.2244 103.931C65.2244 103.214 66.0477 102.816 66.6035 103.264L69.2963 105.433C69.7211 105.775 69.7211 106.425 69.2963 106.767L66.6035 108.936C66.0477 109.384 65.2244 108.986 65.2244 108.269V103.931Z"
						fill="${AppTheme.colors.accentBrandBlue}"
					/>
				</g>
				<g filter="url(#filter0_d_6501_61623)">
					<path
						d="M7.39868 33.7358C7.39868 30.9038 9.67891 28.6079 12.4917 28.6079H67.972C70.7848 28.6079 73.065 30.9038 73.065 33.7358V46.1766C73.065 49.0086 70.7848 51.3045 67.972 51.3045H12.4917C9.67891 51.3045 7.39868 49.0086 7.39868 46.1766V33.7358Z"
						fill="${AppTheme.colors.graphicsBase1}"
					/>
				</g>
				<path
					fill-rule="evenodd"
					clip-rule="evenodd"
					d="M67.972 29.4626H12.4917C10.1477 29.4626 8.24752 31.3758 8.24752 33.7358V46.1766C8.24752 48.5366 10.1477 50.4498 12.4917 50.4498H67.972C70.316 50.4498 72.2162 48.5366 72.2162 46.1766V33.7358C72.2162 31.3758 70.316 29.4626 67.972 29.4626ZM12.4917 28.6079C9.67891 28.6079 7.39868 30.9038 7.39868 33.7358V46.1766C7.39868 49.0086 9.67891 51.3045 12.4917 51.3045H67.972C70.7848 51.3045 73.065 49.0086 73.065 46.1766V33.7358C73.065 30.9038 70.7848 28.6079 67.972 28.6079H12.4917Z"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<g filter="url(#filter1_d_6501_61623)">
					<path
						d="M32.8812 66.6802C32.8812 63.8481 35.1615 61.5522 37.9742 61.5522H90.3411C92.3751 61.5522 94.214 62.7707 95.0186 64.6516L97.6797 70.8719C98.2337 72.1668 98.2337 73.6342 97.6797 74.9291L95.0186 81.1495C94.214 83.0304 92.3751 84.2488 90.3411 84.2488H37.9743C35.1615 84.2488 32.8812 81.953 32.8812 79.1209V66.6802Z"
						fill="${AppTheme.colors.graphicsBase1}"
					/>
				</g>
				<path
					fill-rule="evenodd"
					clip-rule="evenodd"
					d="M90.3411 62.4069H37.9742C35.6303 62.4069 33.7301 64.3201 33.7301 66.6802V79.1209C33.7301 81.481 35.6303 83.3942 37.9743 83.3942H90.3411C92.0361 83.3942 93.5685 82.3788 94.239 80.8114L96.9002 74.591C97.3618 73.512 97.3618 72.2891 96.9002 71.21L94.239 64.9897C93.5685 63.4223 92.0361 62.4069 90.3411 62.4069ZM37.9742 61.5522C35.1615 61.5522 32.8812 63.8481 32.8812 66.6802V79.1209C32.8812 81.953 35.1615 84.2488 37.9743 84.2488H90.3411C92.3751 84.2488 94.214 83.0304 95.0186 81.1495L97.6797 74.9291C98.2337 73.6342 98.2337 72.1668 97.6797 70.8719L95.0186 64.6516C94.214 62.7707 92.3751 61.5522 90.3411 61.5522H37.9742Z"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<g filter="url(#filter2_d_6501_61623)">
					<path
						d="M71.1048 99.624C71.1048 96.7919 73.385 94.4961 76.1978 94.4961H131.678C134.491 94.4961 136.771 96.7919 136.771 99.624V112.065C136.771 114.897 134.491 117.193 131.678 117.193H76.1978C73.385 117.193 71.1048 114.897 71.1048 112.065V99.624Z"
						fill="${AppTheme.colors.graphicsBase1}"
					/>
				</g>
				<path
					fill-rule="evenodd"
					clip-rule="evenodd"
					d="M131.678 95.3507H76.1978C73.8538 95.3507 71.9536 97.264 71.9536 99.624V112.065C71.9536 114.425 73.8538 116.338 76.1978 116.338H131.678C134.022 116.338 135.922 114.425 135.922 112.065V99.624C135.922 97.2639 134.022 95.3507 131.678 95.3507ZM76.1978 94.4961C73.385 94.4961 71.1048 96.7919 71.1048 99.624V112.065C71.1048 114.897 73.385 117.193 76.1978 117.193H131.678C134.491 117.193 136.771 114.897 136.771 112.065V99.624C136.771 96.7919 134.491 94.4961 131.678 94.4961H76.1978Z"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<ellipse
					opacity="0.3"
					cx="18.5326"
					cy="39.9563"
					rx="5.25331"
					ry="5.28929"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<ellipse
					opacity="0.3"
					cx="44.0151"
					cy="72.9001"
					rx="5.25331"
					ry="5.28929"
					fill="${AppTheme.colors.accentBrandBlue}"
					/>
				<ellipse
					opacity="0.3"
					cx="83.2189"
					cy="105.844"
					rx="5.25331"
					ry="5.28929"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<path
					opacity="0.3"
					d="M27.9807 39.9563C27.9807 39.1388 28.6389 38.4761 29.4509 38.4761H54.9333C55.7453 38.4761 56.4035 39.1388 56.4035 39.9563C56.4035 40.7738 55.7453 41.4365 54.9333 41.4365H29.4509C28.6389 41.4365 27.9807 40.7738 27.9807 39.9563Z"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<path
					opacity="0.3"
					d="M53.4632 72.9001C53.4632 72.0826 54.1214 71.4199 54.9333 71.4199H80.4158C81.2277 71.4199 81.8859 72.0826 81.8859 72.9001C81.8859 73.7176 81.2277 74.3803 80.4158 74.3803H54.9333C54.1214 74.3803 53.4632 73.7176 53.4632 72.9001Z"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<path
					opacity="0.3"
					d="M92.667 105.844C92.667 105.027 93.3252 104.364 94.1371 104.364H119.62C120.432 104.364 121.09 105.027 121.09 105.844C121.09 106.662 120.432 107.325 119.62 107.325H94.1371C93.3252 107.325 92.667 106.662 92.667 105.844Z"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<g filter="url(#filter3_d_6501_61623)">
					<path
						d="M103.844 39.9563C103.844 38.5938 104.941 37.4893 106.294 37.4893C107.647 37.4893 108.744 38.5938 108.744 39.9563V97.1911C108.744 98.5536 107.647 99.6582 106.294 99.6582C104.941 99.6582 103.844 98.5536 103.844 97.1911V39.9563Z"
						fill="${AppTheme.colors.graphicsBase1}"
					/>
				</g>
				<path
					fill-rule="evenodd"
					clip-rule="evenodd"
					d="M106.294 38.3439C105.41 38.3439 104.693 39.0658 104.693 39.9563V97.1911C104.693 98.0816 105.41 98.8035 106.294 98.8035C107.178 98.8035 107.895 98.0816 107.895 97.1911V39.9563C107.895 39.0658 107.178 38.3439 106.294 38.3439ZM106.294 37.4893C104.941 37.4893 103.844 38.5938 103.844 39.9563V97.1911C103.844 98.5536 104.941 99.6582 106.294 99.6582C107.647 99.6582 108.744 98.5536 108.744 97.1911V39.9563C108.744 38.5938 107.647 37.4893 106.294 37.4893Z"
					fill="${AppTheme.colors.accentBrandBlue}"
				/>
				<g filter="url(#filter4_d_6501_61623)">
					<path
						d="M110.704 42.159C110.704 41.215 111.464 40.4497 112.402 40.4497H143.271C144.526 40.4497 145.347 41.7728 144.797 42.9083L139.659 53.5158C139.43 53.9886 139.43 54.5414 139.659 55.0143L144.797 65.6218C145.347 66.7572 144.526 68.0803 143.271 68.0803H112.402C111.464 68.0803 110.704 67.315 110.704 66.371V42.159Z"
						fill="${AppTheme.colors.accentBrandBlue}"
					/>
				</g>
				<defs>
					<filter
						id="filter0_d_6501_61623"
						x="4.39868"
						y="26.6079"
						width="71.6664"
						height="28.6968"
						filterUnits="userSpaceOnUse"
						color-interpolation-filters="sRGB"
					>
						<feFlood flood-opacity="0" result="BackgroundImageFix"/>
						<feColorMatrix
							in="SourceAlpha"
							type="matrix"
							values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
							result="hardAlpha"
						/>
						<feOffset dy="1"/>
						<feGaussianBlur stdDeviation="1.5"/>
						<feComposite in2="hardAlpha" operator="out"/>
						<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_6501_61623"/>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_6501_61623" result="shape"/>
					</filter>
					<filter
						id="filter1_d_6501_61623"
						x="29.8812"
						y="59.5522"
						width="71.214"
						height="28.6968"
						filterUnits="userSpaceOnUse"
						color-interpolation-filters="sRGB"
					>
						<feFlood flood-opacity="0" result="BackgroundImageFix"/>
						<feColorMatrix
							in="SourceAlpha"
							type="matrix"
							values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
							result="hardAlpha"
						/>
						<feOffset dy="1"/>
						<feGaussianBlur stdDeviation="1.5"/>
						<feComposite in2="hardAlpha" operator="out"/>
						<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_6501_61623"/>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_6501_61623" result="shape"/>
					</filter>
					<filter
						id="filter2_d_6501_61623"
						x="68.1048"
						y="92.4961"
						width="71.6664"
						height="28.6968"
						filterUnits="userSpaceOnUse"
						color-interpolation-filters="sRGB"
					>
						<feFlood flood-opacity="0" result="BackgroundImageFix"/>
						<feColorMatrix
							in="SourceAlpha"
							type="matrix"
							values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
							result="hardAlpha"
						/>
						<feOffset dy="1"/>
						<feGaussianBlur stdDeviation="1.5"/>
						<feComposite in2="hardAlpha" operator="out"/>
						<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_6501_61623"/>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_6501_61623" result="shape"/>
					</filter>
					<filter
						id="filter3_d_6501_61623"
						x="100.844"
						y="35.4893"
						width="10.9005"
						height="68.1689"
						filterUnits="userSpaceOnUse"
						color-interpolation-filters="sRGB"
					>
						<feFlood flood-opacity="0" result="BackgroundImageFix"/>
						<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
						<feOffset dy="1"/>
						<feGaussianBlur stdDeviation="1.5"/>
						<feComposite in2="hardAlpha" operator="out"/>
						<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_6501_61623"/>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_6501_61623" result="shape"/>
					</filter>
					<filter
						id="filter4_d_6501_61623"
						x="107.704"
						y="38.4497"
						width="40.2665"
						height="33.6309"
						filterUnits="userSpaceOnUse"
						color-interpolation-filters="sRGB"
					>
						<feFlood flood-opacity="0" result="BackgroundImageFix"/>
						<feColorMatrix
							in="SourceAlpha"
							type="matrix"
							values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
							result="hardAlpha"
						/>
						<feOffset dy="1"/>
						<feGaussianBlur stdDeviation="1.5"/>
						<feComposite in2="hardAlpha" operator="out"/>
						<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/>
						<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_6501_61623"/>
						<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_6501_61623" result="shape"/>
					</filter>
				</defs>
			</svg>
		`;
	};

	const corner = `
		<svg width="22" height="27" viewBox="0 0 22 27" fill="none" xmlns="http://www.w3.org/2000/svg">
			<g clip-path="url(#clip0_1238_9544)">
				<path 
					opacity="0.96"
					d="M14.5837 22.1224C13.6399 20.1725 10.9762 15.3838 6.31298 12.3866C6.14114 12.2761 6.0516 12.0982 6.04488 11.9336C6.03854 11.7781 6.10306 11.6474 6.24478 11.5687C9.01174 10.0322 12.2135 9.64873 14.9635 9.71442C17.7085 9.77999 19.9592 10.2919 20.7905 10.5064C20.8917 10.5325 20.9825 10.598 21.0492 10.7046L25.702 18.1501C25.8722 18.4224 25.7453 18.7821 25.442 18.8874L15.2645 22.421C15.0116 22.5088 14.7121 22.3876 14.5837 22.1224Z"
					fill="${AppTheme.colors.bgContentPrimary}"
					stroke="${AppTheme.colors.accentSoftBlue1}"
					stroke-linejoin="round"
				/>
			</g>
			<defs>
				<clipPath id="clip0_1238_9544">
					<rect 
						width="11.7028"
						height="23"
						fill="${AppTheme.colors.bgContentPrimary}"
						transform="matrix(0.899448 0.437028 0.437028 -0.899448 0.443604 21.4171)"
					/>
				</clipPath>
			</defs>
		</svg>
	`;

	module.exports = { SimilarTasksInformer };
});
