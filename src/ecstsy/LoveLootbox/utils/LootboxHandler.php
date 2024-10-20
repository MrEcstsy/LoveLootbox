<?php

namespace ecstsy\LoveLootbox\utils;

use ecstsy\LoveLootbox\utils\inventory\CustomSizedInvMenu;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
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
                // 9 Slot Menu
                $menu = CustomSizedInvMenu::create(9);
                $menu->setName($settings['title'] ?? "Lootbox Animation Type 1");
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            case 2:
                // Another custom-sized inventory menu (e.g., also 9 slots)
                $menu = CustomSizedInvMenu::create(9);  // Adjust size as needed
                $menu->setName($settings['title'] ?? "Lootbox Animation Type 2");
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            case 3:
                // No Menu, just give rewards and bonus rewards directly
                $this->givePlayerRewards($player, $rewards);
                $this->givePlayerRewards($player, $bonusRewards);
                return null;  // No menu is needed here

            case 4:
                // Chest Menu (27 slots)
                $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
                $menu->setName($settings['title'] ?? "Lootbox Animation Type 4");
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            case 5:
                // Start with 9 slots, dynamically increase size for every 9 items
                $slots = max(9, ceil((count($rewards) + count($bonusRewards)) / 9) * 9);
                $menu = CustomSizedInvMenu::create($slots);
                $menu->setName($settings['title'] ?? "Lootbox Animation Type 5");
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            case 6:
                // World effect animation (e.g., particles or entities in front of player)
                $this->createWorldEffect($player);
                return null;  // No menu is needed here

            case 7:
                // Just give all rewards (including bonus) without menu
                $this->givePlayerRewards($player, $rewards);
                $this->givePlayerRewards($player, $bonusRewards);
                return null;

            case 8:
                // Double chest animation (54 slots)
                $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
                $menu->setName($settings['title'] ?? "Lootbox Animation Type 8");
                $this->populateMenuWithRewards($menu->getInventory(), $rewards, $bonusRewards, $settings);
                return $menu;

            default:
                return null;  // Invalid animation type, no menu
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

        // Populate main rewards in designated slots
        foreach ($rewards as $index => $reward) {
            if (isset($rewardSlots[$index])) {
                $slot = $rewardSlots[$index];
                $inventory->setItem($slot, $reward);
            }
        }

        // Populate bonus rewards in designated bonus slots
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
                // Handle inventory full case
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
        // Example: Play some particle or entity animation in the world
        $level = $player->getWorld();
        $pos = $player->getPosition()->add(0, 1, 0);

        // Example: Firework particles or any other effect
        // $level->addParticle($pos, new FireworkParticle());

        // Play animation in front of the player
    }
}