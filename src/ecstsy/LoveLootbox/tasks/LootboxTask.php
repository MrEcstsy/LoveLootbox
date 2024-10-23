<?php

namespace ecstsy\LoveLootbox\tasks;

use ecstsy\LoveLootbox\utils\Utils;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as C;

class LootboxTask extends Task {

    private Player $player;
    private Inventory $inventory;
    private array $timerSlots;
    private array $rewardSlots;
    private array $bonusRewardSlots;
    private array $rewards;
    private array $bonusRewards;
    private int $remainingTime;
    private array $soundSettings;
    private int $ticksElapsed = 0;

    public function __construct(Player $player, Inventory $inventory, array $timerSlots, array $rewardSlots, array $bonusRewardSlots, array $rewards, array $bonusRewards, int $totalTime, array $soundSettings) {
        $this->player = $player;
        $this->inventory = $inventory;
        $this->timerSlots = $timerSlots;
        $this->rewardSlots = $rewardSlots;
        $this->bonusRewardSlots = $bonusRewardSlots;
        $this->rewards = Utils::setupRewards($rewards);
        $this->bonusRewards = Utils::setupRewards($bonusRewards);
        $this->remainingTime = $totalTime;
        $this->soundSettings = $soundSettings;

        Utils::playSound($this->player, $this->soundSettings['start']);
        $this->updateGlassPanes();
    }

    public function onRun(): void {
        $this->ticksElapsed++;

        if ($this->ticksElapsed % 20 === 0) {
            if ($this->remainingTime > 0) {
                $this->remainingTime--;
                $this->updateGlassPanes(); 
            }

            if ($this->remainingTime <= 0) {
                Utils::playSound($this->player, $this->soundSettings['prize']);
                $this->getHandler()->cancel();
                return;
            }
        }

        if ($this->remainingTime > 1 && $this->ticksElapsed % 3 === 0) {
            $this->shuffleRewards();
            Utils::playSound($this->player, $this->soundSettings['start']);
        }
    }

    private function updateGlassPanes(): void {
        foreach ($this->timerSlots as $slot) {
            $glassPane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
            $glassPane->setCount($this->remainingTime > 1 ? $this->remainingTime : 1);
            $glassPane->setCustomName(C::colorize("&r&7" . ($this->remainingTime > 1 ? $this->remainingTime : 1)));
            $this->inventory->setItem($slot, $glassPane);
        }
    }

    private function shuffleRewards(): void {
        foreach ($this->rewardSlots as $slot) {
            $randomReward = $this->rewards[array_rand($this->rewards)];
    
            if ($randomReward instanceof Item) {
                $this->inventory->setItem($slot, $randomReward);
            } else {
                throw new \InvalidArgumentException("Reward is not a valid Item: " . json_encode($randomReward));
            }
        }
    
        if (!empty($this->bonusRewards)) {
            foreach ($this->bonusRewardSlots as $slot) {
                $randomBonusReward = $this->bonusRewards[array_rand($this->bonusRewards)];
        
                if ($randomBonusReward instanceof Item) {
                    $this->inventory->setItem($slot, $randomBonusReward);
                } else {
                    throw new \InvalidArgumentException("Bonus reward is not a valid Item: " . json_encode($randomBonusReward));
                }
            }
        }
    }    
}
