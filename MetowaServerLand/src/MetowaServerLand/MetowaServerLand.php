<?php
/*
* __  __       _                             __    ___    ___   _______
*|  \/  | ___ | |_  ___   _    _  ____  _   |  |  / _ \  / _ \ |___   /
*| |\/| |/ _ \| __|/ _ \ | |  | |/  _ \/ /  |  | |_// / |_// /    /  /
*| |  | |  __/| |_| (_) || |__| || (_)   |  |  |   / /_   / /_   /  /
*|_|  |_|\___| \__|\___/ |__/\__||____/\_\  |__|  /____| /____| /__/
*
*All this program is made by hand of metowa 1227.
*I certify here that all authorities are in metowa 1227.
*Expiration date of certification: unlimited
*Secondary distribution etc are prohibited.
*The update is also done by the developer.
*This plugin is a developer API plugin to make it easier to write code.
*When using this plug-in, be sure to specify it somewhere.
*Warning if violation is confirmed.
*
*Developer: metowa 1227
*Development Team: metowa 1227 Plugin Development Team (Members: metowa 1227 only)
*/

namespace MetowaServerLand;

use pocketmine\math\Vector3;
use pocketmine\plugin\{PluginBase,MethodEventExecutor};
use pocketmine\event\{Listener,Event};
use pocketmine\event\block\{BlockPlaceEvent,BlockBreakEvent,SignChangeEvent};
use pocketmine\event\player\{PlayerInteractEvent,PlayerJoinEvent};
use pocketmine\utils\{Config,TextFormat,UUID};
use pocketmine\command\{Command,CommandSender};
use pocketmine\Player;
use pocketmine\level\{Position,Level};
use pocketmine\event\{EventPriority,TextContainer,TranslationContainer};
use pocketmine\network\protocol\{AddEntityPacket,RemoveEntityPacket};
use pocketmine\command\data\CommandParameter;
use pocketmine\entity\{Item,Entity};
use metowa1227\MoneySystemAPI\MoneySystemAPI;
use pocketmine\item\Item as Block;

class MetowaServerLand extends PluginBase implements Listener{

	public function onEnable(){
		$this->s = $this->getServer();
		$this->s->getPluginManager()->registerEvents($this, $this);
    	if(!file_exists($this->getDataFolder())) mkdir($this->getDataFolder(), 0755, true);
		$this->land = new Config($this->getDataFolder()."Lands.yml",Config::YAML);
		$this->invite = new Config($this->getDataFolder()."Inviters.yml",Config::YAML);
		$this->num = new Config($this->getDataFolder()."LandNumber.yml",Config::YAML);
		$this->entry = new Config($this->getDataFolder()."EntrySelectLand.yml",Config::YAML);
	}

	public function onBreak(BlockBreakEvent $event){
	    $player = $event->getPlayer();
	    $name = $player->getName();
	    $level = $player->getLevel()->getFolderName();
	    $block = $event->getBlock();
	    $x = $block->getX();
	    $z = $block->getZ();
	    if($player->getInventory()->getItemInHand()->getID() == 293){
	    	if($name !== "metowa1227"){
	    		$player->sendMessage(TextFormat::YELLOW."権限がありません。");
	    		$event->setCancelled();
	    		return true;
	    	}
	    	$reset = $this->num->get("EntryNumber") - 1;
	    	if(isset($this->start[$this->num->get("EntryNumber")]) || isset($this->start[$reset])){
	    		$player->sendMessage(TextFormat::YELLOW."最初の処理を完了してください。 土地範囲情報をリセットしました。");
	    		if(isset($this->start[$this->num->get("EntryNumber")])) unset($this->start[$this->num->get("EntryNumber")]);
	    		if(isset($this->start[$reset])) unset($this->start[$reset]);
	    		$event->setCancelled();
	    		return true;
	    	}
	    	$this->start[$this->num->get("EntryNumber")] = array("x" => $x, "z" => $z, "level" => $level);
	    	$player->sendMessage(TextFormat::GREEN."最初の位置を設定しました。");
	    	$event->setCancelled();
	    	return true;
	    }
	    if($level == "world" and !$player->isOp()){$event->setCancelled();return true;}
	    foreach($this->land->getAll() as $land){
	        if($level == $land["Level"] and $land["StartX"] <= $x and $land["EndX"] >= $x and $land["StartZ"] <= $z and $land["EndZ"] >= $z){
	        	if($player->getName() == $land["Owner"] or $this->invite->exists($land["ID"][$name]) or $player->isOp()){
	        		return true;
		}else{
	        	$player->sendPopup(TextFormat::RED.TextFormat::BOLD."ここはあなたの土地ではありません。 所有者: ".$land["Owner"]." 土地番号 #".$land["ID"]."§r");
	        	$event->setCancelled();
	        	return true;
	        }
	  	}else{
		    }
		}
	    if($player->isOp()){
	    	return true;
	    }else{
	    if($level == "resources" || $level == "BonusArea"){
	        return true;
	    }else{
	        $player->sendPopup(TextFormat::YELLOW.TextFormat::BOLD."この土地を編集するには、土地を購入してください。§r");
	        $event->setCancelled();
	        return true;
	    }
	        return true;
		}
		return true;
	}

