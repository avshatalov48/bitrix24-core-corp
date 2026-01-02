<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\DatetimeField;

/**
 * Class MessageViewedTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_MessageUnread_Query query()
 * @method static EO_MessageUnread_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_MessageUnread_Result getById($id)
 * @method static EO_MessageUnread_Result getList(array $parameters = [])
 * @method static EO_MessageUnread_Entity getEntity()
 * @method static \Bitrix\AI\Chatbot\Model\MessageUnread createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Chatbot\Model\EO_MessageUnread_Collection createCollection()
 * @method static \Bitrix\AI\Chatbot\Model\MessageUnread wakeUpObject($row)
 * @method static \Bitrix\AI\Chatbot\Model\EO_MessageUnread_Collection wakeUpCollection($rows)
 */
class MessageUnreadTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_ai_chatbot_message_unread';
	}

	public static function getObjectClass(): string
	{
		return MessageUnread::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),
			(new IntegerField('CHAT_ID'))
				->configureRequired(),
			(new IntegerField('AUTHOR_ID'))
				->configureRequired(),
			(new IntegerField('MESSAGE_ID'))
				->configureRequired(),
			(new DatetimeField('DATE_CREATE')),
		];
	}
}