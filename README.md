# EsLinkOperation
##方法使用介绍
+ bool
>相当于sql中where条件
>>$where[salesAmount] = [eq,value] 一维键值对数组<br>
$where[] = [fieldsin,[field1,...],[value,...]]
+ terms
>相当于sql中的group
>>terms($groupfield,$alias)  分组字段和分组别名(别名不写默认为字段名)
+ sum
+ avg
+ cardinality
+ percentiles
+ order
>调用此方法会在上个terms同级生成排序规则<br>
>>如果没有terms过的话会在query同级生成sort进行排序 并支持多次排序
+ size
>调用此方法会在上个terms同级生成size<br>
>>如果没有terms过的话会在query同级生成size进行数据limit
+ source
>调用此方法会在上个terms同级生成top_hits
>>如果没有terms过的话会在query同级生成_source进行元数据字段的提取
+ range
>$field 要求区间的字段名<br>
>$ranges 区间数组注意格式要求<br>
>$alias 别名  以区间分组 类似terms

