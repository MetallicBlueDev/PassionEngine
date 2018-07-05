function validGeneralSetting(formId, siteNameId, emailId) {
    $(formId).submit(function () {
        var isSiteName = false;
        var isEmail = false;
        if ($(siteNameId).val().length > 0) {
            $(siteNameId).removeClass('error');
            isSiteName = true;
        } else {
            $(siteNameId).addClass('error');
        }
        if (checkEmail($(emailId).val())) {
            $(emailId).removeClass('error');
            isEmail = true;
        } else {
            $(emailId).addClass('error');
        }
        if (isSiteName && isEmail) {
            postForm(this);
        }
        return false;
    });
}

function validSystemSetting(formId, sessionTimeLimitId, cryptKeyId, cookiePrefixId, ftpTypeId, ftpHostId, ftpPortId, ftpUserId, ftpPassId, ftpRootId, dbHostId, dbNameId, dbPrefixId, dbUserId, dbPassId, dbTypeId) {
    $(formId).submit(function () {
        var isValidSystem = true;
        var isValidFtp = false;
        var isValidDatabase = false;
        var integer = new RegExp("[0-9]+");

        if ($(sessionTimeLimitId).val().length > 0 && integer.test($(sessionTimeLimitId).val())) {
            $(sessionTimeLimitId).removeClass('error');
        } else {
            $(sessionTimeLimitId).addClass('error');
            isValidSystem = false;
        }
        if ($(cryptKeyId).val().length > 0) {
            $(cryptKeyId).removeClass('error');
        } else {
            $(cryptKeyId).addClass('error');
            isValidSystem = false;
        }
        if ($(cookiePrefixId).val().length > 0) {
            $(cookiePrefixId).removeClass('error');
        } else {
            $(cookiePrefixId).addClass('error');
            isValidSystem = false;
        }

        isValidFtp = validFtpConnection(ftpTypeId, ftpHostId, ftpPortId, ftpUserId, ftpPassId, ftpRootId);
        isValidDatabase = validDatabaseConnection(dbHostId, dbNameId, dbPrefixId, dbUserId, dbPassId, dbTypeId);
        if (isValidSystem && isValidFtp && isValidDatabase) {
            postForm(this);
        }
        return false;
    });
}

function validFtpConnection(ftpTypeId, ftpHostId, ftpPortId, ftpUserId, ftpPassId, ftpRootId) {
    return false;
}

function validDatabaseConnection(dbHostId, dbNameId, dbPrefixId, dbUserId, dbPassId, dbTypeId) {
    return false;
}