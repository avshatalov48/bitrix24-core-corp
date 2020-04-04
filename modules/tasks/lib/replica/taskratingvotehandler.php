<?php
namespace Bitrix\Tasks\Replica;

class TaskRatingVoteHandler extends \Bitrix\Replica\Client\RatingVoteHandler
{
	protected $entityTypeId = "TASK";
	protected $entityIdTranslation = "b_tasks.ID";
}
