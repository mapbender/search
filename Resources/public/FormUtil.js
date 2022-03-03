;!(function() {
window.Mapbender = Mapbender || {};
window.Mapbender.Search = window.Mapbender.Search || {}
window.Mapbender.Search.FormUtil = {
    checkValidity: function(scope) {
        var allValid = true;
        var $inputs = this.getInputs_(scope).filter('[required]');
        for (var i = 0; i < $inputs.length; ++i) {
            var $input = $inputs.eq(i);
            var valid = $input.is(':valid');
            allValid = allValid && valid;
            $input.closest('.form-group').toggleClass('has-error', !valid);
            if (!valid && typeof ($inputs[i].reportValidity) === 'function') {
                // No IE / Edge <= 16
                // see https://caniuse.com/mdn-api_htmlformelement_reportvalidity
                $inputs[i].reportValidity();
            }
        }
        return allValid;
    },
    getData: function(scope) {
        var formData = {};
        var inputs = this.getInputs_(scope).get();
        for (var i = 0; i < inputs.length; ++i) {
            var input = inputs[i];
            formData[input.name] = input.type === 'checkbox' ? input.checked : (input.value || '');
        }
        return formData;
    },
    setData: function(scope, values) {
        var $inputs = this.getInputs_(scope);
        for (var i = 0; i < $inputs.length; ++i) {
            var input = $inputs[i];
            if (typeof ((values || {})[input.name]) !== 'undefined') {
                if (input.type === 'checkbox') {
                    input.checkd = !!values[input.name];
                } else {
                    $inputs.eq(i).val(values[input.name]);
                }
            }
        }
    },
    getInputs_: function(scope) {
        return $(':input[name]', scope);
    }
};
}());
