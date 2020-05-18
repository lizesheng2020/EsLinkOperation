# EsLinkOperation
es链式操作
方法使用介绍
1.	bool（）相当于sql中where条件
支持的格式为$where[salesAmount] = [eq,value] 一维键值对数组
2.	terms（）相当于sql中的group
3.	sum() avg() cardinality()
4.	order()
调用此方法会在上个terms同级生成排序规则
如果没有terms过的话会在query同级生成sort进行排序 并支持多次排序
5.	size（）
调用此方法会在上个terms同级生成size
如果没有terms过的话会在query同级生成size进行数据limit
6.	source()
调用此方法会在上个terms同级生成top_hits
如果没有terms过的话会在query同级生成_source进行元数据字段的提取
7.	percentiles（）
价格中位数
8.	range()
$field 要求区间的字段名
$ranges 区间数组注意格式要求
$alias 别名
以区间分组 类似terms

