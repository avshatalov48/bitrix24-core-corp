<?php

namespace Bitrix\Crm\Timeline\SignDocument;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Item;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

final class DocumentData implements \JsonSerializable, Arrayable
{
	protected int $documentId;
	protected ?string $documentHash = null;
	protected ?string $memberHash = null;
	protected ?DateTime $createdTime = null;
	protected ?int $authorId = null;
	/** @var ItemIdentifier[] */
	protected array $bindings = [];
	protected ?Uri $fileUrl = null;
	protected ?Signer $mySigner = null;
	/** @var Signer[] */
	protected array $signers = [];
	protected ?Item $item = null;
	protected ?int $fieldsCount = null;

	public function __construct(int $documentId)
	{
		$this->documentId = $documentId;
	}

	public static function createFromArray(array $data): self
	{
		$eventData = new self((int)$data['documentId']);
		if (!empty($data['createdTime']))
		{
			$createdTime = $data['createdTime'];
			if (!($createdTime instanceof DateTime))
			{
				$createdTime = new DateTime($createdTime);
			}
			$eventData->setCreatedTime($createdTime);
		}
		if (!empty($data['authorId']))
		{
			$eventData->setAuthorId((int)$data['authorId']);
		}
		if (!empty($data['memberHash']))
		{
			$eventData->setMemberHash($data['memberHash']);
		}
		if (!empty($data['documentHash']))
		{
			$eventData->setDocumentHash($data['documentHash']);
		}
		if (!empty($data['bindings']) && is_array($data['bindings']))
		{
			foreach ($data['bindings'] as $binding)
			{
				if (is_array($binding))
				{
					$binding = ItemIdentifier::createFromArray($binding);
				}
				if ($binding instanceof ItemIdentifier)
				{
					$eventData->addBinding($binding);
				}
			}
		}
		if (!empty($data['fileUrl']))
		{
			$fileUrl = $data['fileUrl'];
			if (!($fileUrl instanceof Uri))
			{
				$fileUrl = new Uri($fileUrl);
			}
			$eventData->setFileUrl($fileUrl);
		}
		if (!empty($data['mySigner']))
		{
			$mySigner = $data['mySigner'];
			if (is_array($mySigner))
			{
				$mySigner = Signer::createFromArray($data['mySigner']);
			}
			if ($mySigner instanceof Signer)
			{
				$eventData->setMySigner($mySigner);
			}
		}
		if (!empty($data['signers']) && is_array($data['signers']))
		{
			foreach ($data['signers'] as $signer)
			{
				if (is_array($signer))
				{
					$signer = Signer::createFromArray($signer);
				}
				if ($signer instanceof Signer)
				{
					$eventData->addSigner($signer);
				}
			}
		}
		if (isset($data['item']) && $data['item'] instanceof Item)
		{
			$eventData->setItem($data['item']);
		}
		if (isset($data['fieldsCount']))
		{
			$eventData->setFieldsCount((int)$data['fieldsCount']);
		}
		$eventData->bindDocumentEntities();

		return $eventData;
	}

	private function bindDocumentEntities(): void
	{
		if (!$this->item)
		{
			return;
		}

		$item = $this->item;
		$relatedContacts = $item->getContactIds();
		$relatedCompanies = [];

		if ($item->hasField(Item\Contact::FIELD_NAME_COMPANY_BINDINGS))
		{
			$relatedCompanies = $item->get(Item\Contact::FIELD_NAME_COMPANY_BINDINGS);
		}

		foreach ($relatedContacts as $contact)
		{
			$this->addBinding(
				new ItemIdentifier(\CCrmOwnerType::Contact,  $contact)
			);
		}
		foreach ($relatedCompanies as $company)
		{
			$this->addBinding(
				new ItemIdentifier(\CCrmOwnerType::Company,  $company['ID'])
			);
		}
		if (isset($item->getData()['MYCOMPANY_ID']) && (int)$item->getData()['MYCOMPANY_ID'] > 0)
		{
			$this->addBinding(
				new ItemIdentifier(\CCrmOwnerType::Company,  $item->getData()['MYCOMPANY_ID'])
			);
		}
	}

