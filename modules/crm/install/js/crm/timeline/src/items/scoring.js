import History from "./history";

/** @memberof BX.Crm.Timeline.Actions */
export default class Scoring extends History
{
	constructor()
	{
		super();
	}

	prepareContent()
	{
		const outerWrapper = BX.create("DIV", {
			attrs: {
				className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-scoring"
			},
			events: {
				click: function () {
					let url = "/crm/ml/#entity#/#id#/detail";
					const ownerTypeId = this.getOwnerTypeId();
					const ownerId = this.getOwnerId();

					let ownerType;
					if (ownerTypeId === 1)
					{
						ownerType = "lead";
					}
					else if (ownerTypeId === 2)
					{
						ownerType = "deal";
					}
					else
					{
						return;
					}

					url = url.replace("#entity#", ownerType);
					url = url.replace("#id#", ownerId);

					if (BX.SidePanel)
					{
						BX.SidePanel.Instance.open(url, {width: 840});
					}
					else
					{
						top.location.href = url;
					}
				}.bind(this)
			}
		});

		const scoringInfo = BX.prop.getObject(this._data, "SCORING_INFO", null);
		if(!scoringInfo)
		{
			return outerWrapper;
		}

		let score = BX.prop.getNumber(scoringInfo, "SCORE", 0);
		let scoreDelta = BX.prop.getNumber(scoringInfo, "SCORE_DELTA", 0);
		score = Math.round(score * 100);
		scoreDelta = Math.round(scoreDelta * 100);

		const result = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-scoring-total-result"},
				text: score + "%"
			});

		let iconClass = "crm-entity-stream-content-scoring-total-icon";
		if (score < 50)
		{
			iconClass += " crm-entity-stream-content-scoring-total-icon-fail";
		}
		else if (score < 75)
		{
			iconClass += " crm-entity-stream-content-scoring-total-icon-middle";
		}
		else
		{
			iconClass += " crm-entity-stream-content-scoring-total-icon-success";
		}

		const icon = BX.create("DIV",
			{
				attrs: {className: iconClass}
			}
		);

		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-content-scoring-total" },
								children: [
									BX.create("DIV",
										{
											attrs: { className: "crm-entity-stream-content-scoring-total-text" },
											text: BX.message("CRM_TIMELINE_SCORING_TITLE_2")
										}
									),
									result,
									icon
								]
							}
						),
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-content-scoring-event" },
								children: [
									(
										scoreDelta !== 0 ?
											BX.create("DIV",
												{
													attrs: { className: "crm-entity-stream-content-scoring-event-offset" },
													text: (scoreDelta > 0 ? "+" : "") + scoreDelta + "%"
												}
											)
											:
											null
									),
									/*BX.create("DIV",
										{
											attrs: { className: "crm-entity-stream-content-scoring-event-detail" },
											text: "<activity subject>"
										}
									)*/
								]
							}
						)
					]

				}
			)
		);

		return outerWrapper;
	}

	static create(id, settings)
	{
		const self = new Scoring();
		self.initialize(id, settings);
		return self;
	}
}
