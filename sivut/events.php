<?php
// If the page is being accessed directly and not through index.php, then stop it.
if(!$settings)
	exit();
?>

<?php
include_once($settings->$PATH['absolute']."js/calendarDateInput.js");
?>
<form action="#" method="GET">
<label>Fetch events during</label>
<script>DateInput('startTime', true, 'DD-MON-YYYY')</script>
<script>DateInput('endTime', true, 'DD-MON-YYYY')</script>
<input type="submit" value="HAE">
</form>
<?php

if($_POST['startTime']) {

	$haku = sprintf("SELECT categories.name, events.ID, events.time, events.cost, events.subCats, places.pname, events.method, events.info FROM events, categories, places WHERE events.time < '%1' AND events.time > '%2' JOIN categories.ID = events.mainCat JOIN places.ID = events.places", $dataSource->filterVariable($_GET['startTime']), $dataSource->filterVariable($_GET['endTime']));
	$query = $dataSource->query($haku);
	$events = $query->store_result();
?>
		<h2>List events
	<input type='date' id='begin' class='eventList'> - <input type='date' id='end' class='eventList'>
	</h2>
<?php
	while($events->next()) {
?>
		<h4><?= date("d.m.Y", $events[2]) ;?></h4>
		<div class='eventBlocks'>
		<p class='firstRow'>
		Sum:
		<span class='rightSide'><?= $events[3] ;?> EUR</span>
		</p><p class='secondRow'>
		Main category:
		<span class='rightSide'>
<?= $events[0] ;?>
		</span>
		</p><p class='firstRow'>
		Sub categories:
		<span class='rightSide'>
<?php
		$i=0;
		foreach($events[5] as $cat) {
			if($i == 1)
				echo ", ";
			else
				$i=1;
			echo mysql_fetch_row(mysql_query("SELECT name FROM categories WHERE ID='".$events[4]."'"));
		}
?>
		</span>
?>
		</p><p class='secondRow'>
		Place:
		<span class='rightSide'></span>
<?= $events[5] ;?>
		</span>
		</p><p class='firstRow'>
		Payment:
		<span class='rightSide'><?= $tulos[6] ;?></span>
		</p><p class='secondRow'>
		Explanation:
		<textarea class='rightSide'><?= $tulos[7] ;?></span>
		</p>
		<a href='#' class='details' id='detail1'>Products</a>
		</div>
<?php
	}
}
?>