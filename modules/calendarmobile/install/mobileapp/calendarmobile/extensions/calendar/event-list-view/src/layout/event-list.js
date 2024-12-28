/**
 * @module calendar/event-list-view/layout/event-list
 */
jn.define('calendar/event-list-view/layout/event-list', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Color, Component } = require('tokens');
	const { StatusBlock } = require('ui-system/blocks/status-block');

	const { DateHelper } = require('calendar/date-helper');
	const { EventListView } = require('calendar/event-list-view/layout/event-list-view');
	const { ListSkeleton } = require('calendar/event-list-view/list-skeleton');
	const { SectionManager } = require('calendar/data-managers/section-manager');

	const { observeState } = require('calendar/event-list-view/state');

	const store = require('statemanager/redux/store');
	const {
		selectByIds,
		selectByDate,
	} = require('calendar/statemanager/redux/slices/events');

	/**
	 * @class EventList
	 */
	class EventList extends LayoutComponent
	{
		get isSearchMode()
		{
			return this.props.isSearchMode;
		}

		get isInvitationPresetEnabled()
		{
			return this.props.isInvitationPresetEnabled;
		}

		get layout()
		{
			return this.props.layout;
		}

		get floatingActionButtonRef()
		{
			return this.props.floatingActionButtonRef;
		}

		get events()
		{
			return this.props.events;
		}

		get selectedDate()
		{
			return this.props.selectedDate;
		}

		componentDidUpdate(prevProps, prevState)
		{
			super.componentDidUpdate(prevProps, prevState);

			if (!this.events || !this.floatingActionButtonRef)
			{
				return;
			}

			if (this.isSearchMode || this.isInvitationPresetEnabled)
			{
				this.floatingActionButtonRef?.hide();
			}
			else
			{
				this.floatingActionButtonRef?.setFloatingButton({
					hide: false,
					accentByDefault: !Type.isArrayFilled(this.events),
				});
			}
		}

		render()
		{
			return View(
				{
					testId: 'calendar-event-list',
					style: {
						flex: 1,
						paddingHorizontal: Component.areaPaddingLr.toNumber(),
						backgroundColor: this.isSearchMode ? Color.bgContentPrimary.toHex() : Color.bgContentSecondary.toHex(),
					},
				},
				this.props.isLoading && ListSkeleton(),
				!this.props.isLoading && Type.isArrayFilled(this.events) && this.renderList(),
				!this.props.isLoading && !Type.isArrayFilled(this.events) && this.renderEmptyState(),
			);
		}

		renderList()
		{
			return EventListView({
				events: this.events,
				onScroll: this.closeSearch,
				selectedDate: this.selectedDate,
				isSearchMode: this.isSearchMode,
				layout: this.layout,
			});
		}

		renderEmptyState()
		{
			return View(
				{
					style: {
						flex: 1,
						opacity: 0,
						justifyContent: 'center',
						alignItems: 'center',
					},
					onClick: this.closeSearch,
					testId: 'calendar-event-list-empty_state',
					ref: (ref) => ref?.animate({ duration: 200, opacity: 1 }),
				},
				StatusBlock({
					testId: 'calendar-event-list-empty_state-status',
					image: this.isSearchMode ? this.getEmptySearchImage() : this.getEmptyStateImage(),
					title: this.getEmptyStateTitle(),
					titleColor: this.isSearchMode ? Color.base1 : Color.base3,
					description: this.isSearchMode ? this.getEmptyStateDescription() : null,
					descriptionColor: this.isSearchMode ? Color.base2 : Color.base4,
				}),
			);
		}

		getEmptyStateImage()
		{
			return Image({
				style: {
					width: 108,
					height: 108,
				},
				svg: {
					content: emptyStateIcon,
				},
			});
		}

		getEmptySearchImage()
		{
			return Image({
				style: {
					width: 126,
					height: 105,
				},
				svg: {
					content: emptySearchIcon,
				},
			});
		}

		getEmptyStateTitle()
		{
			let title = Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_TITLE');

			if (this.isSearchMode)
			{
				title = this.isInvitationPresetEnabled
					? Loc.getMessage('M_CALENDAR_EVENT_LIST_NO_INVITATIONS_TITLE')
					: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TITLE')
				;
			}

			return title;
		}

		getEmptyStateDescription()
		{
			return Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TEXT');
		}

		closeSearch = () => this.layout?.search?.close();
	}

	const emptyStateIcon = `<svg width="109" height="108" viewBox="0 0 109 108" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M101.077 79.7341C101.077 83.5698 98.1828 86.7874 94.3685 87.1922L16.2147 95.4874C11.7842 95.9576 7.9231 92.4846 7.9231 88.0293V19.7589C7.9231 15.3754 11.6663 11.9262 16.0351 12.2839L94.189 18.6829C98.0808 19.0015 101.077 22.253 101.077 26.1579V79.7341Z" fill="${Color.base8.toHex()}" stroke="${Color.bgSeparatorPrimary.toHex()}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M57.2691 61.4291C57.2691 71.1688 49.7904 79.2762 40.0823 80.0607C29.2007 80.94 19.8845 72.3462 19.8845 61.4291V59.3492C19.8845 48.8315 28.5587 40.3845 39.0726 40.6634C49.1994 40.9321 57.2691 49.2188 57.2691 59.3492V61.4291Z" fill="${Color.accentMainPrimary.toHex()}" fill-opacity="0.78"/><path d="M56.7691 61.4291C56.7691 70.9083 49.4904 78.7988 40.0421 79.5623C29.4515 80.4181 20.3845 72.0542 20.3845 61.4291V59.3492C20.3845 49.1128 28.8267 40.8917 39.0594 41.1633C48.9153 41.4248 56.7691 49.4898 56.7691 59.3492V61.4291Z" stroke="${Color.baseWhiteFixed.toHex()}" stroke-opacity="0.18"/><g filter="url(#filter0_d_5260_36009)"><path d="M30.0967 60.6106C29.4037 61.408 29.4037 62.6544 30.0967 63.3922L35.0811 68.7079L35.0897 68.7171C35.2791 68.9187 35.4986 69.0608 35.7353 69.1433C36.3509 69.3586 37.0697 69.1753 37.5604 68.5979C37.5604 68.5934 37.569 68.5888 37.5733 68.5842L48.4676 55.8585C49.1262 55.0887 49.1262 53.8743 48.4676 53.1457C47.809 52.4125 46.7373 52.4445 46.0744 53.2098L36.3293 64.5149L32.6061 60.5144C31.9174 59.772 30.794 59.8132 30.101 60.606L30.0967 60.6106Z" fill="${Color.baseWhiteFixed.toHex()}" fill-opacity="0.9" shape-rendering="crispEdges"/></g><mask id="path-5-inside-1_5260_36009" fill="${Color.base8.toHex()}"><path fill-rule="evenodd" clip-rule="evenodd" d="M26.8077 9.95794V12.6922L16.0875 11.7988C11.4233 11.4101 7.4231 15.0909 7.4231 19.7712V29.8311L101.577 34.6152V26.284C101.577 22.1233 98.3876 18.6572 94.2413 18.3116L84.9616 17.5383V14.058C84.9616 12.6077 83.8426 11.4031 82.3962 11.2963C80.7909 11.1778 79.4231 12.4483 79.4231 14.058V17.0768L32.3462 13.1537V9.95794C32.3462 8.52659 31.2554 7.3311 29.83 7.20029C28.2075 7.0514 26.8077 8.3286 26.8077 9.95794Z"/></mask><path fill-rule="evenodd" clip-rule="evenodd" d="M26.8077 9.95794V12.6922L16.0875 11.7988C11.4233 11.4101 7.4231 15.0909 7.4231 19.7712V29.8311L101.577 34.6152V26.284C101.577 22.1233 98.3876 18.6572 94.2413 18.3116L84.9616 17.5383V14.058C84.9616 12.6077 83.8426 11.4031 82.3962 11.2963C80.7909 11.1778 79.4231 12.4483 79.4231 14.058V17.0768L32.3462 13.1537V9.95794C32.3462 8.52659 31.2554 7.3311 29.83 7.20029C28.2075 7.0514 26.8077 8.3286 26.8077 9.95794Z" fill="${Color.accentMainSuccess.toHex()}" fill-opacity="0.78"/><path d="M26.8077 12.6922L26.7247 13.6887L27.8077 13.779V12.6922H26.8077ZM16.0875 11.7988L16.1705 10.8023L16.0875 11.7988ZM7.4231 29.8311H6.4231V30.7815L7.37235 30.8298L7.4231 29.8311ZM101.577 34.6152L101.526 35.614L102.577 35.6674V34.6152H101.577ZM101.577 26.284L100.577 26.284V26.284H101.577ZM94.2413 18.3116L94.3243 17.3151L94.2413 18.3116ZM84.9616 17.5383H83.9616V18.4585L84.8785 18.5349L84.9616 17.5383ZM82.3962 11.2963L82.4699 10.299L82.3962 11.2963ZM79.4231 17.0768L79.34 18.0733L80.4231 18.1636V17.0768H79.4231ZM32.3462 13.1537H31.3462V14.0738L32.2631 14.1503L32.3462 13.1537ZM29.83 7.20029L29.9214 6.20448V6.20448L29.83 7.20029ZM27.8077 12.6922V9.95794H25.8077V12.6922H27.8077ZM16.0044 12.7954L26.7247 13.6887L26.8908 11.6956L16.1705 10.8023L16.0044 12.7954ZM8.4231 19.7712C8.4231 15.6759 11.9233 12.4553 16.0044 12.7954L16.1705 10.8023C10.9233 10.365 6.4231 14.5058 6.4231 19.7712H8.4231ZM8.4231 29.8311V19.7712H6.4231V29.8311H8.4231ZM101.628 33.6165L7.47384 28.8324L7.37235 30.8298L101.526 35.614L101.628 33.6165ZM100.577 26.284V34.6152H102.577V26.284H100.577ZM94.1582 19.3082C97.7863 19.6105 100.577 22.6434 100.577 26.284L102.577 26.284C102.577 21.6032 98.989 17.7038 94.3243 17.3151L94.1582 19.3082ZM84.8785 18.5349L94.1583 19.3082L94.3243 17.3151L85.0446 16.5418L84.8785 18.5349ZM83.9616 14.058V17.5383H85.9616V14.058H83.9616ZM82.3226 12.2936C83.2467 12.3618 83.9616 13.1315 83.9616 14.058H85.9616C85.9616 12.084 84.4385 10.4444 82.4699 10.299L82.3226 12.2936ZM80.4231 14.058C80.4231 13.0296 81.297 12.2179 82.3226 12.2936L82.4699 10.299C80.2848 10.1377 78.4231 11.867 78.4231 14.058H80.4231ZM80.4231 17.0768V14.058H78.4231V17.0768H80.4231ZM32.2631 14.1503L79.34 18.0733L79.5061 16.0802L32.4292 12.1572L32.2631 14.1503ZM31.3462 9.95794V13.1537H33.3462V9.95794H31.3462ZM29.7386 8.19611C30.6493 8.27968 31.3462 9.04346 31.3462 9.95794H33.3462C33.3462 8.00971 31.8615 6.38252 29.9214 6.20448L29.7386 8.19611ZM27.8077 9.95794C27.8077 8.91697 28.702 8.10098 29.7386 8.19611L29.9214 6.20448C27.713 6.00181 25.8077 7.74023 25.8077 9.95794H27.8077Z" fill="${Color.baseWhiteFixed.toHex()}" fill-opacity="0.18" mask="url(#path-5-inside-1_5260_36009)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M78.0384 52.4672C78.0384 53.2928 77.3712 53.9632 76.5456 53.9672L71.2379 53.9927C70.4067 53.9967 69.7307 53.3239 69.7307 52.4927V45.8448C69.7307 45.002 70.4249 44.3246 71.2674 44.3452L76.5751 44.4751C77.389 44.495 78.0384 45.1605 78.0384 45.9747V52.4672Z" fill="${Color.base5.toHex()}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M90.5001 52.4677C90.5001 53.2934 89.8328 53.9638 89.0072 53.9677L83.6995 53.9928C82.8683 53.9967 82.1924 53.324 82.1924 52.4928V45.8435C82.1924 45.0012 82.8858 44.324 83.7279 44.3439L89.0356 44.4695C89.8499 44.4888 90.5001 45.1545 90.5001 45.9691V52.4677Z" fill="${Color.base5.toHex()}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M78.0384 64.6756C78.0384 65.4796 77.4045 66.1407 76.6011 66.1743L71.2934 66.3964C70.4411 66.4321 69.7307 65.7508 69.7307 64.8977V58.3559C69.7307 57.5349 70.3908 56.8664 71.2117 56.856L76.5194 56.7888C77.3552 56.7782 78.0384 57.4528 78.0384 58.2887V64.6756Z" fill="${Color.base6.toHex()}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M78.0384 76.9031C78.0384 77.6877 77.4339 78.3397 76.6516 78.3989L71.3439 78.8007C70.4734 78.8666 69.7307 78.178 69.7307 77.3049V71.0533C69.7307 70.2523 70.36 69.5927 71.16 69.555L76.4677 69.3046C77.3229 69.2642 78.0384 69.9467 78.0384 70.8029V76.9031Z" fill="${Color.base6.toHex()}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M90.5001 64.28C90.5001 65.0857 89.8636 65.7475 89.0585 65.7789L83.7508 65.9857C82.9001 66.0189 82.1924 65.3382 82.1924 64.4869V58.3525C82.1924 57.5311 82.8529 56.8626 83.6741 56.8526L88.9818 56.788C89.8173 56.7778 90.5001 57.4523 90.5001 58.2879V64.28Z" fill="${Color.base6.toHex()}"/><defs><filter id="filter0_d_5260_36009" x="19.5769" y="46.6152" width="39.3845" height="36.6152" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_5260_36009"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_5260_36009" result="shape"/></filter></defs></svg>`;
	const emptySearchIcon = `<svg width="126" height="105" viewBox="0 0 126 105" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="ill"><g id="Vector"><path d="M0 17.2992C0 7.9713 7.94707 0.621079 17.2466 1.34786L62.2467 4.8647C70.5743 5.51552 77 12.463 77 20.8161V68.4114C77 76.8858 70.3925 83.8903 61.9325 84.3842L16.9325 87.0114C7.745 87.5478 0 80.2418 0 71.0386V17.2992Z" fill="${Color.accentMainPrimary.toHex()}" fill-opacity="0.78"/><path d="M0.5 17.2992C0.5 8.2628 8.19872 1.14227 17.2077 1.84634L62.2077 5.36318C70.2751 5.99367 76.5 12.724 76.5 20.8161V68.4114C76.5 76.6209 70.0989 83.4066 61.9034 83.8851L16.9034 86.5123C8.00297 87.0319 0.5 79.9542 0.5 71.0386V17.2992Z" stroke="${Color.baseWhiteFixed.toHex()}" stroke-opacity="0.18"/></g><g id="Vector_2"><path d="M68 53.1409C68 46.4587 73.4583 41.0637 80.14 41.1417L114.14 41.5384C120.712 41.6151 126 46.9648 126 53.5376V88.8327C126 95.0453 121.258 100.231 115.07 100.785L81.0703 103.83C74.0485 104.458 68 98.9273 68 91.8774V53.1409Z" fill="${Color.base4.toHex()}" fill-opacity="0.68"/><path d="M68.5 53.1409C68.5 46.7371 73.7309 41.5669 80.1342 41.6416L114.134 42.0384C120.433 42.1119 125.5 47.2387 125.5 53.5376V88.8327C125.5 94.7865 120.956 99.7558 115.026 100.287L81.0257 103.332C74.2965 103.934 68.5 98.6336 68.5 91.8774V53.1409Z" stroke="${Color.baseWhiteFixed.toHex()}" stroke-opacity="0.4"/></g><g id="Vector_3" filter="url(#filter0_d_26923_16940)"><path fill-rule="evenodd" clip-rule="evenodd" d="M46.0818 56.8444C43.2484 58.9867 39.8208 60.2726 36.0975 60.3349C25.695 60.5093 17 51.1183 17 39.3515C17 27.5847 25.6981 18.4927 36.0975 19.022C46.1572 19.5358 54.0692 28.9143 54.0692 39.9836C54.0692 44.0408 53.0189 47.8115 51.2044 50.975L60.217 61.2721C61.261 62.4678 61.261 64.4357 60.217 65.6781L59.0818 67.0263C58.0283 68.278 56.3145 68.331 55.2516 67.1384L46.0818 56.8444ZM49.0409 39.9026C49.0409 47.8769 43.3145 54.3846 36.0943 54.4344C28.8742 54.4842 22.5723 47.7772 22.5723 39.448C22.5723 31.1188 28.6981 24.6205 36.0943 24.9256C43.4906 25.2308 49.0409 31.9253 49.0409 39.8995V39.9026Z" fill="${Color.baseWhiteFixed.toHex()}" fill-opacity="0.9" shape-rendering="crispEdges"/></g><g id="Vector_4" filter="url(#filter1_d_26923_16940)"><path d="M85.0396 60.5311L112.292 60.0005C113.791 59.9716 115 61.3139 115 63.0004C115 64.6869 113.791 66.0897 112.292 66.137L85.0396 66.9986C83.3645 67.0511 82 65.6378 82 63.8436C82 62.0495 83.3645 60.5652 85.0396 60.5337V60.5311Z" fill="${Color.baseWhiteFixed.toHex()}" fill-opacity="0.9" shape-rendering="crispEdges"/></g><g id="Vector_5" filter="url(#filter2_d_26923_16940)"><path d="M85.0808 78.8029L102.143 78.0032C103.726 77.9277 105 79.2028 105 80.8501C105 82.4975 103.723 83.9059 102.143 83.9989L85.0808 84.9948C83.383 85.0929 82 83.7801 82 82.0624C82 80.3446 83.383 78.8859 85.0808 78.8055V78.8029Z" fill="${Color.baseWhiteFixed.toHex()}" fill-opacity="0.9" shape-rendering="crispEdges"/></g></g><defs><filter id="filter0_d_26923_16940" x="7" y="13" width="64" height="69" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_26923_16940"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_26923_16940" result="shape"/></filter><filter id="filter1_d_26923_16940" x="72" y="54" width="53" height="27" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_26923_16940"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_26923_16940" result="shape"/></filter><filter id="filter2_d_26923_16940" x="72" y="72" width="43" height="27" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_26923_16940"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_26923_16940" result="shape"/></filter></defs></svg>`;

	const mapStateToProps = (state) => {
		const events = state.isSearchMode || Type.isArrayFilled(state.filterResultIds)
			? selectByIds(store.getState(), {
				ids: state.filterResultIds,
				parseRecursion: false,
			})
			: selectByDate(store.getState(), {
				date: state.selectedDate,
				showDeclined: state.showDeclined,
				sectionIds: SectionManager.getActiveSectionsIds(),
			}).map((event) => {
				const eventDateFrom = new Date(event.dateFromTs);
				const eventDateTo = new Date(event.dateToTs);
				const isLongWithTime = DateHelper.getDayCode(eventDateFrom) !== DateHelper.getDayCode(eventDateTo);

				return { ...event, isLongWithTime };
			})
		;

		return {
			events,
			selectedDate: state.selectedDate,
			isLoading: state.isLoading,
			isInvitationPresetEnabled: state.isInvitationPresetEnabled,
			isSearchMode: state.isSearchMode,
			showDeclined: state.showDeclined,
		};
	};

	module.exports = { EventList: observeState(EventList, mapStateToProps) };
});