	public function getDocumentId(): int
	{
		return $this->documentId;
	}

	public function setCreatedTime(DateTime $createdTime): self
	{
		$this->createdTime = $createdTime;

		return $this;
	}

	public function getCreatedTime(): ?DateTime
	{
		return $this->createdTime;
	}

	public function setAuthorId(int $authorId): self
	{
		$this->authorId = $authorId;

		return $this;
	}

	public function getAuthorId(): ?int
	{
		return $this->authorId;
	}

	/**
	 * @param ItemIdentifier[] $bindings
	 * @return DocumentData
	 */
	public function setBindings(array $bindings): self
	{
		$this->bindings = [];
		foreach ($bindings as $binding)
		{
			if ($binding instanceof ItemIdentifier)
			{
				$this->addBinding($binding);
			}
		}

		return $this;
	}

	public function addBinding(ItemIdentifier $binding): self
	{
		$this->bindings[$binding->getHash()] = $binding;

		return $this;
	}

	/**
	 * @return ItemIdentifier[]
	 */
	public function getBindings(): array
	{
		return $this->bindings;
	}

	public function getBindingsArray(): array
	{
		$bindings = [];
		foreach ($this->bindings as $binding)
		{
			$bindings[] = $binding->toArray();
		}

		return $bindings;
	}

	public function setFileUrl(Uri $fileUrl): self
	{
		$this->fileUrl = $fileUrl;

		return $this;
	}

	public function getFileUrl(): ?Uri
	{
		return $this->fileUrl;
	}

	public function setMySigner(Signer $mySigner): self
	{
		$this->mySigner = $mySigner;

		return $this;
	}

	public function getMySigner(): ?Signer
	{
		return $this->mySigner;
	}

	/**
	 * @param array $signers
	 * @return DocumentData
	 */
	public function setSigners(array $signers): self
	{
		$this->signers = [];
		foreach ($signers as $signer)
		{
			if ($signer instanceof Signer)
			{
				$this->addSigner($signer);
			}
		}

		return $this;
	}
	
	public function addSigner(Signer $signer): self
	{
		$this->signers[] = $signer;

		return $this;
	}

	public function getSigners(): array
	{
		return $this->signers;
	}

	public function setItem(Item $item): self
	{
		$this->item = $item;
		return $this;
	}

	public function getItem(): ?Item
	{
		return $this->item;
	}

	public function setFieldsCount(?int $fieldsCount): DocumentData
	{
		$this->fieldsCount = $fieldsCount;

		return $this;
	}

	public function getFieldsCount(): ?int
	{
		return $this->fieldsCount;
	}

	/**
	 * @return null|string
	 */
	public function getDocumentHash(): ?string
	{
		return $this->documentHash;
	}

	/**
	 * @param string $documentHash
	 * @return DocumentData
	 */
	public function setDocumentHash(string $documentHash): DocumentData
	{
		$this->documentHash = $documentHash;
		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getMemberHash(): ?string
	{
		return $this->memberHash;
	}

	/**
	 * @param string $memberHash
	 * @return DocumentData
	 */
	public function setMemberHash(string $memberHash): DocumentData
	{
		$this->memberHash = $memberHash;
		return $this;
	}


	public function toArray(): array
	{
		$data = [
			'documentId' => $this->getDocumentId(),
		];
		if ($this->createdTime)
		{
			$data['createdTime'] = $this->createdTime;
		}
		if ($this->authorId)
		{
			$data['authorId'] = $this->authorId;
		}
		if ($this->fileUrl)
		{
			$data['fileUrl'] = $this->fileUrl;
		}
		if ($this->mySigner)
		{
			$data['mySigner'] = $this->mySigner->toArray();
		}
		if ($this->documentHash)
		{
			$data['documentHash'] = $this->documentHash;
		}
		if ($this->memberHash)
		{
			$data['memberHash'] = $this->memberHash;
		}
		$data['bindings'] = $this->getBindingsArray();
		$data['signers'] = [];
		foreach ($this->signers as $signer)
		{
			$data['signers'][] = $signer->toArray();
		}
		if ($this->fieldsCount > 0)
		{
			$data['fieldsCount'] = $this->fieldsCount;
		}

		return $data;
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
