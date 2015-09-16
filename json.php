<?php
require 'UnlimitClass.class.php';
$filepath = 'test.json';
$column = new UnlimitClass($filepath);
$root_id = 'filetree';                      //根节点ID
/*
$column -> insert(array('name' => 'first', 'pid' => 0));

for($i=1; $i<10; $i++)
{
    $new = array();
    $new['pid'] = 1;
    $new['name'] = '第'.$i.'个节点的子节点';
    $column -> insert(array('name' => $new['name'], 'pid' => $new['pid']));
    
}
*/

//$column -> insert(array('name' => '分类二', 'pid' => '0'));
//$column -> update(array('name' => '分类4', 'pid' => '2', 'id' => '14'));
//$column -> delete(5);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>无限级分类</title>
        <link rel="stylesheet" href="jquery.treeview/jquery.treeview.css"/>
        <style type="text/css">
        </style>
    </head>
    <body>
        <div id="tree_control">
            <a title="Collapse the entire tree below" href="#">关闭所有</a>
            <a title="Expand the entire tree below" href="#">展开所有</a>
            <a title="Toggle the tree below, opening closed branches, closing open branches" href="#">切换</a>
        </div>
        <div class="main">
            <?php
                echo $column -> treeview($root_id);
            ?>
            <ul id="black">
                
            </ul>
        </div>
        <script type="text/javascript" src="jquery.treeview/lib/jquery.js"></script>
        <script type="text/javascript" src="jquery.treeview/lib/jquery.cookie.js"></script>
        <script type="text/javascript" src="jquery.treeview/jquery.treeview.js"></script>
        <script type="text/javascript" src="jquery.treeview/jquery.treeview.async.js"></script>
        <script type="text/javascript" src="jquery.treeview/jquery.treeview.edit.js"></script>
        <script type="text/javascript">
        $(document).ready(function(){   
            $("#<?php echo $root_id ?>").treeview({
                animated: "fast",
                control:"#tree_control",
                collapsed: true,
                persist: "cookie",                      //还有浏览器url记录方式location，不推荐
                cookieId: "treeview-black"
            });
            
            $("#black").treeview({
                url: "data.php"
            })
        });
        </script>
    </body>
</html>

