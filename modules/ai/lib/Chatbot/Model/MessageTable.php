<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Model;

use Bitrix\AI\Chatbot\Enum\MessageType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Emoji;

/**
 * Class MessageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Message_Query query()
 * @method static EO_Message_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Message_Result getById($id)
 * @method static EO_Message_Result getList(array $parameters = [])
 * @method static EO_Message_Entity getEntity()
 * @method static \Bitrix\AI\Chatbot\Model\Message createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Chatbot\Model\Messages createCollection()
 * @method static \Bitrix\AI\Chatbot\Model\Message wakeUpObject($row)
 * @method static \Bitrix\AI\Chatbot\Model\Messages wakeUpCollection($rows)
 */
class MessageTable extends DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_ai_chatbot_message';
	}

	public static function getObjectClass(): string
	{
		return Message::class;
	}

	public static function getCollectionClass()
	{
		return Messages::class;
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
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
			(new EnumField('TYPE'))
				->configureRequired()
				->configureDefaultValue(MessageType::Default->value)
				->configureValues(array_column(MessageType::cases(), 'value')),
			(new StringField('CONTENT'))
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode'])
			,
			(new ArrayField('PARAMS'))
				->configureDefaultValue('')
				->configureSerializationJson(),
			(new Reference('UNREAD', MessageUnreadTable::class, Join::on('this.ID', 'ref.MESSAGE_ID'))),
			(new DatetimeField('DATE_CREATE')),
		];
	}
}