<?php

// If the page is being accessed directly and not through index.php, then stop it.

if(!$settings)
	exit();

include("libraries/KIRJASTOCharts.php");

// This should be set to  support multi-localizations. Fetch it through database or PHP later. jQuery.Datepicker works on it's own localization settings:
$dateZone = "d.m.Y";

// Init variables:
$fixedTimeTypes = array("week" => _("This week"), "month" => _("This month"), "year" => _("This year"));
$chartTypes = array("pie" => _("Pie"), "bar" => _("Bar"), "line" => _("Line"), "numbers" => _("Numbers"));
$categoryTypes = array("main" => _("Main category"), "extra" => _("Extra category"));
// For eval / javascript-purposes the variable are in underscore:
$this_month = "first day of this month";
$this_year = "first day of January this year";
$this_week = "last Monday";
$today = "today";

$startDate = "";
$startDateUnix = "";
$endDate =  "";
$endDateUnix = "";

if(!empty($_GET['startDate']) && !empty($_GET['endDate'])) {
    $startDate = $dataSource->filterVariable($_GET['startDate']);
    $startDateUnix = strtotime(date($dateZone,  strtotime($_GET['startDate']))." 00:00");
    $endDate =  $dataSource->filterVariable($_GET['endDate']);
    $endDateUnix = strtotime(date($dateZone,  strtotime($_GET['endDate']))." 23:59:59");
}

$fixedTimes = "";
if(!empty($_GET['fixedTimes'])) {
	$fixedTimes =  $dataSource->filterVariable($_GET['fixedTimes']);
        if(empty($_GET['type'])) {
            $_GET['type'] = "pie";
        }
}

?>

<script type="text/javascript" src="<?= $settings->PATH['site'] ?>js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="<?= $settings->PATH['site'] ?>js/jquery-ui-1.8.16.custom.min.js"></script>
<?php // Localisation files for datepicker: ?>
<script type="text/javascript" src="<?= $settings->PATH['site'] ?>js/datepicker/jquery.ui.datepicker-<?= strtolower($shortCountryTag) ;?>.js"></script>

<?php // jQuery-based date picking calendar. Scripts and the actual HTML: ?>
            <script>
                // Setting datepicker:
		$(function() {
                    $.datepicker.setDefaults($.datepicker.regional[""]);
                    $("#startDate").datepicker($.datepicker.regional["<?= strtolower($shortCountryTag) ;?>"]);
                    $("#endDate").datepicker($.datepicker.regional["<?= strtolower($shortCountryTag) ;?>"]);
		});
                // Setting selection changes:
            </script>

<div class="content">
    <div class="ui-tabs ui-widget ui-widget-content ui-corner-all contentInnerBlock">
<?php // jQuery-based date picking calendar. Scripts and the actual HTML: ?>
	<form name="charts" method="GET" action="index.php">
            <input type='hidden' name='s' value ="charts">
            <table class="charts">
                <tr>
                    <td colspan="2">
                        <div class="columnBase columnColorHeader">
                            <?= _('choose time period or select dates') ;?>
                        </div>
                    </td>
                </tr>
                <tr class="BlockUIcolumnBase columnMain">
                    <td class="columnFirstChild">
                        <?= _('Time period') ;?>
                    </td>
                    <td class="columnSecondChild">
                        <select name="fixedTimes" onChange="fixedTimeSelected();">
                            <option value="">-</option>
<?php
                            // This variable is used to determine, if fixed time is set, we don't even try to evaluate if specific dates were set:
                            $dontDefaultSelectDate = 0;

                            foreach($fixedTimeTypes as $key => $var) {
                                $selected = "";
                                if($key == $fixedTimes) {
                                    $selected = "selected";
                                    $dontDefaultSelectDate = 1;
                                }
                                echo "<option value='".$key."' ".$selected.">".$var."</option>";
                            }
