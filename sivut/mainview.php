<?php
/* If the page is being accessed directly and not through index.php, then stop it.
 * Also we really require the settings-variable, since that is the core-libraries
 * Later we can show something else, than just kill it.
 */
if (!$settings)
    exit();

$classArr = array("categories.class", "receipts.class", "places.class", "warranties.class");
foreach ($classArr as $filename) {
    require_once $settings->PATH['classes'] . $filename . '.php';
}

require_once $settings->PATH['libraries'] . "KIRJASTOHTMLFunctions.php";

$HTMLFunctions = new HTMLFunctions();

// =============================================================================
// THE form for inserting new receipt:
// =============================================================================

$mainCatObj = new MainCategory($dataSource, USER_ID);
$extraCatObj = new ExtraCategory($dataSource, USER_ID);
$placesObj = new Places($dataSource, USER_ID);

$showExtraOptions['class'] = " class='hidden'";
$showExtraOptions['text'] = _("Always show extra options");
$showExtraOptions['dataValue'] = "0";
if($userSettings->getShowExtraOptions() == "1") {
    $showExtraOptions['class'] = "";
    $showExtraOptions['text'] = _("Don't show extra options");
    $showExtraOptions['dataValue'] = "1";
}

$defaultDate = "";
$defaultDateOn = "";

if ($userSettings->getShowDefaultDate()) {
    $defaultDate = date("d.m.Y", strtotime("now"));
    $defaultDateOn = "checked";
}
?>

<div id="tabs">
    <ul>
        <li>
            <a href='#tabs-1' class="tabFonts" id="tabSelector1">
                <?= _('New receipt'); ?>
            </a>
        </li>
        <li>
            <a href='<?= $settings->PATH['site']; ?>ajax/ajax_oldReceipts.php' class="tabFonts" id="tabSelector2">
                <?= _('Old receipts'); ?>
            </a>
        </li>
    </ul>
<?php // ==== This is the form for adding a new receipt ==== ?>
    <form method='POST' action="" name="newReceipt">
        <div class='contentInnerBlock' id="tabs-1">
            <div class="columnBase columnColorHeader">
                <?= _("Mandatory"); ?>
            </div>
            <table class="columnBase columnMain compulsory">
                <tr>
                    <td class="columnFirstChild">
                        <?= _('Total cost'); ?>
                    </td>
                    <td class="columnSecondChild">
                        <input type='text' class="receiptCost" name='receiptCost'>
                    </td>
                </tr>
                <tr>
                    <td class="columnSecondChild">
                        <?= _('Date'); ?>
                    </td>
                    <td class="columnSecondChild">
                        <input type="text" class="datepicker" name="receiptDate" value="<?= $defaultDate; ?>" />
                        <input type="checkbox" name="showDefaultDate" <?= $defaultDateOn; ?> title="show today as default date">
                    </td>
                </tr>
                <tr>
                    <td class="columnFirstChild">
                        <?= _('Main category'); ?>
                    </td>
                    <td class="columnSecondChild showModifyImages">
                        <?= ($show = $HTMLFunctions->doSelect($mainCatObj
                                    , "mainCatID"
                                    , _('no category')
                                    , " class='mainCatID'")) 
                                ? $show : _("No categories created");
                        ?>
                        <img class="modify addCat hidden" data-action="add" data-type="cat" data-specify="main" src="images/plus_blue.png" title="new main category" />
                        <img class="modify modifyCat hidden" data-action="del" data-type="cat" data-specify="main" src="images/del.png" title="delete category" />
                    </td>
                </tr>
                <tr class="hidden" id="subCatTR">
                    <td class="columnFirstChild">
                        -> <?= _('Sub category'); ?>
                    </td>
                    <td class="columnSecondChild showModifyImages" id="subCatDIV">
                        <select name='subCatID'>
                            <option value='0'><?= _('Select main category'); ?></option>
                            <?php // We create the subCategory HTML in the end with javascript. As innerHTML for this DIV.  ?>
                        </select>
                        <img class="modify addCat hidden" data-action="add" data-type="cat" data-specify="sub" src="images/plus_blue.png" title="new sub category" />
                        <img class="modify modifyCat hidden" data-action="del" data-type="cat" data-specify="sub" src="images/del.png" title="delete category" />
                    </td>
                </tr>
            </table>
            <table>
                <tr>
                    <td class="leftTDBUttons">
                        <button class="greyBtn" type="button" id="extraChoices"><?= _('Extra options'); ?></button>
                    </td>
                    <td></td>
                    <td class="rightTDBUttons">
                        <input type="submit" class="blueBtn" name="insertNewReceipt" id="insertNewReceipt" value='<?= _('Save receipt'); ?>'>
                        <input type="reset" class="redBtn" id="resetReceipt" value="<?= _('Reset receipt'); ?>">
                    </td>
                </tr>
            </table>
            <div<?= $showExtraOptions['class']; ?> id="extraOptionsDiv">
                <hr />
                <div class="groups">
                    <div class="extraOptionsDiv" ><span id="extraOptionsDiv" data-value="<?= $showExtraOptions['dataValue']; ?>"><?= $showExtraOptions['text']; ?></span>
                    </div>
                    <table class="columnBase columnMain">
                        <tr>
                            <td class="columnFirstChild">
                                <?= _('Extra category'); ?>
                            </td>
                            <td class="columnSecondChild showModifyImages" id="extraCatDiv">
                                <?= ($show = $HTMLFunctions->doSelect($extraCatObj, 'extraCatIDs', null, " multiple")) ? $show : _("No categories created");
                                ?>
                                <img class="modify addCat hidden" data-action="add" data-type="cat" data-specify="extra" src="images/plus_blue.png" title="new category" />
                                <img class="modify modifyCat hidden" data-action="del" data-type="cat" data-specify="extra" src="images/del.png" title="delete category" />
                            </td>
                        </tr>
                        <tr>
                            <td class="columnFirstChild">
                                <?= _('Place'); ?>
                            </td>
                            <td class="columnSecondChild showModifyImages">
                                <input type="text" id="place" name="place" onkeydown="changeInputValue('premadePlace', 'value')" />
                                <?= ($show = $HTMLFunctions->doSelect($placesObj, "premadePlace", _('Or choose')))
                                ? : "";
                                ?>
                                <img class="modify addCat hidden" data-action="add" data-type="place" src="images/plus_blue.png" title="new place" />
                                <img class="modify modifyCat hidden" data-action="del" data-type="place" src="images/del.png" title="delete place" />
                            </td>
                        </tr>
                        <tr>
                            <td class="columnFirstChild">
    <?= _('Extra information'); ?>
                            </td>
                            <td class="columnSecondChild">
                                <textarea name="info"></textarea>
                            </td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <td class="leftTDBUttons">
                                <button class="greyBtn" type="button" id="addProductBtn"><?= _('Add procucts'); ?></button>
                            </td>
                            <td></td>
                            <td class="rightTDBUttons">
                                <input type="submit" class="blueBtn" name="insertNewReceipt" id="insertNewReceipt" value='<?= _('Save receipt'); ?>'>
                                <input type="reset" class="redBtn" value="<?= _('Reset receipt'); ?>">
                            </td>
                        </tr>
                    </table>
                    <div id="productList">
    <?php // The separate products will be shown and added here  ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php // Remember that we loaded the jquery and maybe something else already at the top (index.php)  ?>

