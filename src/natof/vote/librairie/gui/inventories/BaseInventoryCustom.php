<?php

namespace natof\vote\librairie\gui\inventories;

use pocketmine\block\inventory\BlockInventory;
use pocketmine\block\inventory\BlockInventoryTrait;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;

abstract class BaseInventoryCustom extends SimpleInventory implements BlockInventory
{
    use BlockInventoryTrait;

    protected string $name = "Chest";
    protected bool $hasViewOnly = false;
    protected $clickListener = null;
    protected $closeListener = null;
    private bool $transactionCancel = false;


    public function __construct(int $size = 27)
    {
        parent::__construct($size);
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $value): self
    {
        $this->name = $value;
        return $this;
    }

    public function transactionCancel(): self
    {
        $this->transactionCancel = true;
        return $this;
    }

    public function isCancelTransaction(): bool
    {
        return $this->transactionCancel;
    }

    public function reloadTransaction(): self
    {
        $this->transactionCancel = false;
        return $this;
    }

    public function setViewOnly(bool $value = true): self
    {
        $this->hasViewOnly = $value;
        return $this;
    }

    public function isViewOnly(): bool
    {
        return $this->hasViewOnly;
    }

    public function getClickListener()
    {
        return $this->clickListener;
    }

    public function setClickListener(?callable $callable): self
    {
        $this->clickListener = $callable;
        return $this;
    }

    public function onClose(Player $who): void
    {
        parent::onClose($who);
        $who->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(BlockPosition::fromVector3($this->holder), RuntimeBlockMapping::getInstance()->toRuntimeId($who->getWorld()->getBlock($this->holder)->getFullId()), UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL));
        $closeListener = $this->getCloseListener();
        if ($closeListener !== null) {
            $closeListener($who, $this);
        }
        $inventory = $who->getInventory();
        foreach ($inventory->getContents() as $slot => $item) {
            if ($item->hasNamedTag()) {
                if ($item->getNamedTag()->getString('idAntiClumsy', '404') === 'TRUE') {
                    $inventory->setItem($slot, VanillaItems::AIR());
                }
            }
        }
    }

    public function getCloseListener()
    {
        return $this->closeListener;
    }

    public function setCloseListener(?callable $callable): self
    {
        $this->closeListener = $callable;
        return $this;
    }
}