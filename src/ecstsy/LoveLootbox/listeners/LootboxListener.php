<?php

namespace ecstsy\LoveLootbox\listeners;

use ecstsy\LoveLootbox\utils\LootboxHandler;
use ecstsy\LoveLootbox\utils\Screens;
use ecstsy\LoveLootbox\utils\Utils;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\utils\TextFormat as C;

class LootboxListener implements Listener {
    
    public static array $namingPlayers = [];
    public static array $editingLore = []; 
    public static array $addingLore = [];

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
        $lang = Utils::getConfiguration("lang.yml");

        if ($lootboxTag === null || !$lootboxTag->getTag("lootbox")) {
            return;
        }
    
        $lootboxId = $lootboxTag->getString("lootbox");
        $config = Utils::getConfiguration("lootbox/" . $lootboxId . ".yml");

        if ($config === null) {
            $player->sendMessage(C::colorize($lang->getNested("ERROR.INVALID-LOOTBOX")));
            return;
        }

        $rewards = $config->get("rewards");

        if (empty($rewards)) {
            $player->sendMessage(C::colorize($lang->getNested("ERROR.NO-REWARDS")));
            return;
        }
    
        $item->pop();
        $player->getInventory()->setItemInHand($item);
    
        $lootboxHandler = LootboxHandler::getInstance();
        $animationType = $config->getNested("animation.type");
        $bonusRewards = $config->get("bonus-rewards");
        $animationSettings = $config->getNested("animation.settings");

        $menu = $lootboxHandler->getLootboxMenu($player, $animationType, $rewards, $bonusRewards, $animationSettings);
    
        if ($menu !== null) {
            $menu->send($player);
            Utils::startLootboxAnimation($player, $menu, $animationSettings, $rewards, $bonusRewards);
        }
    }
    
    public function onPlayerChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $message = $event->getMessage();
    
        if (isset(self::$namingPlayers[$player->getName()])) {
            $event->cancel();
            $lootbox = self::$namingPlayers[$player->getName()]; 
    
            if (strtolower($message) === "cancel") {
                unset(self::$namingPlayers[$player->getName()]);  
                $player->sendMessage(C::colorize("&cLootbox creation canceled."));
                Screens::editLootboxCreation($lootbox)->send($player); 
                return;
            }
    
            Screens::$temporaryLootboxData[$lootbox]['item']['name'] = C::colorize($message);
            unset(self::$namingPlayers[$player->getName()]);
            Screens::editLootboxCreation($lootbox)->send($player); 
        }
    
        if (isset(self::$editingLore[$player->getName()])) {
            $event->cancel(); 
            $lineData = self::$editingLore[$player->getName()]; 
            
            if (isset(Screens::$temporaryLootboxData[$lineData['lootbox']])) {
                if (strtolower($message) === "cancel") {
                    unset(self::$editingLore[$player->getName()]);  
                    Screens::editLootboxCreation($lineData['lootbox'])->send($player); 
                    return;
                }
            
                Screens::$temporaryLootboxData[$lineData['lootbox']]['item']['lore'][$lineData['lineIndex']] = C::colorize($message);
                unset(self::$editingLore[$player->getName()]);
                Screens::editLootboxCreation($lineData['lootbox'])->send($player);
            } else {
                $player->sendMessage(C::colorize("&cThe lootbox does not exist."));
            }
        }
    
        if (isset(self::$addingLore[$player->getName()])) {
            $event->cancel(); 
            $lootbox = self::$addingLore[$player->getName()]; 

            if (strtolower($message) === "cancel") {
                unset(self::$addingLore[$player->getName()]);  
                Screens::editLootboxCreation($lootbox)->send($player); 
                return;
            }
    
            Screens::$temporaryLootboxData[self::$addingLore[$player->getName()]]['item']['lore'][] = C::colorize($message);
            unset(self::$addingLore[$player->getName()]);
            Screens::editLootboxCreation($lootbox)->send($player);
        }
    }
    
}
