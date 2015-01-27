$(document).ready(function(){

    /* Global "contants": */
    var receiptForm = $("form[name=newReceipt]"),
        mainCatNr = 1,
        subCatNr = 3,
        extraCatNr = 2,
        LIST_ALL = 1,
        LIST_INDIVIDUAL = 2,
        lineBreak = "<br>",
        countryTag = "fi",
        paymentMethods = new Array();
            paymentMethods[1] = texts.cash;
            paymentMethods[2] = texts.bankCard;
            paymentMethods[3] = texts.creditCard;
            paymentMethods[4] = texts.other;

    /* ==== VARIABLES ==== */
    var $element = $('#addingProduct'),
        top = ($(window).height() / 2) - ($element.outerHeight() / 2);

    /* ==== DEFAULTS ==== */
    $.blockUI.defaults = {
        theme: true, // set to true to use with jQuery UI themes 
        title: 'Window',        // title string; only used when theme == true 
        draggable: true,    // only used when theme == true (requires jquery-ui.js to be loaded) 
        showOverlay: true,

        // Themes in use
        themedCSS: { 
            border: '0px',
            'box-shadow': '6px 6px 14px #D4D5F0',
            '-webkit-box-shadow': '6px 6px 14px #D4D5F0',
            '-moz-box-shadow': '6px 6px 14px #D4D5F0',
            left: ($(document).width() / 2) - ($element.outerWidth() / 2),
            top: (top < 158) ? top : 158,
            opacity: 0.98,
            width: 600
        }, 

        overlayCSS: {
            backgroundColor: '#FFFFFF',
            cursor: 'wait',
            opacity: 0.6
        },

        // z-index for the blocking overlay 
        baseZ: 1000,

        fadeIn:  200, 
        fadeOut:  400

    };
    $("#datepicker").datepicker($.datepicker.regional[countryTag]);
    /* === jQuery function === */

    $("#addAnotherCost").click(function() {
        $(this).parent().append("<br /><input name='receiptCost'></input>");
    });

    $("select[name=premadePlace]").change(function() {
        receiptForm.find("input[name=place]").val(receiptForm.find("select[name=premadePlace] option:selected").text());
    });
    $("input[name=place]").click(function() {
        $(this).val("");
    });

    $("#showAlways").click(function()
    {
        $this = $(this);
        if($this.data("value") == "1") {
            changeSettingByAjax(0);
            $("#showAlways").text("näytä aina lisävalinnat");
            $this.data("value", "0");
        } else {
            changeSettingByAjax(1);
            $("#showAlways").text("älä näytä aina lisävalintoja");
            $this.data("value", "1");
        }

        function changeSettingByAjax(value)
        {
            $.ajax({
                type: 'POST',
                url: 'ajax/ajax_userSettings.php',
                data: ({showExtraDetails:value}),
                error:function(){
                    console.log("Error in ajaxReq: "+this.url+" , "+this.data);
                    alert("Error on request");
                }
            });
        }
    });
    $("#addProductBtn").click(function() {
        var $element = $('#addingProduct');
        $.blockUI({
            message: $element
        });
    });

    $("#addingProductBtnYes").click(function() {
        var toClone = $("#clonableProductList table").clone();
        
        var formi = $("#addingProduct");
        var productArray = new Array();

        /* Process extraCatIDs: */
        /* IDs */
        var extraCatIDs = new Array();
        formi.find('select[name=addProd_extraCatIDs] option:selected').each(function() {
                extraCatIDs.push($(this).val());
            });
        /* Names */
        var extraCatText = new Array();
        formi.find('select[name=addProd_extraCatIDs] option:selected').each(function() {
                extraCatText.push($(this).text());
            });

        productArray["texts"] = new Array(
            formi.find('input[name=addProd_name]').val(), 
            formi.find('input[name=addProd_cost]').val(), 
            formi.find('select[name=addProd_mainCatID] option:selected').text(),
            formi.find('select[name=addProd_subCatID] option:selected').text(),
            extraCatText.join(","),
            formi.find('select[name=addProd_warranty] option:selected').text(),
            formi.find('input[name=addProd_VAT]').val(),
            formi.find('textarea[name=addProd_info]').val(),
            formi.find('input[name=addProd_newPremadeProduct]').attr('checked')
        );
        productArray["values"] = new Array(
            formi.find('input[name=addProd_name]').val(), 
            formi.find('input[name=addProd_cost]').val(), 
            formi.find('select[name=addProd_mainCatID] option:selected').val(),
            formi.find('select[name=addProd_subCatID] option:selected').val(),
            extraCatIDs.join(","),
            formi.find('select[name=addProd_warranty] option:selected').val(),
            formi.find('input[name=addProd_VAT]').val(),
            formi.find('textarea[name=addProd_info]').val(),
            formi.find('input[name=addProd_newPremadeProduct]').attr('checked')
        );

        newProductAdded(toClone, productArray);
        
        toClone.show();
        toClone.appendTo($("div#productList"));
        $.unblockUI();
    });
    $("body").on('click', 'button.addingBtnNo', function() {
        $.unblockUI();
    });
    $(".deleteProduct").click(function () {
        closest("div").remove();
    });
    
    $("body").on('click', 'img.modify', function(){
    
        var $this = $(this);
   
        switch($this.data("action")) {
            case "add":
                switch ($this.data("type")) {
                    case "cat":
                        addCat();
                        break;
                    case "place":
                        addPlace();
                        break;
                }
                break;
            case "del":
               switch ($this.data("type")) {
                    case "cat":
                        delCat($this);
                        break;
                    case "place":
                        delPlace($this);
                        break;
                    case "prod":
                        delProd($this);
                        break;
                }
                break;
        }

        function addCat () {
            var element = $("div#addCat").clone().attr("id", "addCatCloned");
            
            // We set the default-values with these IFs
            if($this.data("specify") == "main") {
                element.find("td#catTypeDiv span").text("Maincategory").siblings("input").val("1");
            } else if ($this.data("specify") == "sub") {
                element.find("td#catTypeDiv span").text("Subcategory").siblings("input").val("3");
                
                var returnedOptions = {};
                var valueMap = {
                    "listing":LIST_ALL,
                    "type":"1"
                }
                
                getUpdatedPart("categories", valueMap)
                    .success(function (response) {
                        returnedOptions = response;
                    });
                
                $.each(returnedOptions, function(i, item) {
                    item.text = item.name;
                    item.value = item.ID;
                });

                var parentEle = element.find("tr.addCat_parentCatSelector");
                parentEle.show();
                parentEle.find("td.addCat_parentCat")
                    .html(
                        libraries.createSelectElement(
                            returnedOptions, ({name:"parentCat"})
                        )
                    );
            } else if ($this.data("specify") == "extra") {
                element.find("td#catTypeDiv span").text("Extracategory").siblings("input").val("2");
            }
            
            $.blockUI({
                message: element
            });
        }
        function delCat (justThis) {
            var optionElements = $(justThis).parent().find("select option:selected");
            if(!isInt(optionElements.val())) {
                alert("fail. You picked several categories");
                return false;
            }
            
            var catTypeValue = 0;
            if($this.data("specify") == "main") {
                catTypeValue = 1;
            } else if ($this.data("specify") == "sub") {
                catTypeValue = 3;
            } else if ($this.data("specify") == "extra") {
                catTypeValue = 2;
            }
            
            var ID = optionElements.val();
            var optionText = optionElements.text();
            var element = $("<div style='text-align:left;'>Are you sure you want to delete category: <b>"+optionText+"</b><br /><br /><input type='hidden' name='catID' value='"+ID+"' /><input type='hidden' name='catType' value='"+catTypeValue+"' /><input type='submit' name='delCat' value='Yes' /><button class='addingBtnNo'>No</button></div>")
                .attr("id", "delCatCloned");

            $.blockUI({
                message: element
            });
        }
        function addPlace () {
            var element = $("div#addPlace").clone().attr("id", "addPlaceCloned");
            
            $.blockUI({
                message: element
            });
        }
        function delPlace (justThis) {
            var optionElements = $(justThis).parent().find("select option:selected");
            var ID = optionElements.val();
            var optionText = optionElements.text();

            var element = $("<div style='text-align:left;'>Are you sure you want to delete place: <b>"+optionText+"</b><br /><br /><input type='hidden' name='placeID' value='"+ID+"' /><input type='submit' name='delPlace' value='Yes' /><button class='addingBtnNo'>No</button></div>")
                .attr("id", "delPlaceCloned");
            
            $.blockUI({
                message: element
            });
        }
        function delProd () {
            confirm("Delete product?");
        }
    });

    $("body").on('click', 'img.deleteProduct', function(){
        $(this).closest("table").remove();
    });

    $(receiptForm).on('submit',function(){
        receiptToAjax ("new");
        resetForm(document.newReceipt);
        return false;
    });
    
    var placeSelected = 0;
    $("#place").click(function() {
        if(placeSelected == 0) {
            $("#place").val("");
        }
        placeSelected = 1;
    });

    $("form[name=newReceipt] select[name=mainCatID]").on('change', function() {
        doMainCatChanged($("form[name=newReceipt] select[name=subCatID]"), $(this));
    });
    $("div#addingProduct select[name=addProd_mainCatID]").on('change', function() {
        doMainCatChanged($("div#addingProduct select[name=addProd_subCatID]"), $(this));
    });

    $("span.link#extraChoices").on('click', function() {
        $('div#extraOptionsDiv').toggle();
    });

    $("form[name=newReceipt] select[name=subCatID]").on('change', function() {
        doSubCatChanged('subCatID');
    });

    $("div#addingProduct select[name=addProd_premadeProduct]").on('change', function() {
        var ID = $(this).children("option:selected").val();
        premadeProductSelected(ID);
    });

    $("body").on("click", "input[name=addCat]", function() {

        var $this = $("div#addCatCloned");
        var catType = $this.find("input[name=catType]").val();
        var possibleParentCat = $this.find("select[name=parentCat]").val()
        var valueMap = {
            toDo:"new",
            name:$this.find("input[name=catName]").val(),
            type:catType,
            parentCat:possibleParentCat
        };
        var updateFunc = (function (resp) {
            var selectedCat = resp;
            
            // Update selected option in main category-listing, if subCategory was added
            if(possibleParentCat) {
                $("form[name=newReceipt] select[name=mainCatID]").val(possibleParentCat);
            }
            updateCategories(catType, selectedCat, possibleParentCat);
        });

        ajaxReq("categories", valueMap, $this, updateFunc);
    });
    $("body").on("click", "input[name=delCat]", function() {
        var $this = $("div#delCatCloned");

        var valueMap = {
            toDo:"del",
            ID:$this.children("input[name=catID]").val()
        };
        var updateFunc = (function () {
            var type = $this.find("input[name=catType]").val();
            updateCategories(type);
        });
        ajaxReq("categories", valueMap, $this, updateFunc);
    });
    $("body").on("click", "input[name=addPlace]", function() {
        var $this = $("div#addPlaceCloned");

        var valueMap = {
            toDo:"new",
            name:$this.find("input[name=placeName]").val()
        };
        var updateFunc = (function () {
            updatePlaces();
        });
        ajaxReq("places", valueMap, $this, updateFunc);
    });
    $("body").on("click", "input[name=delPlace]", function() {
        var $this = $("div#delPlaceCloned");

        var valueMap = {
            toDo:"del",
            ID:$this.find("input[name=placeID]").val()
        };
        var updateFunc = (function () {
            updatePlaces();
        });
        ajaxReq("places", valueMap, $this, updateFunc);
    });

    var isSelectedEarlier = [];
    $("#extraCatDiv select[name=extraCatIDs] option").on('click', function() {
        var $this = $(this);
        var $parent = $("#extraCatDiv select[name=extraCatIDs]");
        if($this.prop("selected")) {
            if(isSelectedEarlier[$this.val()]) {
                $this.prop("selected", false);
                isSelectedEarlier[$this.val()] = false;
            } else {
                isSelectedEarlier[$this.val()] = true;
            }
        }
        $parent.children("option").each(function() {
            var $this2 = $(this);
            if($this2.prop("selected") == true) {
                isSelectedEarlier[$this2.val()] = true;
            } else {
                isSelectedEarlier[$this2.val()] = false;
            }
        });
    });

/* === Receipt-page === */

    /* This monstrosity is responsible for opening up the blockUI-view for showing 
     * and letting the user to modify the specific receipts and their info.
     */
     
    $("body").on("click", "button.modifyReceipt", function() {
        var $this = $(this),
            receiptID = $this.data("rcpt_id"),
            returnedJSON = {},
            toClone = $('#clonableReceiptDiv').clone(),
            tableToSearchFrom = toClone.find("table"),
            valueMap = {
                "listing":LIST_INDIVIDUAL,
                "ID":receiptID
            };

        getUpdatedPart("receipts", valueMap)
            .success(function(response) {
                returnedJSON = response;
            });

        /* Get the whole list for different categories: */
        var cats = categories.getAll(returnedJSON.products[0].mainCat.ID);

        tableToSearchFrom.find("input[name=receiptID]")
            .val(returnedJSON.ID);
        tableToSearchFrom.find("input[name=receiptDate]")
            .val(returnedJSON.date)
            .datepicker($.datepicker.regional[countryTag]);
        tableToSearchFrom.find("input[name=receiptCost]")
            .val(returnedJSON.cost);
        tableToSearchFrom.find("select[name=receiptMainCat]")
            .html(
                libraries.createSelectElement(
                    cats.mainCategories
                    , {onlyOptions:true}
                    , new Array("ID", "name", null)
                    , new Array(returnedJSON.products[0].mainCat.ID)
                )
            );
        tableToSearchFrom.find("select[name=receiptSubCat]").
            html(
                libraries.createSelectElement(
                    cats.subCategories
                    , {onlyOptions:true}
                    , new Array("ID", "name", null)
                    , new Array(returnedJSON.products[0].subCat.ID)
                )
            );
        var selectedExtraCats = new Array();
        for(keyki in returnedJSON.products[0].extraCats) {
            selectedExtraCats.push(keyki);
        };
        tableToSearchFrom.find("select[name=receiptExtraCats]").
            html(
                libraries.createSelectElement(
                    cats.extraCategories
                    , {onlyOptions:true}
                    , new Array("ID", "name", null)
                    , selectedExtraCats
                )
            );
        tableToSearchFrom.find("input[name=receiptPlace]").
            val(returnedJSON.place);
        tableToSearchFrom.find("select[name=receiptPaymentMethod]").
            html(
                libraries.createSelectElement(
                    paymentMethods
                    , {onlyOptions:true}
                    , new Array(null, null, true)
                    , returnedJSON.paymentMethodID
                )
        
            );
        tableToSearchFrom.find("textarea[name=receiptInfo]").
            html(returnedJSON.info);

        var productsHTML = "";
        for(key in returnedJSON.products) {
            // We skip the leftOver array, since we list that to the receipts information
            if(returnedJSON.products[key].leftOver == 1) {
                continue;
            }

            selectedExtraCats = new Array();
            for(keyki in returnedJSON.products[key].extraCats) {
                selectedExtraCats.push(keyki);
            };
            
            var prodID = returnedJSON.products[key].productID;
            productsHTML += "<div data-prod_id='"+prodID+"'>";
            productsHTML += 
                texts.name+": <input type='text' name='productName' value='"+returnedJSON.products[key].name+"' />"+lineBreak+
                texts.cost+": <input type='text' name='productCost' value='"+returnedJSON.products[key].cost+"' />"+lineBreak+
                texts.mainCategory+
                    libraries.createSelectElement(
                        cats.mainCategories
                        , {name:'productMainCat'}
                        , new Array("ID", "name", null)
                        , new Array(returnedJSON.products[key].mainCat.ID)
                    )+lineBreak+
                texts.subCategory+
                    libraries.createSelectElement(
                        cats.subCategories
                        , {name:'productSubCat'}
                        , new Array("ID", "name", null)
                        , new Array(returnedJSON.products[key].subCat.ID)
                    )+lineBreak+
                texts.extraCategories+
                    libraries.createSelectElement(
                        cats.extraCategories
                        , {name:'productExtraCats', multiple:1}
                        , new Array("ID", "name", null)
                        , selectedExtraCats
                    )+lineBreak+
                texts.warranty+
                    libraries.createSelectElement(
                        warranties.getArray()
                        , {name:'productWarranties'}
                        , new Array(null, null, true)
                        , selectedExtraCats
                    )+lineBreak+
                texts["info"]+": <textarea name='productInfo'>"+returnedJSON.products[key].productInfo+"</textarea>";
            productsHTML += "</div";
        }
        tableToSearchFrom.find("td.receiptProducts").
            html(productsHTML);
        
        $.blockUI({
            message: toClone,
            scrollabe: true
        });
    });
    
    // this is responsible for saving the data of modified receipts:
    $("body").on("click", "input[name=modifyReceipt]", function() {
        var $parent = $(this).closest("table");
        
        // ==== required values:
        var receiptData = {
            receiptID: $parent.find("input[name=receiptID]").val(),
            receiptDate: $parent.find("input[name=receiptDate]").val(),
            receiptCost: $parent.find("input[name=receiptCost]").val(),
            receiptMainCat: $parent.find("select[name=receiptMainCat]").val()
        }
        
        // ==== Optional values:
        receiptData.receiptSubCat = $parent.find("select[name=receiptSubCat]").val();
        receiptData.place = $parent.find("input[name=receiptPlace]").val();
        receiptData.whoBought = $parent.find("select[name=whoBought]").val();
        receiptData.paymentMethod = $parent.find("select[name=receiptPaymentMethod]").val();
        receiptData.receiptInfo = $parent.find("textarea[name=receiptInfo]").val();
        
        // ExtraCategories has to be located in array and we set them here:
        //receiptData.receiptExtraCats = $parent.find("select[name=receiptExtraCats]").val().split(",");
        receiptData.receiptExtraCats = $parent.find("select[name=receiptExtraCats]")
            .val()
            .toString()
            .split(',');
                
        receiptData.products = [];
        $parent.find("td.receiptProducts div").each( function() {
            var $this = $(this);
            var productID = $this.data("prod_id");
            
            var data = {
                    "ID": productID,
                    "name": $this.find("input[name=productName]").val(),
                    "cost": $this.find("input[name=productCost]").val(),
                    "mainCat": $this.find("select[name=productMainCat]").val(),
                    "subCat": $this.find("select[name=productSubCat]").val(),
                    "extraCats": $this.find("select[name=productExtraCats]").val(),
                    "warrantyTill": $this.find("select[name=warrantyTill]").val(),
                    "info": $this.find("textarea[name=productInfo]").val(),
                    "VAT": 23
            };            
            receiptData.products.push(data);
        });
        
        receipts.modify(JSON.stringify(receiptData));
        
        $.unblockUI();
        // ==== ==== ==== ====
    });
    
    $("body").on("change", "select[name=receiptMainCat]", function() {
        var $this = $(this);
        doMainCatChanged($this.closest("table").find("select[name=receiptSubCat]"), $(this));
    });
    $("body").on("change", "select[name=productMainCat]", function() {
        var $this = $(this);
        doMainCatChanged($this.closest("div").find("select[name=productSubCat]"), $(this));
    });
/* ======= */

/* === JS-FUNCTIONS === */

    function ajaxReq(page, valueMap, onSuccess, update) {
        $.ajax({
            type: 'POST',
            url: 'ajax/ajax_'+page+'.php',
            data: valueMap,
            success:function(resp){
                $.unblockUI();
                onSuccess.remove();
                update(resp);
            },
            error:function(){
                console.log("Error in ajaxReq: "+this.url+" , "+valueMap);
                alert("Error on request");
                $.unblockUI();
                onSuccess.remove();
            }
        });
    }

    function updateCategories(what, selectedCat, parentID) {
        var valueMap = {
            "listing": "1",
            "type": what
        };
        if(parentID) {
            valueMap.parentID = parentID;
        }

        var catName = "";
        if(what == mainCatNr) {
            catName = "mainCatID";
        } else if(what == subCatNr) {
            catName = "subCatID";
        } else if(what == extraCatNr) {
            catName = "extraCatIDs";
        }

        $catSelect = $("select[name="+catName+"]");
        $catSelect.html("");
        $catSelect.append( new Option(texts.noCategory,"0") );

        var jsonsToIterate;
        getUpdatedPart("categories", valueMap)
            .success(function (response) {
                jsonsToIterate = response;
            });

        $.each(jsonsToIterate, function(i, item) {
            var selected = (selectedCat == item.ID) ? true : false;
            $catSelect.append(new Option(item.name, item.ID, selected, selected));
        });

    }
    function updatePlaces() {
        var valueMap = {
            "listing": "1"
        };

        var jsonsToIterate;
        getUpdatedPart("places", valueMap)
            .success(function (response) {
                jsonsToIterate = response;
            });
        
        // We have to change the object variable names to ID and name, to work with the
        // createSelectElement-function
        var arrayValues = [];
        $.each(jsonsToIterate, function(i, item) {
                arrayValues.push({
                    "value":item.ID, "text":item.name
                });
            }
        );
        var selEle = libraries.createSelectElement(
            arrayValues, {"name":"premadePlace"}
        )
        $("select[name=premadePlace]")
            .replaceWith($(selEle)
                .prepend( new Option("tai valitse","0") ));
    }

    function getUpdatedPart(page, valueMap) {
        return $.ajax({
            async: false,
            type: 'GET',
            url: '/ajax/ajax_'+page+'.php',
            dataType: 'json',
            data: valueMap,
            error:function(){
                console.log("Error in getUpdatedPart: ("+this.url+" | "+valueMap.toSource()+")");
                alert("Error on request");
            }
        });
    }

    function noMainCat(form) {
        if((form.elements['mainCatID'].value == 0) && (form.elements['newMainCategory'].value == "")) {
            if (!confirm('You have no main category selected. Are you sure?'))
                return false;
        }
        return true;
    }

    // This is needed to separate the different products added to the receipt (and their input-fields:
    var productCount = 0;
    function newProductAdded (prodDiv, prodArray) {
        var i = 0;

        // There are the hidden input fields for the specific product information. Should match the input names in mainview.php / div: id="clonableProductList":
        var dataArray = [
            'productName',
            'productCost', 
            'productMainCatID', 
            'productSubCatID', 
            'productExtraCatIDs', 
            'productWarranty', 
            'productVAT',
            'productInfo',
            'newPremadeProduct'
        ];

        $(prodDiv).find("td:odd").each(function () {
            var $this = $(this);

            // We deal with the premadeProduct-select field separately:
            if(dataArray[i] == 'newPremadeProduct') {
                var selectedOrNot = (prodArray["values"][i] == 'checked') ? true : false;                
                var inputEle = $this.children("input[name="+dataArray[i]+"]")
                
                inputEle
                    .attr("name", dataArray[i]+'['+productCount+']')
                        .val(selectedOrNot);
                inputEle.attr('checked', selectedOrNot);
            } else {
                // We set the name to be visible in the div:
                $this.children("span").text(prodArray["texts"][i]);
                // We set the hidden input-fields for ajax / submit:
                $this.children("input[name="+dataArray[i]+"]")
                    .attr("name", dataArray[i]+'['+productCount+']').val(prodArray["values"][i]);
            }

            i++;
        });

        productCount++;
    }

    function doMainCatChanged (subCat, mainCat) {
        var subCatEle = $(subCat);
        var mainCatEle = $(mainCat);
        var selectedOne = mainCatEle.val();
        subCatEle.html("");
        if(mainCatEle.val() != 0) {
            // The default 0-category "not selected":
            subCatEle.append(new Option("no category", 0, true, true));

            // We fetch the subCategories with ajax:
            var fetchedSubCats;
            var valueMap = {
                "listing": 1, 
                "type":3, 
                "parentID":mainCatEle.val()
            };
            getUpdatedPart("categories", valueMap)
                .success(function (response) {
                    fetchedSubCats = response;
                });

            $.each(fetchedSubCats, function(i, item) {
                subCatEle.append(new Option(item.name, item.ID, true, true));
            });
        } else {
                subCatEle.append(new Option("select main category", null, true, true));
        }
    }

    function premadeProductSelected (ID) {
        $.ajax({
            cache: false,
            type: 'GET',
            url: 'ajax/ajax_preProduct.php',
            data: "ID="+ID,
            success:function(responseText){
                var replies = jQuery.parseJSON(responseText);
                var div = $("div#addingProduct");
                
                div.find("input[name=addProd_name]").val(replies.name);
                div.find("input[name=addProd_cost]").val(replies.cost);
                div.find("input[name=addProd_VAT]").val(replies.VAT);
                
                div.find("select[name=addProd_mainCatID]").val(replies.mainCat);
                doMainCatChanged("select[name=addProd_subCatID]", "select[name=addProd_mainCatID]");
                div.find("select[name=addProd_subCatID]").val(replies.subCat);
                
                var splitResult = replies.extraCats.split(",");
                $.each(splitResult, function () {
                    div.find("select[name=addProd_extraCatIDs]").children("option[value="+this+"]").attr('selected', true);
                });
            },
            error:function(){
                alert("Error on ajax-request");
            }
        });
        
    }

    function receiptToAjax (action) {

        $.blockUI(function() {;
            message: texts.saveReceipt
        });
        var processedExtraIDs = processMultipleSelect("select[name=extraCatIDs] option");

        var valueMap = {
            "mainCatID":receiptForm.find("select[name=mainCatID]").val(),
            "datepicker":receiptForm.find("input[name=datepicker]").val(),
            "subCatID":receiptForm.find("select[name=subCatID]").val(),
            "extraCatIDs":processedExtraIDs,
            "place":receiptForm.find("input[name=place]").val(),
            "addAsPremadePlace":receiptForm.find("input[name=addAsPremadePlace]").val(),
            "paymentID":receiptForm.find("select[name=paymentID]").val(),
            "info":receiptForm.find("textarea[name=info]").val(),
            "toDo": action
        };

        valueMap.receiptCost = new Array();
        receiptForm.find("input[name=receiptCost]").each(function() {
           valueMap.receiptCost.push($(this).val());
        });

        $("div#productList table").each(function() {

            $(this).find("input").each( function() {
                $this = $(this);
                valueMap[$this.attr("name")] = $this.val();
            });
            
        });

        $.ajax({
            cache: false,
            type: 'POST',
            url: 'ajax/ajax_receipts.php',
            data: valueMap,
            success:function(resp){
                alert(resp);
                $.unblockUI();
            },
            error:function(){
                alert("Error on ajax-request");
                $.unblockUI();
            }
        });
    }
    
    function setNewProduct (objekti, array2Insert) {
        for(var i = 0; i < 7; i++) {
            objekti.find("td").eq((i*2)).text(array2Insert[i]);
        }
    }
    
    function resetForm(theForm) {
        theForm.reset();
        $(theForm.subCatID).val(0);
    }
    var categories = 
    {
        getAll: function(possibleParentCatID) {
            var returnedOptions = "";
            var valueMap = {
                "listing":LIST_ALL,
                "type":4
            }

            if(typeof possibleParentCatID !== "undefined" && possibleParentCatID !== null) {
                valueMap.parentID = possibleParentCatID;
            }

            getUpdatedPart("categories", valueMap)
                .success(function (response) {
                    returnedOptions = response;
                });
            
            return returnedOptions;
        }
    }
    var receipts = 
    {
        modify: function(receiptData) {
            $.ajax({
                type: 'POST',
                url: 'ajax/ajax_receipts.php',
                data: ({"toDo":"modify"
                    , "receipt":receiptData}),
                error:function(){
                    console.log("Error in ajaxReq: "+this.url+" , "+this.data);
                    alert("Error on request");
                }
            });
        }
    }
    var warranties = 
    {
        getArray: function() {
            var arrayToReturn = new Array();
            var i = 0;
            for(i=1; i <= 12; i++) {
                arrayToReturn[i] = i;
            }
            var year = 0;
            for(i=18; i <= 120; i += 6) {
                year = i / 12;
                arrayToReturn[i] = year;
            }
            return arrayToReturn;
        }
    }
});