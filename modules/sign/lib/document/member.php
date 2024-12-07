<?php
namespace Bitrix\Sign\Document;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blank\Block;
use Bitrix\Sign\Document;
use Bitrix\Sign\Error;
use Bitrix\Sign\File;
use Bitrix\Sign\Proxy;

Loc::loadMessages(__FILE__);

/**
 * @deprecated
 */
class Member extends \Bitrix\Sign\Internal\BaseTable
{
	/**
	 * Allowed communication types.
	 */
	private const ALLOWED_COMMUNICATIONS_TYPE = ['EMAIL', 'PHONE'];
	public const COMMUNICATION_TYPE_MAIL = 'EMAIL';
	public const COMMUNICATION_TYPE_PHONE = 'PHONE';

	/**
	 * Internal class.
	 * @var string
	 */
	public static $internalClass = 'MemberTable';

	/**
	 * Current member data.
	 * @var array
	 */
	protected $data;

	/**
	 * Member document instance.
	 * @var Document
	 */
	private $document;

	/**
	 * Class constructor.
	 * @param array $row Member data.
	 * @param Document $document Document instance.
	 */
	private function __construct(array $row, Document $document)
	{
		$this->document = $document;
		$this->data = $row;
	}

	/**
	 * Returns blocks of current member.
	 * @return Block[]
	 */
	private function getBlocks(): array
	{
		$memberPart = $this->getPart();

		return array_filter($this->document->getBlank()->getBlocks(), function($block) use ($memberPart) {
			return $block->getPart() === $memberPart;
		});
	}

	/**
	 * Returns row id.
	 * @return int
	 */
	public function getId(): int
	{
		return $this->data['ID'];
	}

	/**
	 * Returns hash.
	 * @return string
	 */
	public function getHash(): string
	{
		return $this->data['HASH'];
	}

	/**
	 * Returns Document instance of member.
	 * @return Document
	 */
	public function getDocument(): Document
	{
		return $this->document;
	}

	/**
	 * Returns member contact id.
	 * @return int
     * @deprecated CONTACT_ID is deprecated in v2 documents, look at entity
	 */
	public function getContactId(): int
	{
		return $this->data['CID'];
	}

	/**
	 * Returns true if member is third party.
	 * @return bool
	 */
	public function isThirdParty(): bool
	{
        return (int)$this->data['CID'] !== 0;
	}

	/**
	 * Returns true if member is doc initiator.
	 * @return bool
	 */
	public function isInitiator(): bool
	{
		return !$this->isThirdParty();
	}

	/**
	 * Returns member contact name.
	 * @return string | null
	 */
	public function getContactName(): ?string
	{
		return $this->data['CNAME'] ?? null;
	}

	/**
	 * Returns member part index.
	 * @return int
	 */
	public function getPart(): int
	{
		return $this->data['PART'];
	}

	/**
	 * Returns true if member signed.
	 * @return bool
	 */
	public function isSigned(): bool
	{
		return $this->data['SIGNED'] === 'Y';
	}

	public function getIp(): ?string
	{
		return $this->data['IP'];
	}

	public function getTimeZoneOffset(): ?int
	{
		return $this->data['TIME_ZONE_OFFSET'];
	}

	/**
	 * Returns true if member verified.
	 * @return bool
	 */
	public function isVerified(): bool
	{
		return $this->data['VERIFIED'] === 'Y';
	}

	/**
	 * Member is verified throw communication channel.
	 * @return bool
	 */
	public function verify(): bool
	{
		if ($this->isVerified())
		{
			Error::getInstance()->addError(
				'ALREADY_VERIFIED',
				Loc::getMessage('SIGN_CORE_MEMBER_ALREADY_VERIFIED')
			);
			return false;
		}

		return $this->setData(['VERIFIED' => 'Y']);
	}

	public function downloadDocument(): bool
	{
		return $this->setData(['DATE_DOC_DOWNLOAD' => new Main\Type\DateTime()]);
	}

	public function verifyDocument()
	{
		$this->setData(['DATE_VERIFY' => new Main\Type\DateTime()]);
	}

	public function getDateSign(): ?Main\Type\DateTime
	{
		return $this->data['DATE_SIGN'];
	}

	public function getDateDocumentDownload(): ?Main\Type\DateTime
	{
		return $this->data['DATE_DOC_DOWNLOAD'];
	}

	public function getDateDocumentVerify(): ?Main\Type\DateTime
	{
		return $this->data['DATE_DOC_VERIFY'];
	}

	/**
	 * Returns member communication type.
	 * @return string|null
	 */
	public function getCommunicationType(): ?string
	{
		return $this->data['COMMUNICATION_TYPE'];
	}

	/**
	 * Returns member communication value.
	 * @return string|null
	 */
	public function getCommunicationValue(): ?string
	{
		return $this->data['COMMUNICATION_VALUE'];
	}

	/**
	 * Returns member communications.
	 * @return array
	 */
	public function getCommunications(): array
	{
		return $this->document->getMemberCommunications($this);
	}

