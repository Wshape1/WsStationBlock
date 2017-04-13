<?php

namespace WsStationBlock;

use pocketmine\plugin\{Plugin,PluginBase};
use pocketmine\event\{Listener,player\PlayerMoveEvent,block\BlockBreakEvent};
use pocketmine\{Player,Server};
use pocketmine\command\{Command,CommandSender};
use pocketmine\utils\Config;
use pocketmine\block\Block;

class Main extends PluginBase implements Listener{

 public $prefix="§7[§6WsStationBlock§7]";
 public $set=[];

public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		$this->getLogger()->info("§6插件已加载.作者Wshape1");
		$path=$this->getDataFolder();
		@mkdir($path,0777,true);
		$this->Config=new Config($path."Blocks.yml",Config::YAML,[]);
		$this->saveResource('说明.txt');
	}
	
  public function onCommand(CommandSender $sender, Command $command, $label, array $args){
	switch($command->getName()){
	case "wsblock":
	if(!$sender->isOp()) return $sender->sendMessage($this->prefix." §cYou are not OP§l!!!");
	if(!isset($args[0])) return $sender->sendMessage($this->prefix." /wsblock\n§1> §6cre <msg|cmd> <ID> <执行:信息/命令> --创建一个信息/命令站立方块\n§1> §6del <ID> --删除一个站立方块");
	switch($args[0]){
	case "cre":
	if(!$sender instanceof Player) return $sender->sendMessage($this->prefix." Please use in game.");
	if(!isset($args[3])) return $sender->sendMessage($this->prefix."§6/wsblock cre <msg|cmd> <ID> <执行:信息/命令> --创建一个信息/命令站立方块");
	if(isset($this->Config->getAll()[$args[2]])) return $sender->sendMessage($this->prefix."§c已存在ID为 {$args[2]} 的站立方块.");
	if($args[1] == "msg" or $args[1] == "cmd"){
	$sender->sendMessage($this->prefix."§b正在创建ID为 {$args[2]} 的站立方块,请拿着羽毛破坏方块\n§b 类型 {$args[1]}\n§b 信息/命令 {$args[3]}");
	$this->set[$sender->getName()]["id"]=$args[2];
	$this->set[$sender->getName()]["info"]=[
	"Pos"=>"",
	"Type"=>$args[1],
	"Msg/Cmd"=>$args[3],
	"MsgType"=>"Message"
	];
	return true;
	}else{
	$sender->sendMessage($this->prefix."§6/wsblock cre <msg|cmd> <ID> <执行:信息/命令> --创建一个信息/命令站立方块");
	}
	break;
	
	case "del":
	if(!isset($args[1])) return $sender->sendMessage($this->prefix."§6/wsblock del <ID> --删除一个站立方块");
	if(!isset($this->Config->getAll()[$args[1]])) return $sender->sendMessage($this->prefix."§c不存在ID为 {$args[1]} 的站立方块.");
	
	$sender->sendMessage($this->prefix."§e成功删除ID为 {$args[1]} 的站立方块.");
	$this->Config->remove($args[1]);
	$this->Config->save();
	
	break;
	}
	}
	}
	public function Station(PlayerMoveEvent $event){
	$player=$event->getPlayer();
	$name=$player->getName();
	$level=$player->getLevel();
	$levelname=$level->getFolderName();
	$block=$level->getBlock($player->floor()->subtract(0,1));
	$x=(int)$block->getX();
	$y=(int)$block->getY();
	$z=(int)$block->getZ();
	foreach($this->Config->getAll() as $id){
	if($id["Type"] == "msg"){
	$pos=explode("-",$id["Pos"]);
	if($levelname === $pos[3] and $x == $pos[0] and $y == $pos[1] and $z == $pos[2]){
	$type=strtolower($id["MsgType"]);
	if($type == "message"){
	$player->sendMessage($this->Replace($id["Msg"],$name));
	}elseif($type == "tip"){
	$player->sendTip($this->Replace($id["Msg"],$name));
	}elseif($type == "popup"){
	$player->sendPopup($this->Replace($id["Msg"],$name));
	}
}
	}elseif($id["Type"] == "cmd"){
	$pos=explode("-",$id["Pos"]);
	if($levelname === $pos[3] and $x == $pos[0] and $y == $pos[1] and $z == $pos[2]){
	if($id["Op"] == false){
	$this->getServer()->dispatchCommand($player,$this->Replace($id["Cmd"],$name));
	}else{
	$this->getServer()->addOp($player);
	$this->getServer()->dispatchCommand($player,$this->Replace($id["Cmd"],$name));
	$this->getServer()->removeOp($player);
	}
	}
	}
	}
	}
	
	public function SetBlockAndBreak(BlockBreakEvent $event){
	$player=$event->getPlayer();
	$name=$player->getName();
	$b=$event->getBlock();
	$xyz=(int)$b->getX()."-".(int)$b->getY()."-".(int)$b->getZ()."-".$player->getLevel()->getFolderName();
	if(isset($this->set[$name])){
	if($player->getInventory()->getItemInHand()->getId() == 288){
	if($this->set[$name]["info"]["Type"] == "cmd"){
	$this->set[$name]["info"]=[
	"Pos"=>$xyz,
	"Type"=>$this->set[$name]["info"]["Type"],
	"Cmd"=>$this->set[$name]["info"]["Msg/Cmd"],
	"Op"=>false
	];
	}
	if($this->set[$name]["info"]["Type"] == "msg"){
	$this->set[$name]["info"]=[
	"Pos"=>$xyz,
	"Type"=>$this->set[$name]["info"]["Type"],
	"Msg"=>$this->set[$name]["info"]["Msg/Cmd"],
	"MsgType"=>"message"
	];
	}
	
	$event->setCancelled();
	$player->sendMessage($this->prefix." §b成功设置站立方块.");
	$this->Config->set($this->set[$name]["id"],$this->set[$name]["info"]);
	$this->Config->save();
	unset($this->set[$name]);
	}
	}
	foreach($this->Config->getAll() as $id){
	$pos=explode("-",$id["Pos"]);
	if($b->getLevel()->getFolderName() === $pos[3] and (int)$b->getX() == $pos[0] and (int)$b->getY() == $pos[1] and (int)$b->getZ() == $pos[2]){
	if(!$player->isOp()){
	$event->setCancelled();
	$player->sendMessage($this->prefix."§c你不能破坏这个站立方块.");
	}else{
	$player->sendMessage($this->prefix."你破坏了这个站立方块.ID为 ".array_search($id, $this->Config->getAll())." .§c如要删除,请自行使用指令删除.");
	}
	}
	}
	}
	
	public function Replace($id,$n){
	$m=str_ireplace("@"," ",$id);
	$m=str_ireplace("&","\n",$m);
	$m=str_ireplace("%p",$n,$m);
	return $m;
	}
	
	
	
	
	}
	