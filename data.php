<?php
//sleep(1);
require 'UnlimitClass.class.php';
$filepath = 'test.json';
$column = new UnlimitClass($filepath);
$root = isset($_REQUEST['root']) ? $_REQUEST['root'] : '0';
if($root == 'source')
{
    for($i=0; $i<1000; $i++)
    {
        //$data = $column -> echo_json();               //1000次并发脚本运行24.0ms,总耗时6,353ms
        $data = $column -> tree_array();                //1000次并发脚本运行24.0ms,总耗时139ms
    }
    echo $data;
    
}else{
    $data = $column -> return_child((int)$root);
    echo $data;
}
?>
