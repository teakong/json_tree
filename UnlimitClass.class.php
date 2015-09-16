<?php
/**
 * @功能:无限级分类，文件json版，字段结构id,pid,name
 * @时间:20150916
 */
class UnlimitClass
{
    //field
    public $sys_field = array('id', 'pid');                     //系统自增字段，不能更改,$pid必须提供，但是可以自定义值
    public $usr_field = array('pid', 'name');                   //用户字段，可随意更改
    public $root_str = '[{"id":0, "pid":0, "name":"root"}]';    //自定义root节点
    public $filepath;                                           //数据文件路径
    private $errorMess;                                         //程序报错信息
    private $id_arr;                                            //id索引数组，已排序
    public $arrData;                                            //所有数据数组
    public function __construct($filepath)
    {
        $this -> filepath = $filepath;
        $this -> arrData = $this -> initialize();
    }
    
    public function insert($arr)
    {
        if(is_array($arr))
        {
            $all_keys = array_keys($arr);
            if(array_diff($this -> usr_field, $all_keys))
            {
                $this -> errorMess = 'please check the format of input';
            }else{
                //检查新增节点的pid是否存在
                if(in_array($arr['pid'], $this -> id_arr))
                {
                    //过滤不需要的字段
                    $insert = array();
                    $insert['id'] = count($this -> id_arr);
                    foreach($this -> usr_field as $field)
                    {
                        $insert[$field] = $arr[$field];
                    }
                    //更新$this -> arrData
                    $this -> arrData[] = $insert;
                    //更新$this -> id_arr
                    $this -> id_arr[] = $insert['id'];
                    $this -> save($this -> arrData);
                }else{
                    $this -> errorMess = 'can not append new node on a non-object';
                }
            }
        }else{
            $this -> errorMess = 'you should input an array';
        }
        if($this -> errorMess)
        {
            echo $this -> errorMess;
        }
    }
    
    public function delete($id)
    {
        if(func_get_args() == 0 || $id == '')
        {
            $this -> errorMess = 'please input infomation';
            die($this -> errorMess);
        }else if(is_array($id))
        {
            $result = $this -> select($id);
            $id = $result['id'];
        }
        //删除指定id的节点，关联删除以下所有节点
        if($id == 0)
        {
            file_put_contents($this -> filepath, $this -> root_str);        //清空所有节点
            $this -> arrData = $this -> initialize();                       //重新初始化
        }else{
            //检查需要更改的id是否存在
            if(in_array($id, $this -> id_arr))
            {
                $this -> del_pid($id);
                $this -> save($this -> arrData);
            }else{
                $this -> errorMess = 'can not find the node';
            }
        }
        if($this -> errorMess)
        {
            echo $this -> errorMess;
        }
    }
    
    //需要递归删除以$id为pid的节点，并且删除以节点id为pid的其余子节点，直到找不到为止
    private function del_pid($pid)
    {
        //根据传入的$pid查找节点，全部删除
        foreach($this -> arrData as $key => $node)          //递归调用时会自动重新索引，不用担心误删
        {
            if($node['pid'] == $pid)                        //如果父节点匹配则递归删除
            {
                $this -> del_pid($node['id']);
            }else if($node['id'] == $pid){                  //如果该节点的匹配则删除
                //更新$this -> arrData
                unset($this -> arrData[$key]);
                //更新$this -> id_arr
                unset($this -> id_arr[$key]);
            }
        }
    }   
    
