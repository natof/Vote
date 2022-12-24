<?php

namespace natof\vote\librairie\gui\tasks;

use natof\vote\librairie\gui\inventories\SimpleChestInventory;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class DelayTasks extends Task
{

    public Player $player;
    public SimpleChestInventory $inventory;

    public function __construct(Player $player, SimpleChestInventory $inventory)
    {
        $this->player = $player;
        $this->inventory = $inventory;
    }

    public function onRun(): void
    {
        if ($this->player->isConnected()) {
            $this->player->setCurrentWindow($this->inventory);
        }
    }
}