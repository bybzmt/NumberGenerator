<?php
/**
 * 算法核心SpaceManager使用介绍
 *
 * SpaceManager 管理的是一个 N ^ Y 大小的空间每个点的占用情况
 * 相当于一个超大的数据，key是我们需要数字，val标记这个位置有没有被占用
 *
 * 出于占用内存的考虑程序使用了字符串来存储，每个位(bit)的1或0表示占用
 */

namespace bybzmt\NumberGenerator;

require __DIR__ . "/../src/load.php";


//base必需要8的倍数
$base = 16;
$dep = 5;

//实例化对像
$obj = new SpaceManager($base, $dep);

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
