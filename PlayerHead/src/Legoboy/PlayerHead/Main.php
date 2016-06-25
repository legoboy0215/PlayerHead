<?php

namespace Legoboy\PlayerHead;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\Player;

use pocketmine\item\Item;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\utils\TextFormat;

use pocketmine\utils\Config;

use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener{
	
	public function onEnable(){
		if(!(EconomyAPI::getInstance() instanceof EconomyAPI)){
			$this->getLogger()->critical('EconomyAPI is not installed!');
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if(strtolower($command->getName()) === 'head'){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED . 'Please execute this command as a player.');
				return true;
			}
			if(strtolower($args[0]) === 'sell'){
				$head = Item::get(397, 3, 1);
				$inv = $sender->getInventory();
				$sold = 0;
				foreach($inv->getContents() as $i){
					if($i->equals($head, true, false)){
						$count = $i->getCount();
						$sender->getInventory()->removeItem($i);
						$sold += $count;
					}
				}
				$earned = $sold * $this->getConfig()->get('heads-value', 100);
				EconomyAPI::getInstance()->addMoney($sender, $earned, true, 'PLUGIN');
				$sender->sendMessage(TextFormat::AQUA . "You sold $sold heads and earned $$earned!");
				return true;
			}
			if(!$sender->isOp()){
				$sender->sendMessage(TextFormat::RED . "You are not authorized to run this command!");
				return true;
			}
			if($this->getConfig()->get("heads-active", true) === true){
				$this->getConfig()->set("heads-active", false);
				$this->getConfig()->save();
				$sender->sendMessage(TextFormat::RED . 'You have disabled head-dropping!');
			}else{
				$this->getConfig()->set("heads-active", true);
				$this->getConfig()->save();
				$sender->sendMessage(TextFormat::GREEN . 'You have enabled head-dropping!');
			}
			return true;
		}
	}
	
	public function onDeath(PlayerDeathEvent $event){
		if($this->getConfig()->get('heads-active', true) === true){
			$entity = $event->getEntity();
			$cause = $entity->getLastDamageCause();
			if($cause instanceof EntityDamageByEntityEvent){
				$killer = $cause->getDamager();
				if(!($killer instanceof Player)) return;
				$head = Item::get(397, 3, 1);
				$head->setCustomName($entity->getName() . "'s head");
				$killer->getInventory()->addItem($head);
			}
		}
	
	}
}
