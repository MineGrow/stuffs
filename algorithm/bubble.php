<?php
/**
 * 冒泡排序
 * Array ( [0] => 8 [1] => 9 [2] => 3 [3] => 4 [4] => 0 [5] => 6 [6] => 1 ) 
 * =======上为初始值=================下为排序后==============================
 * Array ( [0] => 0 [1] => 1 [2] => 3 [3] => 4 [4] => 6 [5] => 8 [6] => 9 ) 
 * 
 * http://test.kongxianchao.com/algorithm/test.html
 */

 /**
  * 冒泡排序
  *
  * @param array 待排序数组
  * @return array
  */
function bubble($value = [])
{
  $length = count($value) - 1;
  // 外循环
  for ($j = 0; $j < $length; ++$j) {
    // 内循环
    for ($i = 0; $i < $length; ++$i) {
      // 如果后一个值小于前一个值，则互换位置
      if ($value[$i + 1] < $value[$i]) {
        $tmp = $value[$i + 1];
        $value[$i + 1] = $value[$i];
        $value[$i] = $tmp;
      }
    }
  }
  return $value;
}

/**
 * 优化冒泡算法
 * 
 * 把最大的值放数组最后面，不需要重复比较了
 *
 * @param array $value
 * @return void
 */
function bubble_better($value = []) 
{
  $flag   = true;               // 标示 排序未完成
  $length = count($value) - 1;  // 数组的长度
  $index  = $length;            // 最后一次交换的索引位置 初始值为最后一位

  while($flag) {
    $flag = false;  // 假设排序已经完成
    for ($i = 0; $i < $index; $i++) {
      if ($value[$i] > $value[$i + 1]) {
        $flag = true;       // 如果还发生交换 则排序未完成
        $last = $i;         // 记录最后一次发生交换的索引位置
        $tmp  = $value[$i];
        $value[$i] = $value[$i + 1];
        $value[$i + 1] = $tmp;
      }
    }
    $index = $last;
  }

  return $value;
}

/************************ 测试部分 *************************/

$array = array(8, 9, 3, 4, 0, 6, 1);
// var_export(bubble_better($array));