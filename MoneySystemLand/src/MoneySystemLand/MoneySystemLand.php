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

namespace MoneySystemLand;

use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\Event;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\event\EventPriority;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\event\TextContainer;
use pocketmine\event\TranslationContainer;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\command\data\CommandParameter;
use pocketmine\utils\TextFormat;
use pocketmine\entity\Item;
use pocketmine\entity\Entity;
use pocketmine\utils\UUID;

class MoneySystemLand extends PluginBase implements Listener{

  /*  THIS_PLUGIN_NAME  */
  /**
  * @var MoneySystemLand
  */

  const PLUGIN_NAME = "MoneySystemLand";

  const ECONOMY_NAME = "MoneySystem";

  /*  PLUGIN_VERSION  */
  /**
   * @var Plugin version
   */

  const PLUGIN_VERSION = "1.0.0";

  const PACKAGE_VERSION = "1.0.0 beta";

  /**
  * @var author metowa1227
  */

  private $author = "metowa1227";

  const PLUGIN_AUTHOR = "metowa1227";

  const DEFAULT_CMD = "land";

  /**
  * @var Load plugin database files.
  */

  /**
  **@return $usage, $description
  **/

  protected $description = [];

  protected $usage = [];

  private $unit = "$";

	public function onEnable(){
    $this->getLogger()->notice(self::PLUGIN_NAME." is Loading... please wait...");
    if(!file_exists($this->getDataFolder())){
    mkdir($this->getDataFolder(), 0755, true); 
    }
    $this->saveDefaultConfig();
    $this->saveResource("setting.yml", false);
    $this->land = new Config($this->getDataFolder() . "Land.yml", Config::YAML);
    $this->set = new Config($this->getDataFolder() . "setting.yml", Config::YAML);
    $this->schedule = new Config($this->getDataFolder() . "SendScheduler.data", Config::YAML);
    $this->road = new Config($this->getDataFolder() . "RoadBuilder.data", Config::YAML);
    $this->data = new Config($this->getDataFolder() . "Road.yml", Config::YAML);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
    if(file_exists($this->getDataFolder()."Land.yml") == FALSE){
    $this->getServer()->getPluginManager()->disablePlugin($this);
    $this->getLogger()->warning(TextFormat::YELLOW."データファイルの読み込みに失敗しました。");
    }
    if(!is_numeric($this->set->get("land.limit"))){
      $this->getLogger()->error("Setting.ymlのland.limitの値が異常です。 数字に書き換えてください。");
      $this->getServer()->shutdown();
    }
    $this->sys = $this->getServer()->getPluginManager()->getPlugin("MoneySystemAPI");
    if($this->sys == null){
      $this->getLogger()->error("MoneySystemが導入されていません。");
      $this->getServer()->getPluginManager()->disablePlugin($this);
    }
    $this->getLogger()->notice("§a[Sucsess] MoneySystemLand を起動しました。 製作者: ".self::PLUGIN_AUTHOR);
    }

    public function onDisable(){
      $this->set->save();
      $this->land->save();
      $this->schedule->save();
      $this->getLogger()->info(self::PLUGIN_NAME."をシャットダウンしました。");
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
      switch($command->getName()){
        case "buildroadstart":
          if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::YELLOW."このコマンドはコンソールからは実行できません。");
            return true;
          }
          if($this->road->exists($sender->getName()) || $sender->getName() == "metowa1227"){
          }else{
            $sender->sendMessage(TextFormat::YELLOW."あなたは道路建設者ではありません。");
            return true;
          }
          if($sender->getLevel()->getFolderName() == "resources" || $sender->getLevel()->getFolderName() == "world"){
            $sender->sendMessage(TextFormat::YELLOW."生活ワールド以外での道路の建設は、サーバーオーナー(鯖主)でないとできません。");
            return true;
          }
          $x = (int) $sender->x;
          $z = (int) $sender->z;
          $level = $sender->getLevel()->getFolderName();
          $this->rstart[$sender->getName()] = array("x" => $x, "z" => $z, "level" => $level);
          $sender->sendMessage(TextFormat::GREEN."最初の位置を設定しました。");
          return true;
          break;

