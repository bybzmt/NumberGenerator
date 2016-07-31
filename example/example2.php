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
 *
 * 第1次运行之前需要做一些初始化工作，初史化数据库的数数据
 *
 * 做初史化时需要计算一下自己需要生成的数字个数N，然后添加
 * 大于等于 N / (base ^ dep) 个slot位置, 最后关闭不需要的
 *
 * 需要注意的是如果slot_id对应的记录在数据库中不存在会被当作slot己满处理
 * 另外slot_id比数据库id小1
 */

namespace bybzmt\NumberGenerator;

require __DIR__ . "/../src/load.php";

//这里有$pdo, $table
require __DIR__ . "/test_db.php";

//存储时加锁器, 应该要有 lock($key), unlock($key) 两个方法
//为了简单起见这里置空掉,实际例用中它是必需的
$locker = null;

//可以用这个修改锁Key的前缀，一般默认就行，默认是'bybzmt_number_generator:'
//ToMysql::$_locker_key_prefix = '';

$persistent = new ToMysql($pdo, $table, $locker);

//base必需要8的倍数
$base = 32;
$dep = 3;

//实例化对像
$obj = new SlotManager($base, $dep, $persistent);

//增加10个slot
$ids = $obj->inrcSlot(10);
var_dump('inrcSlot ids:', implode(',', $ids));

//设置指定区间状态
//实际使用中base ^ dep经常与需要生成的数字区间不一至，关掉不需要的部分就行
$obj->setRange(100, 1000, 0);

//如果有特殊需要，也可以直接操作指定slot的数据
//$slot = $obj->getSlot($slot_id);
//$slot->setRange(100, 1000, 0);
//$obj->setSlot($slot_id, $slot);

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

