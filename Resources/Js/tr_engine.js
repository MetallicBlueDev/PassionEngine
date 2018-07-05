$().ajaxSend(function (r, s) {
    $("#loader").show();
});
$().ajaxStop(function (r, s) {
    $("#loader").fadeOut('fast');
});
function displayMessage(message) {
    $('#panel_message').empty().append(message).show();
}
function validLogon(formId, loginId, passwordId) {
    $(formId).submit(function () {
        var isLogin = false;
        var isPassword = false;
        if (checkLogin($(loginId).val())) {
            $(loginId).removeClass('error');
            isLogin = true;
        } else {
            $(loginId).addClass('error');
            isLogin = false;
        }
        if (checkPassword($(passwordId).val())) {
            $(passwordId).removeClass('error');
            isPassword = true;
        } else {
            $(passwordId).addClass('error');
            isPassword = false;
        }
        if (isLogin && isPassword) {
            postForm(this);
        }
        return false;
    });
}
function validForgetLogin(formId, emailId) {
    $(formId).submit(function () {
        if (checkEmail($(emailId).val())) {
            $(emailId).removeClass('error');
            postForm(this);
        } else {
            $(emailId).addClass('error');
        }
        return false;
    });
}
function validForgetPass(formId, loginId) {
    $(formId).submit(function () {
        if (checkLogin($(loginId).val())) {
            $(loginId).removeClass('error');
            postForm(this);
        } else {
            $(loginId).addClass('error');
        }
        return false;
    });
}
function validLink(divId, link) {
    $(divId).load(link);
    return false;
}
function validAccount(formId, loginId, passwordId, passwordConfirmeId, emailId) {
    $(formId).submit(function () {
        var isLogin = false;
        var isPassword = false;
        var isEmail = false;
        if (checkLogin($(loginId).val())) {
            $(loginId).removeClass('error');
            isLogin = true;
        } else {
            $(loginId).addClass('error');
        }
        if ($(passwordId).val().length > 0 || $(passwordConfirmeId).val().length > 0) {
            if (checkPassword($(passwordId).val()) && $(passwordId).val() === $(passwordConfirmeId).val()) {
                isPassword = true;
                $(passwordId).removeClass('error');
                $(passwordConfirmeId).removeClass('error');
            } else {
                $(passwordId).addClass('error');
                $(passwordConfirmeId).addClass('error');
            }
        } else {
            isPassword = true;
            $(passwordId).removeClass('error');
            $(passwordConfirmeId).removeClass('error');
        }
        if (checkEmail($(emailId).val())) {
            $(emailId).removeClass('error');
            isEmail = true;
        } else {
            $(emailId).addClass('error');
        }
        if (isLogin && isPassword && isEmail) {
            postForm(this);
        }
        return false;
    });
}
function postForm(form) {
    disableForm(form);
    $.ajax({
        type: 'POST',
        data: $(form).serialize(),
        url: $(form).attr('action'),
        success: function (message) {
            displayMessage(message);
        }
    });
    enableForm(form);
}
function disableForm(form) {
    var submitButton = $(form).find("input[type='submit']");
    $(submitButton).attr("value", $(submitButton).attr("value") + "...");
    $(submitButton).attr("disabled", "disabled");
}
function enableForm(form) {
    var submitButton = $(form).find("input[type='submit']");
    $(submitButton).attr("value", $(submitButton).attr("value").substr(0, $(submitButton).attr("value").length - 3));
    $(submitButton).removeAttr("disabled");
}
function checkPassword(password) {
    return (password.length >= 5);
}
function checkLogin(login) {
    var filter = new RegExp('^[A-Za-z0-9_-]{3,16}$');
    return (login.length >= 3 && filter.test(login));
}
function checkEmail(email) {
    var filter = new RegExp('^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$');
    return filter.test(email);
}