    public function update($arr)
    {
        if(is_array($arr))
        {
            $all_keys = array_keys($arr);
            if(array_diff(array_merge($this -> sys_field, $this -> usr_field), $all_keys))
            {
                $this -> errorMess = 'please check the format of input';
            }else{
                //检查需要更改的id是否存在
                $flag = false;
                if(in_array($arr['id'], $this -> id_arr))
                {
                    $cur_key = array_search($arr['id'], $this -> id_arr);       //当前索引
                    $cur_node = $this -> arrData[$cur_key];                     //因为已经排序成功，$this -> arrData的索引与$this -> id_arr一致
                    $update = array();
                    $update['id'] = $arr['id'];
                    foreach($this -> usr_field as $field)
                    {
                        if($arr[$field] != $cur_node[$field])
                        {
                            //对pid字段进行特殊判断
                            if($field == 'pid')
                            {
                                //检查修改节点的pid是否存在
                                if(!in_array($arr['pid'], $this -> id_arr))
                                {
                                    $this -> errorMess = 'can not find the pid node';
                                    die($this -> errorMess);
                                }
                            }
                        }
                        $update[$field] = $arr[$field];
                    }
                    $this -> arrData[$cur_key] = $update;           //因为更新不会增减id所以不需要更新$this -> id_arr
                    $this -> save($this -> arrData);
                }else{
                    $this -> errorMess = 'can not find the node';
                }
            }
        }else{
            $this -> errorMess = 'you should input an array';
        }
        if($this -> errorMess)
        {
            echo $this -> errorMess;
        }
    }
    
    public function select()
    {
        $result = array();
        $args = func_get_args();
        $fields = array_merge($this -> sys_field, $this -> usr_field);
        //对多个传入的数组求并集，对单个数组内部求交集
        foreach($args as $arg)
        {
            //根据传入的id查询指定的节点返回结果数组
            if(is_array($arg))
            {
                foreach($this -> arrData as $cur_node)
                {
                    $count = 0;
                    foreach($arg as $k => $v)
                    {
                        //只需要解决交集问题，即全部校验正确者加入数组
                        if(in_array($k, $fields) && $cur_node[$k] == $arg[$k])
                        {
                            $count++;
                        }
                    }
                    if($count == count($arg) && !in_array($cur_node, $result))
                    {
                        $result[] = $cur_node;
                    }
                }
            }else{
                $this -> errorMess = 'you should input an array';
            }
        }
        
        return $result;
    }
    
    public function display()
    {
        //按照模拟tree模式进行输出
        $data = $this -> arrData;
        //1.先找到根节点，输出根节点
        echo $data[0]['name'].'<br/>';
        //2.然后再找出以根节点id为pid的所有节点，递归输出即可
        $count = 0;                                             //$count为层次计数
        $this -> show($data[0]['id'], $count);
    }
    
    private function show($pid, &$count)
    {
        $count++;                                               //向前进一层
        //根据传入的$pid查找对应pid节点,输出该节点内容，并且继续寻找下一个子节点直到结束
        foreach($this -> arrData as $key => $node)
        {
            if($key == 0)
            {
                continue;                                       //除了父节点外如果找到了节点
            }
            if($node['pid'] == $pid)
            {
                echo str_repeat('&nbsp;&nbsp;&nbsp;', $count).'|-';
                echo "array('id'=> ".$node['id'].",'pid'=>".$node['pid'].",'name'=>".$node['name'].")".'<br/>';
                $this -> show($node['id'], $count);
            }
        }
        $count--;                                               //后退一层
    }

    //网友提供的2种将数组格式化的树形结构的方法,其中genTree5会保存传入的所有键值对的索引,而genTree9会重新索引
    //下面的2种方法仅适合于有索引与id对应的数据,对于没有整理的数据不能随便传入,否则会产生错误,如关系错乱但未引起内存错误,为避免内存问题建议这2个方法私有
    public function genTree($childName = 'son', $assoc = true)
    {
        //本例不满足完全对应关系,从数据库中取出的数据也一般不满足,都需要预处理
        $items = array();                   //开始格式化传入的参数数组
        foreach($this -> arrData as $data)
        {
            if($data['id'] == 0)
            {
                continue;                   //系统根节点不作处理
            }else{
                $items[$data['id']] = $data;
            }
        }
        return $assoc ? $this -> genTree5($items, $childName) : $this -> genTree9($items, $childName);
    }
    
