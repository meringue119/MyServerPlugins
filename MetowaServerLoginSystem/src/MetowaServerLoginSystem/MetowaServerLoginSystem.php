<?php

namespace MetowaServerLoginSystem;

use pocketmine\utils\{Config,TextFormat};
use pocketmine\command\{Command,CommandSender};
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerPreLoginEvent,PlayerJoinEvent,PlayerMoveEvent,PlayerCommandPreprocessEvent};
use pocketmine\event\block\{BlockBreakEvent,BlockPlaceEvent};
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\math\Vector3;

class MetowaServerLoginSystem extends PluginBase implements Listener{

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		$this->ip = new Config($this->getDataFolder()."IPBANList.yml",Config::YAML);
		$this->name = new Config($this->getDataFolder()."NAMEBANList.yml",Config::YAML);
		$this->ac = new Config($this->getDataFolder()."PlayersAccount.yml",Config::YAML);
		$this->all = new Config($this->getDataFolder()."AllBANList.yml",Config::YAML);
	}

	public function onLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		$ip = $this->ip;
		$na = $this->name;
		$add = $player->getAddress();
		$host = gethostbyaddr($add);
		$ac = $this->ac->get($name);
		$cid = $player->getClientId();
		if(!$this->ac->exists($name)){
			return true;
		}else{
		}
		if($player->getName() !== $ac["PROTECTEDNAMEWITHALL"]){
			$player->kick(TextFormat::RED."[MetowaServerLoginSystem] 名前が一致しません。",false);
			return true;
		}else{
		}
		if($ac["IP"] !== $host && $ac["CID"] !== $cid or $ac["IP"] !== $host && $player->getName() !== $ac["PROTECTEDNAMEWITHALL"] or $ac["CID"] !== $cid && $ac["PROTECTEDNAMEWITHALL"] !== $player->getName()){
			$player->kick(TextFormat::RED."[MetowaServerLoginSystem] 登録された情報と一致しませんでした。",false);
			return true;
		}else{
		}
		if($na->exists($name) || $ip->exists($name) || $na->exists($name) && $ip->exists($host) || $this->all->exists($name)){
			if($ip->exists($name)){
				if($ip->get($name) == $host){
					$type = "IPBAN";
					$reason = "";
				}
			}
			if($na->exists($name)){
				$type = "NAMEBAN";
				$reason = "理由: ".$na->get($name);
			}elseif($na->exists($name) && $ip->exists($host)){
				$type = "IP/NAMEBAN";
				$reason = "理由: ".$na->get($name);
			}elseif($this->all->exists($name)){
				$type = "ALLBAN";
				$reason = "理由: ".$this->all->get($name)["REASON"];
			}else{
				$type = TextFormat::RED."ERROR";
				$reason = TextFormat::RED."ERROR";
			}
			$player->kick(TextFormat::RED."[MetowaServerLoginSystem]　Your Banned.\nあなたは".$type."されており、サーバーへ接続ができません。\n".$reason,false);
			return true;
		}else{
			return true;
		}
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		if(!$this->ac->exists($name)){
			$player->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE,true);
			$player->sendMessage("[MetowaServerLoginSystem] ようこそ。MetowaServerへ。");
			$player->sendMessage("まずは /register <パスワード> で、アカウントを作成してください。");
			$this->logged[$name] = false;
			$this->register[$name] = true;
			return true;
		}
		$ac = $this->ac->get($name);
		$host = gethostbyaddr($player->getAddress());
		if($ac["IP"] == $host && $ac["NAME"] == $name or $ac["CID"] == $player->getClientId() && $ac["NAME"] == $name){
		$player->sendMessage(TextFormat::GREEN."[MetowaServerLoginSystem] ログイン認証に成功しました。");
		$this->logged[$name] = true;
	}else{
		$player->sendMessage(TextFormat::YELLOW."[MetowaServerLoginSystem] ログイン認証に失敗しました。 /login <Pass> でログインしてください。");
		$player->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE,true);
		$this->logged[$name] = false;
		$this->login[$name] = true;
		}
	}

	public function onBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		if($this->logged[$name] == false){
			$player->sendPopup(TextFormat::YELLOW."あなたはまだログインしていません。");
			$event->setCancelled();
		}
		return true;
	}

	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		if($this->logged[$name] == false){
			$player->sendPopup(TextFormat::YELLOW."あなたはまだログインしていません。");
			$event->setCancelled();
		}
		return true;
	}

	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		if($this->logged[$name] == false){
			$player->sendPopup(TextFormat::YELLOW."あなたはまだログインしていません。");
			$event->setCancelled();
		}
		return true;
	}

	public function onUseCmd(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		$cmd = $event->getMessage();
		$use = explode(" ", $cmd);
		if($this->logged[$name] == false){
			switch($use[0]){
				case "/register":
					return true;
					break;
				case "/login":
					return true;
					break;
				case "/passc":
					return true;
					break;
				case "delac":
					return true;
					break;
				default:
					$player->sendMessage(TextFormat::YELLOW."あなたはまだログインしていません。");
					$event->setCancelled();
					return true;
					break;
			}
		}
	}
	
	private function PasswordProtector($pass){
		/**このコードは非公開です。**/
	}
	
	private function ProtectedPasswordUnlocker($pass){
		/**このコードは非公開です。**/
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		$name = strtolower($sender->getName());
		switch($command->getName()){
			case "register":
				if(!$sender instanceof Player){
					$this->getLogger()->info("このコマンドはコンソールからは実行できません。");
					return true;
				}
				if(!empty($this->register[$name])){
				if($this->ac->exists($name)){
					$sender->sendMessage(TextFormat::YELLOW."あなたはすでにアカウントを作成しています。");
					return true;
				}
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."登録するパスワードを入力してください。");
					return true;
				}
				if($this->register[$name] == true){
					$pass = $args[0];
				if(preg_match("/[^a-zA-Z0-9]/",$pass)){
					$sender->sendMessage(TextFormat::YELLOW."登録可能なパスワードは、英数字のみです。");
					return true;
				}
				if(strlen($pass) < 6){
					$sender->sendMessage(TextFormat::YELLOW."パスワードは6文字以上に設定してください。");
					return true;
				}
				$aname = $sender->getName();
				$protectedpassword = $this->PasswordProtector($pass);
				$this->ac->set($name, [
					"IP" => gethostbyaddr($sender->getAddress()),
					"CID" => $sender->getClientId(),
					"NAME" => $name,
					"PROTECTEDNAMEWITHALL" => $aname,
					"PASSWORD" => $protectedpassword]);
				$this->ac->save();
				$this->logged[$name] = true;
				$this->register[$name] = null;
				$sender->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE,false);
				$sender->sendMessage(TextFormat::GREEN."アカウントを作成しました。 パスワード : <".TextFormat::YELLOW.$pass."§a>");
				return true;
				break;
			}
		}

			case "login":
				if(!$sender instanceof Player){
					$this->getLogger()->info("このコマンドはコンソールからは実行できません。");
					return true;
				}
				if($this->logged[$name] == true){
					$sender->sendMessage(TextFormat::GREEN."あなたはログイン済みです。");
					return true;
				}
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."パスワードを入力してください。");
					return true;
				}
				if($this->ac->get($name)["PASSWORD"] !== $args[0]){
					$sender->sendMessage(TextFormat::YELLOW."パスワードが一致しません。");
					return true;
				}
				$name = $sender->getName();
				if(!$this->ac->exists($name)){
					$sender->sendMessage(TextFormat::YELLOW."あなたはアカウントを作成していません。");
					return true;
				}
					$pass = $args[0];
				if(preg_match("/[^a-zA-Z0-9]/",$pass)){
					$sender->sendMessage(TextFormat::YELLOW."パスワードは、英数字のみです。");
					return true;
				}
				$ac = $this->ac->get($name);
				$unprotectedpassword = $this->ProtectedPasswordUnlocker($pass);
				$unprotectedpassword2 = $this->ProtectedPasswordUnlocker($ac["PASSWORD"]);
				if($unprotectedpassword == $unprotectedpassword2){
					$this->logged[$name] = true;
					$sender->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE,false);
					$sender->sendMessage(TextFormat::GREEN."ログイン認証に成功しました。");
					return true;
					break;
			}

			case "nban":
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."BANするプレイヤー名を入力してください。");
					return true;
				}
				if(!isset($args[1])){
					$reason = "未記入";
				}else{
					$reason = $args[1];
				}
				if(!$this->ac->exists($args[0])){
					$sender->sendMessage(TextFormat::YELLOW.$args[0]."は、サーバーに一度も参加していません。");
					return true;
				}
				if($this->name->exists($args[0])){
					$sender->sendMessage(TextFormat::YELLOW.$args[0]."は、既にBANされています。");
					return true;
				}
				$this->name->set($args[0], $reason);
				$this->name->save();
				$player = $this->getServer()->getPlayer($args[0]);
				if($player->isOnline()){
					$player->kick(TextFormat::RED."[MetowaServerLoginSystem] Your Banned.\nあなたはBANされました。 理由: ".$reason, false);
				}
				$sender->sendMessage(TextFormat::GREEN.$args[0]."をネームBANしました。");
				return true;
				break;

			case "iban":
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."IPBANするプレイヤー名を入力してください。");
					return true;
				}
				if(!isset($args[1])){
					$reason = "未記入";
				}else{
					$reason = $args[1];
				}
				if(!$this->ac->exists($args[0])){
					$sender->sendMessage(TextFormat::YELLOW.$args[0]."は、サーバーに一度も参加していません。");
					return true;
				}
				if($this->ip->exists($args[0])){
					$sender->sendMessage(TextFormat::YELLOW.$args[0]."は、既にIPBANされています。");
					return true;
				}
				$player = $this->getServer()->getPlayer($args[0]);
				$ip = gethostbyaddr($player->getAddress());
				$this->ip->set($args[0], $ip);
				$this->ip->save();
				if($player->isOnline()){
					$player->kick(TextFormat::RED."[MetowaServerLoginSystem] Your Banned.\nあなたはIPBANされました。 理由: ".$reason, false);
				}
				$sender->sendMessage(TextFormat::GREEN.$args[0]."をIPBANしました。");
				return true;
				break;

			case "allban":
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."IP/NAMEBANするプレイヤー名を入力してください。");
					return true;
				}
				if(!isset($args[1])){
					$reason = "未記入";
				}else{
					$reason = $args[1];
				}
				if(!$this->ac->exists($args[0])){
					$sender->sendMessage(TextFormat::YELLOW.$args[0]."は、サーバーに一度も参加していません。");
					return true;
				}
				if($this->all->exists($args[0])){
					$sender->sendMessage(TextFormat::YELLOW.$args[0]."は、既にBANされています。");
					return true;
				}
				$host = gethostbyaddr($player->getAddress());
				$cid = $player->getClientId();
				$name = $player->getName();
				$this->all->set($args[0], [
					"REASON" => $reason,
					"IP" => $host,
					"CLIENTID" => $cid,
					"NAME" => $name]);
				$this->all->save();
				$player = $this->getServer()->getPlayer($args[0]);
				if($player->isOnline()){
					$player->kick(TextFormat::RED."[MetowaServerLoginSystem] Your Banned.\nあなたはBANされました。 理由: ".$reason, false);
				}
				$sender->sendMessage(TextFormat::GREEN.$args[0]."をALLBANしました。");
				return true;
				break;

			case "unban":
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."BANを解除するプレイヤー名を入力してください。");
					return true;
				}
				if(!$this->name->exists($args[0])){
					$sender->sendMessage(TextFormat::YELLOW.$args[0]."は、BANされていません。");
					return true;
				}
				$this->name->remove($args[0]);
				$this->name->save();
				$sender->sendMessage(TextFormat::GREEN.$args[0]."のネームBANを解除しました。");
				return true;
				break;

			case "uiban":
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."IPBANを解除するプレイヤー名を入力してください。");
					return true;
				}
				if(!$this->ip->exists($args[0])){
					$sender->sendMessage(TextFormat::YELLOW.$args[0]."は、IPBANされていません。");
					return true;
				}
				$this->ip->remove($args[0]);
				$this->ip->save();
				$sender->sendMessage(TextFormat::GREEN.$args[0]."のIPBANを解除しました。");
				return true;
				break;

			case "passc":
				if(!$this->ac->exists($name)){
					$sender->sendMessage(TextFormat::YELLOW."まずアカウントを登録してください。");
					return true;
				}
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."現在のパスワードを入力してください。");
					return true;
				}
				if(!isset($args[1])){
					$sender->sendMessage(TextFormat::YELLOW."新しいパスワードを入力してください。");
					return true;
				}
				$ac = $this->ac->get($name);
				$unprotectedpassword = $this->ProtectedPasswordUnlocker($args[0]);
				$unprotectedpassword2 = $this->ProtectedPasswordUnlocker($ac["PASSWORD"]);
				if($unprotectedpassword !== $unprotectedpassword2){
					$sender->sendMessage(TextFormat::YELLOW."現在のパスワードが一致しません。");
					return true;
				}
				$add = $ac["IP"];
				$cid = $ac["CID"];
				$protectedpassword = $this->PasswordProtector($args[1]);
				$this->ac->set($name, [
					"IP" => $add,
					"CID" => $cid,
					"NAME" => $name,
					"PASSWORD" => $protectedpassword]);
				$this->ac->save();
				$sender->sendMessage(TextFormat::GREEN."アカウントのパスワードを変更しました。 新しいパスワード:<".$args[1].">");
				return true;
				break;

			case "delac":
			if(!$sender instanceof Player){
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."アカウントを削除するプレイヤー名を入力してください。");
					return true;
				}
				$terget = $args[0];
				if(!$this->ac->exists($terget)){
					$sender->sendMessage(TextFormat::YELLOW.$terget."は、サーバーに一度も参加していません。");
					return true;
				}
				$this->ac->remove($terget);
				$this->ac->save();
				$player = $this->getServer()->getPlayer($terget);
				if($player->isOnline()){
					$player->kick(TextFormat::RED."      [MetowaServerLoginSystem]\n    あなたのアカウントは強制削除されました。",false);
				}
			}
				if(!$this->ac->exists($name)){
					$sender->sendMessage(TextFormat::YELLOW."まずアカウントを登録してください。");
					return true;
				}
				if(!isset($args[0])){
					$sender->sendMessage(TextFormat::YELLOW."パスワードを入力してください。");
					return true;
				}
				$ac = $this->ac->get($name);
				$unprotectedpassword = $this->ProtectedPasswordUnlocker($args[0]);
				$unprotectedpassword2 = $this->ProtectedPasswordUnlocker($ac["PASSWORD"]);
				if($unprotectedpassword !== $unprotectedpassword2){
					$sender->sendMessage(TextFormat::YELLOW."パスワードが一致しません。");
					return true;
				}
				$this->ac->remove($name);
				$this->ac->save();
				$sender->kick(TextFormat::GREEN."       [MetowaServerLoginSystem]\n          アカウントを削除しました。",false);
				return true;
				break;

			case "hub":
				if(!$sender instanceof Player){
					$this->getLogger()->notice("このコマンドはコンソールからは実行できません。");
					return true;
				}
				$this->getServer()->loadLevel("world");
				$sender->teleport(new Position(302, 68, 324, $this->getServer()->getLevelByName("world")));
				$sender->sendMessage(TextFormat::GREEN."[MetowaTP] リスポーン地点にテレポートしました。");
				return true;
				break;
		}
	}
}
