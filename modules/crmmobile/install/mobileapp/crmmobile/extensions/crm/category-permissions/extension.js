/**
 * @module crm/category-permissions
 */
jn.define('crm/category-permissions', (require, exports, module) => {
	const { Type } = require('crm/type');
	const AppTheme = require('apptheme');
	const { getEntityMessage } = require('crm/loc');
	const { ImageAfterTypes } = require('layout/ui/context-menu/item');
	const { ContextMenu } = require('layout/ui/context-menu');

	const ACCESS = {
		ALL_FOR_ALL: 'X',
		NONE_FOR_ALL: '',
		OWN_FOR_ALL: 'A',
	};

	/**
	 * @class CategoryPermissions
	 */
	class CategoryPermissions extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				access: this.props.access,
			};

			this.menu = null;
		}

		get entityTypeId()
		{
			return BX.prop.getInteger(this.props, 'entityTypeId', 2);
		}

		get categoriesEnabled()
		{
			return BX.prop.getBoolean(this.props, 'categoriesEnabled', false);
		}

		get layout()
		{
			return BX.prop.get(this.props, 'layout', PageManager);
		}

		render()
		{
			return View(
				{
					style: styles.wrapper,
					onClick: () => this.showMenu(),
				},
				View(
					{
						style: styles.accessIconContainer,
					},
					Image({
						style: styles.accessIcon,
						tintColor: AppTheme.colors.base3,
						svg: {
							content: svgImages.access,
						},
					}),
				),
				View(
					{
						style: styles.content,
					},
					Text({
						style: styles.title,
						text: this.getPermissionTitle(),
					}),
					Text({
						style: styles.accessText,
						text: this.getCurrentAccessText(),
					}),
				),
				View(
					{
						style: styles.arrowIconContainer,
					},
					Image({
						style: styles.arrowIcon,
						tintColor: AppTheme.colors.base3,
						svg: {
							content: svgImages.arrow,
						},
					}),
				),
			);
		}

		getPermissionTitle()
		{
			if (this.categoriesEnabled)
			{
				return BX.message('M_CRM_CATEGORY_PERMISSION_TITLE2');
			}

			return BX.message('M_CRM_CATEGORY_PERMISSION_ENTITY_TITLE');
		}

		getCurrentAccessText()
		{
			if (this.state.access === ACCESS.ALL_FOR_ALL)
			{
				return this.getAllForAllTitle();
			}

			if (this.state.access === ACCESS.OWN_FOR_ALL)
			{
				return this.getOwnForAllTitle();
			}

			if (this.state.access === ACCESS.NONE_FOR_ALL)
			{
				return BX.message('M_CRM_CATEGORY_PERMISSION_NONE_FOR_ALL');
			}

			return BX.message('M_CRM_CATEGORY_PERMISSION_MENU_CUSTOM');
		}

		getAllForAllTitle()
		{
			return getEntityMessage('M_CRM_CATEGORY_PERMISSION_ALL_FOR_ALL', this.entityTypeId);
		}

		getOwnForAllTitle()
		{
			return getEntityMessage('M_CRM_CATEGORY_PERMISSION_OWN_FOR_ALL', this.entityTypeId);
		}

		showMenu()
		{
			this.menu = new ContextMenu({
				actions: this.getMenuActions(),
				titlesBySectionCode: {
					managers: Type.isDynamicTypeById(this.entityTypeId)
						? BX.message('M_CRM_CATEGORY_PERMISSION_ITEM_PRIMARY_SECTION')
						: BX.message('M_CRM_CATEGORY_PERMISSION_DEAL_PRIMARY_SECTION'),
					additional: BX.message('M_CRM_CATEGORY_PERMISSION_MENU_ADDITIONAL_ACCESS'),
				},
				params: {
					title: this.getPermissionTitle(),
					showCancelButton: false,
					showActionLoader: false,
				},
				layoutWidget: this.layout,
			});

			this.menu.show(this.layout);
		}

		getMenuActions()
		{
			const { access } = this.state;

			return [
				{
					id: 'allForAll',
					title: this.getAllForAllTitle(),
					sectionCode: 'managers',
					isSelected: access === ACCESS.ALL_FOR_ALL,
					data: {
						svgIcon: svgImages.allForAllAccess,
					},
					showSelectedImage: true,
					onClickCallback: () => this.setAccess(ACCESS.ALL_FOR_ALL),
				},
				{
					id: 'ownForAll',
					title: this.getOwnForAllTitle(),
					sectionCode: 'managers',
					isSelected: access === ACCESS.OWN_FOR_ALL,
					data: {
						svgIcon: svgImages.ownForAllAccess,
					},
					showSelectedImage: true,
					onClickCallback: () => this.setAccess(ACCESS.OWN_FOR_ALL),
				},
				{
					id: 'noneForAll',
					title: BX.message('M_CRM_CATEGORY_PERMISSION_NONE_FOR_ALL_MSGVER_1'),
					sectionCode: 'managers',
					isSelected: access === ACCESS.NONE_FOR_ALL,
					data: {
						svgIcon: svgImages.noneForAllAccess,
					},
					showSelectedImage: true,
					onClickCallback: () => this.setAccess(ACCESS.NONE_FOR_ALL),
				},
				this.categoriesEnabled && {
					id: 'from-tunnels',
					sectionCode: 'additional',
					title: BX.message('M_CRM_CATEGORY_PERMISSION_MENU_COPY_FROM_TUNNELS2_MSGVER_1'),
					data: {
						svgIcon: svgImages.copyFromTunnelAccess,
					},
					onClickCallback: (id, parentId, parentParams) => this.openCategoryList(parentParams),
				},
				{
					id: 'custom',
					sectionCode: 'additional',
					title: BX.message('M_CRM_CATEGORY_PERMISSION_MENU_CUSTOM'),
					isSelected: this.isCustomAccess(access),
					showSelectedImage: true,
					data: {
						svgIcon: svgImages.customAccess,
						svgIconAfter: {
							type: ImageAfterTypes.WEB,
						},
					},
					onClickCallback: this.openOnDesktop.bind(this),
				},
			].filter(Boolean);
		}

		isCustomAccess(access)
		{
			return access !== ACCESS.OWN_FOR_ALL
				&& access !== ACCESS.NONE_FOR_ALL
				&& access !== ACCESS.ALL_FOR_ALL;
		}

		openOnDesktop()
		{
			this.menu.close(() => {
				qrauth.open({
					redirectUrl: '/crm/configs/perms/',
					analyticsSection: 'crm',
				});
			});

			return Promise.resolve({ closeMenu: false });
		}

		setAccess(access)
		{
			return new Promise((resolve) => {
				this.setState({ access }, () => {
					this.onChange(access);
					resolve();
				});
			});
		}

		onChange(access)
		{
			const { onChange } = this.props;
			if (typeof onChange === 'function')
			{
				onChange(access);
			}
		}

		async openCategoryList({ parentWidget })
		{
			const { CategoryListView } = await requireLazy('crm:category-list-view');

			return new Promise((resolve, reject) => {
				CategoryListView.open(
					{
						entityTypeId: this.entityTypeId,
						onSelectCategory: ({ categoryId }) => this.setAccess(categoryId).then(resolve),
						readOnly: true,
						enableSelect: true,
						showCounters: false,
						showTunnels: false,
						currentCategoryId: this.state.access,
					},
					{},
					parentWidget,
				).then((layout) => layout.setListener((eventName) => {
					if (eventName === 'onViewHidden')
					{
						reject();
					}
				})).catch(reject);
			});
		}
	}

	const styles = {
		wrapper: {
			borderRadius: 12,
			flexDirection: 'row',
			paddingTop: 12,
			paddingBottom: 15,
			paddingLeft: 14,
			paddingRight: 17,
			backgroundColor: AppTheme.colors.bgContentPrimary,
			marginBottom: 8,
			alignItems: 'center',
		},
		accessIconContainer: {
			width: 28,
			height: 28,
			justifyContent: 'center',
			alignItems: 'center',
			marginRight: 8,
		},
		accessIcon: {
			width: 13,
			height: 18,
		},
		content: {
			flexDirection: 'column',
			flex: 1,
		},
		title: {
			color: AppTheme.colors.base2,
			fontSize: 13,
			opacity: 0.8,
		},
		accessText: {
			color: AppTheme.colors.base0,
			fontSize: 18,
			opacity: 0.8,
		},
		arrowIconContainer: {
			width: 24,
			height: 24,
			justifyContent: 'center',
			alignItems: 'center',
			marginLeft: 8,
		},
		arrowIcon: {
			width: 10,
			height: 18,
		},
	};

	const svgImages = {
		access: '<svg width="13" height="18" viewBox="0 0 13 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.17045 12.5307V14.3883H5.69918V12.5307C5.4344 12.315 5.26489 11.9853 5.26489 11.6157C5.26489 10.9656 5.78871 10.4386 6.43484 10.4386C7.08092 10.4386 7.60475 10.9656 7.60475 11.6157C7.60475 11.9853 7.43529 12.315 7.17045 12.5307ZM3.23177 4.98398C3.23177 3.20411 4.66583 1.76124 6.4348 1.76124C8.2038 1.76124 9.63787 3.20411 9.63787 4.98398V7.70828H3.23177V4.98398ZM11.3306 7.70828V4.98398C11.3306 2.26346 9.13866 0.0581055 6.4348 0.0581055C3.73097 0.0581055 1.53903 2.26346 1.53903 4.98398V7.70828H0.0527344V17.0373H12.8169V7.70828H11.3306Z" fill="#525C69"/></svg>',
		arrow: '<svg width="10" height="18" viewBox="0 0 10 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2.34343L5.52705 7.87048L6.69963 8.99985L5.52705 10.1299L0 15.6569L1.59747 17.2544L9.85163 9.00025L1.59747 0.746094L0 2.34343Z" fill="#6a737f"/></svg>',
		allForAllAccess: '<svg width="20" height="13" viewBox="0 0 20 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.51 5.26677C13.51 5.26677 13.4925 5.61014 13.4755 5.65604C13.4365 5.71533 13.4036 5.77844 13.3774 5.8444C13.3995 5.8444 13.6282 5.95301 13.6282 5.95301L14.5139 6.23693L14.5011 6.66608C14.3327 6.7348 13.9778 6.99532 13.9167 7.07979C14.7235 7.41438 15.4647 8.08346 15.4837 8.6315C15.4968 8.72906 15.9854 10.5252 16.0145 10.9295H19.473C19.476 10.9127 19.3607 8.58936 19.3594 8.5718C19.3594 8.5718 19.1455 8.01223 18.8064 7.95529C18.4696 7.9116 18.1445 7.80282 17.8492 7.63499C17.6542 7.51943 17.4484 7.42309 17.2347 7.34731C17.1252 7.24882 17.0404 7.12595 16.9872 6.98864C16.8749 6.84569 16.7272 6.73451 16.5588 6.66608L16.5462 6.23693L17.4321 5.95401C17.4321 5.95401 17.6606 5.84541 17.6829 5.84541C17.6475 5.76976 17.6067 5.69675 17.5608 5.62694C17.544 5.58129 17.4605 5.26827 17.4605 5.26827C17.5896 5.43708 17.7416 5.58716 17.9119 5.71423C17.761 5.42606 17.6319 5.127 17.5257 4.81956C17.4546 4.53493 17.4052 4.24532 17.378 3.95323C17.3171 3.41043 17.2223 2.87196 17.0943 2.34097C17.0032 2.07713 16.8411 1.84356 16.6257 1.66602C16.31 1.44195 15.9395 1.3076 15.5535 1.27725C15.5462 1.27725 15.5392 1.27725 15.5317 1.27725C15.5242 1.27725 15.5171 1.27725 15.5091 1.27725C15.1231 1.30749 14.7525 1.44185 14.4369 1.66602C14.2218 1.84378 14.0598 2.07728 13.9686 2.34097C13.8402 2.87191 13.7454 3.41039 13.6847 3.95323C13.6612 4.25187 13.6142 4.54818 13.5439 4.83937C13.4376 5.14138 13.3061 5.43394 13.1509 5.71398C13.3204 5.58627 13.51 5.26677 13.51 5.26677ZM15.4507 12.9674C15.4507 12.2016 14.6091 8.96188 14.6091 8.96188C14.6091 8.96188 14.0031 7.94735 12.8089 7.63231C12.4033 7.51658 12.0185 7.33775 11.6685 7.10239C11.6124 6.95651 11.5902 6.7998 11.6036 6.64409L11.2208 6.58421C11.2208 6.55064 11.1881 6.05457 11.1881 6.05457C11.6481 5.89677 11.6008 4.96562 11.6008 4.96562C11.8929 5.13098 12.0831 4.39513 12.0831 4.39513C12.4287 3.37193 11.9111 3.43349 11.9111 3.43349C12.0016 2.80843 12.0016 2.17359 11.9111 1.54854C11.6811 -0.524433 8.21615 0.0376666 8.62716 0.71532C7.6146 0.523943 7.84571 2.87754 7.84571 2.87754L8.06534 3.48609C7.63418 3.7712 7.93384 4.11702 7.94839 4.51488C7.96965 5.10244 8.32079 4.98045 8.32079 4.98045C8.34261 5.94937 8.81014 6.07667 8.81014 6.07667C8.898 6.68522 8.84344 6.5803 8.84344 6.5803L8.42711 6.63178C8.4328 6.77006 8.42181 6.90853 8.39438 7.04419C7.90362 7.26802 7.79926 7.39785 7.3127 7.61524C6.37261 8.03493 5.35081 8.58304 5.16951 9.31945C4.9882 10.0559 4.44709 12.9674 4.44709 12.9674H15.4507ZM6.43669 5.65338C6.47595 5.71306 6.50903 5.77659 6.53541 5.84298C6.51319 5.84298 6.28294 5.9523 6.28294 5.9523L5.39123 6.23784L5.40411 6.66981C5.57354 6.73897 5.72212 6.85093 5.83532 6.99473C5.87821 7.0905 5.93083 7.18162 5.99235 7.26663C5.18017 7.60342 4.43417 8.09649 4.41524 8.64813C4.40186 8.74634 3.91031 10.5542 3.88102 10.9612H0.399472C0.396443 10.9443 0.512577 8.60571 0.513587 8.58804C0.513587 8.58804 0.72894 8.02479 1.07027 7.96748C1.40928 7.92351 1.73649 7.81401 2.03368 7.64508C2.23001 7.52877 2.43716 7.43179 2.65223 7.3555C2.76246 7.25637 2.84782 7.1327 2.90141 6.99448C3.01447 6.8506 3.1631 6.7387 3.33262 6.66981L3.3455 6.23784L2.45379 5.95305C2.45379 5.95305 2.22379 5.84374 2.20132 5.84374C2.23692 5.7676 2.27793 5.69412 2.32402 5.62384C2.34094 5.57789 2.42501 5.26281 2.42501 5.26281C2.29501 5.43273 2.14207 5.5838 1.97057 5.7117C2.12245 5.42164 2.25242 5.12061 2.35937 4.81115C2.43089 4.52465 2.4806 4.23314 2.50807 3.93913C2.56935 3.39276 2.66472 2.85076 2.79361 2.31628C2.88525 2.05071 3.04845 1.81561 3.26521 1.6369C3.58302 1.41135 3.956 1.27612 4.34451 1.24557C4.35183 1.24557 4.3589 1.24557 4.36647 1.24557C4.37404 1.24557 4.38111 1.24557 4.38919 1.24557C4.77772 1.27601 5.15073 1.41125 5.46848 1.6369C5.68496 1.81582 5.84803 2.05086 5.93984 2.31628C6.06903 2.85071 6.16448 3.39273 6.22563 3.93913C6.24918 4.23973 6.29653 4.53799 6.36726 4.8311C6.47431 5.13508 6.60664 5.42956 6.76288 5.71144C6.59241 5.58288 6.44051 5.4314 6.31147 5.2613C6.31147 5.2613 6.41952 5.60718 6.43669 5.65338Z" fill="#828B95"/></svg>',
		ownForAllAccess: '<svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.5139 14.6925C13.1891 14.4703 13.5592 13.7654 13.4222 13.0679L13.2297 12.0875C13.133 11.4746 12.421 10.7866 10.8285 10.3824C10.289 10.2346 9.77611 10.0056 9.30819 9.70337C9.20586 9.64597 9.22141 9.11566 9.22141 9.11566L8.70851 9.03903C8.70851 8.99598 8.66466 8.36004 8.66466 8.36004C9.27833 8.15763 9.21519 6.96359 9.21519 6.96359C9.60492 7.17577 9.85873 6.23087 9.85873 6.23087C10.3197 4.91807 9.62918 4.99745 9.62918 4.99745C9.74998 4.19601 9.74998 3.3815 9.62918 2.58006C9.32219 -0.0785049 4.70019 0.643229 5.24824 1.51151C3.89741 1.26727 4.20565 4.28426 4.20565 4.28426L4.49864 5.06553C4.09253 5.32409 4.17228 5.62083 4.26137 5.95231C4.2985 6.0905 4.33727 6.23472 4.34312 6.38474C4.37143 7.13761 4.84078 6.9816 4.84078 6.9816C4.86971 8.22418 5.49396 8.38599 5.49396 8.38599C5.61122 9.16634 5.53812 9.03354 5.53812 9.03354L4.98261 9.09948C4.99013 9.27694 4.9754 9.45464 4.93876 9.62857C4.616 9.76978 4.4184 9.88213 4.22275 9.99338C4.02247 10.1073 3.82423 10.22 3.49586 10.3613C2.24176 10.9008 0.984022 11.2027 0.741725 12.1473C0.685998 12.3646 0.605536 12.7381 0.528183 13.1331C0.397358 13.8011 0.765845 14.4613 1.41179 14.6762C2.98663 15.2002 4.7358 15.5091 6.58179 15.5487H7.39315C9.21991 15.5095 10.9518 15.2066 12.5139 14.6925Z" fill="#828B95"/></svg>',
		noneForAllAccess: '<svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.00173 13.4492C6.8759 13.9851 7.90422 14.294 9.00469 14.294C12.1826 14.294 14.7589 11.7178 14.7589 8.53985C14.7589 7.43938 14.45 6.41106 13.9141 5.53688L6.00173 13.4492ZM4.0953 11.5428L12.0077 3.63045C11.1335 3.0946 10.1052 2.78567 9.00469 2.78567C5.82675 2.78567 3.25052 5.36191 3.25052 8.53985C3.25052 9.64032 3.55944 10.6686 4.0953 11.5428ZM9.00469 16.5553C4.57789 16.5553 0.989258 12.9667 0.989258 8.53985C0.989258 4.11305 4.57789 0.524414 9.00469 0.524414C13.4315 0.524414 17.0201 4.11305 17.0201 8.53985C17.0201 12.9667 13.4315 16.5553 9.00469 16.5553Z" fill="#828B95"/></svg>',
		copyFromTunnelAccess: '<svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.89826 8.97192V2.59378C1.89826 2.48625 1.98625 2.39826 2.09378 2.39826H8.47192C8.57944 2.39826 8.66749 2.48625 8.66749 2.59378V2.90131H10.5658V1.14035C10.5658 0.788157 10.2775 0.5 9.9254 0.5H0.640349C0.288157 0.5 0 0.788157 0 1.14035V10.4254C0 10.7775 0.288157 11.0658 0.640349 11.0658H2.29458V9.16744H2.09378C1.98625 9.16744 1.89826 9.0795 1.89826 8.97192ZM12.1209 12.414C12.1209 12.5278 12.0278 12.6209 11.914 12.6209H5.1614C5.04754 12.6209 4.95436 12.5278 4.95436 12.414V5.6614C4.95436 5.54754 5.04754 5.45436 5.1614 5.45436H11.914C12.0278 5.45436 12.1209 5.54754 12.1209 5.6614V12.414ZM13.2686 3.55447H3.71345C3.35101 3.55447 3.05447 3.85101 3.05447 4.21345V13.7686C3.05447 14.131 3.35101 14.4276 3.71345 14.4276H13.2686C13.631 14.4276 13.9276 14.131 13.9276 13.7686V4.21345C13.9276 3.85101 13.631 3.55447 13.2686 3.55447Z" fill="#828B95"/></svg>',
		customAccess: '<svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.8766 11.7595C17.8766 11.1581 17.1072 10.471 15.5921 10.0706C15.0788 9.92431 14.5908 9.69742 14.1456 9.39808C14.0483 9.34122 14.0631 8.81595 14.0631 8.81595L13.5751 8.74005C13.5751 8.69741 13.5334 8.06751 13.5334 8.06751C14.1172 7.86701 14.0571 6.68432 14.0571 6.68432C14.4279 6.89449 14.6694 5.95855 14.6694 5.95855C15.1079 4.65822 14.451 4.73685 14.451 4.73685C14.5659 3.94303 14.5659 3.13625 14.451 2.34243C14.1589 -0.290892 9.76159 0.423987 10.283 1.28402C8.99782 1.0421 9.29108 4.03044 9.29108 4.03044L9.56983 4.80428C9.18346 5.0604 9.25933 5.35431 9.34409 5.68264C9.37942 5.81952 9.4163 5.96237 9.42187 6.11096C9.4488 6.85669 9.89534 6.70216 9.89534 6.70216C9.92286 7.93294 10.5168 8.09321 10.5168 8.09321C10.6283 8.86615 10.5588 8.73461 10.5588 8.73461L10.0303 8.79992C10.0374 8.9757 10.0234 9.15171 9.98856 9.32399C9.68149 9.46385 9.49349 9.57514 9.30736 9.68533C9.11681 9.79813 8.92819 9.90979 8.61579 10.0498C7.42265 10.5841 6.12593 11.279 5.89541 12.2147C5.66489 13.1503 5.58013 14.5 5.58013 14.5H18.2939L17.8766 11.7595ZM0.269531 4.70177C0.269531 4.59131 0.359075 4.50177 0.469532 4.50177H3.72853V1.98388C3.72853 1.8057 3.94395 1.71647 4.06995 1.84246L7.58784 5.36035C7.66595 5.43846 7.66595 5.56509 7.58784 5.6432L4.06995 9.16109C3.94395 9.28708 3.72853 9.19785 3.72853 9.01967V6.50177H0.469532C0.359075 6.50177 0.269531 6.41223 0.269531 6.30177V4.70177Z" fill="#828B95"/></svg>',

	};

	module.exports = { CategoryPermissions };
});
