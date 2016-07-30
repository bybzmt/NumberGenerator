<?php
/**
 * 按槽存储SlotManager使用介绍
 *
 * SlotManager 是以 SpaceManager 为基础单元来存储使用情况的
 *
 * SlotManager 对单个Slot的使用会上锁，为了防止过分的锁竟争Slot
 * 不应太少，但也不宜太多（控制mysql记录行数）
 *
 * 单个Slot的大不也需要权衡，太大会占太多内存或占太多网络IO
 * 太小则效率低
 *
 * Slot体积计算公式为 (base ^ dep) / 8
 */

namespace bybzmt\NumberGenerator;

require __DIR__ . "/../src/load.php";

$dns = '';
$table = 'number_generator';
$user = '';
$pass = '';

$pdo = new PDO($dns, $user, $pass);

$persistent = new ToMysql($pdo, $table);

//base必需要8的倍数
$base = 16;
$dep = 5;

//实例化对像
$obj = new SlotManager($base, $dep, $persistent);

//或者 传第3个参数data，这样可以恢复到上次的运行状态
//$data  = "上次运行后保存在其它地方的数据";
//$obj = new SpaceManager($base, $dep, $data);

//得到一个随军机的可用值
//如果需要多个调用多次即可
$id = $obj->getByRand();
var_dump("getByRand:", $id);

//得到最小的可用值
$id = $obj->getByMin();
var_dump("getByMin:", $id);

//得到最大的可用值
$id = $obj->getByMax();
var_dump("getByMax:", $id);

//得到最大的可用值,但不标记得到的点为己占用
$id = $obj->getByMax(false);

//检查某个点是否己占用 [0 未占用][1 己占用]
$used = $obj->checkPoint($id);
var_dump("checkPoint:", $used);

//设置某个点的状态 [0 未占用][1 己占用]
$obj->setPoint(100, 0);
//$obj->setPoint(100, 1);

//设置一个连续的区域的状态 [0 未占用][1 己占用]
$obj->setRange(100, 100, 1);
//$obj->setRange(100, 100, 0);

//查看是否所有空间都己用完
$full = $obj->isFull();
var_dump("isFull:", $full);

//得到保存得当前状态的数据
$data = $obj->getData();
var_dump("data len:", strlen($data));