    //方法1:利用传地址方式对数组中的每一个元素归类到其指向的pid元素下,保留原有索引且不构造变量,使用foreach循环的时候推荐使用
    //其数据特征是每个子元素的id必须与其索引相同,所以必须传入预先整理好的数据,100000次脚本运行1,439ms,总耗时5,559ms
    private function genTree5($items, $childName = 'son')
    {
        foreach($items as $item)
        {
            $items[$item['pid']][$childName][$item['id']] = &$items[$item['id']];
        }
        return isset($items[0][$childName]) ? $items[0][$childName] : array();
    }
    
    //方法2:先判断每一个元素的pid元素是否存在,如第一个元素的父元素就不存在(之前continue掉了,那么这个元素应该处于第一级)否则这个元素处于其父元素下一级
    //不保留原有索引且构造局部变量,相对于上面的方法在运行效率上稍微低一点,使用for循环的时候勉强使用,其余情况下不推荐
    //其数据特征是每个子元素的id必须与其索引相同,所以必须传入预先整理好的数据,100000次脚本运行1,488ms,总耗时5,675ms
    private function genTree9($items, $childName = 'son')
    {
        $tree = array();
        foreach($items as $item)
        {
            if(isset($items[$item['pid']]))
            {
                $items[$item['pid']][$childName][] = &$items[$item['id']];
            }else{
                $tree[] = &$items[$item['id']];
            }
        }
        return $tree;
    }
    
    public function tree_array()
    {
        //按照tree模式进行输出，返回拼凑的ul和li标签，需要插件支持，与下面echo_json不同的是不采用递归
        $items = array();                   //开始格式化传入的参数数组
        foreach($this -> arrData as $data)
        {
            if($data['id'] == 0)
            {
                continue;                   //系统根节点不作处理
            }else{
                $items[$data['id']] = $data;
            }
        }
        $items = $this -> genTree_array($items, 'children');
        return json_encode($items);
    }
    
    //不采用递归可以省很多内存和时间
    private function genTree_array($items, $childName = 'son')
    {
        $tree = array();
        foreach($items as $item)
        {
            $items[$item['id']]['text'] = $items[$item['id']]['name'];
            $items[$item['id']]['expanded'] = false;
            $items[$item['id']]['classes'] = 'important';
            unset($items[$item['id']]['name']);
            unset($items[$item['id']]['pid']);
            if(isset($items[$item['pid']]))
            {
                $items[$item['pid']][$childName][] = &$items[$item['id']];
            }else{
                $tree[] = &$items[$item['id']];
            }
        }
        return $tree;
    }
    
    public function echo_json()
    {
        //根据treeview的异步传输原理输出整个json数据
        $data = $this -> arrData;
        //先从根节点出发，查找所有根节点的子节点存储，go on
        $arr = $this -> select(array('pid' => 0));
        $result = array();
        foreach($arr as $node)
        {
            if($node['id'] == 0)    continue;
            $result[] = $this -> search_child($node);
        }
        return json_encode($result);
    }
    
    public function search_child($node)
    {
        //取出子节点所有的预置数据，不能算异步请求
        $result['id'] = $node['id'];
        $result['text'] = $node['name'];
        $result['expanded'] = false;
        $result['classes'] = 'important';
        $children = $this -> select(array('pid' => $node['id']));
        if(!empty($children))
        {
            foreach($children as &$child)
            {
                $child = $this -> search_child($child);
            }
            $result['children'] = $children;                //从动态页面生成json数据，但不能算异步请求
        }
        return $result;
    }
    
    //拼凑插件所需的字符串，不得已使用量递归，希望插件能直接支持json对象自己去递归吧
    public function treeview($root_id)
    {
        //按照tree模式进行输出，返回拼凑的ul和li标签，需要插件支持
        $data = $this -> arrData;
        //1.先找到根节点，输出根节点
        $str = '<ul pid="0" id="'.$root_id.'" class="filetree">';
        //2.然后再找出以根节点id为pid的所有节点，递归输出即可
        $this -> make_html($data[0]['id'], $str);
        $str .= '</ul>';
        return $str;
    }