	/**
	 * Saves communication's type and value.
	 * @param string $type Communication type.
	 * @param string $value Communication value.
	 * @return bool
	 */
	public function setCommunication(string $type, string $value): bool
	{
		if (!in_array($type, $this::ALLOWED_COMMUNICATIONS_TYPE))
		{
			return false;
		}

		if ($this->isSigned())
		{
			Error::getInstance()->addError(
				'ALREADY_SIGNED',
				Loc::getMessage('SIGN_CORE_MEMBER_ALREADY_SIGNED')
			);
			return false;
		}

		return $this->setData([
			'COMMUNICATION_TYPE' => $type,
			'COMMUNICATION_VALUE' => $value
		]);
	}

	/**
	 * Returns user data if exists.
	 * @return mixed
	 */
	public function getUserData()
	{
		return $this->data['USER_DATA'];
	}

	/**
	 * Returns true if signature exists.
	 * @return bool
	 */
	public function hasSignatureFile(): bool
	{
		return $this->data['SIGNATURE_FILE_ID'] > 0;
	}

	/**
	 * Returns File instance of signature.
	 * @return File|null
	 */
	public function getSignatureFile(): ?File
	{
		if ($this->data['SIGNATURE_FILE_ID'])
		{
			return new File((int) $this->data['SIGNATURE_FILE_ID']);
		}

		return null;
	}

	/**
	 * Saves File signature to Member.
	 * @param File $file File instance.
	 * @return bool
	 */
	public function setSignatureFile(File $file): bool
	{
		if (!$file->isImage())
		{
			Error::getInstance()->addError(
				'FILE_SIGN_NOT_IMAGE',
				Loc::getMessage('SIGN_CORE_MEMBER_FILE_SIGN_NOT_IMAGE')
			);
			return false;
		}

		if ($this->isSigned())
		{
			Error::getInstance()->addError(
				'ALREADY_SIGNED',
				Loc::getMessage('SIGN_CORE_MEMBER_ALREADY_SIGNED')
			);
			return false;
		}

		return $this->setData([
			'SIGNATURE_FILE_ID' => $file->save()
		]);
	}

	/**
	 * Returns true if stamp exists.
	 * @return bool
	 */
	public function hasStampFile(): bool
	{
		return $this->data['STAMP_FILE_ID'] > 0;
	}

	/**
	 * Returns File instance of stamp.
	 * @return File|null
	 */
	public function getStampFile(): ?File
	{
		if ($this->data['STAMP_FILE_ID'])
		{
			return new File((int) $this->data['STAMP_FILE_ID']);
		}

		return null;
	}

	/**
	 * Saves File stamp to Member.
	 * @param File $file File instance.
	 * @return bool
	 */
	public function setStampFile(File $file): bool
	{
		if (!$file->isImage())
		{
			Error::getInstance()->addError(
				'FILE_STAMP_NOT_IMAGE',
				Loc::getMessage('SIGN_CORE_MEMBER_FILE_STAMP_NOT_IMAGE')
			);
			return false;
		}

		if ($this->isSigned())
		{
			Error::getInstance()->addError(
				'ALREADY_SIGNED',
				Loc::getMessage('SIGN_CORE_MEMBER_ALREADY_SIGNED')
			);
			return false;
		}

		return $this->setData([
			'STAMP_FILE_ID' => $file->save()
		]);
	}

	/**
	 * Returns meta information if exists.
	 * @return mixed
	 */
	public function getMeta()
	{
		return $this->data['META'];
	}

	/**
	 * Saves some user data.
	 * @param array $data User data.
	 * @param bool $skipSend Skip sending to safe.
	 * @return bool
	 */
	public function setUserData(array $data, bool $skipSend = false): bool
	{
		if (!$this->isVerified())
		{
			Error::getInstance()->addError(
				'MEMBER_NOT_VERIFIED',
				Loc::getMessage('SIGN_CORE_MEMBER_NOT_VERIFIED')
			);
			return false;
		}

		if ($this->isSigned())
		{
			Error::getInstance()->addError(
				'ALREADY_SIGNED',
				Loc::getMessage('SIGN_CORE_MEMBER_ALREADY_SIGNED')
			);
			return false;
		}

		$res = self::update($this->data['ID'], [
			'USER_DATA' => array_merge(
				$this->data['USER_DATA'] ?: [],
				$data
			)
		]);
		if (!$res->isSuccess())
		{
			Error::getInstance()->addFromResult($res);
			return false;
		}

		if ($skipSend)
		{
			return true;
		}

		$result = Proxy::sendCommand('document.setMemberUserData', [
			'documentHash' => $this->document->getHash(),
			'memberHash' => $this->getHash(),
			'data' => $data
		]);

		return $result === true;
	}

	/**
	 * Returns sign url for member.
	 * @return string
	 */
	public function getSignUrl(): string
	{
		$url = \Bitrix\Sign\Proxy::getFrontendUrl();

		return str_replace(
			['#doc_hash#', '#member_hash#'],
			[mb_strtolower($this->document->getHash()), $this->data['HASH']],
			$url
		);
	}