<script type="text/javascript">
<?php // We need to set correct countryTag for javascript-functions also:  ?>
    var countryTag = "<?= strtolower($shortCountryTag); ?>";
</script>
<?php
// THESE ARE THE DEFAULT HIDDEN DIVs, THAT ARE SHOWN AND CLONED BY JAVASCRIPT IN CERTAIN EVENTS:
?>

<div class="baseOverlayDiv hidden" id="clonableProductList">
    <table>
        <tr>
            <td class="columnBase columnFirstChild">
                <?= _("Product"); ?>:
            </td>
            <td class="columnBase columnSecondChild">
                <span></span>
                <input type="hidden" name="productName" />
            </td>
        </tr>
        <tr>
            <td class="columnBase columnFirstChild">
                <?= _("Price"); ?>:
            </td>
            <td class="columnBase columnSecondChild">
                <span></span>
                <input type="hidden" name="productCost" />
            </td>
        </tr>
        <tr>
            <td class="columnBase columnFirstChild">
                <?= _("Main category"); ?>:
            </td>
            <td class="columnBase columnSecondChild">
                <span></span>
                <input type="hidden" name="productMainCatID" />
            </td>
        </tr>
        <tr>
            <td class="columnBase columnFirstChild">
                <?= _("Sub category"); ?>:
            </td>
            <td class="columnBase columnSecondChild">
                <span></span>
                <input type="hidden" name="productSubCatID" />
            </td>
        </tr>
        <tr>
            <td class="columnBase columnFirstChild">
                <?= _("Extra categories"); ?>:
            </td>
            <td class="columnBase columnSecondChild">
                <span></span>
                <input type="hidden" name="productExtraCatIDs" />
            </td>
        </tr>
        <tr>
            <td class="columnBase columnFirstChild">
                <?= _("Warranty"); ?>:
            </td>
            <td class="columnBase columnSecondChild">
                <span></span>
                <input type="hidden" name="productWarranty" />
            </td>
        </tr>
        <tr>
            <td class="columnBase columnFirstChild">
                <?= _("Extra information"); ?>:
            </td>
            <td class="columnBase columnSecondChild">
                <span></span>
                <input type="hidden" name="productInfo" />
            </td>
        </tr>
        <tr>
            <td>
                <img class="deleteProduct" src="images/modify.png" title="delete product" />
                <hr>
            </td>
        </tr>
    </table>