	public function Touch(PlayerInteractEvent $event){
		$money = MoneySystemAPI::getInstance();
	    $player = $event->getPlayer();
	    $name = $player->getName();
	    $level = $player->getLevel()->getFolderName();
	    $x = $event->getBlock()->getX();
	    $z = $event->getBlock()->getZ();
	    if($player->getInventory()->getItemInHand()->getID() == 293 && $event->getBlock()->getID() !== Block::AIR){
	    	if($name !== "metowa1227"){
	    		$player->sendMessage(TextFormat::YELLOW."権限がありません。");
	    		$event->setCancelled();
	    		return true;
	    	}
	    $entrynum = $this->num->get("EntryNumber");
	    $this->end[$entrynum] = array("x" => $x, "z" => $z, "level" => $level);
	    $player->sendMessage(TextFormat::GREEN."2つめの位置を設定しました。");
	    $event->setCancelled();
	    return true;
	    }
        if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68){
            $sign = $event->getPlayer()->getLevel()->getTile($event->getBlock());
            if(!($sign instanceof Sign)){
                return true;
            }
            $sign = $sign->getText();
            if($sign[0] == TextFormat::AQUA."[SELLINGLAND]"){
            	$price = ltrim($sign[1], TextFormat::GREEN."[PRICE] ");
            	if($money->Check($player) < $price){
            		$player->sendMessage(TextFormat::YELLOW."所持金が足りません。");
            		return true;
            	}
            	if(!isset($this->buiing[$name])){
            		$player->sendMessage(TextFormat::GREEN."土地を".$money.$price."で購入します。\n購入する場合はもう一度看板をタップしてください。");
            		$this->buiing[$name] = true;
            	}else{
            		$num = $this->num->get("LandNumber");
            		$entrynumber = ltrim($sign[2], TextFormat::WHITE."[ENTRYNUMBERSEC] ");
            		$entry = $this->entry->get();
            		$this->land->set($num, [
            			"Owner" => $name,
            			"Price" => $price,
            			"StartX" => $entry["StartX"],
            			"StartZ" => $entry["StartZ"],
            			"EndX" => $entry["EndZ"],
            			"EndZ" => $entry["EndZ"],
            			"Level" => $entry["Level"]]);
            		$this->land->save();
            		$money->TakeMoney($player, $price);
				    $levelobject = $player->getLevel();
				    $bx = $event->getBlock()->getX();
				    $bz = $event->getBlock()->getZ();
				    $by = $event->getBlock()->getY();
				    $level->setBlock(new Vector3($bx, $by, $bz), Block::AIR, false, true);
            		$player->sendMessage(TextFormat::GREEN."土地を購入しました。");
            		$this->num->set("LandNumber", $this->num->get("LandNumber")+1);
            		$this->num->save();
            		return true;
            	}
	    if($event instanceof PlayerInteractEvent){$block = $event->getBlock()->getSide($event->getFace());}else{$block = $event->getBlock();}
	    $x = $block->getX();
	    $z = $block->getZ();
	    if($level == "world" and !$player->isOp()){$event->setCancelled();return true;}
	    foreach($this->land->getAll() as $land){
	        if($level == $land["Level"] and $land["StartX"] <= $x and $land["EndX"] >= $x and $land["StartZ"] <= $z and $land["EndZ"] >= $z){
	    	    if($player->getName() == $land["Owner"] or $this->invite->exists($land["ID"][$name]) or $player->isOp()){
	        		return true;
		}else{
	        	$player->sendPopup(TextFormat::RED.TextFormat::BOLD."ここはあなたの土地ではありません。 所有者: ".$land["Owner"]." 土地番号 #".$land["ID"]."§r");
	        	$event->setCancelled();
	        	return true;
	        }
	  	}else{
		    }
		}
	    if($player->isOp()){
	    	return true;
	    }else{
	    if($level == "resources" || $level == "BonusArea"){
	        return true;
	    }else{
	        $player->sendPopup(TextFormat::YELLOW.TextFormat::BOLD."この土地を編集するには、土地を購入してください。§r");
	        $event->setCancelled();
	        return true;
	    }
	        return true;
		}
		}
	}
}

	public function Place(BlockPlaceEvent $event){
	    $player = $event->getPlayer();
	    $name = $player->getName();
	    $level = $player->getLevel()->getFolderName();
	    $block = $event->getBlock();
	    $x = $block->getX();
	    $z = $block->getZ();
	    if($level == "world" and !$player->isOp()){$event->setCancelled();return true;}
	    foreach($this->land->getAll() as $land){
	        if($level == $land["Level"] and $land["StartX"] <= $x and $land["EndX"] >= $x and $land["StartZ"] <= $z and $land["EndZ"] >= $z){
	        	if($player->getName() == $land["Owner"] or $this->invite->exists($land["ID"][$name]) or $player->isOp()){
	        		return true;
		}else{
	        	$player->sendPopup(TextFormat::RED.TextFormat::BOLD."ここはあなたの土地ではありません。 所有者: ".$land["Owner"]." 土地番号 #".$land["ID"]."§r");
	        	$event->setCancelled();
	        	return true;
	        }
	  	}else{
		    }
		}
	    if($player->isOp()){
	    	return true;
	    }else{
	    if($level == "resources" || $level == "BonusArea"){
	        return true;
	    }else{
	        $player->sendPopup(TextFormat::YELLOW.TextFormat::BOLD."この土地を編集するには、土地を購入してください。§r");
	        $event->setCancelled();
	        return true;
	    }
	        return true;
		}
	}

	public function CreatedByBuySignForLand(SignChangeEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$x = $block->getX();
		$z = $block->getZ();
		$level = $block->getLevel()->getFolderName();
		$text = $event->getLine(0);
		if($text == "land" or $text == TextFormat::AQUA."[SELLINGLAND]"){
		if(!$player->isOp()){$player->sendMessage(TextFormat::YELLOW."あなたは土地購入看板を設置する権限がありません。");$event->setCancelled();return;}
			$text2 = $event->getLine(1);
			if(!isset($text2) || !is_numeric($text2)){$player->sendMessage(TextFormat::YELLOW."正式な記入法でお試しください。");$event->setCancelled();return;}
				foreach($this->entry->getAll() as $land){
			        if($level == $land["Level"] and $land["StartX"] <= $x and $land["EndX"] >= $x and $land["StartZ"] <= $z and $land["EndZ"] >= $z){
						$event->setLine(0, TextFormat::RED."[ENTRY LAND IS NOT FOUND]");
						$player->sendMessage(TextFormat::RED."この場所はエントリーされていません。");
						return true;
					}else{
						$event->setLine(0, TextFormat::AQUA."[SELLINGLAND]");
						$event->setLine(1, TextFormat::GREEN."[PRICE] ".$text2);
						$event->setLine(2, TextFormat::WHITE."[ENTRYNUMBERSEC] ".$land["EntryNumber"]);
						$event->setLine(3, TextFormat::GREEN."Tap to buy!");
						$player->sendMessage(TextFormat::GREEN."土地を販売しました。");
						return;
				}
			}
		}
	}

	public function CreateAbountEntryPositionOfLand(array $start, array $end, int $entrynum) : bool{
		if($start["level"] == $end["level"]){
			$level = $start["level"];
		}else{
			return false;
		}
	    $this->entry->set($entrynum, [
	    	"EntryNumber" => $entrynum,
	    	"StartX" => $start["x"],
	    	"StartZ" => $start["z"],
	    	"EndX" => $end["x"],
	    	"EndZ" => $end["z"],
	    	"Level" => $level]);
	    $this->entry->save();
	    return true;
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case "invite":
				$result = !isset($args[0]) || !isset($args[1]) ?  false : true;
				if(empty($result)) return false;
				if(!$this->land->exists($args[0])){$sender->sendMessage(TextFormat::YELLOW."#".$args[0]."は存在しません。"); return true;}
				$now = $this->invite->get($args[0]);
				if($this->invite->exists($now[$args[1]])){$sender->sendMessage(TextFormat::YELLOW.$args[1]."は既に追加されています。"); return true;}
				try{
					if($this->land->get($args[0]["Owner"] !== $sender->getName())){
						throw new \Exception(TextFormat::YELLOW."#".$args[0]."はあなたの土地ではありません。");
					}
				}catch(\Exception $error){
					$sender->sendMessage($error);
					return true;
				}
				$set = $now[$args[0]] = true;
				$this->invite->set($args[0], $set);
				$this->invite->save();
				$sender->sendMessage(TextFormat::GREEN.$args[1]."と土地共有しました。");
				return true;
				break;

			case "unvite":
				$result = !isset($args[0]) || !isset($args[1]) ?  false : true;
				if(empty($result)) return false;
				if(!$this->land->exists($args[0])){$sender->sendMessage(TextFormat::YELLOW."#".$args[0]."は存在しません。"); return true;}
				$now = $this->invite->get($args[0]);
				if(!$this->invite->exists($now[$args[1]])){$sender->sendMessage(TextFormat::YELLOW.$args[1]."は土地共有されていません。"); return true;}
				try{
					if($this->land->get($args[0]["Owner"] !== $sender->getName())){
						throw new \Exception(TextFormat::YELLOW."#".$args[0]."はあなたの土地ではありません。");
					}
				}catch(\Exception $error){
					$sender->sendMessage($error);
					return true;
				}
				$set = $now[$args[0]] = true;
				$this->invite->remove($args[0], $set);
				$this->invite->save();
				$sender->sendMessage(TextFormat::GREEN.$args[1]."の土地共有解除しました。");
				return true;
				break;

			case "here":
				if(!$sender instanceof Player){$this->getLogger()->notice("このコマンドはコンソールからは実行できません。"); return true;}
		        $x = floor($sender->x);
		        $z = floor($sender->z);
		        $level = $sender->getLevel()->getFolderName();
		        foreach($this->land->getAll() as $land){
		         	if($level == $land["Level"] and $land["StartX"] <= $x and $land["EndX"] >= $x and $land["StartZ"] <= $z and $land["EndZ"] >= $z){
		               	$sender->sendMessage("情報 || LandNumber(ID): ".$land["ID"]." Owner: ".$land["Owner"]." Price: ".$land["Price"]);
		                return true;
		            }
		        }
		        $sender->sendMessage("この土地は誰も所有していません。");
		        return true;
	            break;

	        case "sold":
	        	if(!isset($args[0])) return false;
				if(!$this->land->exists($args[0])){$sender->sendMessage(TextFormat::YELLOW."#".$args[0]."は存在しません。"); return true;}
				try{
					if($this->land->get($args[0]["Owner"] !== $sender->getName())){
						throw new \Exception(TextFormat::YELLOW."#".$args[0]."はあなたの土地ではありません。");
					}
				}catch(\Exception $error){
					$sender->sendMessage($error);
					return true;
				}
				$this->land->remove($args[0]);
				$this->land->save();
				$land = $this->land->get($args[0]);
				$price = $land["Price"] / 2;
				MoneySystemAPI::getInstance()->AddMoney($sender, $price);
				$sender->sendMessage(TextFormat::GREEN.$args[0]."の土地を売却しました。");
				return true;
				break;

			case "entry":
				if(!$sender instanceof Player){
					$this->getLogger()->notice("このコマンドはコンソールからは実行できません。");
					return true;
				}
				if(!$sender->isOp()){
					$sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを実行する権限がありません。");
					return true;
				}
				$entrynum = $this->num->get("EntryNumber");
				if(!isset($this->start[$entrynum]) or !isset($this->end[$entrynum])){
					$sender->sendMessage(TextFormat::YELLOW."まずは範囲を設定してください。");
					return true;
				}
				$result = $this->CreateAbountEntryPositionOfLand($this->start[$entrynum], $this->end[$entrynum], $entrynum);
				$after = $this->num->get("EntryNumber") + 1;
				$this->num->set("EntryNumber", $after);
				$this->num->save();
				if($result){
					$sender->sendMessage(TextFormat::GREEN."土地を登録しました。");
				}else{
					$sender->sendMessage(TextFormat::YELLOW."指定された土地のワールドが異なります。");
				}
				return true;
				break;
			}
		}
	}