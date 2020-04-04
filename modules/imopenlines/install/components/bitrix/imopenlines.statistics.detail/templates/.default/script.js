BX.ready(function(){
	BX.PULL.extendWatch('IMOL_STATISTICS');

	BX.addCustomEvent("onPullEvent-imopenlines", function(command,params) {
		if (command == 'voteHead')
		{
			if(typeof params.voteValue !== 'undefined')
			{
				var placeholderVote = BX("ol-vote-head-placeholder-"+params.sessionId);
				if (placeholderVote)
				{
					BX.cleanNode(placeholderVote);
					placeholderVote.appendChild(
						BX.MessengerCommon.linesVoteHeadNodes(params.sessionId, params.voteValue, true)
					);
				}
			}

			if(typeof params.commentValue !== 'undefined')
			{
				var placeholderComment = BX("ol-comment-head-placeholder-"+params.sessionId);
				if (placeholderComment)
				{
					BX.cleanNode(placeholderComment);
					placeholderComment.appendChild(
						BX.MessengerCommon.linesCommentHeadNodes(params.sessionId, params.commentValue, true, "statistics")
					);
				}
			}
		}
	});

});