?>
                        </select>
                    </td>
                </tr>
                <tr class="BlockUIcolumnBase columnMain">
                    <td class="columnFirstChild">
                        <?= _('Start date') ;?>:
                    </td>
                    <td class="columnSecondChild">
<?php
// Here we set the possible default time for the starting date
                        $time = "";
                        if($dontDefaultSelectDate != 1) {
                            if(!empty($startDate)) {
                                $time = createDefaultTime($startDate);
                            }
                            else {
                                $time = createDefaultTime(date($dateZone));
                            }
                        }
?>
                        <input id="startDate" type="text" name="startDate" value="<?php echo $time ;?>" />
                    </td>
                </tr>
                <tr class="BlockUIcolumnBase columnMain">
                    <td class="columnFirstChild">
                        <?= _('End date') ;?>:
                    </td>
                    <td class="columnSecondChild">
<?php
// Here we set the possible default time for the ending date
                        $time = "";
                        if($dontDefaultSelectDate != 1) {
                            if(!empty($endDate)) {
                                $time = createDefaultTime($endDate);
                            }
                            else {
                                $time = date($dateZone, strtotime("now"));
                            }
                        }
?>
                        <input id="endDate" type="text" name="endDate" value="<?php echo $time ;?>" />
                    </td>
                </tr>
            </table>
            <table>
                <tr>
                    <td>
                        <div class="columnBase columnColorHeader">
                            <?= _("choose information to be shown"); ?>
                        </div>
                    </td>
                </tr>
                <tr class="BlockUIcolumnBase columnMain">
                    <td>
<?php
                        $selected = "";
                        foreach($categoryTypes as $key => $var) {
                            if(!empty($_GET['categoryListing']) && $key ==  $_GET['categoryListing']) {
                                $selected = "checked";
                            }
                            echo $var."<br />
                                <select name='categoryListing' name='".$key."'>
                                    <option>"._("all main categories")."</option>
                                    <option>only category A</option>
                                    <option>only category B</option>
                                </select>
                                <br /><br />
                                ";
                            $selected = "";
                        }
?>
                        When the certain category is selected it needs to be highlighted. Only one category at a time?<br />
                        And when the user wants information about a specific subCategory, then that can be chosen from the chart-view via ajax.
                    </td>
                    <td>
                        
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="columnBase columnColorHeader">
                            <?= _("choose visual"); ?>
                        </div>
                    </td>
                </tr>
                <tr class="BlockUIcolumnBase columnMain">
                    <td>
<?php
                        $selected = "checked";

                        foreach($chartTypes as $key => $var) {
                            if(!empty($_GET['type']) && $key == $_GET['type']) {
                                $selected = "checked";
                            }
                            echo "<input type='radio' name='type' value='".$key."' ".$selected."/>".$var."<br />";
                            $selected = "";
                        }
?>
                    </td>
                </tr>
            </table>
            <input type="submit" class="blueBtn" value="<?= _('Create chart') ;?>">
	</form>	
    <div style='clear:left;'>
