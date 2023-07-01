/**
 * @module crm/entity-detail/component/timeline-push-processor
 */
jn.define('crm/entity-detail/component/timeline-push-processor', (require, exports, module) => {
	const { debounce } = require('utils/function');

	const LISTEN_PUSH_COMMAND = 'timeline_item_action';

	/** @type {Function|null} */
	let unsubscribe = null;

	const alreadySubscribed = () => unsubscribe !== null;

	/**
	 * @param {string} tabId
	 * @param {object} content
	 * @param {DetailCardComponent} detailCard
	 */
	const listenTimelinePush = (tabId, content, detailCard) => {
		if (tabId !== 'main')
		{
			return;
		}

		const { timelinePushTag } = detailCard.getComponentParams();
		if (!timelinePushTag)
		{
			return;
		}

		if (alreadySubscribed())
		{
			return;
		}

		detailCard.customEventEmitter.on(
			'DetailCard::onBeforeUnmount',
			() => unsubscribe && unsubscribe(),
		);

		subscribe(timelinePushTag, detailCard);
	};

	/**
	 * @param {string} listenTag
	 * @param {DetailCardComponent} detailCard
	 */
	const subscribe = (listenTag, detailCard) => {
		unsubscribe = BX.PULL.subscribe({
			moduleId: 'crm',
			callback: (data) => {
				const command = BX.prop.getString(data, 'command', '');
				const params = BX.prop.getObject(data, 'params', {});
				const tag = BX.prop.getString(params, 'TAG', '');

				if (command === LISTEN_PUSH_COMMAND && tag === listenTag)
				{
					onReceiveMessage(params, detailCard);
				}
			},
		});
	};

	/**
	 * @param {object} message
	 * @param {DetailCardComponent} detailCard
	 */
	const onReceiveMessage = (message, detailCard) => {
		/** @type {TimelineTab|undefined} */
		const timelineTab = detailCard.getTab('timeline');

		if (timelineTab && timelineTab.isReady())
		{
			timelineTab.processPushMessage(message);
		}
		else
		{
			// We received push message, but timeline tab was not opened yet.
			// So we update tab counters just in case.
			reloadTabCounters(detailCard);

			if (message.stream === 'scheduled')
			{
				reloadToDoNotificationParams(detailCard);
			}
		}

		if (message.stream === 'history')
		{
			reloadEntityDocumentList(detailCard);
		}
	};

	const reloadTabCounters = debounce((detailCard) => detailCard.loadTabCounters(), 500, this);

	const fetchToDoNotificationParams = (detailCard) => {
		if (!detailCard.hasEntityModel())
		{
			return;
		}

		const { entityTypeId, entityId, categoryId } = detailCard.getComponentParams();
		const queryConfig = { json: { entityTypeId, entityId, categoryId } };

		BX.ajax
			.runAction(detailCard.getActionEndpoint('loadToDoNotificationParams'), queryConfig)
			.then(({ data: responseNotificationParams }) => {
				if (responseNotificationParams)
				{
					detailCard.setComponentParams({
						todoNotificationParams: responseNotificationParams,
					});
				}
			})
			.catch((response) => {
				console.warn('Unable to load ToDo notification params', response);
			})
		;
	};

	const reloadToDoNotificationParams = debounce((detailCard) => fetchToDoNotificationParams(detailCard), 500, this);

	const reloadEntityDocumentList = debounce((detailCard) => detailCard.emitReloadEntityDocumentList(), 500, this);

	module.exports = { listenTimelinePush };
});