        case "buildroadend":
          if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::YELLOW."このコマンドはコンソールからは実行できません。");
            return true;
          }
          if($this->road->exists($sender->getName()) || $sender->getName() == "metowa1227"){
          }else{
            $sender->sendMessage(TextFormat::YELLOW."あなたは道路建設者ではありません。");
            return true;
          }
          if($sender->getLevel()->getFolderName() == "resources" || $sender->getLevel()->getFolderName() == "world"){
            $sender->sendMessage(TextFormat::YELLOW."生活ワールド以外での道路の建設は、サーバーオーナー(鯖主)でないとできません。");
            return true;
          }
          if($sender->getLevel()->getFolderName() !== $this->rstart[$sender->getName()]["level"]){
            $sender->sendMessage(TextFormat::YELLOW."道路の設定ワールドが最初と異なります。");
            return true;
          }
          $x = (int) $sender->x;
          $z = (int) $sender->z;
          $level = $sender->getLevel()->getFolderName();
          $startX = $this->rstart[$sender->getName()]["x"];
          $startZ = $this->rstart[$sender->getName()]["z"];
          $endX = (int) $sender->x;
          $endZ = (int) $sender->z;
          $num = $this->set->get("RoadID");
          $this->data->set($num, [
            "StartX" => (int) $startX,
            "StartZ" => (int) $startZ,
            "EndX" => (int) $endX,
            "EndZ" => (int) $endZ,
            "Level" => $level,
            ]);
          $this->data->save();
          $roadid = $num + 1;
          $this->set->set("RoadID", $roadid);
          $this->set->save();
          $sender->sendMessage(TextFormat::GREEN."[MoneySystemLand / MSRoadSystem] 建設した道路を登録しました。");
          return true;
          break;

        case "setroadbuilder":
          if($sender instanceof Player){
            $sender->sendMessage(TextFormat::YELLOW."このコマンドはコンソールからのみ実行可能です。");
            return true;
      }else{
            if(!isset($args[0])){
              $this->getLogger()->notice("追加するプレイヤー名を入力してください。");
              return true;
            }
            if($this->road->exists($args[0])){
              $this->getLogger()->info(TextFormat::YELLOW.$args[0]."は、既に登録されています。");
              return true;
            }
            $player = $this->getServer()->getPlayer($args[0]);
            if($player->isOnline() == NULL){
              $this->road->set($args[0], "not");
              $this->road->save();
              $this->getLogger()->info($args[0]."を道路建設者リストに追加しました。");
              return true;
            }
              $player->sendMessage(TextFormat::GREEN."貴方は道路建設者に任命されました。");
              $this->road->set($args[0], "okt");
              $this->road->save();
              $this->getLogger()->info($args[0]."を道路建設者リストに追加しました。");
              return true;
              break;
          }

        case "removeroadbuilder":
          if($sender instanceof Player){
            $sender->sendMessage(TextFormat::YELLOW."このコマンドはコンソールからのみ実行可能です。");
            return true;
      }else{
            if(!isset($args[0])){
              $this->getLogger()->notice("削除するプレイヤー名を入力してください。");
              return true;
            }
            if(!$this->road->exists($args[0])){
              $this->getLogger()->info(TextFormat::YELLOW.$args[0]."は、登録されていません。");
              return true;
            }
            $player = $this->getServer()->getPlayer($args[0]);
              if($player->isOnline() == NULL){
              $this->road->remove($args[0], "remove");
              $this->road->save();
              $this->getLogger()->info($args[0]."を道路建設者リストから除外しました。");
              return true;
            }
              $player->sendMessage(TextFormat::GREEN."貴方は道路建設者から除外されました。");
              $this->road->remove($args[0]);
              $this->road->save();
              $this->getLogger()->info($args[0]."を道路建設者リストから削除しました。");
              return true;
              break;
          }

        case "startp":
        if(!$sender instanceof Player){
          $this->getLogger()->notice("このコマンドはコンソールからは実行できません。");
          return true;
      }else{
        if($sender->getLevel()->getFolderName() == "resources" || $sender->getLevel()->getFolderName() == "BonusArea"){
        if($sender->getName() == "metowa1227"){
        }else{
          $sender->sendMessage(TextFormat::YELLOW."このワールドでは土地を購入できません。");
          return true;
        }
      }
        if(isset($this->start[$sender->getName()])){
          $sender->sendMessage(TextFormat::YELLOW."まず、最初の土地の購入手続きを完了してください。");
          return true;
      }else{
        $ret = [];
        foreach($this->land->getAll() as $land){
          if($land["Owner"] === $sender->getName()){
            $ret[] = $land;
          }
        }
        if(count($ret) >= $this->set->get("land.limit")){
          $sender->sendMessage(TextFormat::YELLOW."これ以上土地を購入することができません。");
          return true;
      }else{
        $x = (int) $sender->x;
        $z = (int) $sender->z;
        $y = (int) $sender->y;
        $level = $sender->getLevel()->getFolderName();
        $this->start[$sender->getName()] = array("x" => $x, "z" => $z, "sy" => $y, "level" => $level);
        $sender->sendMessage(TextFormat::GREEN."最初の位置を設定しました。");
        return true;
        break;
        }
      }
    }

        case "endp":
        if(!$sender instanceof Player){
          $this->getLogger()->notice("このコマンドはコンソールからは実行できません。");
          return true;
      }else{
        if(!isset($this->start[$sender->getName()])){
          $sender->sendMessage(TextFormat::YELLOW."最初の位置を設定してください。");
          return true;
      }else{
        if($sender->getLevel()->getFolderName() !== $this->start[$sender->getName()]["level"]){
          $sender->sendMessage(TextFormat::YELLOW."最初の設定位置と違うワールドでは設定できません。");
          return true;
      }else{
        $x = (int) $sender->x;
        $this->y = (int) $sender->y;
        $z = (int) $sender->z;
        $level = $sender->getLevel()->getFolderName();
        $this->end[$sender->getName()] = array("x" => $x, "z" => $z, "level" => $level);
        $startX = $this->start[$sender->getName()]["x"];
        $startZ = $this->start[$sender->getName()]["z"];
        $endX = (int) $sender->x;
        $endZ = (int) $sender->z;
        $this->end[$sender->getName()] = array(
          "x" => $endX,
          "z" => $endZ
        );
        if($startX > $endX){
          $temp = $endX;
          $endX = $startX;
          $startX = $temp;
        }
        if($startZ > $endZ){
          $temp = $endZ;
          $endZ = $startZ;
          $startZ = $temp;
        }
        $startX--;
        $endX++;
        $startZ--;
        $endZ++;
        $price = (($endX - $startX) - 1) * (($endZ - $startZ) - 1) * $this->set->get("1Block.Price");
        $sender->sendMessage(TextFormat::GREEN."購入価格は ".$this->unit.$price." です。 /land buy で購入手続きを完了します。");
        return true;
        break;
        }
      }
    }

        case "buystop":
        if(!isset($this->start[$sender->getName()]) or !isset($this->end[$sender->getName()])){
          $sender->sendMessage(TextFormat::YELLOW."まだ購入手続きを開始していません。");
          return true;
      }else{
        if(isset($this->start[$sender->getName()])){
          unset($this->start[$sender->getName()]);
        }
        if(isset($this->end[$sender->getName()])){
          unset($this->end[$sender->getName()]);
        }
        $sender->sendMessage(TextFormat::GREEN."購入手続きを停止しました。");
        return true;
        break;
      }

        case "land":
        if(!isset($args[0])){
        $mycmd = self::DEFAULT_CMD." ";
        $description = [];
        $usage = [];
        $result = [];
          $list = strtolower($mycmd."list");
          $description[0] = "サーバーにある土地を表示します。";
          $usage[0] = "/land list <page>";
          $tp = strtolower($mycmd."tp");
          $description[1] = "土地を移動します";
          $usage[1] = "/land tp <LandNumber>";
          $help = strtolower($mycmd."help");
          $description[2] = "プラグインのヘルプ";
          $usage[2] = "/land help";
          $sell = strtolower($mycmd."sell");
          $description[3] = "土地を売却します。";
          $usage[3] = "/land sell <LandNumber>";
          $give = strtolower($mycmd."give");
          $description[4] = "土地を譲渡します。";
          $usage[4] = "/land give <LandNumber>";
          $mark = strtolower($mycmd."mark");
          $description[5] = "土地の範囲をマークアップします。";
          $usage[5] = "/land mark <LandNumber>";
          $unmark = strtolower($mycmd."unmark");
          $description[6] = "マークアップを解除します。";
          $usage[6] = "/unmark <LandNumber>";
          $info = strtolower($mycmd."info");
          $description[7] = "プレイヤーの土地数などの情報を確認します。";
          $usage[7] = "/land info <player>";
          $here = strtolower($mycmd."here");
          $description[8] = "土地の情報を確認します。";
          $usage[8] = "/land here";
          $invite = strtolower($mycmd."invite");
          $description[9] = "土地共有をします。";
          $usage[9] = "/land invite <LandNumber> <player>";
          $invitee = strtolower($mycmd."invitee");
          $description[10] = "土地共有リストを取得します。";
          $usage[10] = "/land invitee <LandNumber>";
          $unvite = strtolower($mycmd."unvite");
          $description[11] = "土地共有を解除します。";
          $stop = strtolower($mycmd."buystop");
          $description[12] = "土地の購入手続きをキャンセルします。";
          $usage[12] = "/buystop";
          $start[0] = strtolower("startp");
          $start[1] = "最初の位置を設定します。";
          $start[2] = "/startp";
          $end[0] = strtolower("endp");
          $end[1] = "次の位置を設定します。";
          $end[2] = "/endp";
          $usage[11] = "/land unvite <LandNumber> <player>";
          $result[0] = $this->encorder($list);
          $result[1] = $this->encorder($tp);
          $result[2] = $this->encorder($help);
          $result[3] = $this->encorder($sell);
          $result[4] = $this->encorder($give);
          $result[5] = $this->encorder($mark);
          $result[6] = $this->encorder($unmark);
          $result[7] = $this->encorder($info);
          $result[8] = $this->encorder($here);
          $result[9] = $this->encorder($invite);
          $result[10] = $this->encorder($invitee);
          $result[11] = $this->encorder($unvite);
          $result[12] = $this->encorder($stop);
          $result[13] = $this->encorder($start[0]);
          $result[14] = $this->encorder($end[0]);
          $sender->sendMessage(TextFormat::BOLD.TextFormat::AQUA.">>>>[MoneySystemLand HELP]<<<<");
          $sender->sendMessage($result[13].$start[1]." 使用法: ".$start[2]);
          $sender->sendMessage($result[14].$end[1]." 使用法: ".$end[2]);
          $sender->sendMessage($result[12].$description[12]." 使用法: ".$usage[12]);
          $sender->sendMessage("/land: ".TextFormat::GRAY."<args[0]::type> <args[1]::string> <args[2]::array:id:data>");
          $sender->sendMessage($result[0].$description[0]." 使用法: ".$usage[0]);
          $sender->sendMessage($result[1].$description[1]." 使用法: ".$usage[1]);
          $sender->sendMessage($result[2].$description[2]." 使用法: ".$usage[2]);
          $sender->sendMessage($result[3].$description[3]." 使用法: ".$usage[3]);
          $sender->sendMessage($result[4].$description[4]." 使用法: ".$usage[4]);
          $sender->sendMessage($result[5].$description[5]." 使用法: ".$usage[5]);
          $sender->sendMessage($result[6].$description[6]." 使用法: ".$usage[6]);
          $sender->sendMessage($result[7].$description[7]." 使用法: ".$usage[7]);
          $sender->sendMessage($result[8].$description[8]." 使用法: ".$usage[8]);
          $sender->sendMessage(TextFormat::BOLD.TextFormat::AQUA.">>>>>>>[Powered by MoneySystem]");
          return true;
          break;
        }
      }

        switch($args[0]){
        case "help":
        $mycmd = self::DEFAULT_CMD." ";
        $description = [];
        $usage = [];
        $result = [];
          $list = strtolower($mycmd."list");
          $description[0] = "サーバーにある土地を表示します。";
          $usage[0] = "/land list <page>";
          $tp = strtolower($mycmd."tp");
          $description[1] = "土地を移動します";
          $usage[1] = "/land tp <LandNumber>";
          $help = strtolower($mycmd."help");
          $description[2] = "プラグインのヘルプ";
          $usage[2] = "/land help";
          $sell = strtolower($mycmd."sell");
          $description[3] = "土地を売却します。";
          $usage[3] = "/land sell <LandNumber>";
          $give = strtolower($mycmd."give");
          $description[4] = "土地を譲渡します。";
          $usage[4] = "/land give <LandNumber>";
          $mark = strtolower($mycmd."mark");
          $description[5] = "土地の範囲をマークアップします。";
          $usage[5] = "/land mark <LandNumber>";
          $unmark = strtolower($mycmd."unmark");
          $description[6] = "マークアップを解除します。";
          $usage[6] = "/unmark <LandNumber>";
          $info = strtolower($mycmd."info");
          $description[7] = "プレイヤーの土地数などの情報を確認します。";
          $usage[7] = "/land info <player>";
          $here = strtolower($mycmd."here");
          $description[8] = "土地の情報を確認します。";
          $usage[8] = "/land here";
          $invite = strtolower($mycmd."invite");
          $description[9] = "土地共有をします。";
          $usage[9] = "/land invite <LandNumber> <player>";
          $invitee = strtolower($mycmd."invitee");
          $description[10] = "土地共有リストを取得します。";
          $usage[10] = "/land invitee <LandNumber>";
          $unvite = strtolower($mycmd."unvite");
          $description[11] = "土地共有を解除します。";
          $stop = strtolower($mycmd."buystop");
          $description[12] = "土地の購入手続きをキャンセルします。";
          $usage[12] = "/buystop";
          $start[0] = strtolower("startp");
          $start[1] = "最初の位置を設定します。";
          $start[2] = "/startp";
          $end[0] = strtolower("endp");
          $end[1] = "次の位置を設定します。";
          $end[2] = "/endp";
          $usage[11] = "/land unvite <LandNumber> <player>";
          $result[0] = $this->encorder($list);
          $result[1] = $this->encorder($tp);
          $result[2] = $this->encorder($help);
          $result[3] = $this->encorder($sell);
          $result[4] = $this->encorder($give);
          $result[5] = $this->encorder($mark);
          $result[6] = $this->encorder($unmark);
          $result[7] = $this->encorder($info);
          $result[8] = $this->encorder($here);
          $result[9] = $this->encorder($invite);
          $result[10] = $this->encorder($invitee);
          $result[11] = $this->encorder($unvite);
          $result[12] = $this->encorder($stop);
          $result[13] = $this->encorder($start[0]);
          $result[14] = $this->encorder($end[0]);
          $sender->sendMessage(TextFormat::BOLD.TextFormat::AQUA.">>>>[MoneySystemLand HELP]<<<<");
          $sender->sendMessage($result[13].$start[1]." 使用法: ".$start[2]);
          $sender->sendMessage($result[14].$end[1]." 使用法: ".$end[2]);
          $sender->sendMessage($result[12].$description[12]." 使用法: ".$usage[12]);
          $sender->sendMessage("/land: ".TextFormat::GRAY."<args[0]::type> <args[1]::string> <args[2]::array:id:data>");
          $sender->sendMessage($result[0].$description[0]." 使用法: ".$usage[0]);
          $sender->sendMessage($result[1].$description[1]." 使用法: ".$usage[1]);
          $sender->sendMessage($result[2].$description[2]." 使用法: ".$usage[2]);
          $sender->sendMessage($result[3].$description[3]." 使用法: ".$usage[3]);
          $sender->sendMessage($result[4].$description[4]." 使用法: ".$usage[4]);
          $sender->sendMessage($result[5].$description[5]." 使用法: ".$usage[5]);
          $sender->sendMessage($result[6].$description[6]." 使用法: ".$usage[6]);
          $sender->sendMessage($result[7].$description[7]." 使用法: ".$usage[7]);
          $sender->sendMessage($result[8].$description[8]." 使用法: ".$usage[8]);
          $sender->sendMessage(TextFormat::BOLD.TextFormat::AQUA.">>>>>>>[Powered by MoneySystem]");
          return true;
          break;

        case "buy":
        if(!$sender->hasPermission("default.command.buy")){
          $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
          return true;
      }else{
        if(!$sender instanceof Player){
          $sender->sendMessage("このコマンドはコンソールからは実行できません。");
          return true;
      }else{
        if(in_array($sender->getLevel()->getFolderName(), $this->set->get("disable.levels"))){
          $sender->sendMessage(TextFormat::YELLOW."このワールドでは土地を購入できません。");
          return true;
      }else{
        if(!isset($this->start[$sender->getName()])){
          $sender->sendMessage(TextFormat::YELLOW."最初の位置を設定してください。");
          return true;
      }elseif(!isset($this->end[$sender->getName()])){
          $sender->sendMessage(TextFormat::YELLOW."最後の値を設定してください。");
          return true;
      }
        $start = $this->start[$sender->getName()];
        $endp = $this->end[$sender->getName()];
        $startX = (int) $start["x"];
        $endX = (int) $endp["x"];
        $startZ = (int) $start["z"];
        $endZ = (int) $endp["z"];
        $startY = (int) $start["sy"];
        $endY = (int) $this->y;
        if($startX > $endX){
          $backup = $startX;
          $startX = $endX;
          $endX = $backup;
        }
        if($startZ > $endZ){
          $backup = $startZ;
          $startZ = $endZ;
          $endZ = $backup;
        }
        $level = $sender->getLevel()->getFolderName();
        $levels = $start["level"];
        $x = $start["x"];
        $z = $start["z"];
        foreach($this->land->getAll() as $land){
          if((($land["StartX"] <= $startX and $land["EndX"] >= $startX) or ($land["StartX"] <= $endX and $land["EndX"] >= $endX)) and (($land["StartZ"] <=$startZ and $land["EndZ"] >= $startZ and $level === $land["Level"]) or ($land["EndZ"] <= $endZ and $land["EndZ"] >= $endZ))){
            $sender->sendMessage(TextFormat::YELLOW."この土地は ".$land["Owner"]." が所有しています。");
            return true;
      }else{
      }
    }
        $price = ((($endX + 1) - ($startX - 1)) - 1) * ((($endZ + 1) - ($startZ - 1)) - 1) * $this->set->get("1Block.Price");
        $money = $this->sys->Check($sender);
        if($money < $price){
          $sender->sendMessage(TextFormat::YELLOW."所持金が足りません。");
          return true;
        }
        $this->sys->TakeMoney($sender, $price);
        $num = $this->set->get("LandID");
        $sender->sendMessage(TextFormat::GREEN."土地を ".$this->unit.$price." で購入しました。 土地番号 #".$num."　が振り分けられました。");
        $special = 0;
          $this->land->set($num, [
            "ID" => (int) $num,
            "Owner" => $sender->getName(),
            "StartX" => (int) $startX,
            "StartY" => (int) $startY,
            "StartZ" => (int) $startZ,
            "EndX" => (int) $endX,
            "EndY" => (int) $endY,
            "EndZ" => (int) $endZ,
            "Price" => (int) $price,
            "Level" => $level,
            "Special" => $special,
            ]);
          $this->land->save();
          $afterid = $num + 1;
          $this->set->set("LandID", $afterid);
          $this->set->save();
        unset($this->start[$sender->getName()], $this->end[$sender->getName()]);
        return true;
        break;
      }
    }
  }


        case "list":
        $page = isset($args[1]) ? (int) $args[1] : 1;
        $land = $this->land->getAll();
        $output = "";
        $max = ceil(count($land) / 5);
        $pro = 1;
        $page = (int) $page;
        $output1 = TextFormat::BOLD.TextFormat::AQUA."===[Land List Page ".$page."/".$max."]===";
        $current = 1;
        foreach($land as $l){
          $cur = (int) ceil($current / 5);
          if($cur > $page)
            continue;
          if($pro == 6)
            break;
          if($page === $cur){
            $output .= "Owner:".TextFormat::AQUA.$l["Owner"].TextFormat::RESET." | ID:".$l["ID"]."\n";
            $pro++;
          }
          $current++;
        }
        $sender->sendMessage($output1);
        $sender->sendMessage($output);
        return true;
        break;

        case "tp":
        if(!$sender->hasPermission("default.command.tp")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
        if(!isset($args[1])){
          $sender->sendMessage(TextFormat::YELLOW."テレポート先の土地番号(ID)を入力してください。");
          return true;
        }else{
          if(!$sender instanceof Player){
            $this->getLogger()->notice("このコマンドはコンソールからは実行できません。");
            return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."テレポート先の土地番号(ID)を入力してください。");
            return true;
        }else{
          $num = $args[1];
          if(!is_numeric($num)){
            $sender->sendMessage(TextFormat::YELLOW."正式なID(LandNumber | IDは数字形式)を入力してください。");
            return true;
        }else{
          if($num > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."MoneySystemLandが保存可能な土地数(ID)を超えています。");
            return true;
        }else{
          $land = $this->land->get($num);
          if(!$this->land->exists($num)){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num."は存在しない土地です。");
            return true;
        }else{
          $x = $land["StartX"];
          $z = $land["StartZ"];
          $newX = (int) $x + (($land["EndX"] - $x) / 2);
          $newZ = (int) $z + (($land["EndZ"] - $z) / 2);
          $level = $this->getServer()->getLevelByName($land["Level"]);
          $cnt = 0;
          for($y = 1;; $y++){
            if($level->getBlock(new Vector3($newX, $y, $newZ))->getID() === 0){
              break;
            }
          if($cnt === 5){
            break;
          }
          if($y > 255){
            ++$cnt;
            ++$newX;
            --$newZ;
            $y = 1;
            continue;
          }
        }
          $newY = $y;
          $vector = new Vector3($newX, $newY, $newZ, $level);
          $sender->teleport($vector);
          $sender->sendMessage(TextFormat::GREEN."土地番号 #".$num." に移動しました。");
          return true;
          break;
        }
        }
        }
        }
        }
        }
        }

        case "sell":
        if(!$sender->hasPermission("default.command.sell")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
        if(!isset($args[1])){
          $sender->sendMessage(TextFormat::YELLOW."売却する土地番号(ID)を入力してください。");
          return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."売却する土地番号(ID)を入力してください。");
            return true;
        }else{
          $num = $args[1];
          if(!is_numeric($num)){
            $sender->sendMessage(TextFormat::YELLOW."正式なID(LandNumber | IDは数字形式)を入力してください。");
            return true;
        }else{
          if($num > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."MoneySystemLandが保存可能な土地数(ID)を超えています。");
            return true;
        }else{
          $land = $this->land->get($num);
          if(!$this->land->exists($num)){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num."は存在しない土地です。");
            return true;
        }else{
          if($land["Owner"] !== $sender->getName() and !$sender->isOp()){
            $sender->sendMessage(TextFormat::YELLOW."土地番号　#".$num." はあなたの土地ではありません。");
            return true;
        }else{
          $price = $land["Price"];
          $this->land->remove($num);
          $this->land->save();
          $this->sys->AddMoney($sender, ($price / 2));
          $sender->sendMessage(TextFormat::GREEN."土地番号 #".$num."　の土地を売却しました。 所持金が ".$this->unit.($price / 2)." 増えました。");
          return true;
          break;
          }
          }
          }
          }
          }
          }
          }

        case "give":
        if(!$sender->hasPermission("default.command.give")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
          if(!isset($args[1])){
          $sender->sendMessage(TextFormat::YELLOW."譲渡する土地番号(ID)を入力してください。");
          return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."譲渡する土地番号(ID)を入力してください。");
            return true;
        }else{
          if(!isset($args[2])){
            $sender->sendMessage(TextFormat::YELLOW."土地を譲渡する相手を入力して下さい。");
            return true;
        }else{
          if($args[2] > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."そのプレイヤーは存在しません。");
            return true;
        }else{
          $mut = $this->getServer()->getDataPath() .'players/' . strtolower($args[2] .'.dat');
          if(!file_exists($mut)){
            $sender->sendMessage(TextFormat::YELLOW."そのプレイヤーは存在しません。(過去に参加していない)");
            return true;
        }else{
          $num = $args[1];
          if(!is_numeric($num)){
            $sender->sendMessage(TextFormat::YELLOW."正式なID(LandNumber | IDは数字形式)を入力してください。");
            return true;
        }else{
          if($num > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."MoneySystemLandが保存可能な土地数(ID)を超えています。");
            return true;
        }else{
          $land = $this->land->get($num);
          if(!$this->land->exists($num)){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num."は存在しない土地です。");
            return true;
        }else{
          if($land["Owner"] !== $sender->getName() and !$sender->isOp()){
            $sender->sendMessage(TextFormat::YELLOW."土地番号　#".$num." はあなたの土地ではありません。");
            return true;
        }else{
          $owner = $land["Owner"];
          $sx = $land["StartX"];
          $sy = $land["StartY"];
          $sz = $land["StartZ"];
          $ex = $land["EndX"];
          $ey = $land["EndY"];
          $ez = $land["EndZ"];
          $price = $land["Price"];
          $level = $land["Level"];
          $special = 0;
          $this->land->set($num, [
            "ID" => (int) $num,
            "Owner" => $args[2],
            "StartX" => (int) $sx,
            "StartY" => (int) $sy,
            "StartZ" => (int) $sz,
            "EndX" => (int) $ex,
            "EndY" => (int) $ey,
            "EndZ" => (int) $ez,
            "Price" => (int) $price,
            "Level" => $level,
            "Special" => $special,
            ]);
          $this->land->save();
          $sender->sendMessage(TextFormat::GREEN."土地番号 #".$num." を ".$args[2]."　に譲渡しました。");
          $target = $this->getServer()->getPlayer($args[2]);
          $x = $sx + (($sx - $ex) / 2);
          $z = $sz + (($sz - $ez) / 2); 
          if($target instanceof Player){
          if($target->isOnline() !== NULL){
            $target->sendMessage(TextFormat::GREEN."土地番号 #".$num." の土地を ".$sender->getName()." から受け取りました。");
            $target->sendMessage("info: X:".$x." Z:".$z." 価格:".$price);
            return true;
        }else{
          $this->schedule->set($target->getName(), [
          "had" => $sender->getName(),
          "LandNumber" => $num,
          "Price" => $price,
          ]);
          $this->schedule->save();
          break;
        }
        }
        }
        }
        }
        }
        }
        }
        }
        }
        }
        }

        case "mark":
        if(!$sender instanceof Player){
          $this->getLogger()->notice("このコマンドはコンソールからは実行できません。");
          return true;
        }else{
        if(!$sender->hasPermission("default.command.mark")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
          if(!isset($args[1])){
          $sender->sendMessage(TextFormat::YELLOW."マークアップする土地番号(ID)を入力してください。");
          return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."マークアップする土地番号(ID)を入力してください。");
            return true;
        }else{
          $num = $args[1];
          if(!is_numeric($num)){
            $sender->sendMessage(TextFormat::YELLOW."正式なID(LandNumber | IDは数字形式)を入力してください。");
            return true;
        }else{
          if($num > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."MoneySystemLandが保存可能な土地数(ID)を超えています。");
            return true;
        }else{
          $land = $this->land->get($num);
          if(!$this->land->exists($num)){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num."は存在しない土地です。");
            return true;
        }else{
          $sx = $land["StartX"];
          $sy = $land["StartY"];
          $sz = $land["StartZ"];
          $pk = new AddEntityPacket();
          $eid = 100;
          $new = $eid + $num;
          $set = $new;
          $pk->eid = $set;
          $username = TextFormat::GREEN."◆ LandNumber: ".TextFormat::WHITE.$num.TextFormat::GREEN." ◆";
          $pk->type = Item::NETWORK_ID;
          $pk->x = $sx;
          $pk->y = $sy;
          $pk->z = $sz;
          $pk->yaw = 0;
          $pk->pitch = 0;
          $pk->item = 0;
          $pk->meta = 0;
          $flags = 0;
          @$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
          @$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
          @$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
          @$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
          $pk->metadata = [
          Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
          Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $username],
          Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG,-1]
          ];
          $sender->dataPacket($pk);

          $ex = $land["EndX"];
          $ey = $land["StartY"];
          $ez = $land["EndZ"];
          $pk = new AddEntityPacket();
          $eid = 100;
          $new = $eid + $num;
          $set = $new + 1;
          $pk->eid = $set;
          $username = TextFormat::GREEN."◆ LandNumber: ".TextFormat::WHITE.$num.TextFormat::GREEN." ◆";
          $pk->type = Item::NETWORK_ID;
          $pk->x = $sx;
          $pk->y = $sy;
          $pk->z = $sz;
          $pk->yaw = 0;
          $pk->pitch = 0;
          $pk->item = 0;
          $pk->meta = 0;
          $flags = 0;
          @$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
          @$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
          @$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
          @$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
          $pk->metadata = [
          Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
          Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $username],
          Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG,-1]
          ];
          $sender->dataPacket($pk);

          $sx = $land["StartX"];
          $sy = $land["StartY"];
          $sz = $land["StartZ"];
          $x = ((($sx + 1) - ($sx - 1)) - 1);
          $y = $sy;
          $z = ((($sz + 1) - ($sz - 1)) - 1);
          $pk = new AddEntityPacket();
          $eid = 100;
          $new = $eid + $num;
          $set = $new + 2;
          $pk->eid = $set;
          $username = TextFormat::GREEN."◆ LandNumber: ".TextFormat::WHITE.$num.TextFormat::GREEN." ◆";
          $pk->type = Item::NETWORK_ID;
          $pk->x = $sx;
          $pk->y = $sy;
          $pk->z = $sz;
          $pk->yaw = 0;
          $pk->pitch = 0;
          $pk->item = 0;
          $pk->meta = 0;
          $flags = 0;
          @$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
          @$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
          @$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
          @$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
          $pk->metadata = [
          Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
          Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $username],
          Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG,-1]
          ];
          $sender->dataPacket($pk);

          $ex = $land["EndX"];
          $ey = $land["StartY"];
          $ez = $land["EndZ"];
          $x = ((($ex + 1) - ($ex - 1)) - 1);
          $y = $ey;
          $z = ((($ez + 1) - ($ez - 1)) - 1);
          $pk = new AddEntityPacket();
          $eid = 100;
          $new = $eid + $num;
          $set = $new + 3;
          $pk->eid = $set;
          $username = TextFormat::GREEN."◆ LandNumber: ".TextFormat::WHITE.$num.TextFormat::GREEN." ◆";
          $pk->type = Item::NETWORK_ID;
          $pk->x = $sx;
          $pk->y = $sy;
          $pk->z = $sz;
          $pk->yaw = 0;
          $pk->pitch = 0;
          $pk->item = 0;
          $pk->meta = 0;
          $flags = 0;
          @$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
          @$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
          @$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
          @$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
          $pk->metadata = [
          Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
          Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $username],
          Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG,-1]
          ];
          $sender->dataPacket($pk);
          $sender->sendMessage(TextFormat::GREEN."土地番号 #".$num." をマークアップしました。");
          $this->marking[$sender->getName()] = true;
          return true;
          break;
          }
          }
          }
          }
          }
          }
          }

        case "unmark":
        if(!$sender instanceof Player){
          $this->getLogger()->notice("このコマンドはコンソールからは実行できません。");
          return true;
        }else{
        if(!$sender->hasPermission("default.command.unmark")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
          if(!isset($args[1])){
          $sender->sendMessage(TextFormat::YELLOW."マークアップを解除する土地番号(ID)を入力してください。");
          return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."マークアップを解除する土地番号(ID)を入力してください。");
            return true;
        }else{
          $num = $args[1];
          if(!is_numeric($num)){
            $sender->sendMessage(TextFormat::YELLOW."正式なID(LandNumber | IDは数字形式)を入力してください。");
            return true;
        }else{
          if($num > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."MoneySystemLandが保存可能な土地数(ID)を超えています。");
            return true;
        }else{
          $land = $this->land->get($num);
          if(!$this->land->exists($num)){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num."は存在しない土地です。");
            return true;
        }else{
          if(empty($this->marking[$sender->getName()])){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num." は現在マークアップされていません。");
            return true;
        }else{
          $pk = new RemoveEntityPacket();
          $eid = 100;
          $new = $eid + $num;
          $set = $new;
          $pk->eid = $set;
          $sender->dataPacket($pk);

          $pk = new RemoveEntityPacket();
          $eid = 100;
          $new = $eid + $num;
          $set = $new + 1;
          $pk->eid = $set;
          $sender->dataPacket($pk);

          $pk = new RemoveEntityPacket();
          $eid = 100;
          $new = $eid + $num;
          $set = $new + 2;
          $pk->eid = $set;
          $sender->dataPacket($pk);

          $pk = new RemoveEntityPacket();
          $eid = 100;
          $new = $eid + $num;
          $set = $new + 3;
          $pk->eid = $set;
          $sender->dataPacket($pk);

          $sender->sendMessage(TextFormat::GREEN."マークアップを解除しました。");
          $this->marking[$sender->getName()] = null;
          return true;
          break;
        }
        }
        }
        }
        }
        }
        }
        }

          case "info":
          if(!$sender->isOp()){
            $sender->sendMessage(TextFormat::RED."このコマンドを実行する権限がありません。");
            return true;
        }else{
          if(!$sender->hasPermission("default.command.info")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
          if(!isset($args[1])){
            $sender->sendMessage(TextFormat::YELLOW."詳細を確認するプレイヤー名を入力してください。");
            return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."詳細を確認するプレイヤー名を入力してください。");
            return true;
        }else{
          $target = $this->getServer()->getPlayer($args[1]);
          if($target instanceof Player){
          $mut = $this->getServer()->getDataPath() .'players/' . strtolower($args[1] .'.dat');
          if(!file_exists($mut)){
            $sender->sendMessage(TextFormat::YELLOW."そのプレイヤーは存在しません。(過去に参加していない)");
            return true;
        }else{
            foreach($this->land->getAll() as $land => $value){
              if($value["Owner"] == $target->getName()){
                if($value["ID"] === $land){
                  $land = $this->land->getAll();
                  $output = "";
                  $id = $value["ID"];
                  $outputx = "";
                  $output1 = "";
                  $output1 = TextFormat::BOLD.TextFormat::AQUA."・======[".$args[1]."'s Land info]======・";
                  $output .= "所有土地数: ".count($value["ID"]);
                  $outputx .= "ID表: ".$id;
                  $sender->sendMessage($output1);
                  $sender->sendMessage($output);
                  $sender->sendMessage($outputx);
                  return true;
                  break;
        }
        }
        }
        }
        }
        }
        }
        }
        }

          case "here":
          if(!$sender instanceof Player){
            $this->getLogger()->notice("このコマンドはコンソールからは実行できません。");
            return true;
        }else{
          if(!$sender->hasPermission("default.command.here")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
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
        }
    }

