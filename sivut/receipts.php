<?php
// If the page is being accessed directly and not through index.php, then stop it.
// Also we really require the settings-variable, since that is the core-libraries
// Later we can show something else, than just kill it.
if(!$settings)
	exit();

$classArr = array("receipts.class", "categories.class");
foreach ($classArr as $filename) {
    require_once $settings->PATH['classes'] .  $filename . '.php';
}
require_once $settings->PATH['libraries']."KIRJASTOHTMLFunctions.php";
include_once $settings->PATH['libraries']."pagination.class.php";

$HTMLFunctions = new HTMLFunctions();

$pages = new pagination();
$pages->paginationLeft = 3;
$pages->paginationRight = 3;
$pages->path = 'receipts/?page=[pageno]';

$pages->appearance = array(
                    'nav_prev' => '<a href="[link]" class="prev"><span> prev </span></a>',
                    'nav_number_link' => '<a href="[link]"><span> [number] </span></a>',
                    'nav_number' => '<a href="javascript:;" class="active"><span> [number] </span></a>',
                    'nav_more' => '<a href="javascript:;" class="more"><span>...</span></a>',
                    'nav_next' => '<a href="[link]" class="next"><span> next </span></a>',
                );
// items count
$kysely = $dataSource->queryWithExceptions("SELECT re.ID, re.time, SUM(pro.cost) as cost, re.place, re.info, SUM(pro.warrantyTill), cat.name as mainCat FROM receipts re LEFT JOIN products pro ON pro.receiptID = re.ID LEFT JOIN categories cat ON cat.ID = pro.mainCat WHERE re.userID = '".USER_ID."' GROUP BY re.ID", "Fecthing receipts from DB");
$rowCount = $kysely->num_rows;
$pages->setCount($rowCount); 
// current page
if(isset($_GET['page'])){
    $pages->setStart($_GET['page']);
}

// we do the requested changes to the receipts. ATM it means deletes:
if(!empty($_POST['doIt']) && !empty($_POST["deleteID"])) {
    foreach($_POST["deleteID"] as $receiptID => $receiptCheck) {

        $ID = $dataSource->filterVariable($receiptID);

        // Removing extraCategoies
        $productIDQuery = $dataSource->queryWithExceptions("SELECT id FROM products WHERE products.receiptID = '".$receiptID."'");
        while($productID = $productIDQuery->fetch_row()) {
            $haku = "DELETE FROM extraCategoriesInProducts WHERE extraCategoriesInProducts.productID = '".$productID[0]."'";
            $dataSource->queryWithExceptions($haku);
        }

        // Removing products
        $haku = "DELETE FROM products WHERE receiptID = '".$ID."'";
        $dataSource->queryWithExceptions($haku);

        // Removing receipt
        $haku = "DELETE FROM receipts WHERE receipts.ID = '".$ID."'";
        $dataSource->queryWithExceptions($haku);
    }
}
?>
Showing receipts: <?= $pages->getMySqlLimitStart() ;?> - <?= ($pages->getMySqlLimitStart()+$pages->getMySqlLimitEnd()) ;?>
<?php
$tulos = "";
$kysely = $dataSource->queryWithExceptions("SELECT re.ID, re.time, SUM(pro.cost) as cost, re.place, re.info, SUM(pro.warrantyTill) as warrantySum, cat.name as mainCat FROM receipts re LEFT JOIN products pro ON pro.receiptID = re.ID LEFT JOIN categories cat ON cat.ID = pro.mainCat WHERE re.userID = '".USER_ID."' GROUP BY re.ID ORDER BY re.time DESC, pro.leftOver DESC LIMIT ".$pages->getMySqlLimitStart().",".$pages->getMySqlLimitEnd(), "Fecthing receipts from DB");

?>
<script type="text/javascript">
    var receiptForm = $("form[name=newReceipt]"),
    <?php // We need to set correct countryTag for javascript-functions also: ?>
    countryTag = "<?= "en" ;?>";
</script>

<div class='eventBlocks'>
    <form method="POST" action="#">
