(function () {
  window.appendCsrf = function (body) {
    if (!window.CSRF_TOKEN) {
      return body;
    }

    if (body instanceof FormData) {
      body.append('csrf_token', window.CSRF_TOKEN);
    } else if (body instanceof URLSearchParams) {
      body.append('csrf_token', window.CSRF_TOKEN);
    }

    return body;
  };
})();
