<?php

define('APP_NAME', 'ZADANIE');

define('DB_USER', 'postgres');
define('DB_PASSWORD', 'stud');
define('DB_NAME', 'test');

global $conn;
$conn = pg_connect('hostaddr=127.0.0.1 port=5432 dbname='.DB_NAME.' user='.DB_USER.' password='.DB_PASSWORD);
if (!$conn) {
    echo 'Соединения нет';
	exit;
}


function query($sql)
{
    global $conn;
    $result = pg_query($conn, $sql);
    if (!$result) {
        echo 'Ошибка запроса '.$sql;
        exit;
    }
    return $result;
}

function getData($sql)
{
    $result = query($sql);
    return pg_fetch_all($result);
}

function listTables()
{
    $sql = 'SELECT * FROM information_schema.tables where table_schema=\'public\' ORDER BY table_name;';
    $a = getData($sql);
    $data = array();
    foreach ($a as $k => $v) {
    	$data []= $v['table_name'];
    }
    return $data;
}

function listTablesFull()
{
    $tableNames = listTables();
    $sql = 'SELECT * FROM pg_class WHERE relname IN (\''.implode('\', \'', $tableNames).'\') ORDER BY relname';
    $data = getData($sql);
    return $data;
}

// Список полей
function listFields($tbl, $onlyNames=false)
{
    global $pdo;
    $sql = '
 SELECT  t.table_name, c.column_name, c.data_type
   FROM information_schema.TABLES t JOIN information_schema.COLUMNS c ON t.table_name::text = c.table_name::text
  WHERE t.table_schema::text = \'public\'::text AND
        t.table_catalog::name = current_database() AND
        t.table_type::text = \'BASE TABLE\'::text AND
        NOT "substring"(t.table_name::text, 1, 1) = \'_\'::text AND
        t.table_name=\''.$tbl.'\'
  ORDER BY t.table_name, c.ordinal_position;
    ';
    $data = getData($sql, $onlyNames ? 1 : false);
    return $data;
}

// Ключевые поля
function primaryKeys($table, $onlyNames=false)
{
    $sql = '
    SELECT
        tc.table_schema,
        tc.constraint_name,
        tc.table_name,
        kcu.column_name,
        ccu.table_schema AS foreign_table_schema,
        ccu.table_name AS foreign_table_name,
        ccu.column_name AS foreign_column_name,
        tc.constraint_type
    FROM
        information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
          ON tc.constraint_name = kcu.constraint_name
          AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage AS ccu
          ON ccu.constraint_name = tc.constraint_name
          AND ccu.table_schema = tc.table_schema
    WHERE tc.constraint_type = \'PRIMARY KEY\' AND tc.table_name=\''.$table.'\';
    ';
    $data = getData($sql, $onlyNames ? 3 : false);
    return $data;
}

function generatePagesLinks($limit, $start, $countAll, $floatLimit=50)
{
    $pageLinks = '';
    $pageCount = ceil($countAll / $limit);
    if ($pageCount == 1) {
        return '';
    }
    $j = 0;
    if ($start > $floatLimit) {
        $pageLinks .= '<li><a href="'.url('start=0').'">1...</a></li> ';
    }
    for ($i = max(1, $start - $floatLimit); $i <= $pageCount; $i ++) {
        if ($j > $floatLimit * 2) {
            break;
        }
        $st = '';
        if ($i - 1 == $start) {
            $st = ' style="font-weight:bold; color:#FF0000; background-color:green; color:white "';
        }
        $pageLinks .= '<li><a'.$st.' href="'.url('start='.($i-1)).'">'.$i.'</a></li> ';
        $j ++;
    }
    if ($pageCount > $floatLimit * 2) {
        $pageLinks .= '<li><a href="'.url('start='.($pageCount-1)).'"><span aria-hidden="true">&raquo;</span></a></li> ';
    }
    $pageLinks = '
        <nav aria-label="Page navigation">
          <ul class="pagination">
            '.$pageLinks.'
          </ul>
        </nav>
    ';
    return $pageLinks;
}

function url($add='', $query='')
{
    $httpHost = 'http://'.$_SERVER['HTTP_HOST'];
    $path     = $_SERVER['SCRIPT_NAME'];
    $query    = $query == '' ? $_SERVER['QUERY_STRING'] : $query;
    if ($query == '') {
        return $path.'?'.$add;
    }
    parse_str($query, $currentAssoc);
    parse_str($add, $addAssoc);
    if (is_array($addAssoc)) {
        foreach ($addAssoc as $k => $v) {
            $currentAssoc [$k]= $v;
        }
    }
    $a = array();
    foreach ($currentAssoc as $k => $v) {
        if ($v == '') {
            continue;
        }
        $a []= $v == '' ? $k : "$k=$v";
    }
    return $path.'?'.implode('&', $a);
}
?>


<!DOCTYPE html>
<html>
<head>
    <title><?=APP_NAME?></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <!-- Bootstrap -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

    <style type="text/css">
    .table-pg {width:auto}
    .tbl-menu {list-style:none; padding: 0; font-size: 11px;}
    h3 {margin: 0 0 15px; font-size: 20px;}
    </style>
</head><body>

<nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?=str_replace('index.php', '', $_SERVER['PHP_SELF'])?>"><?=APP_NAME?></a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <!-- <li class="active"><a href="#">Link</a></li>
        <li><a href="#">Link</a></li> -->
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<div class="container-fluid">

    <div class="row">
        <div class="col-sm-2">
        <?php
        $data = listTables();
        echo '<ul class="tbl-menu">';
        foreach ($data as $table) {
            echo '<li><a href="?page=tbl_data&table='.$table.'">'.$table.'</a></li>';
        }
        echo '</ul>';
        ?>
        </div>
        <div class="col-sm-10">
            <?php
            if ($_GET['page']) {
                $page = $_GET['page'];
            	include_once $page.'.php';
            } else {
                include_once 'tbl_list.php';
            }
            ?>
        </div>
    </div>




</div>

</body></html>