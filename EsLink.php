<?php
namespace eslinkoperation;

class EsLink{
    /**
     * 初始化资源限制条件
     * @var array
     */
    public $bool = ['bool'=>[]];
    /**
     * 别名记录
     * @var string
     */
    public $aggsalias;
    /**
     * 初始化buckets
     * @var array
     */
    public $aggs = ['aggs'=>[]];
    /**
     * 聚合记录
     * @var
     */
    public $termsRecord = [];
    /**
     * field 要查询的字段
     * @var array
     */
    public $source = [];
    /**
     * dls语句
     * @var
     */
    public $dsl;
    /**
     * query同级size default:0
     * @var
     */
    public $querysize = 0;
    /**
     * query同级sort函数
     * @var array
     */
    public $queryorder = [];

    /**
     * @param $arr
     * @return $this
     */
    public function bool($arr){
        $must     = [];
        $must_not = [];
        $should   = [];
        if (is_array($arr)){
            foreach ($arr as $k => $v){
                $operators = strtolower($v[0]); //运算符
                switch ($operators){
                    case '=':case 'eq':
                    array_push($must,['term' => [$k => ['value' => $v[1]]]]);
                    break;
                    case '!=':case 'neq':
                    array_push($must_not,['term' => [$k => ['value' => $v[1]]]]);
                    break;
                    case 'rangeeq':
                        array_push($must,["range" => [$k => $v[1]]]);
                        break;
                    case 'rangeneq':
                        array_push($must_not,["range" => [$k => $v[1]]]);
                        break;
                    case 'like':
                        array_push($must,["match_phrase" => [$k => $v[1]]]);
                        break;
                    case 'likeor':
                        $item = [];
                        foreach ($v[1] as $in){
                            $item[] = ["match_phrase" => [$k => $in]];
                        }
                        array_push($must,["bool" => ["should" => $item]]);
                        break;
                    case 'not like':
                        array_push($must_not,["match_phrase" => [$k => $v[1]]]);
                        break;
                    case 'should':
                        break;
                    case 'in':
                        $item = [];
                        foreach ($v[1] as $in){
                            $item[] = ["term" => [$k => ["value" => $in]]];
                        }
                        array_push($must,["bool" => ["should" => $item]]);
                        break;
                    case 'fieldsin':
                        $item = [];
                        foreach ($v[1] as $fieldk => $fieldv){
                            $item[] = ["term" => [$fieldv => ["value" => $v[2][$fieldk]]]];
                        }
                        array_push($must,["bool" => ["should" => $item]]);
                        break;
                    case 'not in':
                        $notitem = [];
                        foreach ($v[1] as $in){
                            $notitem[] = ["term" => [$k => ["value" => $in]]];
                        }
                        array_push($must_not,["bool" => ["should" => $notitem]]);
                        break;
                    default:
                        die('This expression is not supported '.$operators);
                }
            }
        }else{
            die('param error!');
        }
        if (!empty($must))     $this->bool['bool']['must']      = $must;
        if (!empty($must_not)) $this->bool['bool']['must_not']  = $must_not;
        if (!empty($should))   $this->bool['bool']['should']    = $should;
        return $this;
    }

    /**
     * @param $field
     * @param string $alias
     * @return $this
     */
    public function terms($field,$alias = ''){
        if (empty($alias))  $alias = $field;
        $this->aggsalias = $this->aggsalias.$alias.'|'; //记录aggs别名
        if (empty($this->aggs['aggs'])){
            $this->aggs['aggs'][$alias] = ['terms' => ['field' => $field]];
        }else{
            $aliasArray = explode('|',rtrim($this->aggsalias,'|'));
            $aggsStr = '';
            foreach ($aliasArray as $a){
                $aggsStr .= "['aggs']['$a']";
            }
            eval('$this->aggs'.$aggsStr.' = ["terms" => ["field" => $field]];');
        }
        return $this;
    }

