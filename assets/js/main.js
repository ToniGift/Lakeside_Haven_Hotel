// Site-wide JavaScript. Date pickers and booking helpers are added in phase 5.
document.addEventListener('DOMContentLoaded', function () {
  // Keep check-out after check-in on any search form
  var cin = document.querySelector('input[name="check_in"]');
  var cout = document.querySelector('input[name="check_out"]');
  if (cin && cout) {
    var today = new Date().toISOString().split('T')[0];
    cin.min = today;
    cout.min = today;
    cin.addEventListener('change', function () {
      var next = new Date(cin.value);
      next.setDate(next.getDate() + 1);
      cout.min = next.toISOString().split('T')[0];
      if (cout.value && cout.value <= cin.value) cout.value = '';
    });
  }
});

// Adults + children together can never be more than the room (or the largest room) sleeps.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form').forEach(function (form) {
    var adults = form.querySelector('select[name="adults"]');
    var children = form.querySelector('select[name="children"]');
    if (!adults || !children) return;
    var max = parseInt(adults.dataset.maxTotal, 10) || 1;

    function limit() {
      var a = parseInt(adults.value, 10), c = parseInt(children.value, 10);
      Array.prototype.forEach.call(children.options, function (o) { o.disabled = parseInt(o.value, 10) > max - a; });
      Array.prototype.forEach.call(adults.options, function (o) { o.disabled = parseInt(o.value, 10) > max - c; });
    }
    adults.addEventListener('change', function () {
      if (parseInt(adults.value, 10) + parseInt(children.value, 10) > max) children.value = String(max - parseInt(adults.value, 10));
      limit();
    });
    children.addEventListener('change', limit);
    limit();
  });
});
