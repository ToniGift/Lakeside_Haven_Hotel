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