<?php
if((!empty($startDate) && !empty($endDate)) || !empty($fixedTimes)) {
	$rows = array();
	$columns = array();
	$endDate = "";
	$startDate = "";	

	if(!empty($fixedTimes)) {
		switch($fixedTimes) {		
		case "month":		
			$startDateUnix = strtotime($this_month);
			$endDateUnix = strtotime("now");
			break;
		case "year":
			$startDateUnix = strtotime($this_year);
			$endDateUnix = strtotime("now");
			break;
		case "week":
			$startDateUnix = strtotime($this_week);
			$endDateUnix = strtotime("now");
			break;
		case "today":
			$startDateUnix = strtotime($today);
			$endDateUnix = strtotime("now");
			break;
		}
	}
	elseif(strtotime($_GET['startDate']) > strtotime($_GET['endDate'])) {
            exit( _("Incorrect dates. Ending date must be after starting date"));
	}

	$chart = "";	
	$groupedBy = "";
	$selectCategory = "";
	if(!empty($_GET['categoryListing'])) {
            switch($_GET['categoryListing']) {
                    case "main":
                            $groupedBy = "GROUP BY pro.mainCat";
                            $selectCategory = "LEFT JOIN categories cat ON cat.ID = pro.mainCat";
                            break;	
                    case "sub":
                            $groupedBy = "GROUP BY pro.subCat";
                            $selectCategory = "LEFT JOIN categories cat ON cat.ID = pro.subCat";
                            break;
                    case "extra":
                            $groupedBy = "GROUP BY extra.extraCatID";
                            $selectCategory = "LEFT JOIN extraCategoriesInProducts extra ON pro.ID = extra.productID LEFT JOIN categories cat ON cat.ID = extra.extraCatID";
                            break;
                    default:
                            $groupedBy = "GROUP BY pro.mainCat";
                            $selectCategory = "LEFT JOIN categories cat ON cat.ID = pro.mainCat";
                            break;
            }
        }

        $haku = $dataSource->queryWithExceptions("SELECT cat.name, SUM(pro.cost) summed FROM receipts rec LEFT JOIN products pro ON pro.receiptID = rec.ID ".$selectCategory." WHERE rec.time >= '".$startDateUnix."' AND rec.time <= '".$endDateUnix."' AND pro.receiptID = rec.ID AND rec.userID = '".USER_ID."' ".$groupedBy." ORDER BY summed DESC", "fetching chart-information", "fetching chart-information");
        array_push($columns, "'string', 'category'", "'number', 'amount'");

        $forNumbers="";
	while($tiedot = $haku->fetch_row()) {
		array_push($rows, "'".$tiedot[0]."', ".$tiedot[1]);
                $forNumbers = $tiedot[0];
	}
	
	switch ($_GET['type']) {
		case "line":
			$chart['line'] = new Chart($_GET['type'], _('Expenses').": ".date($dateZone, $startDateUnix)
                            ." - ".date($dateZone, $endDateUnix), 400, 300);
			$chart['line']->showLineChart($columns, $rows);
			break;
		case "bar":
			$chart['bar'] = new Chart($_GET['type'], _('Expenses').": ".date($dateZone, $startDateUnix)
                            ." - ".date($dateZone, $endDateUnix), 400, 300);
			$chart['bar']->showBarChart($columns, $rows);
			break;
		case "overview":
		case "pie":
			$chart['pie'] = new Chart($_GET['type'], _('Expenses').": ".date($dateZone, $startDateUnix)
                            ." - ".date($dateZone, $endDateUnix), 400, 300);
			$chart['pie']->showPieChart($columns, $rows);
			if($_GET['type'] == 'pie')
				break;
		case "numbers":
			$chart['numbers'] = new Chart($_GET['type'], _('Expenses').": ".date($dateZone, $startDateUnix)
                            ." - ".date($dateZone, $endDateUnix), 400, 300);
			$chart['numbers']->showNumbers($rows);
			break;
	}
}

// functions for the page:
function createDefaultTime ($var) {
    $var = preg_replace('/[^\d]/i','', $var);
    return (substr($var, 0, 2).".".substr($var, 2, 2).".".substr($var, 4, 4));
}

?>
	</div>
    </div>
</div>
<script>
    var form = document.forms['charts'];
    var start = new Array();
    var end = new Array();
<?php
        // ---- Generate javascript variables:
        foreach($fixedTimeTypes as $key => $var) {
            $helpVar = "this_".$key;
            echo "start['".$key."'] = '".date($dateZone, strtotime($$helpVar))."';".PHP_EOL;
            echo "end['".$key."'] = '".date($dateZone)."';".PHP_EOL;
        }
        // ---- 
?>
    function fixedTimeSelected() {
        for(var looper in start) {
            if(form.elements["fixedTimes"].value == looper) {
                form.elements["startDate"].value = start[looper];
                form.elements["endDate"].value = end[looper];
            }            
        }
    }
</script>