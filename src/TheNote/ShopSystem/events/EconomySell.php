<?php

//  ╔═════╗ ╔═════╗ ╔═════╗     ╔═╗ ╔═════╗ ╔═════╗ ╔═════╗      ╔═════╗ ╔═════╗ ╔═════╗ ╔═════╗
//  ║ ╔═╗ ║ ║ ╔═╗ ║ ║ ╔═╗ ║     ║ ║ ║ ╔═══╝ ║ ╔═══╝ ╚═╗ ╔═╝      ║ ╔═══╝ ║ ╔═╗ ║ ║ ╔═╗ ║ ║ ╔═══╝
//  ║ ╚═╝ ║ ║ ╚═╝ ║ ║ ║ ║ ║     ║ ║ ║ ╚══╗  ║ ║       ║ ║        ║ ║     ║ ║ ║ ║ ║ ╚═╝ ║ ║ ╚══╗
//  ║ ╔═══╝ ║ ╔╗ ╔╝ ║ ║ ║ ║ ╔═╗ ║ ║ ║ ╔══╝  ║ ║       ║ ║        ║ ║     ║ ║ ║ ║ ║ ╔╗ ╔╝ ║ ╔══╝
//  ║ ║     ║ ║╚╗╚╗ ║ ╚═╝ ║ ║ ╚═╝ ║ ║ ╚═══╗ ║ ╚═══╗   ║ ║        ║ ╚═══╗ ║ ╚═╝ ║ ║ ║╚╗╚╗ ║ ╚═══╗
//  ╚═╝     ╚═╝ ╚═╝ ╚═════╝ ╚═════╝ ╚═════╝ ╚═════╝   ╚═╝        ╚═════╝ ╚═════╝ ╚═╝ ╚═╝ ╚═════╝
//  Easy to Use! Written in Love! Project Core by TheNote\RetroRolf\Rudolf2000\note3crafter

namespace TheNote\ShopSystem\events;

use pocketmine\block\utils\SignText;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;
use TheNote\core\CoreAPI;
use TheNote\core\listener\ScoreBoardListner;
use TheNote\ShopSystem\Main;