    private function make_html($pid, &$str)
    {
        //根据传入的$pid查找对应pid节点,输出该节点内容，并且继续寻找下一个子节点直到结束
        foreach($this -> arrData as $key => $node)
        {
            if($key == 0)
            {
                continue;                                       //除了父节点外如果找到了节点
            }
            if($node['pid'] == $pid)
            {
                $str .= '<li><span class="folder" pid="'.$node['name'].'">'.$node['name'].'</span><ul>';
                $this -> make_html($node['id'], $str);
                $str .= '</ul></li>';
            }
        }
    }
    
    public function return_child($id)
    {
        //根据传入的节点ID取出对应的节点的下一级子节点，好像效果不怎么好
        $data = $this -> select(array('id' => $id));
        foreach($data as &$row)
        {
            $children = $this -> select(array('pid' => $id));
            $result = array();
            if(!empty($children))
            {
                foreach($children as $child)
                {
                    $arr = array();
                    if($child['id'] == 0)
                    {
                        //跳过根节点
                        continue;
                    }else{
                        $arr['id'] = $child['id'];
                        $arr['text'] = $child['name'];
                        $arr['expanded'] = true;
                        $arr['classes'] = 'important';
                    }
                    $next = $this -> select(array('pid' => $child['id']));
                    if(!empty($next))
                    {
                        $arr['haschildren'] = true;
                    }
                    $result[] = $arr;
                }
            }
            //预置数据
            $row['text'] = $row['name'];
            $row['expanded'] = true;
            $row['classes'] = 'important';
            $row['children'] = $result;
        }
        return json_encode($data);
    }
    
    private function save($result)
    {
        $json = json_encode($result);
        if($json)
        {
            $flag = file_put_contents($this -> filepath, $json);
            if(!$flag)
            {
                $this -> errorMess = 'can not save the content, please check the config file is writeable';
            }
        }else{
            $this -> errorMess = 'input data error';
        }
    }
    
    private function initialize()
    {
        $arr = $this -> _json_decode();
        if(is_array($arr))
        {
            //对数据进行排序，计算出最大id。
            $id_arr = array();
            foreach($arr as $row)
            {
                $id_arr[] = $row['id'];                     //这里需要不需要加$key,因为$key是从0自增的，并且不能先对$id_arr排序，必须保证$id_arr和$arr一一对应
            }
            array_multisort($id_arr, SORT_ASC, $arr);       //按照id升序排列，方便定位节点进行修改删除等。
            sort($id_arr);                                  //简单排序
            $this -> id_arr = $id_arr;
            return $arr;
        }else{
            die($arr);                                      //直接抛出异常
        }
    }
    
    private function _json_decode()
    {
        //读取文件内容
        $con = '';                                          //配置内容
        if(!is_file($this -> filepath))
        {
            if(is_dir($this -> filepath))
            {
                return 'can not read config file';
            }else{
                $dir = dirname($this -> filepath);
                if(is_writeable($dir))
                {
                    file_put_contents($this -> filepath, $this -> root_str);
                    $con = $this -> root_str;
                }else{
                    return 'can not make config file, please check the directory is writeable';
                }
            }
        }else{
            $con = file_get_contents($this -> filepath);
            if(!$con)
            {
                file_put_contents($this -> filepath, $this -> root_str);
                $con = $this -> root_str;
            }
        }
        //校验文件的格式
        $json = json_decode($con ,true);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = '';
            break;
            case JSON_ERROR_DEPTH:
                $error = ' - Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = ' - Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
                $error = ' - Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
                $error = ' - Syntax error, malformed JSON';
            break;
            case JSON_ERROR_UTF8:
                $error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
            default:
                $error = ' - Unknown error';
            break;
        }
        if(!$error)
        {
            return $json;
        }else{
            $this -> errorMess = $error;
            return $error;
        }
    }
}
?>