/**
 * @var Invite機能は現在開発中です。
 */

/*
          case "invite":
        if(!$sender->hasPermission("default.command.invite")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
          if(!isset($args[1])){
          $sender->sendMessage(TextFormat::YELLOW."土地共有する土地番号(ID)を入力してください。");
          return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."土地共有する土地番号(ID)を入力してください。");
            return true;
        }else{
          if(!isset($args[2])){
            $sender->sendMessage(TextFormat::YELLOW."土地共有する相手を入力して下さい。");
            return true;
        }else{
          if($args[2] > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."そのプレイヤーは存在しません。");
            return true;
        }else{
          $mut = $this->getServer()->getDataPath() .'players/' . strtolower($args[2] .'.dat');
          if(!file_exists($mut)){
            $sender->sendMessage(TextFormat::YELLOW."そのプレイヤーは存在しません。(過去に参加していない)");
            return true;
        }else{
          $num = $args[1];
          if(!is_numeric($num)){
            $sender->sendMessage(TextFormat::YELLOW."正式なID(LandNumber | IDは数字形式)を入力してください。");
            return true;
        }else{
          if($num > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."MoneySystemLandが保存可能な土地数(ID)を超えています。");
            return true;
        }else{
          $land = $this->land->get($num);
          if(!$this->land->exists($num)){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num."は存在しない土地です。");
            return true;
        }else{
          if($land["Owner"] !== $sender->getName() and !$sender->isOp()){
            $sender->sendMessage(TextFormat::YELLOW."土地番号　#".$num." はあなたの土地ではありません。");
            return true;
        }else{
          if($land["Inviters"] === $args[2]){
            $sender->sendMessage(TextFormat::YELLOW."すでに土地共有をしています。");
            return true;
        }else{
          $sx = $land["StartX"];
          $sz = $land["StartZ"];
          $ex = $land["EndX"];
          $ez = $land["EndZ"];
          $owner = $land["Owner"];
          $price = $land["Price"];
          $invite = $land["Inviters"];
          $level = $land["Level"];
          $special = $land["Special"];
          $this->land->set($num, [
            "ID" => (int) $num,
            "Owner" => $owner,
            "StartX" => (int) $sx,
            "StartZ" => (int) $sz,
            "EndX" => (int) $ex,
            "EndZ" => (int) $ez,
            "Price" => (int) $price,
            "Inviters" => [
            $invite => true,
            $args[2] => true,
            ],
            "Level" => $level,
            "Special" => $special,
            ]);
          $this->land->save();
          $sender->sendMessage(TextFormat::GREEN."土地番号 #".$num." に ".$args[2]." を招待しました。");
          return true;
          break;
        }
        }
        }
        }
        }
        }
        }
        }
        }
        }
        }

          case "invitee":
        if(!$sender->hasPermission("default.command.invitee")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
          if(!isset($args[1])){
          $sender->sendMessage(TextFormat::YELLOW."土地共有リストを取得する土地番号(ID)を入力してください。");
          return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."土地共有リストを取得する土地番号(ID)を入力してください。");
            return true;
        }else{
          $num = $args[1];
          if(!is_numeric($num)){
            $sender->sendMessage(TextFormat::YELLOW."正式なID(LandNumber | IDは数字形式)を入力してください。");
            return true;
        }else{
          if($num > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."MoneySystemLandが保存可能な土地数(ID)を超えています。");
            return true;
        }else{
          $land = $this->land->get($num);
          if(!$this->land->exists($num)){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num."は存在しない土地です。");
            return true;
        }else{
          if($land["Inviters"] !== $sender->getName() and !$sender->isOp()){
            $sender->sendMessage(TextFormat::YELLOW."土地番号　#".$num." にあなたは招待されていません。");
            return true;
        }else{
          $invitee = $land["Inviters"];
          $output = "";
          $output .= implode(", ", $invitee);
          $sender->sendMessage("土地共有リスト: ".$output);
          return true;
          break;
        }
        }
        }
        }
        }
        }
        }

          case "unvite":
        if(!$sender->hasPermission("default.command.unvite")){
            $sender->sendMessage(TextFormat::YELLOW."あなたはこのコマンドを使用する権限がありません。");
            return true;
        }else{
          if(!isset($args[1])){
          $sender->sendMessage(TextFormat::YELLOW."土地共有を解除する土地番号(ID)を入力してください。");
          return true;
        }else{
          if($args[1] == ""){
            $sender->sendMessage(TextFormat::YELLOW."土地共有を解除する土地番号(ID)を入力してください。");
            return true;
        }else{
          $num = $args[1];
          if(!is_numeric($num)){
            $sender->sendMessage(TextFormat::YELLOW."正式なID(LandNumber | IDは数字形式)を入力してください。");
            return true;
        }else{
          if($num > PHP_INT_MAX){
            $sender->sendMessage(TextFormat::YELLOW."MoneySystemLandが保存可能な土地数(ID)を超えています。");
            return true;
        }else{
          $land = $this->land->get($num);
          if(!$this->land->exists($num)){
            $sender->sendMessage(TextFormat::YELLOW."土地番号 #".$num."は存在しない土地です。");
            return true;
        }else{
          if($land["Owner"] !== $sender->getName() and !$sender->isOp()){
            $sender->sendMessage(TextFormat::YELLOW."土地番号　#".$num." はあなたの土地ではありません。");
            return true;
        }else{
          if($land["Inviters"] !== $sender->getName() and !$sender->isOp()){
            $sender->sendMessage(TextFormat::YELLOW."土地番号　#".$num." にあなたは招待されていません。");
            return true;
        }else{
          if($args[2] === $land["Owner"]){
            $sender->sendMessage(TextFormat::YELLOW."土地所有者を解除することはできません。");
            return true;
        }else{
          $invite = [];
          $owner = $land["Owner"];
          $sx = $land["StartX"];
          $sz = $land["StartZ"];
          $ex = $land["EndX"];
          $ez = $land["EndZ"];
          $price = $land["Price"];
          $invite = $land["Inviters"];
          $level = $land["Level"];
          $special = $land["Special"];
          $afterinvite = ltrim($invite, $args[2]);
          $this->land->set($num, [
            "ID" => (int) $num,
            "Owner" => $owner,
            "StartX" => (int) $sx,
            "StartZ" => (int) $sz,
            "EndX" => (int) $ex,
            "EndZ" => (int) $ez,
            "Price" => (int) $price,
            "Inviters" => $afterinvite,
            "Level" => $level,
            "Special" => $special,
            ]);
          $sender->sendMessage(TextFormat::GREEN."土地共有リストから" .$args[2]." を削除しました。");
          return true;
          break;
        }
        }
        }
        }
        }
        }
        }
        }
        }

*/

        default:
        $sender->sendMessage("/land help を参照してください。");
        return true;
        break;
        }
      }

  private function encorder($target){
    if($target == null || empty($target) == true or strlen($target) > PHP_INT_MAX){
      return false;
    }else{
      $result = "/".$target.": ";
      return $result;
      }
    }

  public function onJoin(PlayerJoinEvent $event){
    $player = $event->getPlayer();
    $name = $player->getName();
    if($this->schedule->exists($name)){
      $had = $this->schedule->get($name);
      $player->sendMessage("[".self::PLUGIN_NAME."] ".$had["had"]." さんから土地を引き取りました。 土地番号 #".$had["LandNumber"]." 販売価格 ".$this->unit.$had["Price"].TextFormat::GREEN." /land tp ".$num.TextFormat::RESET." で移動できます。");
      }
    if($this->road->exists($name)){
      $road = $this->road->get($name);
      if($road == "not"){
        $player->sendMessage(TextFormat::GREEN."貴方は道路建設者に任命されました。");
        $this->road->set($name, "okt");
        $this->road->save();
        return true;
      }
      if($road == "remove"){
        $player->sendMessage(TextFormat::YELLOW."貴方は道路建設者から除外されました。");
        $this->road->remove($name);
        $this->road->save();
        return true;
      }
    }
    }

  public function onTouch(PlayerInteractEvent $event){//StartFunction
    $player = $event->getPlayer();
    $name = $player->getName();
    $level = $player->getLevel()->getFolderName();
    if($event instanceof PlayerInteractEvent){
      $block = $event->getBlock()->getSide($event->getFace());
    }else{
      $block = $event->getBlock();
    }
    $x = $block->getX();
    $z = $block->getZ();
    if($level == "world" and !$player->isOp()){
      $event->setCancelled();
      return true;
    }
    foreach($this->land->getAll() as $land){//StartForeach
      if($level == $land["Level"] and $land["StartX"] <= $x and $land["EndX"] >= $x and $land["StartZ"] <= $z and $land["EndZ"] >= $z){//範囲該当
        if($player->getName() === $land["Owner"] or isset($land["Inviters"][$player->getName()]) or $player->isOp()){//プレイヤー免除
          return true;
  }else{//免除外
          $player->sendPopup(TextFormat::RED.TextFormat::BOLD."ここはあなたの土地ではありません。 所有者: ".$land["Owner"]." 土地番号 #".$land["ID"]."§r");
          $event->setCancelled();
          return true;
        }//範囲セレクト終点
  }else{//範囲不該当
        }//範囲終点
      }//EndForeach
    $level = $player->getLevel()->getFolderName();
    if($event instanceof PlayerInteractEvent){
      $block = $event->getBlock()->getSide($event->getFace());
    }else{
      $block = $event->getBlock();
    }
    $x = $block->getX();
    $z = $block->getZ();
    foreach($this->data->getAll() as $road){//StartForeach
      if($level == $road["Level"] and $road["StartX"] <= $x and $road["EndX"] >= $x and $road["StartZ"] <= $z and $road["EndZ"] >= $z){//範囲該当
        if($player->isOp()){//対象外
          return true;
  }else{//対象
          $event->setCancelled();
          return true;
        }//対象終点
  }else{//範囲不該当
        }//範囲終点
      }//EndForeach
        if($player->isOp()){//全不該当免除
          return true;
        }else{//範囲不該当
        if($level == "resources" || $level == "BonusArea"){//不該当ワールド
          return true;
        }else{//該当ワールド
          $player->sendPopup(TextFormat::YELLOW.TextFormat::BOLD."この土地を編集するには、土地を購入してください。§r");
          $event->setCancelled();
          return true;
        }//不該当ワールド終点
        return true;
    }//範囲終点
}//EndFunction

  public function onBreak(BlockBreakEvent $event){//StartFunction
    $player = $event->getPlayer();
    $name = $player->getName();
    if($event instanceof PlayerInteractEvent){
      $block = $event->getBlock()->getSide($event->getFace());
    }else{
      $block = $event->getBlock();
    }
    $x = $block->getX();
    $z = $block->getZ();
    $level = $block->getLevel()->getFolderName();
    if($level == "world" and !$player->isOp()){
      $event->setCancelled();
      return true;
    }
    foreach($this->land->getAll() as $land){//StartForeach
      if($level == $land["Level"] and $land["StartX"] <= $x and $land["EndX"] >= $x and $land["StartZ"] <= $z and $land["EndZ"] >= $z){//範囲該当
        if($player->getName() === $land["Owner"] or isset($land["Inviters"][$player->getName()]) or $player->isOp()){//プレイヤー免除
          return true;
  }else{//免除外
          $player->sendPopup(TextFormat::RED.TextFormat::BOLD."ここはあなたの土地ではありません。 所有者: ".$land["Owner"]." 土地番号 #".$land["ID"]."§r");
          $event->setCancelled();
          return true;
        }//範囲セレクト終点
  }else{//範囲不該当
        }//範囲終点
      }//EndForeach
    $level = $player->getLevel()->getFolderName();
    if($event instanceof PlayerInteractEvent){
      $block = $event->getBlock()->getSide($event->getFace());
    }else{
      $block = $event->getBlock();
    }
    $x = $block->getX();
    $z = $block->getZ();
    foreach($this->data->getAll() as $road){//StartForeach
      if($level == $road["Level"] and $road["StartX"] <= $x and $road["EndX"] >= $x and $road["StartZ"] <= $z and $road["EndZ"] >= $z){//範囲該当
        if($player->isOp()){//対象外
          return true;
  }else{//対象
          $event->setCancelled();
          return true;
        }//対象終点
  }else{//範囲不該当
        }//範囲終点
      }//EndForeach
        if($player->isOp()){//全不該当免除
          return true;
        }else{//範囲不該当
        if($level == "resources" || $level == "BonusArea"){//不該当ワールド
          return true;
        }else{//該当ワールド
          $player->sendPopup(TextFormat::YELLOW.TextFormat::BOLD."この土地を編集するには、土地を購入してください。§r");
          $event->setCancelled();
          return true;
        }//不該当ワールド終点
        return true;
    }//範囲終点
}//EndFunction

  public function onPlace(BlockPlaceEvent $event){//StartFunction
    $player = $event->getPlayer();
    $name = $player->getName();
    if($event instanceof PlayerInteractEvent){
      $block = $event->getBlock()->getSide($event->getFace());
    }else{
      $block = $event->getBlock();
    }
    $x = $block->getX();
    $z = $block->getZ();
    $level = $block->getLevel()->getFolderName();
    if($level == "world" and !$player->isOp()){
      $event->setCancelled();
      return true;
    }
    foreach($this->land->getAll() as $land){//StartForeach
      if($level == $land["Level"] and $land["StartX"] <= $x and $land["EndX"] >= $x and $land["StartZ"] <= $z and $land["EndZ"] >= $z){//範囲該当
        if($player->getName() === $land["Owner"] or isset($land["Inviters"][$player->getName()]) or $player->isOp()){//プレイヤー免除
          return true;
  }else{//免除外
          $player->sendPopup(TextFormat::RED.TextFormat::BOLD."ここはあなたの土地ではありません。 所有者: ".$land["Owner"]." 土地番号 #".$land["ID"]."§r");
          $event->setCancelled();
          return true;
        }//範囲セレクト終点
  }else{//範囲不該当
        }//範囲終点
      }//EndForeach
    $level = $player->getLevel()->getFolderName();
    if($event instanceof PlayerInteractEvent){
      $block = $event->getBlock()->getSide($event->getFace());
    }else{
      $block = $event->getBlock();
    }
    $x = $block->getX();
    $z = $block->getZ();
    foreach($this->data->getAll() as $road){//StartForeach
      if($level == $road["Level"] and $road["StartX"] <= $x and $road["EndX"] >= $x and $road["StartZ"] <= $z and $road["EndZ"] >= $z){//範囲該当
        if($player->isOp()){//対象外
          return true;
  }else{//対象
          $event->setCancelled();
          return true;
        }//対象終点
  }else{//範囲不該当
        }//範囲終点
      }//EndForeach
        if($player->isOp()){//全不該当免除
          return true;
        }else{//範囲不該当
        if($level == "resources" || $level == "BonusArea"){//不該当ワールド
          return true;
        }else{//該当ワールド
          $player->sendPopup(TextFormat::YELLOW.TextFormat::BOLD."この土地を編集するには、土地を購入してください。§r");
          $event->setCancelled();
          return true;
        }//不該当ワールド終点
        return true;
    }//範囲終点
}//EndFunction
}//EndClass
