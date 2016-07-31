#NumberGenerator

NumberGenerator是一个数字生成器，用来生成一段不重复的数字

可以用来生成随机id。
比如：给用户分配一个随机的id，要求id必需1000000 - 9999999之间。

* SpaceManager 是算法核心
* SlotManager 用来按SpaceManager分slot来进行持久化操作

需要注意的时Persistent里面需要有加锁功能，如果没有合适的锁可以使用
[bybzmt/tcplock](https://github.com/bybzmt/tcplock)

composer安装:

`composer require "bybzmt/numbergenerator"`

/example 目录里有用法示例

![算法示例](https://github.com/bybzmt/NumberGenerator/raw/example/principle.png)

如上图a0表示b0-b2是否己用b0表示c0-c2是否己用，类推
