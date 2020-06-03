(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.StoreLocator = {
    attach: function (context, settings) {
      let userZip = $.cookie('userZip');
      let searchBox = $("#search");
      if (userZip) {
        searchBox.once("StoreLocator").val(userZip);
      }
      $("#moreOptions").once("StoreLocator").click(function () {
        $("#moreOptionsSection").toggle();
      });
      $("#searchSubmit").on('click', function(e) {
        if (searchBox.val() === '') {
          e.preventDefault();
          $('.help').removeClass('is-hidden');
        }
      })
    }
  }
})(jQuery, Drupal, drupalSettings);
