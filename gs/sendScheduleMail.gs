function initialize() {

    /* Clear the current sheet */
    var sheet = SpreadsheetApp.getActiveSheet();
    sheet.getRange(2, 1, sheet.getLastRow() + 1, 5).clearContent();

    /* Delete all existing triggers */
    var triggers = ScriptApp.getProjectTriggers();
    for (var i = 0; i < triggers.length; i++) {
        if (triggers[i].getHandlerFunction() === "sendMails") {
            ScriptApp.deleteTrigger(triggers[i]);
        }
    }

    /* Import Gmail Draft Messages into the Spreadsheet */
    var drafts = GmailApp.getDraftMessages();
    if (drafts.length > 0) {
        var rows = [];
        for (var i = 0; i < drafts.length; i++) {
            if (drafts[i].getTo() !== "") {
                rows.push([drafts[i].getId(), drafts[i].getTo(), drafts[i].getSubject(), "", ""]);
            }
        }
        sheet.getRange(2, 1, rows.length, 5).setValues(rows);
    }
}

/* Create time-driven triggers based on Gmail send schedule */
function setSchedule() {
    var sheet = SpreadsheetApp.getActiveSheet();
    var data = sheet.getDataRange().getValues();
    var time = new Date().getTime();
    var code = [];
    for (var row in data) {
        if (row != 0) {
            var schedule = data[row][3];
            if (schedule !== "") {
                if (schedule.getTime() > time) {
                    ScriptApp.newTrigger("sendMails")
                        .timeBased()
                        .at(schedule)
                        .inTimezone(SpreadsheetApp.getActiveSpreadsheet().getSpreadsheetTimeZone())
                        .create();
                    code.push("Scheduled");
                } else {
                    code.push("Date is in the past");
                }
            } else {
                code.push("Not Scheduled");
            }
        }
    }
    for (var i = 0; i < code.length; i++) {
        sheet.getRange("E" + (i + 2)).setValue(code[i]);
    }
}


function sendMails() {
    var sheet = SpreadsheetApp.getActiveSheet();
    var data = sheet.getDataRange().getValues();
    var time = new Date().getTime();
    for (var row = 1; row < data.length; row++) {
        if (data[row][4] == "Scheduled") {
            var schedule = data[row][3];
            if ((schedule != "") && (schedule.getTime() <= time)) {
                var message = GmailApp.getMessageById(data[row][0]);
                var body = message.getBody();
                var options = {
                    cc: message.getCc(),
                    bcc: message.getBcc(),
                    htmlBody: body,
                    replyTo: message.getReplyTo(),
                    attachments: message.getAttachments()
                }

                /* Send a copy of the draft message and move it to Gmail trash */
                GmailApp.sendEmail(message.getTo(), message.getSubject(), body, options);
                message.moveToTrash();
                sheet.getRange("E" + (row + 1)).setValue("Delivered");
            }
        }
    }
}