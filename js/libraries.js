function isInt(value) {
    return !isNaN(parseInt(value,10)) && (parseFloat(value,10) == parseInt(value,10)); 
}
function show(object) {
	var ele = document.getElementById(object);
        ele.style.display = "block";
}
function remove(object) {
	var ele = document.getElementById(object);
        ele.style.display = "none";
} 
function showOrRemove(object) {
	var ele = document.getElementById(object);
	if(ele.style.display == "block") {
    		ele.style.display = "none";
  	}
	else {
		ele.style.display = "block";
	}
}
function showOrHide(object) {
	var ele = document.getElementById(object);
	if(ele.style.display == "block") {
    		ele.style.display = "hide";
  	}
	else {
		ele.style.display = "block";
	}
}
function multipleSelect2Hidden(form, inputEle, hiddenInputEle)
{
    var $selectedOnes = "";
    var element = form.elements[inputEle];
    for (i=0; i<element.options.length; i++) {
        if(element.options[i].selected)
            $selectedOnes = $selectedOnes + element.options[i].value + ",";
    }
    form.elements[hiddenInputEle].value = $selectedOnes.slice(0, $selectedOnes.length-1); // Slice the last part which is ,-character
}
function removeOption(selectName, fieldValue)
{
  var elSel = form.elements[selectName];
  var i;
  for (i = elSel.length - 1; i>=0; i--) {
    if (elSel.options[i].value == fieldValue) {
      elSel.remove(i);
    }
  }
}
function removeOptionSelected(selectField)
{
  var elSel = form.elements[selectField];
  var i;
  for (i = elSel.length - 1; i>=0; i--) {
    if (elSel.options[i].selected) {
      elSel.remove(i);
    }
  }
}
function changeInputValue(inputName, type, teksti) {
    eval("$('"+inputName+"')."+type+"('"+teksti+"')");
}

function processMultipleSelect (element) {
    var returnString = "";
    $(element).filter(":selected").each(function() {
        returnString += $(this).val()+",";
    });
    
    return returnString.substring(0, returnString.length - 1);
}

/* 
 * values, valueFIELD and selectedValues-variables NEEDS TO BE ARRAY:
 * valueField[0] = values['options-value'], 'options-value' is the key where the values-array holds the option-values informations
 * valueField[1] = values['options-text']
 * valueField[2] = if you want to use array key as options-value and the array value as option-text
 */
var libraries = {
    createSelectElement: function (values, extraConfig, valueField, selectedValues)
    {
        var valueFieldVal = "value";
        var valueFieldText = "text" ;
        var keyAsValue = false;
        if(valueField instanceof Array) {
            valueFieldVal = valueField[0];
            valueFieldText = valueField[1];
            keyAsValue = valueField[2] ? valueField[2] : false;
        }
        
        var extras = "";

        if(extraConfig.name) extras += " name='"+extraConfig.name+"'";
        if(extraConfig.id) extras += " id='"+extraConfig.id+"'";
        if(extraConfig.multiple) extras += " multiple";
                
        HTML = ""
        if(extraConfig.onlyOptions != true) var HTML = "<select"+extras+">";
        
        if(extraConfig.defaultOption) HTML += new Option(extraConfig.defaultOption[0], extraConfig.defaultOption[1]);
        
        /* Input option from passed json objects */
        var valueToHTML = 0;
        var textToHTML = 0;
        var selected = "";
        
        for(key2 in values) {
            selected = "";
            if(keyAsValue !== false) {
                valueToHTML = key2;
                textToHTML = values[key2];
            }
            else {
                valueToHTML = values[key2][valueFieldVal];
                textToHTML = values[key2][valueFieldText];
            }
            
            if((selectedValues instanceof Array && $.inArray(valueToHTML, selectedValues) != -1) || valueToHTML === selectedValues) {
                selected = " selected";
            }
            HTML += "<option value='"+valueToHTML+"'"+selected+">"+textToHTML+"</option>";
        }
        if(extraConfig.onlyOptions != true) HTML = HTML+"</select>";
        
        return HTML;
    }
}

function newPopup(url, title, width, height) {
  popupWindow = window.open(
         url,title,'height=height,width=width,left=10,top=10,resizable=yes,scrollbars=yes,toolbar=yes,menubar=no,location=no,directories=no,status=yes')
}

// for datepickers
var LIBRARYdatepickers = 
{
    regionalSetting: "",
    initializeRegion: function(countryTag) {
        this.regionalSetting = countryTag;
    },
    initializeAll: function(selector) {
        $("body").find(selector)
            .removeClass('hasDatepicker')
            .datepicker($.datepicker.regional[LIBRARYdatepickers.regionalSetting]);
    }
}

$.extend({
  getUrlVars: function(){
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }
    return vars;
  },
  getUrlVar: function(name){
    return $.getUrlVars()[name];
  }
});