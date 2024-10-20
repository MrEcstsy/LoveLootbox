<?php

namespace ecstsy\LoveLootbox\utils;

use ecstsy\LoveLootbox\utils\inventory\CustomSizedInvMenu;
use ecstsy\LoveLootbox\libs\muqsit\invmenu\InvMenu;
use ecstsy\LoveLootbox\libs\muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as C;

class LootboxHandler {
    use SingletonTrait;

    public function __construct()
    {
        self::setInstance($this);
    }

    /**
     * Handles lootbox animations based on the type and returns the menu or null.
     *
     * @param Player $player
     * @param int $animationType The type of animation to display
     * @param array $rewards The main rewards to show in the menu
     * @param array $bonusRewards The bonus rewards to show in the menu
     * @param array $settings The settings (such as reward slots and bonus reward slots)
     * @return CustomInvMenu|InvMenu|null The created menu or null if no menu is needed.
     */
    public function getLootboxMenu(Player $player, int $animationType, array $rewards, array $bonusRewards, array $settings): ?InvMenu {

        switch ($animationType) {
            case 1:
                $menu = CustomSizedInvMenu::create(9);
                $menu->setName(C::colorize($settings['title'] ?? "Lootbox Animation Type 1"));
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            case 2:
                $menu = CustomSizedInvMenu::create(9);  
                $menu->setName(C::colorize($settings['title'] ?? "Lootbox Animation Type 2"));
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            case 3:
                $this->givePlayerRewards($player, $rewards);
                $this->givePlayerRewards($player, $bonusRewards);
                return null;  

            case 4:
                $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
                $menu->setName(C::colorize($settings['title'] ?? "Lootbox Animation Type 4"));
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            case 5:
                $slots = max(9, ceil((count($rewards) + count($bonusRewards)) / 9) * 9);
                $menu = CustomSizedInvMenu::create($slots);
                $menu->setName(C::colorize($settings['title'] ?? "Lootbox Animation Type 5"));
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            case 6:
                $this->createWorldEffect($player);
                return null;  

            case 7:
                $this->givePlayerRewards($player, $rewards);
                $this->givePlayerRewards($player, $bonusRewards);
                return null;

            case 8:
                $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
                $menu->setName(C::colorize($settings['title'] ?? "Lootbox Animation Type 8"));
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            default:
                return null;  
        }
    }

    /**
     * Populate the menu with both rewards and bonus rewards.
     *
     * @param Inventory $inventory
     * @param array $rewards
     * @param array $bonusRewards
     * @param array $settings
     */
    private function populateMenuWithRewards(Inventory $inventory, array $rewards, array $bonusRewards, array $settings): void {
        $rewardSlots = $settings['rewards-slots'] ?? [];
        $bonusRewardSlots = $settings['bonus-rewards-slots'] ?? [];

        foreach ($rewards as $index => $reward) {
            if (isset($rewardSlots[$index])) {
                $slot = $rewardSlots[$index];
                $inventory->setItem($slot, $reward);
            }
        }

        foreach ($bonusRewards as $index => $bonusReward) {
            if (isset($bonusRewardSlots[$index])) {
                $slot = $bonusRewardSlots[$index];
                $inventory->setItem($slot, $bonusReward);
            }
        }
    }

    /**
     * Give the player rewards directly.
     *
     * @param Player $player
     * @param array $rewards
     */
    private function givePlayerRewards(Player $player, array $rewards): void {
        foreach ($rewards as $reward) {
            if (!$player->getInventory()->canAddItem($reward)) {
                $player->sendMessage(C::colorize("&r&cYour inventory is full!"));
                continue;
            }
            $player->getInventory()->addItem($reward);
        }
    }

    /**
     * Create a world effect in front of the player (e.g., particles or entities).
     *
     * @param Player $player
     */
    private function createWorldEffect(Player $player): void {
        $level = $player->getWorld();
        $pos = $player->getPosition()->add(0, 1, 0);

    }
}
