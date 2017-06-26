/**
 * Created by mm on 17/02/2017.
 */
//----------------------------------------------------------------
// TEST FOR STORAGE AVAILABILITY
function storageAvailable(type) {
    "use strict";
    try {
        var storage = window[type];
        var x = "__storage_test__";
        storage.setItem(x, x);
        storage.removeItem(x);
        return true;
    } catch (e) {
        return false;
    }
}
//----------------------------------------------------------------
// DISPLAY ERROR DIALOG BOX
function ShowError($mdDialog, errors, title) {
    var content = [];
    angular.forEach(errors, function(error, key) {
        var errorTxt = Translator.trans('ERROR.'+error+'.TITLE', {}, 'errors');
        if (errorTxt == 'ERROR.'+error+'.TITLE') {
            errorTxt = error;
        }
        this.push(errorTxt);
    }, content);
    $mdDialog.show($mdDialog.alert().clickOutsideToClose(true).title(title).textContent(content.join(' ')).ok(Translator.trans('FORM.CLOSE')));
}