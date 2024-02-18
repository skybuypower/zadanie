<?php
$data = listTablesFull();
?>

<h3>Список таблиц базы данных <?=DB_NAME?></h3>

<table class="table table-pg">
<?php
foreach ($data as $v) {
    $table_name = $v['relname'];
    $rows = $v['reltuples'];
?>
<tr>
    <td><a href="?page=tbl_data&table=<?=$table_name?>"><?=$table_name?></a></td>
    <td><?=$rows?></td>
</tr
<?php
}
?>
</table>