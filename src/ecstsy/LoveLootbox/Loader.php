<?php

namespace ecstsy\LoveLootbox;

use ecstsy\LoveLootbox\commands\LootboxCommand;
use ecstsy\LoveLootbox\listeners\LootboxListener;
use ecstsy\LoveLootbox\utils\inventory\CustomSizedInvMenuType;
use ecstsy\LoveLootbox\utils\Utils;
use JackMD\ConfigUpdater\ConfigUpdater;
use libCustomPack\libCustomPack;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;

class Loader extends PluginBase {

    use SingletonTrait;

    private static ?ZippedResourcePack $pack;

    public const TYPE_DYNAMIC_PREFIX = "muqsit:customsizedinvmenu_";

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        $files = ["lang.yml", "lootbox/example.yml"];

        foreach ($files as $resource) {
            $this->saveResource($resource);
        }
        
        $this->getServer()->getCommandMap()->registerAll("lovelootbox", [
            new LootboxCommand($this, "lovelootbox", "View a list of commands for lootbox", ["ll", "lb"])
        ]);

        $listeners = [
            new LootboxListener()
        ];

        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        libCustomPack::registerResourcePack(self::$pack = libCustomPack::generatePackFromResources($this));

        $packet = StaticPacketCache::getInstance()->getAvailableActorIdentifiers();
        $tag = $packet->identifiers->getRoot();
        assert($tag instanceof CompoundTag);
        $id_list = $tag->getListTag("idlist");
        assert($id_list !== null);
        $id_list->push(CompoundTag::create()
                ->setString("bid", "")
                ->setByte("hasspawnegg", 0)
                ->setString("id", CustomSizedInvMenuType::ACTOR_NETWORK_ID)
                ->setByte("summonable", 0));

    }

    protected function onDisable(): void {
        libCustomPack::unregisterResourcePack(self::$pack);
        $this->getLogger()->info("Resource pack unloaded");

        unlink(Path::join($this->getDataFolder(), self::$pack->getPackName() . ".mcpack"));
    }
}