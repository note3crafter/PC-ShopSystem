<?php

//  ╔═════╗ ╔═════╗ ╔═════╗     ╔═╗ ╔═════╗ ╔═════╗ ╔═════╗      ╔═════╗ ╔═════╗ ╔═════╗ ╔═════╗
//  ║ ╔═╗ ║ ║ ╔═╗ ║ ║ ╔═╗ ║     ║ ║ ║ ╔═══╝ ║ ╔═══╝ ╚═╗ ╔═╝      ║ ╔═══╝ ║ ╔═╗ ║ ║ ╔═╗ ║ ║ ╔═══╝
//  ║ ╚═╝ ║ ║ ╚═╝ ║ ║ ║ ║ ║     ║ ║ ║ ╚══╗  ║ ║       ║ ║        ║ ║     ║ ║ ║ ║ ║ ╚═╝ ║ ║ ╚══╗
//  ║ ╔═══╝ ║ ╔╗ ╔╝ ║ ║ ║ ║ ╔═╗ ║ ║ ║ ╔══╝  ║ ║       ║ ║        ║ ║     ║ ║ ║ ║ ║ ╔╗ ╔╝ ║ ╔══╝
//  ║ ║     ║ ║╚╗╚╗ ║ ╚═╝ ║ ║ ╚═╝ ║ ║ ╚═══╗ ║ ╚═══╗   ║ ║        ║ ╚═══╗ ║ ╚═╝ ║ ║ ║╚╗╚╗ ║ ╚═══╗
//  ╚═╝     ╚═╝ ╚═╝ ╚═════╝ ╚═════╝ ╚═════╝ ╚═════╝   ╚═╝        ╚═════╝ ╚═════╝ ╚═╝ ╚═╝ ╚═════╝
//  Easy to Use! Written in Love! Project Core by TheNote\RetroRolf\Rudolf2000\note3crafter

namespace TheNote\ShopSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use TheNote\core\CoreAPI;
use TheNote\ShopSystem\events\EconomySell;
use TheNote\ShopSystem\events\EconomyShop;

class Main extends PluginBase
{
    public Config $shopSign;
    public Config $sellSign;

    public function onLoad(): void
    {
        $projectcore = $this->getServer()->getPluginManager()->getPlugin("ProjectCore");
        if ($projectcore === null) {
            $this->getLogger()->alert("This Plugin need ProjectCore! Please install ProjectCore before Using this Plugin!");
            $this->getServer()->shutdown();
        }
        @mkdir($this->getDataFolder() . "Lang");
        $this->saveResource("Lang/LangDEU.json");
        $this->saveResource("Lang/LangENG.json");
        $this->saveResource("Lang/LangESP.json");
        $this->saveResource("Config.yml");
    }

    public function onEnable(): void
    {
        $this->sellSign = new Config($this->getDataFolder() . "Lang/SellSign.yml", Config::YAML, array(
            "sell" => array(
                "§f[§cSell§f]",
                "§ePrice§f: {cost}§e$",
                "§eAmount §f: §e{amount}",
                "§e {item}"
            )
        ));
        $this->sellSign->save();
        $this->shopSign = new Config($this->getDataFolder() . "Lang/ShopSign.yml", Config::YAML, array(
            "shop" => array(
                "§f[§aShop§f]",
                "§ePrice §f: {price}§e$",
                "§eAmount §f: §e{amount}",
                "§e {item}"
            )
        ));
        $this->shopSign->save();
        $this->getServer()->getPluginManager()->registerEvents(new EconomySell($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EconomyShop($this), $this);
    }

    public function getLang(string $player, $langkey) {
        $api = new CoreAPI();
        $lang = new Config($this->getDataFolder() . "Lang/Lang" . $api->getUser($player, "language") . ".json", Config::JSON);
        return $lang->get($langkey);
    }
}