    /**
     * range操作类似terms
     * @param $field   要range的字段
     * @param $ranges  range区间参数字段
     * @param $alias   range别名
     * @return $this
     */
    public function range($field,$ranges,$alias){
        //检测参数$ranges数组格式 限制为二维数组
        if (is_array($ranges)){ //是数组 开始检查格式 key from to
            $keyCount  = count(array_column($ranges,'key'));
            $fromCount = count(array_column($ranges,'from'));
            if (($keyCount == $fromCount)){  //格式正确
                if (empty($alias))  $alias = $field;
                $this->aggsalias = $this->aggsalias.$alias.'|'; //记录aggs别名
                if (empty($this->aggs['aggs'])){
                    $this->aggs['aggs'][$alias] = ['range' => ['field' => $field,'ranges'=>$ranges]];
                }else{
                    $aliasArray = explode('|',rtrim($this->aggsalias,'|'));
                    $aggsStr = '';
                    foreach ($aliasArray as $a){
                        $aggsStr .= "['aggs']['$a']";
                    }
                    eval('$this->aggs'.$aggsStr.' = ["range" => ["field" => $field,"ranges"=>$ranges]];');
                }
                return $this;
            }else{
                die("Array Invalid format [ ['key'=>alias,'from'=>value1,'to'=>value2],...['key'=>alias,'from'=>value1]]");
            }
        }else{
            die("Param ranges accept Array");
        }
    }
    /**
     * sum 聚合查詢
     * @param $field
     * @param string $alias
     * @param string $type
     * @return $this
     */
    public function sum($field,$alias,$type = 'sum'){
        if (empty($alias))  //没有起别名
            die('Please give a name to the field you want to aggregate');
        if (!in_array($type,['sum','avg','cardinality','percentiles']))  //目前只支持的操作 sum avg cardinality
            die("Currently only aggregate types are supported sum,avg,cardinality,percentiles");
        if (!empty($this->aggsalias)){     //表示有可sum的结果集
            $aliasArray = explode('|',rtrim($this->aggsalias,'|'));
            $aggsStr = '';
            foreach ($aliasArray as $a){
                $aggsStr .= "['aggs']['$a']";
            }
            switch ($type){
                case 'percentiles':
                    if (empty($this->termsRecord))
                        eval('$this->aggs'.$aggsStr.'["aggs"] = [$alias => [$type => ["field" => $field,"percents"=>[0,1,5,25,50,75,95,99,100]]]];');  //沒有聚合记录
                    else
                        eval('$this->aggs'.$aggsStr.'["aggs"][$alias] = [$type => ["field" => $field,"percents"=>[0,1,5,25,50,75,95,99,100]]];');      //有聚合记录的話直接賦值到aggs下

                    break;
                default:
                    if (empty($this->termsRecord))
                        eval('$this->aggs'.$aggsStr.'["aggs"] = [$alias => [$type => ["field" => $field]]];');  //沒有聚合记录
                    else
                        eval('$this->aggs'.$aggsStr.'["aggs"][$alias] = [$type => ["field" => $field]];');      //有聚合记录的話直接賦值到aggs下
                    break;
            }
        }else{  //没有terms以$type函数起手
            switch ($type){
                case 'percentiles':
                    $this->aggs['aggs'][$alias] = [$type => ['field' => $field,"percents"=>[0,1,5,25,50,75,95,99,100]]];
                    break;
                default:
                    $this->aggs['aggs'][$alias] = [$type => ['field' => $field]];
                    break;
            }
        }
        array_push($this->termsRecord,$alias);   //聚合记录
        return $this;
    }

    /**
     * 求avg平均数
     * @param $field
     * @param string $alias
     * @return EsLinkOperation
     */
    public function avg($field,$alias = ''){
        return $this->sum($field,$alias,'avg');
    }

    /**
     * 去重并count
     * @param $field
     * @param string $alias
     * @return EsLinkOperation
     */
    public function cardinality($field,$alias = ''){
        return $this->sum($field,$alias,'cardinality');
    }

    /**
     * 价格中位数
     * @param $field
     * @param string $alias
     * @return EsLink
     */
    public function percentiles($field,$alias = ''){
        return $this->sum($field,$alias,'percentiles');
    }

    /**
     * 排序
     * @param $field
     * @param string $order
     * @return $this
     */
    public function order($field,$order = 'desc'){
        if (!in_array($order,['desc','asc'])) die("[ Order Param accept desc or asc , $order gived ]");
        if (!empty($this->aggsalias)){
            $aliasArray = explode('|',rtrim($this->aggsalias,'|'));
            $aggsStr = '';
            foreach ($aliasArray as $a){
                $aggsStr .= "['aggs']['$a']";
            }
            eval('$this->aggs'.$aggsStr.'["terms"]["order"] = [$field => $order];');
        }else{
            array_push($this->queryorder,[$field => ["order" => $order]]);
        }
        return $this;
    }