	/**
	 * Returns sign url for member.
	 * @return string
	 * @deprecated
	 */
	public function getDownloadUrl(): string
	{
		$url = \Bitrix\Sign\Proxy::getFrontendUrl();

		return str_replace(
			['#doc_hash#', '#member_hash#'],
			[mb_strtolower($this->document->getHash()), $this->data['HASH']],
			$url
		);
	}

	/**
	 * Mutes member (not send any letters to member).
	 * @param bool $flag Mute if true, unmute in other way.
	 * @return bool
	 */
	public function mute(bool $flag = true): bool
	{
		return $this->setData(['MUTE' => $flag ? 'Y' : 'N']);
	}

	/**
	 * Unmutes member (send letters to member).
	 * @return bool
	 */
	public function unMute(): bool
	{
		return $this->mute(false);
	}

	/**
	 * Returns true, if this member is no allow to receive any messages.
	 * @return bool
	 */
	public function isMuted(): bool
	{
		return $this->data['MUTE'] === 'Y';
	}

	/**
	 * Returns current instance as simple array.
	 * @return array
	 */
	public function toArray(): array
	{
		$signatureFile = $this->getSignatureFile();
		$stampFile = $this->getStampFile();
		$contactName = $this->getContactName();

		if ($this->isInitiator())
		{
			// $directorName = \Bitrix\Sign\Integration\CRM::getDirectorName($this->getDocument()->getCompanyId());
			// $contactName = !empty($directorName) ? $directorName : null;
			$contactName = null;
		}

		return [
			'signed' => $this->data['SIGNED'],
			'verified' => $this->data['VERIFIED'],
			'mute' => $this->data['MUTE'],
			'hash' => $this->getHash(),
			'cid'  => $this->getContactId(),
			'part'  => $this->getPart(),
			'name' => $this->getContactName(),
			'communicationType' => $this->getCommunicationType(),
			'communicationValue' => $this->getCommunicationValue(),
			'meta' => [
				'contactName' => $contactName
			],
			'signature' => $signatureFile
				? [
					'ext' => $signatureFile->getExtension(),
					'content' => $signatureFile->getBase64Content(),
				]
				: null,
			'stamp' => $stampFile
				? [
					'ext' => $stampFile->getExtension(),
					'content' => $stampFile->getBase64Content(),
				]
				: null,
			'ip' => $this->getIp(),
			'timeZoneOffset' => $this->getTimeZoneOffset(),
		];
	}

	/**
	 * Creates member for document, returns true on success.
	 * @param Document $document Document instance.
	 * @param int $contactId Contact id.
	 * @param int $part Part index.
	 * @return bool
	 */
	public static function create(Document $document, int $contactId, int $part): bool
	{
		$res = self::add([
			'DOCUMENT_ID' => $document->getId(),
			'CONTACT_ID' => $contactId,
			'SIGNED' => 'N',
			'PART' => $part,
			'HASH' => md5(uniqid(mt_rand(), true) . '|' . $document->getId() . '|' . $contactId)
		]);
		if ($res->isSuccess())
		{
			return true;
		}
		else
		{
			Error::getInstance()->addFromResult($res);
			return false;
		}
	}

	/**
	 * Returns document's members.
	 * @param Document $document Document instance.
	 * @param bool $asArray Returns list as simple array.
	 * @return self[]
	 */
	public static function getDocumentMembers(Document $document, bool $asArray = false): array
	{
		$members = [];
		$bCrmInclude = \Bitrix\Main\Loader::includeModule('crm');

		$res = Member::getList([
			'select' => [
				'*',
				'CID' => 'CONTACT_ID',
			] + ($bCrmInclude ? [
				'CONTACT_HONORIFIC' => 'CONTACT.HONORIFIC',
				'CONTACT_NAME' => 'CONTACT.NAME',
				'CONTACT_LAST_NAME' => 'CONTACT.LAST_NAME',
				'CONTACT_SECOND_NAME' => 'CONTACT.SECOND_NAME',
			] : []),
			'filter' => [
				'DOCUMENT_ID' => $document->getId()
			],
			'order' => [
				'PART' => 'asc'
			]
		]);
		while ($row = $res->fetch())
		{
			if ($bCrmInclude)
			{
				$row['CNAME'] = \CCrmContact::prepareFormattedName([
					'HONORIFIC' => $row['CONTACT_HONORIFIC'] ?? '',
					'NAME' => $row['CONTACT_NAME'] ?? '',
					'LAST_NAME' => $row['CONTACT_LAST_NAME'] ?? '',
					'SECOND_NAME' => $row['CONTACT_SECOND_NAME'] ?? '',
				]);
			}
			else
			{
				$row['CNAME'] = $row['ID'];
			}

			$member = new static($row, $document);
			$members[] = $asArray ? $member->toArray() : $member;
		}

		return $members;
	}
}