class EconomySell implements Listener
{
    private $sell;
    private $placeQueue;
    private Main $plugin;
    private $tap;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->placeQueue = [];
        $this->sell = (new Config($this->plugin->getDataFolder() . "Sell.yml", Config::YAML))->getAll();
    }

    public function onSignChange(SignChangeEvent $event): void
    {
        $api = new CoreAPI();
        $result = $this->tagExists($event->getNewText()->getLine(0));

        if ($result !== false) {
            $player = $event->getPlayer();
            if (!$player->hasPermission("economy.sell.create")) {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellNoPerm"));
                return;
            }
            $signText = $event->getNewText();
            $count = (int)$signText->getLine(2);
            $price = (int)$signText->getLine(1);
            $productData = $signText->getLine(3);
            $item = StringToItemParser::getInstance()->parse($productData) ?? LegacyStringToItemParser::getInstance()->parse($productData);

            if (!is_numeric($count) /*or $count <= 0*/) {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellAmountNumb"));
                return;
            }
            if (!is_numeric($price) /*or $price < 0*/) {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellPriceNumb"));
                return;
            }
            if ($item === null) {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellItemWrong"));
                return;
            }
            $block = $event->getBlock();
            $position = $block->getPosition();
            $this->sell[$position->getX() . ":" . $position->getY() . ":" . $position->getZ() . ":" . $player->getWorld()->getFolderName()] = array(
                "x" => $block->getPosition()->getX(),
                "y" => $block->getPosition()->getY(),
                "z" => $block->getPosition()->getZ(),
                "level" => $block->getPosition()->getWorld()->getFolderName(),
                "cost" => (int)$event->getNewText()->getLine(1),
                "itemName" => StringToItemParser::getInstance()->lookupAliases($item)[0],
                "amount" => (int)$event->getNewText()->getLine(2)
            );
            $cfg = new Config($this->plugin->getDataFolder() . "Sell.yml", Config::YAML);
            $cfg->setAll($this->sell);
            $cfg->save();
            $player->sendTip($api->getCommandPrefix("Money") . $this->plugin->getLang($player->getName(), "SellPlaceSucces"));
            $c = new Config($this->plugin->getDataFolder() . "Config.yml", Config::YAML);
            if ($c->get("GlowingSign") === true) {
                $event->setNewText(new SignText([
                    0 => $result[0],
                    1 => str_replace("{cost}", $price, $result[1]),
                    2 => str_replace("{amount}", $count, $result[2]),
                    3 => str_replace("{item}", $item->getName(), $result[3])
                ], null, true));
            } else {
                $event->setNewText(new SignText([
                    0 => $result[0],
                    1 => str_replace("{cost}", $price, $result[1]),
                    2 => str_replace("{amount}", $count, $result[2]),
                    3 => str_replace("{item}", $item->getName(), $result[3])
                ]));
            }
        }
    }

    public function onTouch(PlayerInteractEvent $event)
    {
        $api = new CoreAPI();
        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            return;
        }
        $block = $event->getBlock();
        $position = $block->getPosition();
        $loc = $position->getX() . ":" . $position->getY() . ":" . $position->getZ() . ":" . $event->getPlayer()->getWorld()->getFolderName();

        if (isset($this->sell[$loc])) {
            $sell = $this->sell[$loc];
            $player = $event->getPlayer();

            if ($player->getGamemode()->getEnglishName() === "Creative") {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellErrorCreative"));
                $event->cancel();
                return;
            }

            if (!$player->hasPermission("economy.sell.sell")) {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellNoPermSell"));
                $event->cancel();
                return;
            }
            $cnt = 0;

            foreach ($player->getInventory()->getContents() as $item) {
                if ($item->getName() == $sell["itemName"]) {
                    $cnt += $item->getCount();
                }
            }

            if (!isset($sell["itemName"])) {
                $item = StringToItemParser::getInstance()->parse($sell["itemName"]);
                $item->setCount($sell["amount"]);
                $player->getInventory()->addItem($item);
                if ($item === false) {
                    $item = StringToItemParser::getInstance()->parse($sell["itemName"]);
                } else {
                    $item = $item[0];
                }
                $this->sell[$loc]["itemName"] = $item;
                $sell["itemName"] = $item;
            }
            $now = microtime(true);
            if (!isset($this->tap[$player->getName()]) or $now - $this->tap[$player->getName()][1] >= 1.5 or $this->tap[$player->getName()][0] !== $loc) {
                $this->tap[$player->getName()] = [$loc, $now];
                $player->sendTip($api->getCommandPrefix("Money") . $this->plugin->getLang($player->getName(), "SellTabAgain"));
                return;
            } else {
                unset($this->tap[$player->getName()]);
            }
            if ($sell["itemName"] != StringToItemParser::getInstance()->lookupAliases($player->getInventory()->getItemInHand())[0]) {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellItemInHand"));
                return;
            }
            if ($player->getInventory()->getItemInHand()->getCount() >= $sell["amount"]) {
                $api->addMoney($player, $sell["cost"]);
                $item = StringToItemParser::getInstance()->parse($sell['itemName'])->setCount((int)$sell['amount']);
                $player->getInventory()->removeItem($item);
                $player->sendTip($api->getCommandPrefix("Money") . $this->plugin->getLang($player->getName(), "SellSucces"));
                $sb = new ScoreBoardListner();
                $sb->scoreboard();
            } else {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellSelledAll"));
            }
            $event->cancel();
            if ($event->getItem()->canBePlaced()) {
                $this->placeQueue [$player->getName()] = true;
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event)
    {
        $username = $event->getPlayer()->getName();
        if (isset($this->placeQueue [$username])) {
            $event->cancel();
            unset($this->placeQueue [$username]);
        }
    }

    public function onBreak(BlockBreakEvent $event)
    {
        $api = new CoreAPI();
        $block = $event->getBlock();
        if (isset($this->sell[$block->getPosition()->getX() . ":" . $block->getPosition()->getY() . ":" . $block->getPosition()->getZ() . ":" . $block->getPosition()->getWorld()->getFolderName()])) {
            $player = $event->getPlayer();
            if (!$player->hasPermission("economy.sell.remove")) {
                $player->sendTip($api->getCommandPrefix("Error") . $this->plugin->getLang($player->getName(), "SellNoPermDestroy"));
                $event->cancel();
                return;
            }
            $this->sell[$block->getPosition()->getX() . ":" . $block->getPosition()->getY() . ":" . $block->getPosition()->getZ() . ":" . $block->getPosition()->getWorld()->getFolderName()] = null;
            unset($this->sell[$block->getPosition()->getX() . ":" . $block->getPosition()->getY() . ":" . $block->getPosition()->getZ() . ":" . $block->getPosition()->getWorld()->getFolderName()]);
            $player->sendTip($api->getCommandPrefix("Money") . $this->plugin->getLang($player->getName(), "SellDestroySucces"));
            $cfg = new Config($this->plugin->getDataFolder() . "Sell.yml", Config::YAML);
            $cfg->setAll($this->sell);
            $cfg->save();
        }
    }

    public function tagExists($tag)
    {
        foreach ($this->plugin->sellSign->getAll() as $key => $val) {
            if ($tag == $key) {
                return $val;
            }
        }
        return false;
    }
}
