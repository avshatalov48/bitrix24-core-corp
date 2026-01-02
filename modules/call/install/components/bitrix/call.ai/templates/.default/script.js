;(function()
{
	BX.namespace("BX.Call.AI");

	const Analytics = BX.Call.Lib?.Analytics;

	BX.Call.AI.Tabs = {
		callId: null,
		tabsList: [],
		tabTitle: [],
		tabContents: [],
		contentWrapper: [],
		tabButtons: [],
		taskButtons: [],
		meetingButtons: [],
		mensionElements: [],
		userListAppInstance: null,
		audioPlayerInstance: {},
		playbackMarks: [],
		scrolling: false,
		scrollTimer: null,
		lastActiveScrollTab: null,
		viewedTabs: [],
		deletePopupInstance: null,
		init: function()
		{
			this.tabsList = document.getElementsByClassName('bx-call-component-call-ai__tab-content');
			this.callId = document.getElementsByClassName('bx-call-component-call-ai')[0]?.dataset.callId;
			this.hideAllTabs();
			this.initEvents();
			this.initAudioPlayer();
			this.initUserList();
			this.initPlaybackMarks();
			this.initGradeChart();

		Analytics.getInstance().copilot.onOpenFollowUpSlider({
				callId: this.callId,
			});
		},
		onTabClick: function(e)
		{
			const { tabId, tabName } = e.target.dataset;
			this.hideAllTabs();

			this.sendOpenFollowUpTabAnalytics(tabName);

			for (let i = 0; i < this.tabButtons.length; i++) {
				this.tabButtons[i].className = this.tabButtons[i].className.replace(' --active-button', '');
			}

			document.getElementById(tabId).style.display = 'flex';
			e.currentTarget.className += ' --active-button';
		},
		hideAllTabs: function()
		{
			for (let i = 0; i < this.tabsList.length; i++) {
				this.tabsList[i].style.display = 'none';
			}
		},
		onCreateTaskClick: function(e)
		{
			Analytics.getInstance().copilot.onFollowUpCreateTaskClick({ callId: this.callId });

			const { userId, description, auditors } = e.target.dataset;
			const taskUrl = `/company/personal/user/${userId}/tasks/task/edit/0/`;

			BX.SidePanel.Instance.open(taskUrl, {
				requestMethod: 'post',
				requestParams: {
					AUDITORS: auditors,
					DESCRIPTION: description,
					RESPONSIBLE_ID: userId,
				},
				cacheable: false,
			});
		},
		onCreateMeetingClick: function(e)
		{
			Analytics.getInstance().copilot.onFollowUpCreateEventClick({ callId: this.callId });

			const { meetingDescription, meetingId, meetingIdType } = e.target.dataset;
			new (window.top.BX || window.BX).Calendar.SliderLoader(0, {
				entryDescription: meetingDescription,
				sliderId: meetingId,
				type: meetingIdType,
			}).show();
		},

		getTimeInSeconds: function(index)
		{
			const startTime = this.playbackMarks[index].innerText.split(/[-—–]/)[0];
			const [seconds, minutes, hours] = startTime.split(':').map(Number).reverse();

			return hours === undefined
				? minutes * 60 + seconds
				: hours * 3600 + minutes * 60 + seconds;
		},

		initEvents: function()
		{
			this.tabButtons = document.getElementsByClassName('bx-call-component-call-ai__tab-header-button');
			for (let i = 0; i < this.tabButtons.length; i++) {
				this.tabButtons[i].addEventListener('click', this.onTabClick.bind(this));
			}
			if (this.tabButtons.length)
			{
				this.tabButtons[0].click();
			}

			this.tabTitle = document.querySelectorAll('.bx-call-component-call-ai__tab-title');
			for (let i = 0; i < this.tabTitle.length; i++)
			{
				this.tabTitle[i].addEventListener('click', this.scrollToTab.bind(this));
			}

			this.tabContents = document.querySelectorAll('.bx-call-component-call-ai__tab-details');
			this.contentWrapper = document.querySelector('.bx-call-component-call-ai__tab-content-wrapper');
			this.contentWrapper.addEventListener('scroll', this.changeTitleForScroll.bind(this));

			this.taskButtons = document.getElementsByClassName('bx-call-component-call-ai__task-button');
			for (let i = 0; i < this.taskButtons.length; i++) {
				this.taskButtons[i].addEventListener('click', this.onCreateTaskClick.bind(this));
			}

			this.meetingButtons = document.getElementsByClassName('bx-call-component-call-ai__meetings-button');
			for (let i = 0; i < this.meetingButtons.length; i++) {
				this.meetingButtons[i].addEventListener('click', this.onCreateMeetingClick.bind(this));
			}

			this.mensionElements = document.getElementsByClassName('bx-call-component-call-ai__user-mention');
			for (let i = 0; i < this.mensionElements.length; i++) {
				this.mensionElements[i].addEventListener('click', this.clickMension.bind(this));
			}

			const disclaimerLink = document.getElementsByClassName('bx-call-component-call-ai__disclaimer-link');
			disclaimerLink[0]?.addEventListener('click', this.clickDisclaimer.bind(this));

			const deleteBtn = document.getElementsByClassName('bx-call-component-call-ai__delete');
			deleteBtn[0]?.addEventListener('click', this.deleteFollowUp.bind(this));
		},
		initAudioPlayer: function()
		{
			if (!BX.Vue3)
			{
				return;
			}

			const BitrixVue = BX.Vue3.BitrixVue;
			const audioRecordContainer = document.getElementsByClassName('bx-call-component-call-ai__call-audio-record');

			if (!audioRecordContainer.length)
			{
				return;
			}

			const callId = this.callId;

			for (let i = 0; i < audioRecordContainer.length; i++) {
				const { audioSrc, audioId } = audioRecordContainer[i].dataset;

				if (!audioSrc)
				{
					continue;
				}

				this.audioPlayerInstance[audioId] = BitrixVue.createApp({
					components: {
						AudioPlayer: BX.Call.Component.Elements.AudioPlayer,
					},
					data() {
						return {
							audioSrc,
							analyticsCallback: () => {
								Analytics.getInstance().copilot.onAIPlayRecord({ callId });
							},
						};
					},
					template: `
						<AudioPlayer :src="audioSrc" :analyticsCallback="analyticsCallback" ref="AudioPlayerRef"/>
					`,
				}).mount(`#bx-call-component-call-ai__call-audio-record-${audioId}`);
			}
		},
		initUserList: function()
		{
			if (!BX.Vue3)
			{
				return;
			}

			const BitrixVue = BX.Vue3.BitrixVue;
			const userListContainer = document.getElementsByClassName('bx-call-component-call-ai-page__title-users-container');

			if (userListContainer.length === 0)
			{
				return;
			}

			const { callId } = userListContainer[0].dataset;

			if (!callId)
			{
				return;
			}

			BitrixVue.createApp({
				components: {
					UserList: BX.Call.Component.UserList
				},
				data() {
					return {
						isLoadingUsers: true,
						callId,
						usersData: [],
						withBorder: false,
						withIcon: true,
					};
				},
				created()
				{
					BX.ajax.runComponentAction('bitrix:call.ai', 'getUsers', {
						mode: 'ajax',
						data: { callId }
					}).then(response => {
						this.isLoadingUsers = false;
						this.usersData = Object.values(response.data.users);
					}).catch(error => {
						this.isLoadingUsers = false;
					});
				},
				template: `
					<UserList :loading="isLoadingUsers" :usersData="usersData" :withBorder="withBorder" :withIcon="withIcon"/>
				`,
			}).mount('.bx-call-component-call-ai-page__title-users-container');
		},
		initPlaybackMarks: function()
		{
			if (Object.keys(this.audioPlayerInstance).length === 0)
			{
				return;
			}

			this.playbackMarks = document.getElementsByClassName('bx-call-component-call-ai__time-code');

			Object.keys(this.audioPlayerInstance).forEach(key => {
				for (let i = 0; i < this.playbackMarks.length; i++)
				{
					const { audioId } = this.playbackMarks[i].dataset;

					if (audioId !== key)
					{
						continue;
					}

					this.playbackMarks[i].addEventListener('click', () => {
						Analytics.getInstance().copilot.onAIRecordTimeCodeClick({ callId: this.callId });
						this.audioPlayerInstance[key].$refs.AudioPlayerRef.choosePlaybackTime(this.getTimeInSeconds(i));
					});
				}
			});
		},
		clickMension: function(e)
		{
			const { userId } = e.target.dataset;

			if (!userId)
			{
				return;
			}

			BX.Messenger.Public.openChat(userId);
		},
		clickDisclaimer: function()
		{
			const infoHelper = top.BX.Helper || window.top.BX.Helper;
			if (!infoHelper)
			{
				return;
			}
			if (infoHelper.isOpen())
			{
				infoHelper.close();
			}

			const callAi = top.BX.Call.CallAI || window.top.BX.Call.CallAI;
			const ARTICLE_CODE = callAi.disclaimerArticleCode;

			infoHelper.show(`redirect=detail&code=${ARTICLE_CODE}`);
		},
		scrollToTab: function(e)
		{
			this.scrolling = true;
			const { tabId, tabName } = e.target.dataset;
			const element = document.getElementById(tabId);

			if (element)
			{
				for (let i = 0; i < this.tabTitle.length; i++)
				{
					this.tabTitle[i].classList.remove('--active');
				}
				e.target.classList.add('--active');

				const parent = element.offsetParent;
				const elementRect = element.getBoundingClientRect();
				const parentRect = parent.getBoundingClientRect();

				const offsetTop = elementRect.top - parentRect.top + parent.scrollTop;
				const marginTop = parseFloat(window.getComputedStyle(element).marginTop);

				parent.scrollTo({
					top: offsetTop - marginTop,
					behavior: 'smooth',
				});

				this.sendOpenFollowUpTabAnalytics(tabName);
			}

			if (this.scrollTimer)
			{
				clearTimeout(this.scrollTimer);
				this.scrollTimer = null;
			}

			this.scrollTimer = setTimeout(() => {
				this.scrolling = false;
				this.scrollTimer = null;
			}, 1000);
		},
		changeTitleForScroll: function() {
			if (this.scrolling) {
				return;
			}

			let activeTab = null;

			this.tabContents.forEach((content, index) => {
				const rect = content.getBoundingClientRect();
				const parentRect = this.contentWrapper.getBoundingClientRect();

				if (rect.top <= parentRect.top + 100 && rect.bottom >= parentRect.top + 100)
				{
					activeTab = this.tabTitle[index];
				}
			});

			if (this.contentWrapper.scrollTop + this.contentWrapper.clientHeight >= this.contentWrapper.scrollHeight)
			{
				this.tabContents.forEach((content, index) => {
					this.checkViewedTabs(this.tabTitle[index].dataset.tabName);
				});
				activeTab = this.tabTitle[this.tabContents.length - 1];
			}

			if (activeTab && this.lastActiveScrollTab !== activeTab)
			{
				this.lastActiveScrollTab = activeTab;
				this.tabTitle.forEach(title => title.classList.remove('--active'));
				activeTab.classList.add('--active');

				this.checkViewedTabs(activeTab.dataset.tabName);
			}
		},
		checkViewedTabs: function(tabName)
		{
			if (!this.viewedTabs.includes(tabName))
			{
				this.sendOpenFollowUpTabAnalytics(tabName);
				this.viewedTabs.push(tabName);
			}
		},
		sendOpenFollowUpTabAnalytics: function(tabName)
		{
			Analytics.getInstance().copilot.onOpenFollowUpTab({
				callId: this.callId,
				tabName: tabName,
			});
		},
		generateGradientElevent: function(gradientName, startColor, stopColor, gradientType)
		{
			const svgNS = 'http://www.w3.org/2000/svg';
			const gradient = document.createElementNS(svgNS, gradientType);
			gradient.setAttribute('id', gradientName);
			gradient.setAttribute('x1', '0%');
			gradient.setAttribute('y1', '0%');
			gradient.setAttribute('x2', '100%');
			gradient.setAttribute('y2', '100%');

			const stop1 = document.createElementNS(svgNS, 'stop');
			stop1.setAttribute('offset', '0%');
			stop1.setAttribute('stop-color', startColor);
			gradient.appendChild(stop1);

			const stop2 = document.createElementNS(svgNS, 'stop');
			stop2.setAttribute('offset', '100%');
			stop2.setAttribute('stop-color', stopColor);
			gradient.appendChild(stop2);

			const svg = document.createElementNS(svgNS, 'svg');
			svg.setAttribute('width', '0');
			svg.setAttribute('height', '0');
			svg.appendChild(gradient);
			document.body.appendChild(svg);

			return gradientName;
		},
		initGradeChart: function()
		{
			const chartContainer = document.getElementsByClassName('bx-call-component-call-ai__grade-value-wrapper');

			if (chartContainer.length === 0)
			{
				return;
			}

			let { efficiencyValue } = chartContainer[0].dataset;

			if (!efficiencyValue)
			{
				efficiencyValue = 0;
			}

			this.generateGradientElevent(
				'bx-call-component-call-ai__gradient-primary',
				'#29D887',
				'#30AC73',
				'linearGradient',
			);
			this.generateGradientElevent(
				'bx-call-component-call-ai__gradient-secondary',
				'#EBF0F3',
				'#DBDBDB',
				'radialGradient',
			);

			let chartData = [];
			let chartColors = [];

			if (Number(efficiencyValue) === 100)
			{
				chartData = [{
					percentage: 100,
				}];

				chartColors = [
					'url(#bx-call-component-call-ai__gradient-primary)',
				];
			}
			else if (Number(efficiencyValue) > 0)
			{
				chartData = [{
					percentage: 1,
				}, {
					percentage: efficiencyValue,
				}, {
					percentage: 1,
				}, {
					percentage: 100 - efficiencyValue - 2,
				}];

				chartColors = [
					'#F8F6FD',
					'url(#bx-call-component-call-ai__gradient-primary)',
					'#F8F6FD',
					'url(#bx-call-component-call-ai__gradient-secondary)',
				];
			}
			else
			{
				chartData = [{
					percentage: 100,
				}];

				chartColors = [
					'url(#bx-call-component-call-ai__gradient-secondary)',
				];
			}

			AmCharts.makeChart('bx-call-component-call-ai__grade-chart', {
				type: 'pie',
				dataProvider: chartData,
				titleField: 'status',
				valueField: 'percentage',
				balloonText: '',
				labelText: '',
				innerRadius: '85%',
				colors: chartColors,
				legend: {
					enabled: false,
				},
				responsive: {
					enabled: true,
					addDefaultRules: true,
					rules: [
						{
							minWidth: 200,
							minHeight: 200,
							overrides: {
								innerRadius: '85%',
							},
						},
					],
				},
				startDuration: 0,
				width: '100%',
				height: '100%',
				pullOutRadius: 0,
				pullOutOnlyOne: false,
				outlineAlpha: 0,
				startEffect: 'elastic',
				pullOutEffect: 'elastic',
				listeners: [{
					event: 'clickSlice',
					method: (e) => {
						return false;
					},
				}],
			});
		},
		deleteFollowUp: function()
		{
			if (!BX.Vue3)
			{
				return;
			}

			const BitrixVue = BX.Vue3.BitrixVue;

			if (this.deletePopupInstance)
			{
				this.deletePopupInstance.unmount();
				this.deletePopupInstance = null;
			}

			const callId = this.callId;

			this.deletePopupInstance = BitrixVue.createApp({
				components: {
					CallActionPopup: BX.Call.Component.Elements.CallActionPopup,
				},
				data() {
					return {
						titleText: BX.Loc.getMessage('CALL_COMPONENT_DELETE_FOLLOWUP_TITLE'),
						descriptionText: BX.Loc.getMessage('CALL_COMPONENT_DELETE_FOLLOWUP_DESCRIPTION'),
						okText: BX.Loc.getMessage('CALL_COMPONENT_DELETE_FOLLOWUP_OK_BTN'),
						okAction: () => {
							BX.ajax.runAction('call.FollowUp.drop', {
								data: { callId },
							}).then(() => {
								const topSlider = BX.SidePanel.Instance.getTopSlider();
								if (topSlider)
								{
									topSlider.close();
								}
							}).catch(e => {
								console.error('FollowUp delete error', e);
							});
						},
					};
				},
				template: `
					<CallActionPopup
						:titleText="titleText"
						:descriptionText="descriptionText"
						:okText="okText"
						:okAction="okAction"
					/>
				`,
			});
			this.deletePopupInstance.mount('.bx-call-component-call-ai__delete-popup');
		},
	};

	document.addEventListener('DOMContentLoaded', () => {
		BX.Call.AI.Tabs.init();
	});
})();