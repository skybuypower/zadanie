<?php
$table = $_GET['table'];
?>

<h3>Просмотр таблицы <?=$table?></h3>


<?php

$limit = 17;
$start = $_GET['start'] ?: 0;
$countAll = getData('SELECT COUNT(*) AS c FROM '.$table)[0]['c'];

$pageLinks = generatePagesLinks($limit, $start, $countAll, $floatLimit=50);

// Находим order
$order = $_GET['order'];
if (!$order) {
	$pks = primaryKeys($table);
    if (count($pks)) {
    	$order = $pks[0]['column_name'];
    }
}

// Составляем запрос
$sql = 'SELECT * FROM '.$table.'';
if ($order) {
	$sql .= ' ORDER BY "'.$order.'"';
}
if ($_GET['order-desc']) {
	$sql .= ' DESC';
}
$sql .= ' LIMIT '.$limit.' OFFSET '.$start;

// Извлекаем данные
$data = getData($sql);


if (!$data) {
    echo 'Нет данных';
    return ;
}

$fields = array_keys($data[0]);


?>


<table class="table table-pg">
<tr>
<?php
foreach ($fields as $field) {
    $add = '';
    if ($field == $_GET['order']) {
        if ($_GET['order-desc']) {
        	$add = '&order-desc=';
        } else {
        	$add = '&order-desc=1';
        }
    } else {
        if ($_GET['order-desc']) {
        	$add = '&order-desc=';
        }
    }
    echo '<th><a href="'.url('order='.$field.$add).'">'.$field.'</a></th>';
}
?>
</tr>
<?php
foreach ($data as $row) {
    echo '<tr>';
    foreach ($fields as $field) {
        echo '<td>'.$row[$field].'</td>';
    }
    echo '</tr>';
}
?>
</table>

<?php
echo $pageLinks;
?>