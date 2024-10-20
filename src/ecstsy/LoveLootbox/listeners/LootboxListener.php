<?php

namespace ecstsy\LoveLootbox\listeners;

use ecstsy\LoveLootbox\utils\LootboxHandler;
use ecstsy\LoveLootbox\utils\Utils;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;

class LootboxListener implements Listener {

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $item = $event->getItem();
        $tag = $item->getNamedTag();

        if ($tag->getTag("LoveLootbox") !== null) {
            $event->cancel();
        }
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $tag = $item->getNamedTag();
        $lootboxTag = $tag->getCompoundTag("LoveLootbox");

        if ($lootboxTag === null || !$lootboxTag->getTag("lootbox")) {
            return;
        }
    
        $lootboxId = $lootboxTag->getString("lootbox");
    
        $config = Utils::getConfiguration("lootbox/" . $lootboxId . ".yml");
        if ($config === null) {
            $player->sendMessage("Failed to open the lootbox. Configuration not found!");
            return;
        }
    
        $item->pop();
        $player->getInventory()->setItemInHand($item);
    
        $lootboxHandler = LootboxHandler::getInstance();
        $animationType = $config->getNested("animation.type");
        $rewards = $config->get("rewards");
        $bonusRewards = $config->get("bonus-rewards");
        $animationSettings = $config->getNested("animation.settings");
    
        $menu = $lootboxHandler->getLootboxMenu($player, $animationType, $rewards, $bonusRewards, $animationSettings);
    
        if ($menu !== null) {
            $menu->send($player);
        } 
    }
    
}
