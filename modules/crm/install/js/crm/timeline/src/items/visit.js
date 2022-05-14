import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Actions */
export default class Visit extends HistoryActivity
{
	constructor()
	{
		super();
		this._playerDummyClickHandler = BX.delegate(this.onPlayerDummyClick, this);
		this._playerWrapper = null;
		this._transcriptWrapper = null;
		this._mediaFileInfo = null;
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());

		const entityData = this.getAssociatedEntityData();
		const visitInfo = BX.prop.getObject(entityData, "VISIT_INFO", {});
		const recordLength = BX.prop.getInteger(visitInfo, "RECORD_LENGTH", 0);
		const recordLengthFormatted = BX.prop.getString(visitInfo, "RECORD_LENGTH_FORMATTED_FULL", "");

		header.appendChild(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: (recordLength > 0 ? recordLengthFormatted + ', ' + BX.message('CRM_TIMELINE_VISIT_AT') + ' ' : '') + this.formatTime(this.getCreatedTime())
				}
			)
		);

		return header;
	}

	prepareContent()
	{
		const entityData = this.getAssociatedEntityData();

		const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
		const communicationTitle = BX.prop.getString(communication, "TITLE", "");
		const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");

		const visitInfo = BX.prop.getObject(entityData, "VISIT_INFO", {});
		const recordLength = BX.prop.getInteger(visitInfo, "RECORD_LENGTH", 0);
		const recordLengthFormatted = BX.prop.getString(visitInfo, "RECORD_LENGTH_FORMATTED_SHORT", "");
		const vkProfile = BX.prop.getString(visitInfo, "VK_PROFILE", "");

		const outerWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-visit"}});
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-visit" }
				}
			)
		);

		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [ wrapper ]
				}
			)
		);

		//Header
		const header = this.prepareHeaderLayout();
		wrapper.appendChild(header);

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		//Details
		const detailWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail crm-entity-stream-content-detail-call-inline"}
			}
		);
		wrapper.appendChild(detailWrapper);

		this._mediaFileInfo = BX.prop.getObject(entityData, "MEDIA_FILE_INFO", null);
		if(this._mediaFileInfo !== null && recordLength > 0)
		{
			this._playerWrapper = this._history.getManager().renderAudioDummy(recordLengthFormatted, this._playerDummyClickHandler);
			detailWrapper.appendChild(
				//crm-entity-stream-content-detail-call
				this._playerWrapper
			);
			detailWrapper.appendChild(this._history.getManager().getAudioPlaybackRateSelector().render());
		}

		const communicationWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail-contact-info"}
			}
		);
		wrapper.appendChild(communicationWrapper);

		//Communications
		if(communicationTitle !== "")
		{
			communicationWrapper.appendChild(document.createTextNode(BX.message("CRM_TIMELINE_VISIT_WITH") + ' '));
			if(communicationShowUrl !== "")
			{
				communicationWrapper.appendChild(
					BX.create("A",
						{
							attrs: { href: communicationShowUrl },
							text: communicationTitle
						}
					)
				);
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		if(BX.type.isNotEmptyString(vkProfile))
		{
			communicationWrapper.appendChild(document.createTextNode(" "));
			communicationWrapper.appendChild(
				BX.create(
					"a",
					{
						attrs:
							{
								className: "crm-entity-stream-content-detail-additional",
								target: "_blank",
								href: this.getVkProfileUrl(vkProfile)
							},
						text: BX.message('CRM_TIMELINE_VISIT_VKONTAKTE_PROFILE')
					}
				)
			)

		}

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			wrapper.appendChild(authorNode);
		}
		//endregion

		return outerWrapper;
	}

	onPlayerDummyClick(e)
	{
		const stubNode = this._playerWrapper.querySelector(".crm-audio-cap-wrap");
		if(stubNode)
		{
			BX.addClass(stubNode, "crm-audio-cap-wrap-loader");
		}

		this._history.getManager().getAudioPlaybackRateSelector().addPlayer(this._history.getManager().loadMediaPlayer(
			"history_" + this.getId(),
			this._mediaFileInfo["URL"],
			this._mediaFileInfo["TYPE"],
			this._playerWrapper,
			this._mediaFileInfo["DURATION"],
			{
				playbackRate: this._history.getManager().getAudioPlaybackRateSelector().getRate()
			}
		));
	}

	getVkProfileUrl(profile)
	{
		return 'https://vk.com/' + BX.util.htmlspecialchars(profile);
	}

	view()
	{
		if (BX.getClass('BX.Crm.Restriction.Bitrix24') && BX.Crm.Restriction.Bitrix24.isRestricted('visit'))
		{
			return BX.Crm.Restriction.Bitrix24.getHandler('visit').call();
		}
		super.view();
	}

	edit()
	{
		if (BX.getClass('BX.Crm.Restriction.Bitrix24') && BX.Crm.Restriction.Bitrix24.isRestricted('visit'))
		{
			return BX.Crm.Restriction.Bitrix24.getHandler('visit').call();
		}
		super.edit();
	}

	static create(id, settings)
	{
		const self = new Visit();
		self.initialize(id, settings);
		return self;
	}
}