<?php
    $id = NULL;
    $foundReceipts = 0;
    while(($tulos = $kysely->fetch_assoc())) {
        $foundReceipts = 1;
?>      <table class="columnBase receiptListing">
            <tr>
<?php
            if($tulos['ID'] != $id) {
                $id = $tulos[ID];
                $subCat = $tulos['subCat'] ? _('sub category').":<b>".$tulos['subCat']."/b><br />" : "";
                $extraCats = $tulos['extrafile:///home/janne/productpage.html#Cat'] ? _('extra categories').":<b>".$tulos['extraCats']."/b><br />" : "";
?>
                <td class="columnFirstChild">
                    <label><?= date("d.m.y", $tulos['time']) ;?>:</label>
                    <a href='#' onClick="showOrRemove('hidden_<?= $tulos['ID'] ;?>');"><?= (float)$tulos['cost']."&nbsp;EUR" ;?></a>
                </td><td class="columnSecondChild">
                    Poista:<input type="checkbox" name="deleteID[<?= $tulos['ID'] ;?>]">
                </td>
            </tr>
            <tr>
                <td class="columnFirstChild hidden" id="hidden_<?= $tulos['ID'] ;?>">
                    <?= _('main category').":<b>".$tulos['mainCat'] ;?></b><br />
                    <?= $subCat ;?>
                    <?= $extraCat ;?>
                    <?= _('place').":<b>".$tulos['place'] ;?></b><br />
                    <?= _('info').":<b>".$tulos['info']; ?></b><br /><br />
<?php
            }
            $tuoteHaku = "
                SELECT pro.name as name, pro.cost, pro.info, mainCat.name as mainCat, subCat.name as subCat 
                    FROM products pro 
                        LEFT JOIN categories mainCat ON mainCat.ID = pro.mainCat 
                        LEFT JOIN categories subCat ON subCat.ID = pro.subCat 
                            WHERE pro.receiptID = '".$id."' 
                                AND leftOver != 1";
            $tuoteQ = $dataSource->queryWithExceptions($tuoteHaku);
            $notDoneYet = 0;
            while($tuotteet = $tuoteQ->fetch_assoc()) {
                if(!$notDoneYet) {
                    $notDoneYet = 1;
                    echo "<b>"._('show products')."</b><br />";
                }
                if($tuotteet['name']) {
                    $tuotetieto = $tuotteet['name']." - ".$tuotteet['cost']." EUR <br />
                        Pääkategoria: ".$tuotteet['mainCat']."<br />
                        Sivukategoria: ".$tuotteet['subCat']."<br />
                        Lisätietoa: ".$tuotteet['info'];
                }
                echo $tuotetieto."<br />";
            }
?>
                    <button type='button' class='modifyReceipt' data-rcpt_id='<?= $tulos['ID'] ;?>'><?= _('Modify receipt'); ?></button>
                </td>
            </tr>
        </table>
    <?php
    }
    if($foundReceipts == 1) {
        // true to echo pagination
        echo "Pages:";
        $pages->display(true);
?>      <br /><input type='submit' name='doIt' value ='DO IT!'>   <?php
    }
    else {
?>      <br />
        No receipts found
        <br /><br />
<?php 
    }
?>
    </form>
    
</div>
<div class="baseOverlayDiv hidden" id="clonableReceiptDiv">
    <div>
        <?= _("modifying a receipt") ;?>
    </div>
    <table>
        <input type="hidden" name="receiptID" />
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label><?= _("Time") ;?>:</label>
            </td>
            <td class="columnSecondChild">
                <input type="text" name="receiptDate" />
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label><?= _("Receipt cost") ;?>:</label>
            </td>
            <td class="columnSecondChild">
                <input type="text" name="receiptCost" />
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label><?= _("Main category") ;?>:</label>
            </td>
            <td class="columnSecondChild">
                <select name="receiptMainCat"></select>
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label><?= _("Sub category") ;?>:</label>
            </td>
            <td class="columnSecondChild">
                <select name="receiptSubCat"></select>
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label><?= _("Extra categories") ;?>:</label>
            </td>
            <td class="columnSecondChild">
                <select name="receiptExtraCats" multiple="multiple"></select>
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label><?= _("Place") ;?>:</label>
            </td>
            <td class="columnSecondChild">
                <input type='text' name="receiptPlace" />
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label><?= _("Information") ;?>:</label>
            </td>
            <td class="columnSecondChild">
                <textarea name="receiptInfo"></textarea>
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label><?= _("Products") ;?>:</label>
            </td>
            <td class="columnSecondChild receiptProducts">
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <input type="button" value="<?= _("Modify") ;?>" name="modifyReceipt" />
                <button class="addingBtnNo"><?= _("Cancel") ;?></button>
            </td>
        </tr>
    </table>
</div>