    /**
     * size() default:10
     * @param $field
     * @return $this
     */
    public function size($size){
        if (!empty($this->aggsalias)){
            $aliasArray = explode('|',rtrim($this->aggsalias,'|'));
            $aggsStr = '';
            foreach ($aliasArray as $a){
                $aggsStr .= "['aggs']['$a']";
            }
            eval('$this->aggs'.$aggsStr.'["terms"]["size"] = $size;');
        }else{
            $this->querysize = $size;
        }
        return $this;
    }

    /**
     * 设置query同级size数值
     * @param $size
     * @return $this
     */
    public function querysize($size){
        $this->querysize = $size;
        return $this;
    }

    /**
     * @param $arr
     * @return $this
     */
    public function source($field,$alias = false){
        //处理$field 为数组
        if (is_string($field)){  //字符串
            if (strpos($field,',')){ //检查是否有逗号分隔符
                $field = explode(',',$field);
            }else{
                $field = [$field];
            }
        }elseif (is_array($field)){
            $field = $field;
        }
        if (!empty($this->aggsalias)){     //表示有分组查询terms
            if ($alias == false)  //没有起别名
                die('Please give a name to the field you want to aggregate -->source()');
            $aliasArray = explode('|',rtrim($this->aggsalias,'|'));
            $aggsStr = '';
            foreach ($aliasArray as $a){
                $aggsStr .= "['aggs']['$a']";
            }
            if (empty($this->termsRecord))
                eval('$this->aggs'.$aggsStr.'["aggs"] = [$alias => ["top_hits" => ["size"=>1,"_source"=>["includes"=>$field]]]];');  //沒有聚合记录
            else
                eval('$this->aggs'.$aggsStr.'["aggs"][$alias] = ["top_hits" => ["size"=>1,"_source"=>["includes"=>$field]]];');     //有聚合记录的話直接賦值到aggs下
        }else{
            $this->dsl['_source'] = $field;
        }
        array_push($this->termsRecord,$alias);   //聚合记录
        return $this;
    }

    /**
     * 二次聚合
     * @param $field
     * @param $alias
     * @return $this
     */
    public function havsum($field,$alias){
        if (!empty($this->termsRecord)){
            if (in_array($field,$this->termsRecord)){
                $aliasArray = explode('|',rtrim($this->aggsalias,'|'));
                $aggsStr = '';
                foreach ($aliasArray as $a){
                    $aggsStr .= "['aggs']['$a']";
                }
                $subStr = substr($aggsStr,0,strripos($aggsStr,'['));
                eval('$this->aggs'.$subStr.'[$alias] = ["sum" => ["field" => $field]];');
            }else{
                die('expects parameter '.implode(',',$this->termsRecord));
            }
        }else{
            die('There is no set that can be filtered twice');
        }
        return $this;
    }

    /**
     * 获取一个数组的维度
     * @param array $arr
     * @return int
     */
    public function getArrayDimension($arr){
        if (!is_array($arr)) return 0;
        else{
            $count = 0;
            foreach ($arr as $v){
                $d = $this->getArrayDimension($v);
                if ($d > $count) $count = $d;
            }
            return $count + 1;
        }
    }

    /**
     * flag
     * true时返回数组
     * dsl时返回dsl语句
     * @param bool $flag
     * @return false|string select('dsl') select('arr')
     */
    public function select($flag = '',$type = '',$period = '',$f = false){
        $this->dsl['size']    = $this->querysize;
        $this->dsl['query']   = $this->bool;
        if (!empty($this->aggs['aggs']))
            $this->dsl['aggs']    = $this->aggs['aggs'];
        if (!empty($this->queryorder))
            $this->dsl['sort']    = $this->queryorder;
        /**********************赋值dsl给一个变量并清除属性 相当于每次创建对象都重新实例化一次*****/
        $querydsl   = $this->dsl;
        $this->bool = ['bool'=>[]];
        $this->aggsalias = NULL;
        $this->aggs = ['aggs'=>[]];
        $this->termsRecord = [];
        $this->source = [];
        $this->dsl = NULL;
        $this->querysize = 0;
        $this->queryorder = [];
        /*********************有新属性时【记得】添加清除属性*******************************************/
        if ($flag == 'arr'){
            dd($querydsl);
        }elseif($flag == 'dsl'){
            dd('Query Dsl:<br>'.json_encode($querydsl,JSON_UNESCAPED_UNICODE));
        }else{
            return json_encode($querydsl,JSON_UNESCAPED_UNICODE);
        }
    }
}