</div>
<div class="baseOverlayDiv hidden" id="addingProduct">
    <div>
        <?= _("Adding a product"); ?>:
    </div>
    <table>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <?= _("Name"); ?>:
            </td>
            <td class="columnSecondChild">
                <input type="text" id="productName" name="addProd_name" value="" onkeydown="changeInputValue('#addingProduct select[name=premadeProduct]', 'val')" />
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <?= _("Price"); ?>:
            </td>
            <td class="columnSecondChild">
                <input type="text" id="productCost" name="addProd_cost" value="" onkeydown="changeInputValue('#addingProduct select[name=premadeProduct]', 'val')" />
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <?= _("Main category"); ?>:
            </td>
            <td class="columnSecondChild">
                <?= ($show = $HTMLFunctions->doSelect($mainCatObj, "addProd_mainCatID", _('no category'))) ? $show : _("No categories created");
                ?>
            </td>
        </tr>
    <table class="columnBase columnMain compulsory">
        <tr>
            <td class="columnFirstChild">
                <?= _("Choose"); ?>:
            </td>
            <td class="columnSecondChild">
                <select name='addProd_subCatID'>
                    <option value='0'><?= _("Select main category"); ?>:</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="columnFirstChild">
                <label for="addingProduct_extraCat"><?= _("Extra category"); ?></label>
            </td>
            <td class="columnSecondChild">
                <?= ($show = $HTMLFunctions->doSelect($extraCatObj, 'addProd_extraCatIDs', null, " multiple id='addingProduct_extraCat'")) ? $show : _("No categories created");
                ?>
            </td>
        </tr>
        <tr>
            <td class="columnFirstChild">
                <?= _("Warranty"); ?>
            </td>
            <td class="columnSecondChild">
                <select name="addProd_warranty">
                    <?= Warranty::createWarrantyOptions("addProd_warranty"); ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="columnFirstChild">
                <?= _("Extra information"); ?>
            </td>
            <td class="columnSecondChild">
                <textarea class="textareas" name="addProd_info"></textarea>
            </td>
        </tr>
        <tr>
            <td>
                <input id="addingProductBtnYes" type="button" value="lisää" />
                <button class="addingBtnNo">Peru</button>
            </td>
        </tr>
    </table>
</div>
<div class="baseOverlayDiv hidden addCat" id="addCat">
    <div>
<?= _("Adding category"); ?>
    </div>
    <table>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label for="addCat_catName"><?= _("Name"); ?></label>
            </td>
            <td class="columnSecondChild">
                <input type="text" id="addCat_catName" name="catName" />
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain addCat_parentCatSelector hidden">
            <td class="columnFirstChild">
                <label for="addCat_parentCat"><?= _("Main category"); ?></label>
            </td>
            <td class="columnSecondChild addCat_parentCat">
                <select name="parentCat"></select>
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label for="addCat_catType"><?= _("Type"); ?></label>
            </td>
            <td class="columnSecondChild catTypeDiv">
                <span></span>
                <input type="hidden" id="addCat_catType" name="catType" value="0">
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <input type="button" value="lisää" name="addCat" />
                <button class="addingBtnNo"><?= _("Cancel"); ?></button>
            </td>
        </tr>
    </table>
</div>
<div class="baseOverlayDiv hidden addPlace" id="addPlace">
    <div>
        <?= _("Adding a place"); ?>
    </div>
    <table>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <label for="addPlace_placeName"><?= _("Place"); ?></label>
            </td>
            <td class="columnSecondChild">
                <input type="text" name="placeName" />
            </td>
        </tr>
        <tr class="BlockUIcolumnBase columnMain">
            <td class="columnFirstChild">
                <input type="button" value="lisää" name="addPlace" />
                <button class="addingBtnNo"><?= _("Cancel"); ?></button>
            </td>
        </tr>
    </table>
</div>
<div class="growlSuccess growlNewReceipt hidden">
    <table>
        <tr>
            <td>
                <b>
                    <?= _("Receipt inserted succesfully"); ?>
                </b>
            </td>
        </tr>
        <tr>
            <td>
                <?= _("If you wish to insert a new receipt with the same main information, edit the details. For a totally new receipt, please 'reset receipt'"); ?>
            </td>
        </tr>
    </table>
</div>
<div class="growlSuccess growlDelReceipt hidden">
    <table>
        <tr>
            <td>
                <b><?= _("Receipt deleted succesfully"); ?></b>
            </td>
        </tr>
    </table>
</div>