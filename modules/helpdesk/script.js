function initHelpdeskDetail(id) {
  if ($E(id)) {
    callClick($E(id), function() {
      if (confirm(trans('YOU_WANT_TO_XXX').replace('XXX', this.innerText))) {
        var hs = /reopen_([0-9]+)/.exec(this.id);
        if (hs) {
          send(WEB_URL + 'index.php/helpdesk/model/setup/action', 'action=reopen&id=' + hs[1], doFormSubmit);
        }
      }
    });
  }
}
